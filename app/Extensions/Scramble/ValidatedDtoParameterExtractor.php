<?php

declare(strict_types=1);

namespace App\Extensions\Scramble;

use Dedoc\Scramble\Infer;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\TypeBasedRulesDocumentationRetriever;
use Dedoc\Scramble\Support\OperationExtensions\RequestBodyExtension;
use Dedoc\Scramble\Support\OperationExtensions\RulesEvaluator\ComposedFormRequestRulesEvaluator;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\GeneratesParametersFromRules;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\SchemaClassDocReflector;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Reference\MethodCallReferenceType;
use PhpParser\PrettyPrinter;
use WendellAdriel\ValidatedDTO\ValidatedDTO;

final class ValidatedDtoParameterExtractor implements ParameterExtractor
{
    use GeneratesParametersFromRules;

    public function __construct(
        private PrettyPrinter $printer,
        private TypeTransformer $openApiTransformer,
    ) {}

    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        if (! $dtoClassName = $this->getDtoClassName($routeInfo)) {
            return $parameterExtractionResults;
        }

        $parameterExtractionResults[] = $this->extractDtoParameters($dtoClassName, $routeInfo);

        return $parameterExtractionResults;
    }

    public function extractDtoParameters(string $dtoClassName, RouteInfo $routeInfo): ParametersExtractionResult
    {
        $classReflector = Infer\Reflector\ClassReflector::make($dtoClassName);

        $phpDocReflector = SchemaClassDocReflector::createFromDocString($classReflector->getReflection()->getDocComment() ?: '');

        $schemaName = ($phpDocReflector->getTagValue('@ignoreSchema')->value ?? null) !== null
            ? null
            : $phpDocReflector->getSchemaName($dtoClassName);

        return new ParametersExtractionResult(
            parameters: $this->makeParameters(
                rules: (new ComposedFormRequestRulesEvaluator($this->printer, $classReflector, $routeInfo->method))->handle(),
                typeTransformer: $this->openApiTransformer,
                rulesDocsRetriever: new TypeBasedRulesDocumentationRetriever(
                    $routeInfo->getScope(),
                    new MethodCallReferenceType(new ObjectType($dtoClassName), 'rules', []),
                ),
                in: in_array(mb_strtolower($routeInfo->method), RequestBodyExtension::HTTP_METHODS_WITHOUT_REQUEST_BODY)
                    ? 'query'
                    : 'body',
            ),
            schemaName: $schemaName,
            description: $phpDocReflector->getDescription(),
        );
    }

    private function getDtoClassName(RouteInfo $routeInfo): ?string
    {
        if (! $reflectionAction = $routeInfo->reflectionAction()) {
            return null;
        }

        /** @var \ReflectionParameter $dtoParam */
        if (! $dtoParam = collect($reflectionAction->getParameters())->first($this->isDtoParam(...))) {
            return null;
        }

        $type = $dtoParam->getType();

        if (! $type instanceof \ReflectionNamedType) {
            return null;
        }

        $dtoClassName = $type->getName();

        $reflectionClass = new \ReflectionClass($dtoClassName);

        // If the classname is actually an interface, it may be bound to the container.
        if (! $reflectionClass->isInstantiable() && app()->bound($dtoClassName)) {
            $classInstance = app()->getBindings()[$dtoClassName]['concrete'](app());
            $dtoClassName = $classInstance::class;
        }

        return $dtoClassName;
    }

    private function isDtoParam(\ReflectionParameter $reflectionParameter): bool
    {
        if (! $reflectionParameter->getType() instanceof \ReflectionNamedType) {
            return false;
        }

        $className = $reflectionParameter->getType()->getName();

        // Check if the class extends ValidatedDTO
        return is_subclass_of($className, ValidatedDTO::class);
    }
}
