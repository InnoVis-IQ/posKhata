<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReceiving;
use App\Models\RegisterSession;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\StockLedgerEntry;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Services\StockLedgerService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Seeds demo POS data (no factories). Counts are random between 3 and 10 per entity group.
 * Expects DatabaseSeeder to have created shop, branch, users, and units first.
 */
class DummyDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private int $shopId;

    private int $branchId;

    private int $userId;

    /** @var Collection<int, Unit> */
    private Collection $units;

    public function run(): void
    {
        $shop = Shop::query()->first();
        $branch = Branch::query()->where('shop_id', $shop?->id)->where('is_default', true)->first()
            ?? Branch::query()->where('shop_id', $shop?->id)->first();
        $user = User::query()->where('shop_id', $shop?->id)->where('role', 'cashier')->first()
            ?? User::query()->where('shop_id', $shop?->id)->first();

        if ($shop === null || $branch === null || $user === null) {
            $this->command?->warn('DummyDataSeeder skipped: shop/branch/user missing. Run DatabaseSeeder first.');

            return;
        }

        $this->shopId = (int) $shop->id;
        $this->branchId = (int) $branch->id;
        $this->userId = (int) $user->id;
        $this->units = Unit::query()->where('shop_id', $this->shopId)->get()->keyBy('code');

        if ($this->units->isEmpty()) {
            $this->command?->warn('DummyDataSeeder skipped: no units found.');

            return;
        }

        $categories = $this->seedCategories();
        $products = $this->seedProducts($categories);
        $customers = $this->seedCustomers();
        $vendors = $this->seedVendors();
        $this->seedOpeningStock($products);
        $invoices = $this->seedPurchases($vendors, $products);
        $this->seedVendorPayments($invoices);
        $session = $this->seedRegisterSession();
        $this->seedSales($session, $products, $customers);
    }

    private function count(): int
    {
        return random_int(3, 10);
    }

    /** @return Collection<int, Category> */
    private function seedCategories(): Collection
    {
        $pool = [
            'Grocery',
            'Dairy',
            'Beverages',
            'Snacks',
            'Household',
            'Personal Care',
            'Spices',
            'Bakery',
            'Frozen',
            'Stationery',
        ];

        shuffle($pool);
        $names = array_slice($pool, 0, $this->count());
        $categories = collect();

        foreach ($names as $i => $name) {
            $categories->push(Category::create([
                'shop_id' => $this->shopId,
                'name' => $name,
                'parent_id' => null,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]));
        }

        return $categories->keyBy('id');
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Product>
     */
    private function seedProducts(Collection $categories): Collection
    {
        $catalog = [
            ['name' => 'Fine Atta 10kg', 'unit' => 'pcs', 'cost' => 185000, 'sale' => 210000, 'category' => 'Grocery'],
            ['name' => 'Sugar 1kg', 'unit' => 'kg', 'cost' => 16000, 'sale' => 18500, 'category' => 'Grocery'],
            ['name' => 'Basmati Rice 5kg', 'unit' => 'pcs', 'cost' => 95000, 'sale' => 115000, 'category' => 'Grocery'],
            ['name' => 'Dal Mash 1kg', 'unit' => 'kg', 'cost' => 42000, 'sale' => 48000, 'category' => 'Grocery'],
            ['name' => 'Cooking Oil 1L', 'unit' => 'l', 'cost' => 52000, 'sale' => 58000, 'category' => 'Grocery'],
            ['name' => 'Olper Milk 1L', 'unit' => 'l', 'cost' => 32000, 'sale' => 36000, 'category' => 'Dairy'],
            ['name' => 'Nestle Yogurt 400g', 'unit' => 'pcs', 'cost' => 18000, 'sale' => 22000, 'category' => 'Dairy'],
            ['name' => 'Eggs (dozen)', 'unit' => 'dozen', 'cost' => 35000, 'sale' => 42000, 'category' => 'Dairy'],
            ['name' => 'Butter 200g', 'unit' => 'pcs', 'cost' => 28000, 'sale' => 34000, 'category' => 'Dairy'],
            ['name' => 'Tapal Danedar 950g', 'unit' => 'pcs', 'cost' => 110000, 'sale' => 125000, 'category' => 'Beverages'],
            ['name' => 'Nescafe Classic 50g', 'unit' => 'pcs', 'cost' => 55000, 'sale' => 65000, 'category' => 'Beverages'],
            ['name' => 'Rooh Afza 800ml', 'unit' => 'pcs', 'cost' => 45000, 'sale' => 52000, 'category' => 'Beverages'],
            ['name' => 'Coke 1.5L', 'unit' => 'pcs', 'cost' => 18000, 'sale' => 22000, 'category' => 'Beverages'],
            ['name' => 'Lays Classic 70g', 'unit' => 'pcs', 'cost' => 7000, 'sale' => 10000, 'category' => 'Snacks'],
            ['name' => 'Kurkure Masala 70g', 'unit' => 'pcs', 'cost' => 6500, 'sale' => 10000, 'category' => 'Snacks'],
            ['name' => 'Sooper Biscuits Pack', 'unit' => 'pack', 'cost' => 12000, 'sale' => 15000, 'category' => 'Snacks'],
            ['name' => 'Surf Excel 1kg', 'unit' => 'pcs', 'cost' => 48000, 'sale' => 56000, 'category' => 'Household'],
            ['name' => 'Vim Dishwash 500ml', 'unit' => 'pcs', 'cost' => 22000, 'sale' => 28000, 'category' => 'Household'],
            ['name' => 'Tissue Box', 'unit' => 'pcs', 'cost' => 15000, 'sale' => 20000, 'category' => 'Household'],
            ['name' => 'Lifebuoy Soap', 'unit' => 'pcs', 'cost' => 9000, 'sale' => 12000, 'category' => 'Personal Care'],
            ['name' => 'Colgate Toothpaste 100g', 'unit' => 'pcs', 'cost' => 22000, 'sale' => 28000, 'category' => 'Personal Care'],
            ['name' => 'Shampoo Sachet Pack', 'unit' => 'pack', 'cost' => 8000, 'sale' => 12000, 'category' => 'Personal Care'],
            ['name' => 'Red Chilli Powder 200g', 'unit' => 'pcs', 'cost' => 18000, 'sale' => 24000, 'category' => 'Spices'],
            ['name' => 'Haldi Powder 200g', 'unit' => 'pcs', 'cost' => 14000, 'sale' => 18000, 'category' => 'Spices'],
            ['name' => 'Garam Masala 100g', 'unit' => 'pcs', 'cost' => 16000, 'sale' => 22000, 'category' => 'Spices'],
            ['name' => 'Bread Large', 'unit' => 'pcs', 'cost' => 14000, 'sale' => 18000, 'category' => 'Bakery'],
            ['name' => 'Rusk Pack', 'unit' => 'pack', 'cost' => 20000, 'sale' => 26000, 'category' => 'Bakery'],
            ['name' => 'Frozen Paratha Pack', 'unit' => 'pack', 'cost' => 35000, 'sale' => 42000, 'category' => 'Frozen'],
            ['name' => 'Notebook A4', 'unit' => 'pcs', 'cost' => 12000, 'sale' => 18000, 'category' => 'Stationery'],
            ['name' => 'Ballpoint Pen Pack', 'unit' => 'pack', 'cost' => 8000, 'sale' => 12000, 'category' => 'Stationery'],
        ];

        shuffle($catalog);
        $picked = array_slice($catalog, 0, $this->count());
        $byName = $categories->keyBy('name');
        $fallbackCategory = $categories->first();
        $products = collect();
        $runTag = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $skuSeq = 1;

        foreach ($picked as $row) {
            $unit = $this->units->get($row['unit']) ?? $this->units->first();
            $category = $byName->get($row['category']) ?? $fallbackCategory;
            $discount = random_int(0, 1) === 1 ? random_int(0, 5) * 100 : 0;
            $sale = (int) $row['sale'];
            $tax = (int) round($sale * 0.05); // illustrative included tax portion
            $final = max(0, $sale - $discount);

            $products->push(Product::create([
                'shop_id' => $this->shopId,
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'name' => $row['name'],
                'sku' => 'SKU-'.$runTag.'-'.str_pad((string) $skuSeq++, 3, '0', STR_PAD_LEFT),
                'barcode' => '89'.$runTag.str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
                'unit_cost' => (int) $row['cost'],
                'unit_sale_price' => $sale,
                'tax' => $tax,
                'unit_discount' => $discount,
                'final_unit_price' => $final,
                'batch_size' => 0,
                'batch_discount' => 0,
                'reorder_level' => random_int(5, 20),
                'is_active' => true,
            ]));
        }

        return $products->values();
    }

    /** @return Collection<int, Customer> */
    private function seedCustomers(): Collection
    {
        $names = [
            'Ahmed Khan',
            'Fatima Ali',
            'Bilal Hussain',
            'Ayesha Malik',
            'Usman Raza',
            'Sara Ahmed',
            'Imran Qureshi',
            'Nadia Sheikh',
            'Hamza Iqbal',
            'Zainab Butt',
            'Omar Farooq',
            'Maryam Siddiqui',
            'Ali Haider',
            'Hina Javed',
            'Shahid Mehmood',
        ];

        $cities = ['Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Gujranwala'];

        shuffle($names);
        $picked = array_slice($names, 0, $this->count());
        $customers = collect();

        foreach ($picked as $i => $name) {
            $customers->push(Customer::create([
                'shop_id' => $this->shopId,
                'name' => $name,
                'phone' => '03'.random_int(0, 9).random_int(0, 9).str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
                'email' => null,
                'address' => $cities[array_rand($cities)].', Pakistan',
                'khaata_warning_amount' => random_int(0, 1) === 1 ? random_int(50, 200) * 10000 : null,
                'is_active' => true,
            ]));
        }

        return $customers->values();
    }

    /** @return Collection<int, Vendor> */
    private function seedVendors(): Collection
    {
        $names = [
            'Al-Madina Traders',
            'Karachi Wholesale Mart',
            'Lahore Distributors',
            'Pak General Supply Co.',
            'Faisalabad Kiryana Hub',
            'Islamabad Cash & Carry',
            'Multan Food Importers',
            'Rawalpindi Daily Needs',
            'Sialkot Merchants',
            'Hyderabad Trading House',
            'Quetta Spice Centre',
            'Peshawar Goods Agency',
        ];

        shuffle($names);
        $picked = array_slice($names, 0, $this->count());
        $vendors = collect();

        foreach ($picked as $name) {
            $vendors->push(Vendor::create([
                'shop_id' => $this->shopId,
                'name' => $name,
                'phone' => '02'.random_int(1, 9).str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email' => null,
                'address' => 'Warehouse Road, Pakistan',
                'notes' => 'Dummy vendor',
                'is_active' => true,
            ]));
        }

        return $vendors->values();
    }

    /** @param  Collection<int, Product>  $products */
    private function seedOpeningStock(Collection $products): void
    {
        $subset = $products->shuffle()->take(min($products->count(), $this->count()));

        foreach ($subset as $product) {
            $qty = random_int(10, 80);
            StockLedgerEntry::create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'product_id' => $product->id,
                'qty_delta' => number_format($qty, 3, '.', ''),
                'movement_type' => StockLedgerService::OPENING,
                'reference_type' => null,
                'reference_id' => null,
                'note' => 'Opening stock (seed)',
                'created_by' => $this->userId,
                'created_at' => now()->subDays(random_int(10, 30)),
            ]);
        }
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @param  Collection<int, Product>  $products
     * @return Collection<int, PurchaseInvoice>
     */
    private function seedPurchases(Collection $vendors, Collection $products): Collection
    {
        $invoices = collect();
        $count = $this->count();

        for ($i = 1; $i <= $count; $i++) {
            $vendor = $vendors->random();
            $invoiceDate = now()->subDays(random_int(2, 25))->startOfDay();
            $lineProducts = $products->shuffle()->take(random_int(1, min(4, $products->count())));
            $subtotal = 0;
            $lineRows = [];

            foreach ($lineProducts as $product) {
                $qty = (float) random_int(5, 40);
                $unitCost = (int) $product->unit_cost;
                $lineTotal = (int) round($qty * $unitCost);
                $subtotal += $lineTotal;
                $lineRows[] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            $invoice = PurchaseInvoice::create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'vendor_id' => $vendor->id,
                'invoice_number' => 'PI-'.now()->format('Ymd').'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT).'-'.random_int(100, 999),
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $invoiceDate->copy()->addDays(random_int(7, 30))->toDateString(),
                'subtotal' => $subtotal,
                'tax_total' => 0,
                'total' => $subtotal,
                'notes' => 'Seed purchase',
                'created_by' => $this->userId,
            ]);

            foreach ($lineRows as $row) {
                $invoice->lines()->create([
                    'shop_id' => $this->shopId,
                    'branch_id' => $this->branchId,
                    'product_id' => $row['product']->id,
                    'qty' => number_format($row['qty'], 3, '.', ''),
                    'unit_cost' => $row['unit_cost'],
                    'tax_amount' => 0,
                    'line_total' => $row['line_total'],
                ]);
            }

            $receiving = PurchaseReceiving::create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'purchase_invoice_id' => $invoice->id,
                'received_at' => $invoiceDate->copy()->addHours(random_int(2, 10)),
                'created_by' => $this->userId,
                'notes' => 'Fully received (seed)',
                'created_at' => $invoiceDate->copy()->addHours(random_int(2, 10)),
            ]);

            foreach ($lineRows as $row) {
                $receiving->lines()->create([
                    'shop_id' => $this->shopId,
                    'branch_id' => $this->branchId,
                    'product_id' => $row['product']->id,
                    'qty' => number_format($row['qty'], 3, '.', ''),
                    'unit_cost' => $row['unit_cost'],
                ]);

                StockLedgerEntry::create([
                    'shop_id' => $this->shopId,
                    'branch_id' => $this->branchId,
                    'product_id' => $row['product']->id,
                    'qty_delta' => number_format($row['qty'], 3, '.', ''),
                    'movement_type' => StockLedgerService::PURCHASE,
                    'reference_type' => 'purchase_receiving',
                    'reference_id' => $receiving->id,
                    'note' => "Receive {$invoice->invoice_number}",
                    'created_by' => $this->userId,
                    'created_at' => $receiving->received_at,
                ]);
            }

            $invoices->push($invoice->load('lines'));
        }

        return $invoices->values();
    }

    /** @param  Collection<int, PurchaseInvoice>  $invoices */
    private function seedVendorPayments(Collection $invoices): void
    {
        $methods = ['cash', 'bank', 'card', 'other'];
        $count = $this->count();
        $pool = $invoices->values()->shuffle();

        for ($i = 0; $i < $count; $i++) {
            $invoice = $pool[$i % $pool->count()];
            $payAmount = (int) max(10000, round($invoice->total * (random_int(30, 100) / 100)));
            $paidAt = now()->subDays(random_int(0, 15));
            $method = $methods[array_rand($methods)];

            $payment = VendorPayment::create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'vendor_id' => $invoice->vendor_id,
                'amount' => $payAmount,
                'method' => $method,
                'paid_at' => $paidAt,
                'register_session_id' => null,
                'created_by' => $this->userId,
                'note' => 'Seed vendor payment',
                'created_at' => $paidAt,
            ]);

            $payment->allocations()->create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'purchase_invoice_id' => $invoice->id,
                'amount' => $payAmount,
                'created_at' => $paidAt,
            ]);
        }
    }

    private function seedRegisterSession(): RegisterSession
    {
        return RegisterSession::create([
            'shop_id' => $this->shopId,
            'branch_id' => $this->branchId,
            'opened_by' => $this->userId,
            'opened_at' => now()->subDays(3)->setTime(9, 0),
            'opening_float' => 500000, // Rs 5,000.00
            'status' => 'open',
        ]);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, Customer>  $customers
     */
    private function seedSales(RegisterSession $session, Collection $products, Collection $customers): void
    {
        $saleCount = $this->count();
        $walkInCount = (int) ceil($saleCount / 2);
        $savedCount = $saleCount - $walkInCount;
        $seq = 1;

        for ($i = 0; $i < $walkInCount; $i++) {
            $this->createSale($session, $products, null, $seq++);
        }

        for ($i = 0; $i < $savedCount; $i++) {
            $this->createSale($session, $products, $customers->random(), $seq++);
        }
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function createSale(RegisterSession $session, Collection $products, ?Customer $customer, int $seq): void
    {
        $soldAt = now()->subDays(random_int(0, 2))->setTime(random_int(10, 20), random_int(0, 59));
        $lineProducts = $products->shuffle()->take(random_int(1, min(4, $products->count())));
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $total = 0;
        $computed = [];

        foreach ($lineProducts as $product) {
            $qty = (float) random_int(1, 5);
            $unitPrice = (int) $product->unit_sale_price;
            $gross = (int) round($qty * $unitPrice);
            $discount = (int) round($qty * (int) $product->unit_discount);
            $discount = min($discount, $gross);
            $lineTotal = $gross - $discount;
            $tax = $gross > 0 ? (int) round($qty * (int) $product->tax * ($lineTotal / $gross)) : 0;
            $tax = min($tax, $lineTotal);

            $computed[] = [
                'product' => $product,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'unit_cost' => (int) $product->unit_cost,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal - $tax;
            $discountTotal += $discount;
            $taxTotal += $tax;
            $total += $lineTotal;
        }

        $saleNumber = 'S-'.$soldAt->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT).'-'.random_int(100, 999);

        $sale = Sale::create([
            'shop_id' => $this->shopId,
            'branch_id' => $this->branchId,
            'customer_id' => $customer?->id,
            'register_session_id' => $session->id,
            'sale_number' => $saleNumber,
            'status' => 'completed',
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'sold_at' => $soldAt,
            'cashier_id' => $this->userId,
            'notes' => $customer ? 'Saved customer sale (seed)' : 'Walk-in sale (seed)',
        ]);

        foreach ($computed as $c) {
            $sale->lines()->create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'product_id' => $c['product']->id,
                'qty' => number_format($c['qty'], 3, '.', ''),
                'unit_price' => $c['unit_price'],
                'unit_cost' => $c['unit_cost'],
                'discount_amount' => $c['discount_amount'],
                'tax_amount' => $c['tax_amount'],
                'line_total' => $c['line_total'],
            ]);

            StockLedgerEntry::create([
                'shop_id' => $this->shopId,
                'branch_id' => $this->branchId,
                'product_id' => $c['product']->id,
                'qty_delta' => number_format(-$c['qty'], 3, '.', ''),
                'movement_type' => StockLedgerService::SALE,
                'reference_type' => 'sale',
                'reference_id' => $sale->id,
                'note' => "Sale {$sale->sale_number}",
                'created_by' => $this->userId,
                'created_at' => $soldAt,
            ]);
        }

        $methods = $customer !== null
            ? ['cash', 'card', 'bank']
            : ['cash', 'card'];
        $method = $methods[array_rand($methods)];

        $sale->payments()->create([
            'shop_id' => $this->shopId,
            'branch_id' => $this->branchId,
            'method' => $method,
            'amount' => $total,
            'created_at' => $soldAt,
        ]);
    }
}
