<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use App\Http\Traits\ApiResponses;

abstract class Controller extends BaseController
{
    use ApiResponses;
}
