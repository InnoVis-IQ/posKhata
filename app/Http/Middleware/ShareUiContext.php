<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\RegisterSession;
use App\Models\Shop;
use App\Support\PosContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ShareUiContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            try {
                $shop = PosContext::shop();
                $branch = PosContext::branch();
                $user = PosContext::user();
                $branches = $user->isAdmin() || $user->isSuperadmin()
                    ? Branch::query()->where('shop_id', $shop->id)->where('is_active', true)->orderBy('name')->get()
                    : $user->branches()->where('branches.is_active', true)->orderBy('name')->get();

                if ($branches->isEmpty() && $user->homeBranch) {
                    $branches = collect([$user->homeBranch]);
                }

                View::share('viewer', (object) [
                    'name' => $user->name,
                    'username' => $user->username(),
                    'role' => $user->role,
                ]);
                View::share('shop', $shop);
                View::share('branches', $branches);
                View::share('currentBranch', $branch);
                View::share('registerOpen', RegisterSession::query()
                    ->where('branch_id', $branch->id)
                    ->where('status', 'open')
                    ->exists());
            } catch (\Throwable) {
                $this->shareGuestDefaults();
            }
        } else {
            $this->shareGuestDefaults();
        }

        return $next($request);
    }

    private function shareGuestDefaults(): void
    {
        $shop = Shop::query()->first();

        View::share('viewer', (object) [
            'name' => 'Guest',
            'username' => '',
            'role' => 'cashier',
        ]);
        View::share('shop', $shop ?? (object) [
            'name' => 'posKhata',
            'logo_path' => null,
            'brand_tag' => null,
            'address' => null,
            'phone' => null,
            'receipt_header' => null,
            'receipt_footer' => null,
            'currency_code' => 'PKR',
            'currency_minor_units' => 2,
            'tax_rate_bps' => 0,
            'default_khaata_warning_amount' => 0,
            'multi_branch_enabled' => false,
        ]);
        View::share('branches', collect());
        View::share('currentBranch', (object) ['id' => 0, 'name' => '—', 'code' => '']);
        View::share('registerOpen', false);
    }
}
