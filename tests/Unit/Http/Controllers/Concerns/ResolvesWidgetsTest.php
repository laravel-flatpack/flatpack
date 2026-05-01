<?php

declare(strict_types=1);

namespace Flatpack\Tests\Unit\Http\Controllers\Concerns;

use Flatpack\Http\Controllers\Concerns\ResolvesWidgets;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\CompositionDebugContext;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\TableWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

uses(TestCase::class);

beforeEach(function () {
    config()->set('flatpack.widget_providers', [
        'columns_provider' => RecentPostsTestProviderWithColumns::class,
        'rows_only_provider' => RecentPostsTestProviderRowsOnly::class,
    ]);
});

test('provider-returned columns are ignored when yaml provides columns', function () {
    config()->set('app.debug', true);
    $compositionDebug = app(CompositionDebugContext::class);
    $compositionDebug->activate('test');

    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run(
        widgetId: 'recent_posts',
        definition: [
            'type' => 'table',
            'provider' => 'columns_provider',
            'columns' => [
                'title' => ['label' => 'Title (YAML)', 'editable' => false],
            ],
        ],
    );

    expect($resolved['recent_posts']['columns'])->toBe([
        'title' => ['label' => 'Title (YAML)', 'editable' => false],
    ])
        ->and(implode("\n", $compositionDebug->lines()))->toContain(
            'widgets.recent_posts: ignoring provider-returned columns',
        );
});

test('provider-returned columns are kept when yaml omits columns', function () {
    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run(
        widgetId: 'recent_posts',
        definition: [
            'type' => 'table',
            'provider' => 'columns_provider',
        ],
    );

    expect($resolved['recent_posts']['columns'])->toHaveKey('title')
        ->and($resolved['recent_posts']['columns']['title']['label'])->toBe('Title (provider)')
        ->and($resolved['recent_posts']['columns']['title']['editable'])->toBeFalse();
});

test('empty fallback columns are emitted when neither yaml nor provider supply columns', function () {
    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run(
        widgetId: 'recent_posts',
        definition: [
            'type' => 'table',
            'provider' => 'rows_only_provider',
        ],
    );

    expect($resolved['recent_posts']['columns'])->toBe([]);
});

function makeResolvesWidgetsHarness(): ResolvesWidgetsTestHarness
{
    $request = Request::create('/flatpack');
    $user = User::factory()->createOne();
    $request->setUserResolver(static fn () => $user);

    return new ResolvesWidgetsTestHarness(
        request: $request,
        widgetSchemaNormalizer: app(WidgetSchemaNormalizer::class),
        widgetRuntime: app(WidgetRuntime::class),
        listRecordsLoader: app(ListRecordsLoader::class),
    );
}

final class ResolvesWidgetsTestHarness
{
    use ResolvesWidgets;

    public function __construct(
        private readonly Request $request,
        private readonly WidgetSchemaNormalizer $widgetSchemaNormalizer,
        private readonly WidgetRuntime $widgetRuntime,
        private readonly ListRecordsLoader $listRecordsLoader,
    ) {}

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, array<string, mixed>>
     */
    public function run(string $widgetId, array $definition): array
    {
        return $this->resolveWidgetData(
            $this->request,
            'dashboard',
            [$widgetId => $definition],
        );
    }

    private function widgetSchemaNormalizer(): WidgetSchemaNormalizer
    {
        return $this->widgetSchemaNormalizer;
    }

    private function widgetRuntime(): WidgetRuntime
    {
        return $this->widgetRuntime;
    }

    private function listRecordsLoader(): ListRecordsLoader
    {
        return $this->listRecordsLoader;
    }
}

final class RecentPostsTestProviderWithColumns implements WidgetDataProvider
{
    public function authorize(Authenticatable $user, WidgetContext $context): bool
    {
        return true;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        return new TableWidgetData(
            rows: [['id' => 1, 'title' => 'Hello']],
            columns: [
                'title' => ['label' => 'Title (provider)'],
            ],
        );
    }
}

final class RecentPostsTestProviderRowsOnly implements WidgetDataProvider
{
    public function authorize(Authenticatable $user, WidgetContext $context): bool
    {
        return true;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        return new TableWidgetData(
            rows: [['id' => 1, 'title' => 'Hello']],
        );
    }
}
