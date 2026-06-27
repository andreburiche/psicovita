<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        return view('legal.privacy');
    }

    public function terms(): View
    {
        return view('legal.terms');
    }

    public function dpiaAi(): View
    {
        return view('legal.dpia-ai');
    }
}
