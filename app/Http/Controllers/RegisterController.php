<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegisterController extends Controller
{
    use Tier3Stub;

    public function show(): View
    {
        return view('register.show', [
            'session' => null,
            'movements' => collect(),
            'expectedCash' => null,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function movement(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function close(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function reopen(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
