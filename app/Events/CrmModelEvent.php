<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

abstract class CrmModelEvent
{
    use SerializesModels;

    public function __construct(
        public Model $model,
        public string $action,
    ) {}

    public function modelType(): string
    {
        return $this->model::class;
    }
}
