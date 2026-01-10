<?php

declare(strict_types=1);

namespace App\Providers;

use App\Extensions\Scramble\QueryBuilderParameterExtractor;
use App\Extensions\Scramble\ValidatedDtoParameterExtractor;
use Dedoc\Scramble\Scramble;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureCommands();
        $this->configureModels();
        $this->configureQueryLog();
        $this->configureApiDocs();
    }

    /**
     * Configure the application's commands.
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->environment('production'),
        );
    }

    /**
     * Configure the application's query log.
     */
    private function configureQueryLog(): void
    {
        if (config('app.env') === 'local') {
            DB::listen(function (QueryExecuted $query): void {
                info($query->sql.'--context--'.json_encode($query->bindings).'--time--'.$query->time);
            });
        }
    }

    /**
     * Configure the application's models.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict();

        Model::unguard();

        Model::handleMissingAttributeViolationUsing(function (Model $model, string $column) {
            $class = $model::class;

            info("Attempted to read missing column: [{$column}] on model [{$class}].");
        });

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            $class = $model::class;

            info("Attempted to lazy load [{$relation}] on model [{$class}].");
        });

        Model::handleDiscardedAttributeViolationUsing(function (Model $model, string $column) {
            $class = $model::class;

            info("Attempted to add disacrd column: [{$column}] on model [{$class}].");
        });
    }

    /**
     * Configure application's role and permissions.
     */
    private function configureRoleAndPermissions(): void {}

    /**
     * Configure API documentation access.
     */
    private function configureApiDocs(): void
    {
        Gate::define('viewApiDocs', fn () => app()->environment('local'));

        Scramble::configure()->withParametersExtractors(function ($extractors) {
            $extractors->append(QueryBuilderParameterExtractor::class);
            $extractors->append(ValidatedDtoParameterExtractor::class);
        });
    }
}
