<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Vendor;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class VendorController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function index(): View
    {
        return view('vendors.index', [
            'vendors' => Vendor::query()->where('shop_id', PosContext::shopId())->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('vendors.form', ['vendor' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Vendor::create($this->validated($request) + ['shop_id' => PosContext::shopId()]);

        return redirect()->route('vendors.index')->with('status', 'Vendor created.');
    }

    public function edit(int $vendor): View
    {
        return view('vendors.form', ['vendor' => $this->find($vendor)]);
    }

    public function update(Request $request, int $vendor): RedirectResponse
    {
        $model = $this->find($vendor);
        $model->update($this->validated($request));

        return redirect()->route('vendors.index')->with('status', 'Vendor updated.');
    }

    private function find(int $id): Vendor
    {
        return Vendor::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
