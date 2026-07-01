<?php

namespace App\Http\Controllers;

class AuthCheckerController extends Controller
{
    public function __invoke(): bool
    {
        return auth()->check();
    }
}
