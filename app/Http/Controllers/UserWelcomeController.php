<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class UserWelcomeController extends Controller
{
    public function __invoke(string $userId): View
    {
        return view('welcome');
    }
}
