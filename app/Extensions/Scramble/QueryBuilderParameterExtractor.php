<?php

declare(strict_types=1);

namespace App\Extensions\Scramble;

use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\OperationExtensions\ParameterExtractor\ParameterExtractor;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\ParametersExtractionResult;
use Dedoc\Scramble\Support\RouteInfo;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;

final class QueryBuilderParameterExtractor implements ParameterExtractor
{
    public function handle(RouteInfo $routeInfo, array $parameterExtractionResults): array
    {
        if (! $actionNode = $routeInfo->actionNode()) {
            return $parameterExtractionResults;
        }

        $queryBuilderInfo = $this->findQueryBuilderUsage($actionNode);

        if (empty($queryBuilderInfo)) {
            return $parameterExtractionResults;
        }

        $parameters = $this->extractParameters($queryBuilderInfo);

        if (empty($parameters)) {
            return $parameterExtractionResults;
        }

        $parameterExtractionResults[] = new ParametersExtractionResult($parameters);

        return $parameterExtractionResults;
    }

    /**
     * @return array{filters: array<string>, sorts: array<string>, includes: array<string>, fields: array<string>, has_pagination: bool}
     */
    private function findQueryBuilderUsage(Node $actionNode): array
    {
        $finder = new NodeFinder();

        $queryBuilderInfo = [
            'filters' => [],
            'sorts' => [],
            'includes' => [],
            'fields' => [],
            'has_pagination' => false,
        ];

        // Find QueryBuilder::for() static calls
        $queryBuilderCalls = $finder->find($actionNode, function (Node $node) {
            return $node instanceof StaticCall
                && $node->class instanceof Node\Name
                && $this->isQueryBuilderClass($node->class->toString())
                && $node->name instanceof Node\Identifier
                && $node->name->name === 'for';
        });

        foreach ($queryBuilderCalls as $qbCall) {
            if (! $qbCall instanceof StaticCall) {
                continue;
            }

            // Find the variable or expression that holds the QueryBuilder instance
            // Then trace method chaining to find allowedFilters, allowedSorts, etc.
            $this->traceMethodChaining($actionNode, $qbCall, $queryBuilderInfo);
        }

        // Check if paginate() method is called
        $hasPaginate = $finder->findFirst($actionNode, function (Node $node) {
            return $node instanceof MethodCall
                && $node->name instanceof Node\Identifier
                && $node->name->name === 'paginate';
        });

        if ($hasPaginate) {
            $queryBuilderInfo['has_pagination'] = true;
        }

        return $queryBuilderInfo;
    }

    private function isQueryBuilderClass(string $className): bool
    {
        return $className === 'Spatie\QueryBuilder\QueryBuilder'
            || is_a($className, \Spatie\QueryBuilder\QueryBuilder::class, true);
    }

    private function traceMethodChaining(Node $actionNode, StaticCall $qbCall, array &$queryBuilderInfo): void
    {
        $finder = new NodeFinder();

        // Find all method calls that might be on the QueryBuilder
        $methodCalls = $finder->find($actionNode, function (Node $node) {
            if (! $node instanceof MethodCall) {
                return false;
            }

            // Check if this method call is part of the chain starting from QueryBuilder::for()
            $methodName = $node->name instanceof Node\Identifier ? $node->name->name : null;

            return in_array($methodName, ['allowedFilters', 'allowedSorts', 'allowedIncludes', 'allowedFields'], true);
        });

        foreach ($methodCalls as $methodCall) {
            if (! $methodCall instanceof MethodCall) {
                continue;
            }

            $methodName = $methodCall->name instanceof Node\Identifier ? $methodCall->name->name : null;

            if (! $methodName) {
                continue;
            }

            // Extract arguments from the method call
            $args = $methodCall->args ?? [];
            $values = $this->extractArrayValues($args);

            match ($methodName) {
                'allowedFilters' => $queryBuilderInfo['filters'] = array_merge($queryBuilderInfo['filters'], $values),
                'allowedSorts' => $queryBuilderInfo['sorts'] = array_merge($queryBuilderInfo['sorts'], $values),
                'allowedIncludes' => $queryBuilderInfo['includes'] = array_merge($queryBuilderInfo['includes'], $values),
                'allowedFields' => $queryBuilderInfo['fields'] = array_merge($queryBuilderInfo['fields'], $values),
                default => null,
            };
        }
    }

