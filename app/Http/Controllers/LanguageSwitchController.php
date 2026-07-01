<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageSwitchController extends Controller
{
    public function __invoke(Request $request, string $language): RedirectResponse
    {
        $request->session()->put('active_language', $language);

        return redirect()->back();
    }
}
