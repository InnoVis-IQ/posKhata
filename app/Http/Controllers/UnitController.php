<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Unit;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::superadminOnly()];
    }

    public function index(): View
    {
        return view('units.index', [
            'units' => Unit::query()->where('shop_id', PosContext::shopId())->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('units.form', ['unit' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Unit::create($data + ['shop_id' => PosContext::shopId()]);

        return redirect()->route('units.index')->with('status', 'Unit created.');
    }

    public function edit(int $unit): View
    {
        return view('units.form', ['unit' => $this->find($unit)]);
    }

    public function update(Request $request, int $unit): RedirectResponse
    {
        $model = $this->find($unit);
        $model->update($this->validated($request, $model));

        return redirect()->route('units.index')->with('status', 'Unit updated.');
    }

    private function find(int $id): Unit
    {
        return Unit::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('units', 'code')
                    ->where('shop_id', PosContext::shopId())
                    ->ignore($unit?->id),
            ],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
