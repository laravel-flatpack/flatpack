<?php

it('returns asset url with versioning param', function () {
    expect(flatpackAsset('file.js'))
        ->toBe("http://localhost/file.js?v=". \Flatpack\Flatpack::VERSION);
});

it('returns the error values for a given key from an associative array of errors', function () {
    expect(getErrors([
        'name' => ['The name field is required.'],
        'email' => ['The email field is required.'],
    ], 'name'))
        ->toBe(['The name field is required.']);
});

it('returns an array of fields grouped by "group" option', function () {
    expect(groupComposition([
        'name' => ['group' => 'Personal'],
        'email' => ['group' => 'Personal'],
        'password' => ['group' => 'Personal'],
        'address' => ['group' => 'Address'],
        'city' => ['group' => 'Address'],
        'state' => ['group' => 'Address'],
        'zip' => ['group' => 'Address'],
        'country' => ['group' => 'Address'],
    ]))
        ->toBe([
            [
                'name' => ['group' => 'Personal'],
                'email' => ['group' => 'Personal'],
                'password' => ['group' => 'Personal'],
            ],
            [
                'address' => ['group' => 'Address'],
                'city' => ['group' => 'Address'],
                'state' => ['group' => 'Address'],
                'zip' => ['group' => 'Address'],
                'country' => ['group' => 'Address'],
            ],
        ]);
});
it('returns an array of fields grouped together only if consecutive fields are consecutive', function () {
    expect(groupComposition([
        'name' => ['group' => 'Personal'],
        'email' => ['group' => 'Personal'],
        'password' => ['group' => 'Personal'],
        'address' => ['group' => 'Address'],
        'city' => ['group' => 'Address'],
        'state' => ['group' => 'Address'],
        'confirm_password' => ['group' => 'Personal'],
        'zip' => ['group' => 'Address'],
        'country' => ['group' => 'Address'],
    ]))->toBe([
        [
            'name' => ['group' => 'Personal'],
            'email' => ['group' => 'Personal'],
            'password' => ['group' => 'Personal'],
        ],
        [
            'address' => ['group' => 'Address'],
            'city' => ['group' => 'Address'],
            'state' => ['group' => 'Address'],
        ],
        [
            'confirm_password' => ['group' => 'Personal'],
        ],
        [
            'zip' => ['group' => 'Address'],
            'country' => ['group' => 'Address'],
        ]
    ]);
});
