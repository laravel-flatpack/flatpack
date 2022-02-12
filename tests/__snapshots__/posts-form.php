<?php

return array(
  'header' =>
  array(
    'title' =>
    array(
      'type' => 'editable',
      'size' => 'large',
      'placeholder' => 'Your post title...',
      'rules' => 'required|string|max:255',
    ),
    'slug' =>
    array(
      'type' => 'editable',
      'label' => 'Slug',
      'size' => 'small',
      'placeholder' => 'your-post-title',
    ),
  ),
  'toolbar' =>
  array(
    'save' =>
    array(
      'type' => 'button',
      'label' => 'Save',
      'action' => 'save',
      'shortcut' => 's',
    ),
    'delete' =>
    array(
      'type' => 'button',
      'label' => 'Delete',
      'action' => 'delete',
    ),
  ),
  'main' =>
  array(
    'tabs' =>
    array(
      'edit' =>
      array(
        'label' => 'Edit',
        'fields' =>
        array(
          'body' =>
          array(
            'type' => 'editor',
            'required' => true,
          ),
        ),
      ),
      'info' =>
      array(
        'label' => 'Info',
        'fields' =>
        array(
          'created_at' =>
          array(
            'label' => 'Created',
            'type' => 'datetimepicker',
            'disabled' => true,
            'span' => 1,
          ),
          'updated_at' =>
          array(
            'label' => 'Updated',
            'type' => 'datetimepicker',
            'span' => 1,
          ),
        ),
      ),
    ),
  ),
  'sidebar' =>
  array(
    'created_at' =>
    array(
      'group' => 'Info',
      'label' => 'Created',
      'type' => 'datetimepicker',
      'disabled' => true,
    ),
    'updated_at' =>
    array(
      'group' => 'Info',
      'label' => 'Updated',
      'type' => 'datetimepicker',
      'disabled' => true,
    ),
    'categories' =>
    array(
      'group' => 'Categories',
      'type' => 'relation',
      'relation' =>
      array(
        'name' => 'categories',
        'display' => 'name',
        'make' => true,
      ),
    ),
    'tags' =>
    array(
      'group' => 'Tags',
      'type' => 'taginput',
      'relation' =>
      array(
        'name' => 'tags',
        'display' => 'name',
        'make' => true,
      ),
    ),
  ),
);
