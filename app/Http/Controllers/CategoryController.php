<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\Tier3Stub;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use Tier3Stub;

    public function index(): View
    {
        return view('categories.index', ['categories' => collect()]);
    }

    public function create(): View
    {
        return view('categories.form', ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->tier3Pending();
    }

    public function edit(int $category): View
    {
        return view('categories.form', [
            'category' => (object) [
                'id' => $category,
                'name' => '',
                'parent_id' => null,
                'sort_order' => 0,
                'is_active' => true,
            ],
        ]);
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        return $this->tier3Pending();
    }
}
