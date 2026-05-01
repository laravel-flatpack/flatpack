<?php

declare(strict_types=1);

use Flatpack\Support\SuccessRedirectSchema;

test('findForListHeaderAction resolves redirect from list schema actions', function () {
    $list = [
        'actions' => [
            ['action' => 'create', 'success_redirect' => 'list'],
        ],
    ];

    expect(SuccessRedirectSchema::findForListHeaderAction($list, 'create'))->toBe('list');
});

test('findForBulkAction returns normalized redirect when bulk action matches', function () {
    $list = [
        'bulk_actions' => [
            ['action' => 'delete', 'success_redirect' => 'list'],
        ],
    ];

    expect(SuccessRedirectSchema::findForBulkAction($list, 'delete'))->toBe('list');
});

test('findForBulkAction returns null when schema or bulk_actions missing', function (
    ?array $list,
    string $action,
    mixed $expected,
) {
    expect(SuccessRedirectSchema::findForBulkAction($list, $action))->toBe($expected);
})->with([
    [null, 'delete', null],
    [['bulk_actions' => []], 'delete', null],
]);

test('findForRowAction prefers form schema header actions then list schema', function (
    ?array $form,
    ?array $list,
    string $actionName,
    mixed $expected,
) {
    expect(SuccessRedirectSchema::findForRowAction($form, $list, $actionName))->toBe($expected);
})->with([
    [
        ['actions' => [['action' => 'publish', 'success_redirect' => 'edit']]],
        null,
        'publish',
        'edit',
    ],
    [
        null,
        ['actions' => [['action' => 'archive', 'success_redirect' => 'list']]],
        'archive',
        'list',
    ],
]);

test('findForRowAction reads column action buttons on list schema', function () {
    $list = [
        'columns' => [
            [
                'actions' => [
                    ['action' => 'preview', 'success_redirect' => 'stay'],
                ],
            ],
        ],
    ];

    expect(SuccessRedirectSchema::findForRowAction(null, $list, 'preview'))->toBe('stay');
});
