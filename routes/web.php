<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorPaymentController;
use App\Http\Middleware\ShareUiContext;
use Illuminate\Support\Facades\Route;

Route::middleware(ShareUiContext::class)->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['pos.auth'])->group(function () {

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::post('/branch/switch', [BranchContextController::class, 'switch'])->name('branch.switch');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::resource('branches', BranchController::class)->except(['show', 'destroy']);
        Route::resource('units', UnitController::class)->except(['show', 'destroy']);
        Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);

        Route::get('/products/low-stock', [ProductController::class, 'lowStock'])->name('products.low-stock');
        Route::resource('products', ProductController::class)->except(['show', 'destroy']);

        Route::get('/stock/opening', [StockController::class, 'opening'])->name('stock.opening');
        Route::post('/stock/opening', [StockController::class, 'storeOpening'])->name('stock.opening.store');
        Route::get('/stock/adjustments', [StockController::class, 'adjustments'])->name('stock.adjustments');
        Route::post('/stock/adjustments', [StockController::class, 'storeAdjustment'])->name('stock.adjustments.store');
        Route::get('/stock/transfers', [StockController::class, 'transfers'])->name('stock.transfers.index');
        Route::get('/stock/transfers/create', [StockController::class, 'createTransfer'])->name('stock.transfers.create');
        Route::post('/stock/transfers', [StockController::class, 'storeTransfer'])->name('stock.transfers.store');

        Route::resource('vendors', VendorController::class)->except(['show', 'destroy']);

        Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])->name('purchases.receive');
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'storeReceive'])->name('purchases.receive.store');

        Route::get('/purchase-returns/create', [PurchaseReturnController::class, 'create'])->name('purchase-returns.create');
        Route::post('/purchase-returns', [PurchaseReturnController::class, 'store'])->name('purchase-returns.store');

        Route::get('/vendor-payments/create', [VendorPaymentController::class, 'create'])->name('vendor-payments.create');
        Route::post('/vendor-payments', [VendorPaymentController::class, 'store'])->name('vendor-payments.store');

        Route::get('/register', [RegisterController::class, 'show'])->name('register.show');
        Route::post('/register/open', [RegisterController::class, 'open'])->name('register.open');
        Route::post('/register/movement', [RegisterController::class, 'movement'])->name('register.movement');
        Route::post('/register/close', [RegisterController::class, 'close'])->name('register.close');
        Route::post('/register/reopen', [RegisterController::class, 'reopen'])->name('register.reopen');

        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');

        Route::get('/sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');

        Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
        Route::get('/refunds/create', [RefundController::class, 'create'])->name('refunds.create');
        Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');
        Route::post('/refunds/{refund}/approve', [RefundController::class, 'approve'])->name('refunds.approve');
        Route::post('/refunds/{refund}/reject', [RefundController::class, 'reject'])->name('refunds.reject');

        Route::resource('customers', CustomerController::class)->except(['show', 'destroy']);
        Route::get('/customers/{customer}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
        Route::get('/customers/{customer}/payment', [CustomerController::class, 'paymentForm'])->name('customers.payment');
        Route::post('/customers/{customer}/payment', [CustomerController::class, 'storePayment'])->name('customers.payment.store');

        Route::get('/reports/day-sales', [ReportController::class, 'daySales'])->name('reports.day-sales');
        Route::get('/reports/payables', [ReportController::class, 'payables'])->name('reports.payables');

        Route::get('/logs/stock', [LogController::class, 'stock'])->name('logs.stock');
        Route::get('/logs/payments', [LogController::class, 'payments'])->name('logs.payments');
    });
});
