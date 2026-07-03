<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageSwitchController extends Controller
{
    public function __invoke(Request $request, string $language): RedirectResponse
    {
        abort_unless(Language::query()->where('name', $language)->exists(), 404);

        $request->session()->put('active_language', $language);

        return redirect()->back();
    }
}
