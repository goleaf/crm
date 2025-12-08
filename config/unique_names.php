<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Unique Field
    |--------------------------------------------------------------------------
    */
    'unique_field' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Constraint Fields
    |--------------------------------------------------------------------------
    | Scoped uniqueness fields; models can override per use case.
    */
    'constraint_fields' => [],

    /*
    |--------------------------------------------------------------------------
    | Suffix Format
    |--------------------------------------------------------------------------
    | Use `-{n}` for slugs; models can override when needed.
    */
    'suffix_format' => ' ({n})',

    /*
    |--------------------------------------------------------------------------
    | Attempt Limits
    |--------------------------------------------------------------------------
    | The package reads `max_attempts`; keep `max_tries` for backwards
    | compatibility with the published stub.
    */
    'max_attempts' => 25,
    'max_tries' => 25,

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Handling
    |--------------------------------------------------------------------------
    | Include trashed records when checking uniqueness to mirror unique indexes.
    */
    'soft_delete' => true,
    'with_trashed' => true,

    /*
    |--------------------------------------------------------------------------
    | Trimming
    |--------------------------------------------------------------------------
    */
    'trim' => true,
];
