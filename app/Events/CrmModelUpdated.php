<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Database\Eloquent\Model;

final class CrmModelUpdated extends CrmModelEvent
{
    public function __construct(Model $model)
    {
        parent::__construct($model, 'updated');
    }
}
