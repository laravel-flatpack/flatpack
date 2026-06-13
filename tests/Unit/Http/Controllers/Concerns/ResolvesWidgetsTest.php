<?php

declare(strict_types=1);

namespace Flatpack\Tests\Unit\Http\Controllers\Concerns;

use Flatpack\Http\Controllers\Concerns\ResolvesWidgets;
use Flatpack\Schema\Widgets\WidgetSchemaNormalizer;
use Flatpack\Services\Lists\ListRecordsLoader;
use Flatpack\Services\Runtime\WidgetRuntime;
use Flatpack\Support\CompositionDebugContext;
use Flatpack\Support\Exceptions\WidgetRuntimeException;
use Flatpack\Tests\Models\Post;
use Flatpack\Tests\Models\User;
use Flatpack\Tests\TestCase;
use Flatpack\Widgets\Contracts\WidgetDataProvider;
use Flatpack\Widgets\Data\TableWidgetData;
use Flatpack\Widgets\Data\WidgetPayload;
use Flatpack\Widgets\WidgetContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('flatpack.widget_providers', [
        'columns_provider' => RecentPostsTestProviderWithColumns::class,
        'rows_only_provider' => RecentPostsTestProviderRowsOnly::class,
        'throws_runtime' => ThrowingWidgetRuntimeProvider::class,
        'denied_widget' => ResolvesWidgetsDeniedWidgetProvider::class,
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

test('grid widget reuses table provider resolution for column merge and row payload', function () {
    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run(
        widgetId: 'recent_posts_grid',
        definition: [
            'type' => 'grid',
            'provider' => 'columns_provider',
        ],
    );

    expect($resolved['recent_posts_grid']['columns'])->toHaveKey('title')
        ->and($resolved['recent_posts_grid']['columns']['title']['editable'])->toBeFalse()
        ->and($resolved['recent_posts_grid']['data']['rows'])->toBeArray();
});

test('resolveWidgetData skips integer widget ids and non-array definitions', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->runAll([
        404 => [
            'type' => 'table',
            'provider' => 'columns_provider',
        ],
        'bad' => 'not-an-array',
        'ok' => [
            'type' => 'table',
            'provider' => 'columns_provider',
        ],
    ]);

    expect($resolved)->toHaveCount(1)->toHaveKey('ok');
});

test('resolveWidgetData logs when table-like widget omits provider and model', function (): void {
    config()->set('app.debug', true);
    $compositionDebug = app(CompositionDebugContext::class);
    $compositionDebug->activate('test');

    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run('ghost', [
        'type' => 'table',
    ]);

    expect($resolved)->toBe([])
        ->and(implode("\n", $compositionDebug->lines()))->toContain('widgets.ghost ignored: missing provider.');
});

test('resolveWidgetData absorbs WidgetRuntimeException from providers into composition debug', function (): void {
    config()->set('app.debug', true);
    $compositionDebug = app(CompositionDebugContext::class);
    $compositionDebug->activate('test');

    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run('boom', [
        'type' => 'table',
        'provider' => 'throws_runtime',
    ]);

    expect($resolved['boom']['data'] ?? null)->toBe(['rows' => []])
        ->and(implode("\n", $compositionDebug->lines()))->toContain('widgets.boom provider error:');
});

test('resolveWidgetData absorbs AuthorizationException from providers into composition debug', function (): void {
    config()->set('app.debug', true);
    $compositionDebug = app(CompositionDebugContext::class);
    $compositionDebug->activate('test');

    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run('denied', [
        'type' => 'table',
        'provider' => 'denied_widget',
    ]);

    expect($resolved['denied']['data'] ?? null)->toBe(['rows' => []])
        ->and(implode("\n", $compositionDebug->lines()))->toContain('widgets.denied unauthorized:');
});

test('normalizeResolvedWidgetData passes metric payloads through unchanged', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $data = $harness->exposeNormalizeResolvedWidgetData(
        ['type' => 'metric', 'provider' => 'columns_provider'],
        ['value' => 12],
    );

    expect($data)->toBe(['value' => 12]);
});

