<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Support\Money;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller implements HasMiddleware
{
    use GatesRoles;

    public static function middleware(): array
    {
        return [self::adminOnly()];
    }

    public function index(): View
    {
        $products = Product::query()
            ->where('shop_id', PosContext::shopId())
            ->addSelect(['unit_code' => Unit::query()->select('code')->whereColumn('units.id', 'products.unit_id')->limit(1)])
            ->orderBy('name')
            ->get();

        return view('products.index', ['products' => $products]);
    }

    /** Products whose on-hand qty at the current branch (SUM of the stock ledger) is <= reorder_level. */
    public function lowStock(): View
    {
        $branchId = PosContext::branchId();

        $qtySql = '(select coalesce(sum(sle.qty_delta), 0) from stock_ledger_entries sle '
            .'where sle.product_id = products.id and sle.branch_id = ?)';

        $products = Product::query()
            ->where('products.shop_id', PosContext::shopId())
            ->where('products.is_active', true)
            ->select('products.*')
            ->selectRaw("{$qtySql} as qty", [$branchId])
            ->whereRaw("{$qtySql} <= products.reorder_level", [$branchId])
            ->orderBy('products.name')
            ->get();

        return view('products.low-stock', ['products' => $products]);
    }

    public function create(): View
    {
        return view('products.form', [
            'product' => null,
            'categories' => $this->categories(),
            'units' => $this->units(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request) + ['shop_id' => PosContext::shopId()]);

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    public function edit(int $product): View
    {
        $model = $this->find($product);

        return view('products.form', [
            'product' => $model,
            'categories' => $this->categories($model->category_id),
            'units' => $this->units($model->unit_id),
        ]);
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $model = $this->find($product);
        $model->update($this->validated($request, $model));

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    private function find(int $id): Product
    {
        return Product::query()->where('shop_id', PosContext::shopId())->findOrFail($id);
    }

    private function units(?int $includeId = null)
    {
        return Unit::query()
            ->where('shop_id', PosContext::shopId())
            ->where(fn ($q) => $q->where('is_active', true)->when($includeId, fn ($q) => $q->orWhere('id', $includeId)))
            ->orderBy('name')
            ->get();
    }

    private function categories(?int $includeId = null)
    {
        return Category::query()
            ->where('shop_id', PosContext::shopId())
            ->where(fn ($q) => $q->where('is_active', true)->when($includeId, fn ($q) => $q->orWhere('id', $includeId)))
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $shop = PosContext::shop();
        $shopId = (int) $shop->id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->where('shop_id', $shopId)->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->where('shop_id', $shopId)->ignore($product?->id)],
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')->where('shop_id', $shopId)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('shop_id', $shopId)],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'unit_sale_price' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'unit_discount' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'batch_size' => ['nullable', 'numeric', 'min:0'],
            'batch_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $price = Money::round($data['unit_sale_price']);
        $discount = Money::round($data['unit_discount'] ?? 0);

        if ($discount > $price) {
            throw ValidationException::withMessages(['unit_discount' => 'Unit discount cannot exceed the unit sale price.']);
        }

        // Unit price is tax-inclusive: when tax is blank, default to the tax portion of the shop rate.
        $bps = (int) $shop->tax_rate_bps;
        $tax = isset($data['tax'])
            ? Money::round($data['tax'])
            : ($bps > 0 ? Money::round($price * $bps / (10000 + $bps)) : 0.0);

        if ($tax > $price) {
            throw ValidationException::withMessages(['tax' => 'Tax cannot exceed the unit sale price.']);
        }

        $batchSize = round((float) ($data['batch_size'] ?? 0), 3);
        $batchDiscount = $batchSize > 0 ? Money::round($data['batch_discount'] ?? 0) : 0.0;

        return [
            'name' => $data['name'],
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'unit_id' => (int) $data['unit_id'],
            'category_id' => $data['category_id'] ?? null,
            'unit_cost' => Money::round($data['unit_cost'] ?? 0),
            'unit_sale_price' => $price,
            'tax' => $tax,
            'unit_discount' => $discount,
            'final_unit_price' => Money::round($price - $discount),
            'reorder_level' => number_format((float) ($data['reorder_level'] ?? 0), 3, '.', ''),
            'batch_size' => number_format($batchSize, 3, '.', ''),
            'batch_discount' => $batchDiscount,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
