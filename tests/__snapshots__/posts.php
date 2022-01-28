<?php

return [
    "header" => [
        "title" => [
            "type" => "editable",
            "size" => "large",
            "placeholder" => "Your title goes here...",
        ],
        "slug" => [
            "type" => "editable",
            "label" => "Slug",
            "placeholder" => "/",
            "preset" => [
                "field" => "title",
                "type" => "slug",
            ],
        ],
    ],
    "toolbar" => [
        "save" => [
            "type" => "button",
            "label" => "Save",
            "action" => "save",
            "shortcut" => "s",
        ]
    ],
    "fields" => [
        "body" => [
            "label" => "Body",
            "type" => "editor",
            "required" => true,
        ],
        "created_at" => [
            "label" => "Created",
            "type" => "datetimepicker",
            "disabled" => true,
            "span" => 1,
        ],
        "updated_at" => [
            "label" => "Updated",
            "type" => "datetimepicker",
            "span" => 1,
        ]
    ]
];
