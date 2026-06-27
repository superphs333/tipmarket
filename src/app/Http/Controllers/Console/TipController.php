<?php

namespace App\Http\Controllers\Console;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class TipController extends Controller
{
    public function __invoke(): View
    {
        return view('console.tips.index');
    }
}
