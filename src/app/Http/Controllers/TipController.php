<?php

namespace App\Http\Controllers;

use App\Models\Tip;
use Illuminate\Contracts\View\View;

class TipController extends Controller
{
    public function show(Tip $tip): View
    {
        $tip->load([
            'user.profileAvatar',
            'thumbnail',
            'category',
            'tags',
        ]);
        return view('tips.show', [
            'tip' => $tip,
        ]);
    }
}
