<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Branch;
use App\Models\User;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    /**
     * User::query() carries HideSuperadminScope, so superadmins are only visible to
     * another superadmin; admins/cashiers never see (or can edit) them.
     */
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()
                ->where('shop_id', PosContext::shopId())
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', ['user' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = PosContext::shopId();
        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data, $shopId) {
                $user = User::create([
                    'shop_id' => $shopId,
                    'branch_id' => $data['branch_id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => $data['role'],
                    'is_active' => $data['is_active'],
                ]);
                $user->syncBranchIds($this->branchIdsFor($data));
            });
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('users.index')->with('status', 'User created.');
    }

    public function edit(int $user): View
    {
        $model = $this->find($user);
        $model->setAttribute('branch_ids', $model->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all());

        return view('users.form', ['user' => $model]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $model = $this->find($user);
        $data = $this->validated($request, $model);

        if ($model->id === PosContext::user()->id && ! $data['is_active']) {
            return $this->backWithError(new \InvalidArgumentException('You cannot deactivate your own account.'));
        }

        try {
            DB::transaction(function () use ($model, $data) {
                $attributes = [
                    'branch_id' => $data['branch_id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'is_active' => $data['is_active'],
                ];
                // Admin/cashier only; a superadmin's own role is never changed from this screen.
                if (! $model->isSuperadmin()) {
                    $attributes['role'] = $data['role'];
                }
                if (! empty($data['password'])) {
                    $attributes['password'] = $data['password'];
                }

                $model->update($attributes);
                $model->syncBranchIds($this->branchIdsFor($data));
            });
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    private function find(int $id): User
    {
        return User::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    /** Home branch is always part of the assigned set. @return array<int, int> */
    private function branchIdsFor(array $data): array
    {
        return collect($data['branch_ids'] ?? [])
            ->push($data['branch_id'])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $shopId = PosContext::shopId();
        $branchRule = Rule::exists('branches', 'id')->where('shop_id', $shopId)->where('is_active', true);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:255'],
            'role' => [$user?->isSuperadmin() ? 'nullable' : 'required', Rule::in([User::ROLE_ADMIN, User::ROLE_CASHIER])],
            'branch_id' => ['required', 'integer', $branchRule],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', $branchRule],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
