<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use LaracraftTech\LaravelDateScopes\DateScopes;

abstract class Model extends BaseModel
{
    use DateScopes;
}
