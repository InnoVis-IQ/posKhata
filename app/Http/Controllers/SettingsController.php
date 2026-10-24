<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function edit(): View
    {
        return view('settings.edit', ['shop' => PosContext::shop()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand_tag' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'receipt_header' => ['nullable', 'string', 'max:2000'],
            'receipt_footer' => ['nullable', 'string', 'max:2000'],
            'currency_code' => ['required', 'string', 'size:3'],
            'tax_rate_bps' => ['required', 'integer', 'min:0', 'max:100000'],
            'default_khaata_warning_amount' => ['nullable', 'integer', 'min:0'],
        ]);

        $shop = PosContext::shop();

        $attributes = [
            'name' => $data['name'],
            'brand_tag' => $data['brand_tag'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'receipt_header' => $data['receipt_header'] ?? null,
            'receipt_footer' => $data['receipt_footer'] ?? null,
            'currency_code' => strtoupper($data['currency_code']),
            'tax_rate_bps' => (int) $data['tax_rate_bps'],
            'default_khaata_warning_amount' => (int) ($data['default_khaata_warning_amount'] ?? 0),
            'multi_branch_enabled' => $request->boolean('multi_branch_enabled'),
        ];

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            if ($shop->logo_path) {
                Storage::disk('public')->delete($shop->logo_path);
            }
            $attributes['logo_path'] = $path;
        }

        $shop->update($attributes);

        return redirect()->route('settings.edit')->with('status', 'Settings saved.');
    }
}
