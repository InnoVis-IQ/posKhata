@extends('layouts.pos')

@section('title', 'POS')

@section('content')
    @php
        $catalog = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'unit' => $p->unit ?? 'pcs',
            'price' => (int) ($p->final_unit_price ?? 0),
            'batch_size' => (float) ($p->batch_size ?? 0),
            'batch_discount' => (int) ($p->batch_discount ?? 0),
        ])->values();
    @endphp

    <div
        class="grid gap-4 lg:grid-cols-5"
        x-data="posCart({{ Js::from($catalog) }}, {{ (int) $shop->tax_rate_bps }})"
        @pos-add-customer.window="openCustomerModal()"
    >
        <section class="lg:col-span-3 space-y-3">
            <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-white/80">Products</p>
                <x-info dark>Find an item by name, code, or barcode, then tap it to add to the cart.</x-info>
            </div>
            <input type="search" x-model="query" placeholder="Search name, SKU, barcode…" class="w-full rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm placeholder:text-white/40">
            <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="product in filtered" :key="product.id">
                    <button type="button" @click="add(product)" class="rounded-xl border border-white/10 bg-white/5 p-4 text-left hover:border-accent hover:bg-white/10">
                        <p class="font-medium" x-text="product.name"></p>
                        <p class="mt-1 text-xs text-white/50" x-text="product.sku"></p>
                        <p class="mt-2 text-accent" x-text="formatMoney(product.price) + ' / ' + product.unit"></p>
                        <p class="mt-1 text-xs text-white/40" x-show="product.batch_size > 0" x-text="'Batch ' + product.batch_size + ': −' + formatMoney(product.batch_discount)"></p>
                    </button>
                </template>
            </div>
        </section>

        <section class="lg:col-span-2 rounded-2xl border border-white/10 bg-white text-ink shadow-xl">
            <div class="border-b border-line px-4 py-3">
                <h2 class="flex items-center gap-1.5 font-semibold">Cart <x-info>Items being sold now. Set quantities, choose how the customer pays, then complete the sale.</x-info></h2>
            </div>
            <div class="max-h-64 space-y-2 overflow-y-auto px-4 py-3">
                <template x-if="cart.length === 0">
                    <p class="text-sm text-muted">Tap a product to add.</p>
                </template>
                <template x-for="(line, index) in cart" :key="line.id">
                    <div class="flex items-center justify-between gap-2 text-sm">
                        <div class="min-w-0">
                            <p class="truncate font-medium" x-text="line.name"></p>
                            <p class="text-muted" x-text="formatMoney(line.price) + ' × ' + line.qty + ' = ' + formatMoney(lineTotal(line))"></p>
                            <p class="text-xs text-accent" x-show="batchOff(line) > 0" x-text="'Batch −' + formatMoney(batchOff(line))"></p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" class="rounded border border-line px-2" @click="dec(index)">−</button>
                            <button type="button" class="rounded border border-line px-2" @click="inc(index)">+</button>
                            <button type="button" class="rounded px-2 text-danger" @click="remove(index)">×</button>
                        </div>
                    </div>
                </template>
            </div>

            <form method="POST" action="{{ route('pos.checkout') }}" class="space-y-3 border-t border-line px-4 py-4" @submit="prepareCheckout($event)">
                @csrf
                <template x-for="(line, index) in cart" :key="'h'+line.id">
                    <span>
                        <input type="hidden" :name="`lines[${index}][product_id]`" :value="line.id">
                        <input type="hidden" :name="`lines[${index}][qty]`" :value="line.qty">
                        <input type="hidden" :name="`lines[${index}][unit_price]`" :value="line.price">
                    </span>
                </template>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <label class="col-span-2">
                        <span class="mb-1 block text-muted">Discount (minor)</span>
                        <input type="number" name="discount_total" x-model.number="discount" class="w-full rounded-lg border border-line px-3 py-2">
                    </label>
                    <p>Subtotal <span class="float-right font-medium" x-text="formatMoney(subtotal)"></span></p>
                    <p>Tax included <span class="float-right font-medium" x-text="formatMoney(tax)"></span></p>
                    <p class="col-span-2 text-xs text-muted">Shop tax {{ number_format($shop->tax_rate_bps / 100, 2) }}% (from Settings)</p>
                    <p class="col-span-2 text-lg font-semibold">Total <span class="float-right" x-text="formatMoney(total)"></span></p>
                    <input type="hidden" name="tax_total" :value="tax">
                    <input type="hidden" name="subtotal" :value="subtotal">
                    <input type="hidden" name="total" :value="total">
                </div>

                <div class="space-y-2 border-t border-line pt-3 text-sm">
                    <p class="font-medium">Tender</p>
                    <label class="flex items-center justify-between gap-2">Cash
                        <input type="number" name="payments[cash]" x-model.number="pay.cash" class="w-28 rounded-lg border border-line px-2 py-1">
                    </label>
                    <label class="flex items-center justify-between gap-2">Card
                        <input type="number" name="payments[card]" x-model.number="pay.card" class="w-28 rounded-lg border border-line px-2 py-1">
                    </label>
                    <label class="flex items-center justify-between gap-2">Khaata
                        <input type="number" name="payments[khaata]" x-model.number="pay.khaata" class="w-28 rounded-lg border border-line px-2 py-1">
                    </label>
                    <p class="text-xs" :class="tenderOk ? 'text-accent' : 'text-danger'" x-text="tenderOk ? 'Tender matches total' : 'Tender must equal total'"></p>
                </div>

                <div class="space-y-2 border-t border-line pt-3 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="save_customer" value="1" x-model="saveCustomer">
                            Save customer at checkout
                        </label>
                        <button type="button" class="shrink-0 text-accent hover:underline" @click="openCustomerModal()">Add customer</button>
                    </div>
                    <div class="space-y-2" x-show="saveCustomer || pay.khaata > 0 || customerName" x-cloak>
                        <input type="text" name="customer_name" x-model="customerName" placeholder="Customer name" class="w-full rounded-lg border border-line px-3 py-2" :required="pay.khaata > 0">
                        <input type="text" name="customer_phone" x-model="customerPhone" placeholder="Phone" class="w-full rounded-lg border border-line px-3 py-2">
                    </div>
                    <div>
                        <label class="mb-1 block text-muted">Khaata pay-down (optional)</label>
                        <input type="number" name="khaata_payment_amount" value="0" class="w-full rounded-lg border border-line px-3 py-2" placeholder="Amount toward prior balance">
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-accent py-3 text-sm font-semibold text-white hover:bg-accent-hover" :disabled="cart.length === 0 || !tenderOk">Charge</button>
            </form>
        </section>

        {{-- Stay on POS: modal keeps Alpine cart in memory --}}
        <div
            x-show="customerModalOpen"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            @keydown.escape.window="closeCustomerModal()"
        >
            <div
                class="w-full max-w-md rounded-2xl border border-line bg-panel p-6 text-ink shadow-xl"
                @click.outside="closeCustomerModal()"
            >
                <h3 class="flex items-center gap-1.5 text-lg font-semibold">
                    Add customer
                    <x-info>Save a customer for this sale (needed for khaata). Your cart stays open.</x-info>
                </h3>
                <form class="mt-4 space-y-3" @submit.prevent="applyCustomerModal()">
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="modal_customer_name">Name</label>
                        <input id="modal_customer_name" type="text" x-model="modalCustomer.name" required class="w-full rounded-lg border border-line px-3 py-2 text-sm" autofocus>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium" for="modal_customer_phone">Phone</label>
                        <input id="modal_customer_phone" type="text" x-model="modalCustomer.phone" class="w-full rounded-lg border border-line px-3 py-2 text-sm">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-lg border border-line px-4 py-2 text-sm hover:bg-surface" @click="closeCustomerModal()">Cancel</button>
                        <button type="submit" class="rounded-lg bg-accent px-4 py-2 text-sm font-semibold text-white hover:bg-accent-hover">Use on sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function posCart(catalog, taxRateBps) {
            return {
                catalog,
                taxRateBps,
                query: '',
                cart: [],
                discount: 0,
                pay: { cash: 0, card: 0, khaata: 0 },
                saveCustomer: false,
                customerName: '',
                customerPhone: '',
                customerModalOpen: false,
                modalCustomer: { name: '', phone: '' },
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    if (!q) return this.catalog;
                    return this.catalog.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        String(p.sku || '').toLowerCase().includes(q) ||
                        String(p.barcode || '').includes(q)
                    );
                },
                batchOff(line) {
                    const size = Number(line.batch_size) || 0;
                    if (size <= 0) return 0;
                    return Math.floor(line.qty / size) * (Number(line.batch_discount) || 0);
                },
                lineTotal(line) {
                    return Math.max(0, line.price * line.qty - this.batchOff(line));
                },
                get subtotal() {
                    return this.cart.reduce((s, l) => s + this.lineTotal(l), 0);
                },
                get taxable() {
                    return Math.max(0, this.subtotal - (this.discount || 0));
                },
                get tax() {
                    // Prices are tax-inclusive; extract embedded tax using shop tax_rate_bps.
                    const bps = Number(this.taxRateBps) || 0;
                    if (!bps) return 0;
                    return Math.round(this.taxable * bps / (10000 + bps));
                },
                get total() {
                    return this.taxable;
                },
                get tenderOk() {
                    return (this.pay.cash || 0) + (this.pay.card || 0) + (this.pay.khaata || 0) === this.total && this.total > 0;
                },
                formatMoney(minor) {
                    return (Number(minor || 0) / 100).toFixed(2);
                },
                add(product) {
                    const existing = this.cart.find(l => l.id === product.id);
                    if (existing) {
                        existing.qty += 1;
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            qty: 1,
                            batch_size: product.batch_size || 0,
                            batch_discount: product.batch_discount || 0,
                        });
                    }
                },
                inc(i) { this.cart[i].qty += 1; },
                dec(i) { if (this.cart[i].qty > 1) this.cart[i].qty -= 1; else this.remove(i); },
                remove(i) { this.cart.splice(i, 1); },
                openCustomerModal() {
                    this.modalCustomer = {
                        name: this.customerName || '',
                        phone: this.customerPhone || '',
                    };
                    this.customerModalOpen = true;
                },
                closeCustomerModal() {
                    this.customerModalOpen = false;
                },
                applyCustomerModal() {
                    const name = (this.modalCustomer.name || '').trim();
                    if (!name) return;
                    this.customerName = name;
                    this.customerPhone = (this.modalCustomer.phone || '').trim();
                    this.saveCustomer = true;
                    this.customerModalOpen = false;
                },
                prepareCheckout(e) {
                    if (!this.tenderOk || this.cart.length === 0) e.preventDefault();
                },
            };
        }
    </script>
@endsection
