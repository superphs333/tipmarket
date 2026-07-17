<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TipSearchController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('tips.search');
    }
}
