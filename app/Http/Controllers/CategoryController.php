<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Category;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::query()
                ->where('shop_id', PosContext::shopId())
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('categories.form', ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request) + ['shop_id' => PosContext::shopId()]);

        return redirect()->route('categories.index')->with('status', 'Category created.');
    }

    public function edit(int $category): View
    {
        return view('categories.form', ['category' => $this->find($category)]);
    }

    public function update(Request $request, int $category): RedirectResponse
    {
        $model = $this->find($category);
        $model->update($this->validated($request, $model));

        return redirect()->route('categories.index')->with('status', 'Category updated.');
    }

    private function find(int $id): Category
    {
        return Category::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('categories', 'id')->where('shop_id', PosContext::shopId()),
                Rule::notIn([$category?->id]),
            ],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
