<?php

declare(strict_types=1);

use Flatpack\Support\SuccessRedirect;

test('fromFormSchema reads actions.save.success_redirect', function () {
    expect(SuccessRedirect::fromFormSchema([
        'actions' => [
            'save' => [
                'success_redirect' => 'create',
            ],
        ],
    ]))->toBe('create');

    expect(SuccessRedirect::fromFormSchema([
        'actions' => [
            'other' => ['action' => 'delete'],
        ],
    ]))->toBeNull();
});

test('normalize accepts allowed enum strings', function () {
    expect(SuccessRedirect::normalize(' list '))->toBe('list');
    expect(SuccessRedirect::normalize('edit'))->toBe('edit');
    expect(SuccessRedirect::normalize('stay'))->toBe('stay');
});

test('normalize rejects unknown or empty values', function () {
    expect(SuccessRedirect::normalize('custom'))->toBeNull();
    expect(SuccessRedirect::normalize(''))->toBeNull();
    expect(SuccessRedirect::normalize(null))->toBeNull();
});