    /**
     * @param  array<mixed>  $args
     * @return array<string>
     */
    private function extractArrayValues(array $args): array
    {
        $values = [];

        foreach ($args as $arg) {
            if (! $arg instanceof Node\Arg) {
                continue;
            }

            $value = $arg->value;

            // Handle array literals: ['filter1', 'filter2']
            if ($value instanceof Node\Expr\Array_) {
                foreach ($value->items as $item) {
                    if ($item instanceof Node\Expr\ArrayItem) {
                        $itemValue = $item->value;

                        if ($itemValue instanceof String_) {
                            $values[] = $itemValue->value;
                        }
                    }
                }
            } elseif ($value instanceof String_) {
                // Single string value
                $values[] = $value->value;
            }
        }

        return $values;
    }

    /**
     * @param  array{filters: array<string>, sorts: array<string>, includes: array<string>, fields: array<string>, has_pagination: bool}  $queryBuilderInfo
     * @return array<Parameter>
     */
    private function extractParameters(array $queryBuilderInfo): array
    {
        $parameters = [];
        $config = config('query-builder.parameters', [
            'filter' => 'filter',
            'sort' => 'sort',
            'include' => 'include',
            'fields' => 'fields',
        ]);

        // Extract filters
        if (! empty($queryBuilderInfo['filters'])) {
            foreach ($queryBuilderInfo['filters'] as $filter) {
                $parameter = new Parameter("{$config['filter']}[{$filter}]", 'query');
                $parameter->description = "Filter by {$filter}";
                $parameter->schema = Schema::fromType(new StringType());
                $parameters[] = $parameter;
            }
        }

        // Extract sorts
        if (! empty($queryBuilderInfo['sorts'])) {
            $sortNames = implode(', ', $queryBuilderInfo['sorts']);
            $parameter = new Parameter($config['sort'], 'query');
            $parameter->description = "Available sorts are `{$sortNames}`. You can sort by multiple options by separating them with a comma. To sort in descending order, use `-` sign in front of the sort, for example: `-{$queryBuilderInfo['sorts'][0]}`.";
            $parameter->schema = Schema::fromType(new StringType());
            $parameters[] = $parameter;
        }

        // Extract includes
        if (! empty($queryBuilderInfo['includes'])) {
            $includeNames = implode(', ', $queryBuilderInfo['includes']);
            $parameter = new Parameter($config['include'], 'query');
            $parameter->description = "Available includes are `{$includeNames}`. You can include multiple options by separating them with a comma.";
            $parameter->schema = Schema::fromType(new StringType());
            $parameters[] = $parameter;
        }

        // Extract fields
        if (! empty($queryBuilderInfo['fields'])) {
            $fieldNames = implode(', ', $queryBuilderInfo['fields']);
            $parameter = new Parameter($config['fields'], 'query');
            $parameter->description = "Available fields are `{$fieldNames}`. You can include multiple options by separating them with a comma.";
            $parameter->schema = Schema::fromType(new StringType());
            $parameters[] = $parameter;
        }

        // Add pagination parameters if paginate() is called
        if ($queryBuilderInfo['has_pagination'] ?? false) {
            $perPageParam = new Parameter('per_page', 'query');
            $perPageParam->description = 'Number of items per page. Default is 15.';
            $perPageParam->schema = Schema::fromType(new IntegerType());
            $perPageParam->required = false;
            $parameters[] = $perPageParam;

            $pageParam = new Parameter('page', 'query');
            $pageParam->description = 'Page number to retrieve.';
            $pageParam->schema = Schema::fromType(new IntegerType());
            $pageParam->required = false;
            $parameters[] = $pageParam;
        }

        return $parameters;
    }
}