test('normalizeResolvedWidgetData normalizes chart points and tolerates invalid rows', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $emptyPoints = $harness->exposeNormalizeResolvedWidgetData(
        ['type' => 'chart', 'provider' => 'columns_provider'],
        ['points' => null],
    );
    expect($emptyPoints)->toBe(['points' => []]);

    $mixed = $harness->exposeNormalizeResolvedWidgetData(
        ['type' => 'chart', 'provider' => 'columns_provider'],
        [
            'points' => [
                ['x' => 1],
                'skip',
            ],
        ],
    );
    expect($mixed['points'])->toHaveCount(1);
});

test('normalizeResolvedWidgetData applies status normalization with fallback default', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $normalized = $harness->exposeNormalizeResolvedWidgetData(
        ['type' => 'status', 'label' => 'S', 'provider' => 'columns_provider'],
        [],
    );

    expect($normalized['status'] ?? null)->toBe('default');
});

test('normalizeResolvedWidgetData treats provider table rows as a top-level list when rows key missing', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $data = $harness->exposeNormalizeResolvedWidgetData(
        ['type' => 'table', 'provider' => 'columns_provider'],
        [['id' => 'row-a']],
    );

    expect($data['rows'][0]['id'] ?? null)->toBe('row-a');
});

test('normalizeResolvedWidgetData shapes model-backed table snapshots including sorting and pagination', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $data = $harness->exposeNormalizeResolvedWidgetData(
        [
            'type' => 'table',
            'model' => Post::class,
        ],
        [
            'rows' => [['id' => 9]],
            'sorting' => ['sort_by' => 'title', 'sort_direction' => 'desc'],
            'pagination' => [
                'current_page' => 2,
                'last_page' => 4,
                'per_page' => 7,
                'total' => 40,
                'from' => 'not-numeric',
                'to' => null,
            ],
            'search' => 'needle',
        ],
    );

    expect($data['sorting']['sort_by'])->toBe('title')
        ->and($data['sorting']['sort_direction'])->toBe('desc')
        ->and($data['pagination']['current_page'])->toBe(2)
        ->and($data['pagination']['from'])->toBeNull()
        ->and($data['search'])->toBe('needle');
});

test('resolveWidgetData returns empty payload when model-backed columns are missing', function (): void {
    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run('mt', [
        'type' => 'table',
        'model' => Post::class,
        'columns' => [],
    ]);

    expect($resolved['mt']['data']['rows'] ?? null)->toBe([]);
});

test('grid widget yaml columns take precedence over provider-returned columns', function () {
    config()->set('app.debug', true);
    $compositionDebug = app(CompositionDebugContext::class);
    $compositionDebug->activate('test');

    $harness = makeResolvesWidgetsHarness();

    $resolved = $harness->run(
        widgetId: 'recent_posts_grid',
        definition: [
            'type' => 'grid',
            'provider' => 'columns_provider',
            'columns' => [
                'title' => ['label' => 'Title (YAML)', 'editable' => false],
            ],
        ],
    );

    expect($resolved['recent_posts_grid']['columns'])->toBe([
        'title' => ['label' => 'Title (YAML)', 'editable' => false],
    ])
        ->and(implode("\n", $compositionDebug->lines()))->toContain(
            'widgets.recent_posts_grid: ignoring provider-returned columns',
        );
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

    /**
     * @param  array<string|int, mixed>  $widgets
     * @return array<string, array<string, mixed>>
     */
    public function runAll(array $widgets): array
    {
        return $this->resolveWidgetData(
            $this->request,
            'dashboard',
            $widgets,
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    public function exposeNormalizeResolvedWidgetData(array $definition, mixed $rawData): array
    {
        return $this->normalizeResolvedWidgetData($definition, $rawData);
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

final class ThrowingWidgetRuntimeProvider implements WidgetDataProvider
{
    public function authorize(Authenticatable $user, WidgetContext $context): bool
    {
        return true;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        throw new WidgetRuntimeException(500, 'simulated widget failure');
    }
}

final class ResolvesWidgetsDeniedWidgetProvider implements WidgetDataProvider
{
    public function authorize(Authenticatable $user, WidgetContext $context): bool
    {
        return false;
    }

    public function handle(WidgetContext $context): WidgetPayload
    {
        return new TableWidgetData(rows: []);
    }
}
