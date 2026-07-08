<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // 컨트롤러에서 $this->authorize('ability', $model) 문법을 쓸 수 있게 한다.
    use AuthorizesRequests;
}
