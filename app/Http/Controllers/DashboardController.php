<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Intentionally minimal: shop/branch/viewer context is shared by ShareUiContext. */
class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
