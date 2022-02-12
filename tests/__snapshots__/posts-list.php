<?php

return [
  'columns' =>
  [
    'id' =>
    [
      'label' => 'ID',
      'sortable' => true,
    ],
    'title' =>
    [
      'label' => 'Title',
      'sortable' => true,
      'searchable' => true,
    ],
    'created_at' =>
    [
      'label' => 'Created',
      'type' => 'datetime',
      'format' => 'M d, Y  h:i a',
      'sortable' => true,
    ],
    'updated_at' =>
    [
      'label' => 'Updated',
      'type' => 'datetime',
      'format' => 'M d, Y  h:i a',
      'sortable' => true,
    ],
  ],
];
