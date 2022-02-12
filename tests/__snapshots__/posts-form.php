<?php

return [
  'header' =>
  [
    'title' =>
    [
      'type' => 'editable',
      'size' => 'large',
      'placeholder' => 'Your post title...',
      'rules' => 'required|string|max:255',
    ],
    'slug' =>
    [
      'type' => 'editable',
      'label' => 'Slug',
      'size' => 'small',
      'placeholder' => 'your-post-title',
    ],
  ],
  'toolbar' =>
  [
    'save' =>
    [
      'type' => 'button',
      'label' => 'Save',
      'action' => 'save',
      'shortcut' => 's',
    ],
    'delete' =>
    [
      'type' => 'button',
      'label' => 'Delete',
      'action' => 'delete',
    ],
  ],
  'main' =>
  [
    'tabs' =>
    [
      'edit' =>
      [
        'label' => 'Edit',
        'fields' =>
        [
          'body' =>
          [
            'type' => 'editor',
            'required' => true,
          ],
        ],
      ],
      'info' =>
      [
        'label' => 'Info',
        'fields' =>
        [
          'created_at' =>
          [
            'label' => 'Created',
            'type' => 'datetimepicker',
            'disabled' => true,
            'span' => 1,
          ],
          'updated_at' =>
          [
            'label' => 'Updated',
            'type' => 'datetimepicker',
            'span' => 1,
          ],
        ],
      ],
    ],
  ],
  'sidebar' =>
  [
    'created_at' =>
    [
      'group' => 'Info',
      'label' => 'Created',
      'type' => 'datetimepicker',
      'disabled' => true,
    ],
    'updated_at' =>
    [
      'group' => 'Info',
      'label' => 'Updated',
      'type' => 'datetimepicker',
      'disabled' => true,
    ],
    'categories' =>
    [
      'group' => 'Categories',
      'type' => 'relation',
      'relation' =>
      [
        'name' => 'categories',
        'display' => 'name',
        'make' => true,
      ],
    ],
    'tags' =>
    [
      'group' => 'Tags',
      'type' => 'taginput',
      'relation' =>
      [
        'name' => 'tags',
        'display' => 'name',
        'make' => true,
      ],
    ],
  ],
];
