<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('refunds.index', [
            'pending' => collect(),
            'recent' => collect(),
        ]);
    }

    public function create(): View
    {
        return view('refunds.form', [
            'sale' => null,
            'registerOpen' => (bool) session('register_open', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function approve(Request $request, int $refund): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function reject(Request $request, int $refund): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
