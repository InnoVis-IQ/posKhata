<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Branch;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::superadminOnly()];
    }

    public function index(): View
    {
        return view('branches.index', [
            'items' => Branch::query()
                ->where('shop_id', PosContext::shopId())
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('branches.form', ['branch' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $shopId = PosContext::shopId();

        DB::transaction(function () use ($data, $shopId) {
            $hasDefault = Branch::query()->where('shop_id', $shopId)->where('is_default', true)->exists();
            // The shop's first branch is always the default.
            $data['is_default'] = $data['is_default'] || ! $hasDefault;
            if ($data['is_default']) {
                Branch::query()->where('shop_id', $shopId)->update(['is_default' => false]);
            }

            Branch::create($data + ['shop_id' => $shopId]);
        });

        return redirect()->route('branches.index')->with('status', 'Branch created.');
    }

    public function edit(int $branch): View
    {
        return view('branches.form', ['branch' => $this->find($branch)]);
    }

    public function update(Request $request, int $branch): RedirectResponse
    {
        $shopId = PosContext::shopId();
        $data = $this->validated($request, $this->find($branch));

        try {
            DB::transaction(function () use ($branch, $data, $shopId) {
                $model = Branch::query()->where('shop_id', $shopId)->lockForUpdate()->findOrFail($branch);

                $others = Branch::query()->where('shop_id', $shopId)->where('is_active', true)->where('id', '!=', $model->id);

                if (! $data['is_active']) {
                    $replacement = (clone $others)->orderBy('id')->first();
                    if ($replacement === null) {
                        throw new \RuntimeException('At least one branch must stay active.');
                    }
                    if ($model->is_default) {
                        // An inactive branch cannot be the default: promote another active one.
                        $replacement->update(['is_default' => true]);
                    }
                    $data['is_default'] = false;
                } elseif ($data['is_default']) {
                    Branch::query()->where('shop_id', $shopId)->where('id', '!=', $model->id)->update(['is_default' => false]);
                } elseif ($model->is_default) {
                    // The default branch cannot be un-defaulted directly: mark another branch as default instead.
                    $data['is_default'] = true;
                }

                $model->update($data);
            });
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('branches.index')->with('status', 'Branch updated.');
    }

    private function find(int $id): Branch
    {
        return Branch::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('branches', 'code')
                    ->where('shop_id', PosContext::shopId())
                    ->ignore($branch?->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
