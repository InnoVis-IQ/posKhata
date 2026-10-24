<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    use Tier3Stub;

    public function edit(): View
    {
        return view('settings.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
