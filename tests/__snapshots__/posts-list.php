<?php

return array(
  'columns' =>
  array(
    'id' =>
    array(
      'label' => 'ID',
      'sortable' => true,
    ),
    'title' =>
    array(
      'label' => 'Title',
      'sortable' => true,
      'searchable' => true,
    ),
    'created_at' =>
    array(
      'label' => 'Created',
      'type' => 'datetime',
      'format' => 'M d, Y  h:i a',
      'sortable' => true,
    ),
    'updated_at' =>
    array(
      'label' => 'Updated',
      'type' => 'datetime',
      'format' => 'M d, Y  h:i a',
      'sortable' => true,
    ),
  ),
);
