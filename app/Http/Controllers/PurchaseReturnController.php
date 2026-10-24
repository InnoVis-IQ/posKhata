<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\PurchaseReturnService;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PurchaseReturnController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public function __construct(private readonly PurchaseReturnService $returns) {}

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function create(): View
    {
        $shopId = PosContext::shopId();

        return view('purchase-returns.form', [
            'vendors' => Vendor::query()->where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shopId = PosContext::shopId();

        $data = $request->validate([
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')->where('shop_id', $shopId)],
            'purchase_invoice_id' => ['nullable', 'integer', Rule::exists('purchase_invoices', 'id')->where('shop_id', $shopId)],
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('shop_id', $shopId)],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $return = $this->returns->create([
                'vendor_id' => (int) $data['vendor_id'],
                'purchase_invoice_id' => $data['purchase_invoice_id'] ?? null,
                'return_date' => $data['return_date'],
                'notes' => $data['notes'] ?? null,
                'lines' => collect($data['lines'])->map(fn ($l) => array_filter([
                    'product_id' => (int) $l['product_id'],
                    'qty' => $l['qty'],
                    'unit_cost' => isset($l['unit_cost']) ? (int) $l['unit_cost'] : null,
                ], fn ($v) => $v !== null))->values()->all(),
            ]);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        return redirect()->route('purchases.index')
            ->with('status', "Purchase return #{$return->id} posted; credit note of {$return->total} (minor units) created.");
    }
}
