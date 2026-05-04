<?php

declare(strict_types=1);

namespace Flatpack\Providers;

use Flatpack\Actions\ActionModelClassResolver;
use Flatpack\Composition\CompositionValues;
use Flatpack\Composition\DefaultCompositionQuery;
use Flatpack\Composition\EntityComposition;
use Flatpack\Composition\FormSidebarYamlExpander;
use Flatpack\Composition\ModelClassEntitySlugResolver;
use Flatpack\Composition\YamlCompositionLoader;
use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionQuery;
use Flatpack\Schema\Forms\FormSchemaNormalizer;
use Flatpack\Schema\Widgets\Normalization\WidgetSchemaNormalizationSupport;
use Flatpack\Schema\Widgets\Normalization\WidgetTypes\TableLikeWidgetNormalizer;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Services\Uploads\FileUploadBrowserUrl;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Composition graph, YAML loading, and schema-adjacent runtime singletons.
 */
final class CompositionServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerCompositionBindings();
        $this->registerConcreteServiceSingletons();
    }

    protected function registerCompositionBindings(): void
    {
        $this->app->singleton(CompositionLoader::class, fn ($app): YamlCompositionLoader => new YamlCompositionLoader(
            $app->make('files'),
            (string) $app['config']->get('flatpack.composition.path', base_path('flatpack')),
        ));

        $this->app->singleton(CompositionQuery::class, fn ($app): DefaultCompositionQuery => new DefaultCompositionQuery(
            $app->make(CompositionLoader::class),
        ));

        $this->app->singleton(CompositionValues::class, fn (): CompositionValues => new CompositionValues);

        $this->app->singleton(EntityComposition::class, fn ($app): EntityComposition => new EntityComposition(
            $app->make(CompositionQuery::class),
            $app->make(CompositionValues::class),
        ));

        $this->app->singleton(FormSidebarYamlExpander::class, fn ($app): FormSidebarYamlExpander => new FormSidebarYamlExpander(
            $app->make('files'),
            $app['config'],
        ));
    }

    protected function registerConcreteServiceSingletons(): void
    {
        $this->app->singleton(ListRecordsLoader::class, fn ($app): ListRecordsLoader => new ListRecordsLoader(
            $app->make(FileUploadBrowserUrl::class),
        ));

        $this->app->singleton(FormSchemaNormalizer::class);
        $this->app->singleton(ModelClassEntitySlugResolver::class);
        $this->app->singleton(TableLikeWidgetNormalizer::class, fn ($app): TableLikeWidgetNormalizer => new TableLikeWidgetNormalizer(
            $app->make(ModelClassEntitySlugResolver::class),
        ));
        $this->app->singleton(WidgetSchemaNormalizationSupport::class, fn ($app): WidgetSchemaNormalizationSupport => new WidgetSchemaNormalizationSupport(
            $app->make(TableLikeWidgetNormalizer::class),
        ));
        $this->app->singleton(WidgetSchemaNormalizer::class);

        $this->app->singleton(ActionModelClassResolver::class);
        $this->app->singleton(WidgetRuntime::class);
    }
}
