<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\GatesRoles;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Unit;
use App\Services\KhaataPaymentService;
use App\Services\SaleCheckoutService;
use App\Support\PosContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Open to every authenticated role. */
class PosController extends Controller
{
    use GatesRoles;

    public function __construct(
        private readonly SaleCheckoutService $checkout,
        private readonly KhaataPaymentService $khaata,
    ) {}

    public function index(): View
    {
        $shopId = PosContext::shopId();

        $products = Product::query()
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->addSelect(['unit' => Unit::query()->select('code')->whereColumn('units.id', 'products.unit_id')->limit(1)])
            ->orderBy('name')
            ->get()
            // The POS shows the net price (sale price minus per-unit discount); never trust a stale stored column.
            ->each(fn (Product $p) => $p->setAttribute(
                'final_unit_price',
                max(0, (int) $p->unit_sale_price - (int) $p->unit_discount)
            ));

        return view('pos.index', [
            'products' => $products,
            'customers' => Customer::query()->where('shop_id', $shopId)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Maps the pos/index.blade.php form onto SaleCheckoutService::checkout():
     *  - lines[i][product_id|qty]  -> lines (unit_price is ignored: the service prices from the product,
     *    including per-unit and batch discounts, so the form's already-net price is not discounted twice)
     *  - discount_total            -> spread over lines as extra discount_amount (cart-level discount)
     *  - payments[method]=amount   -> payments list (zero rows dropped)
     *  - customer_id | customer_name/customer_phone + save_customer -> customer resolution
     *  - khaata_payment_amount     -> khaata_payment pay-down (cash)
     *  The form's subtotal/tax_total/total hidden fields are display-only and are recomputed server-side.
     */
    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'discount_total' => ['nullable', 'integer', 'min:0'],
            'payments' => ['nullable', 'array'],
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'khaata_payment_amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $payments = $this->payments((array) ($data['payments'] ?? []));
            $khaataTender = (int) collect($payments)->where('method', 'khaata')->sum('amount');

            $payload = [
                'lines' => $this->lines($data['lines'], (int) ($data['discount_total'] ?? 0)),
                'payments' => $payments,
                'notes' => $data['notes'] ?? null,
            ];

            if (! empty($data['customer_id'])) {
                $payload['customer_id'] = (int) $data['customer_id'];
            } else {
                $payload['customer'] = [
                    'name' => $data['customer_name'] ?? '',
                    'phone' => $data['customer_phone'] ?? null,
                ];
                // A khaata tender always needs a (saved) customer.
                $payload['save_customer'] = $request->boolean('save_customer') || $khaataTender > 0;
            }

            $paydown = (int) ($data['khaata_payment_amount'] ?? 0);
            if ($paydown > 0) {
                $payload['khaata_payment'] = ['amount' => $paydown, 'method' => 'cash'];
            }

            $sale = $this->checkout->checkout($payload);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return $this->backWithError($e);
        }

        $redirect = redirect()->route('sales.receipt', $sale->id)->with('status', "Sale {$sale->sale_number} completed.");

        if ($sale->customer_id !== null) {
            $customer = Customer::query()->find($sale->customer_id);
            $threshold = (int) ($customer?->khaata_warning_amount ?? PosContext::shop()->default_khaata_warning_amount ?? 0);
            if ($customer !== null && $threshold > 0 && $this->khaata->balance($customer) >= $threshold) {
                $redirect->with('warning', "{$customer->name}'s khaata balance has reached the warning threshold.");
            }
        }

        return $redirect;
    }

    /**
     * Accepts either payments[method]=amount (the POS form) or a list of {method, amount} rows.
     *
     * @return array<int, array{method:string, amount:int}>
     */
    private function payments(array $raw): array
    {
        $rows = [];
        foreach ($raw as $key => $value) {
            if (is_array($value)) {
                $method = (string) ($value['method'] ?? '');
                $amount = (int) round((float) ($value['amount'] ?? 0));
            } else {
                $method = (string) $key;
                $amount = (int) round((float) $value);
            }
            if ($amount > 0) {
                $rows[] = ['method' => $method, 'amount' => $amount];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array{product_id:int|string, qty:int|float|string}>  $lines
     * @return array<int, array{product_id:int, qty:float, discount_amount?:int}>
     */
    private function lines(array $lines, int $cartDiscount): array
    {
        $out = collect($lines)->map(fn ($l) => [
            'product_id' => (int) $l['product_id'],
            'qty' => round((float) $l['qty'], 3),
        ])->values()->all();

        if ($cartDiscount <= 0) {
            return $out;
        }

        // Replicate the service's default line discount, then add a pro-rata share of the cart discount.
        $products = Product::query()
            ->where('shop_id', PosContext::shopId())
            ->whereIn('id', collect($out)->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('id');

        $base = [];
        $net = [];
        foreach ($out as $i => $line) {
            $product = $products->get($line['product_id']);
            if ($product === null) {
                throw new \InvalidArgumentException('Line '.($i + 1).": product #{$line['product_id']} not found in this shop.");
            }
            $gross = (int) round($line['qty'] * (int) $product->unit_sale_price);
            $discount = (int) round($line['qty'] * (int) $product->unit_discount);
            $batchSize = (float) $product->batch_size;
            if ($batchSize > 0 && (int) $product->batch_discount > 0) {
                $discount += (int) floor($line['qty'] / $batchSize + 1e-9) * (int) $product->batch_discount;
            }
            $base[$i] = min($discount, $gross);
            $net[$i] = $gross - $base[$i];
        }

        $totalNet = array_sum($net);
        if ($cartDiscount > $totalNet) {
            throw new \InvalidArgumentException('Cart discount cannot exceed the sale amount.');
        }

        $left = $cartDiscount;
        $last = array_key_last($out);
        foreach ($out as $i => $line) {
            $share = $i === $last
                ? $left
                : min($net[$i], (int) floor($cartDiscount * $net[$i] / max(1, $totalNet)));
            $share = min($share, $net[$i]);
            $left -= $share;
            $out[$i]['discount_amount'] = $base[$i] + $share;
        }

        if ($left > 0) {
            // Rounding/caps left a remainder: push it onto lines that still have headroom.
            foreach ($out as $i => $line) {
                $room = $net[$i] - ($out[$i]['discount_amount'] - $base[$i]);
                $take = min($room, $left);
                $out[$i]['discount_amount'] += $take;
                $left -= $take;
                if ($left <= 0) {
                    break;
                }
            }
        }

        return $out;
    }
}
