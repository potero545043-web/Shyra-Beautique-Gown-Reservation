<?php

use App\Http\Controllers\AccessoryController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\NotificationController as CustomerNotificationController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\GownController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\NotificationController as StaffNotificationController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\ProfileController;
use App\Models\Gown;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $featuredGowns = Gown::with('category')->whereNull('archived_at')->where('status', 'available')->latest()->take(3)->get();

    return view('welcome', compact('featuredGowns'));
});

/*
|--------------------------------------------------------------------------
| Owner
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {

        Route::get('/dashboard', [
            OwnerDashboardController::class,
            'index',
        ])->name('dashboard');
        Route::get('/notifications', [StaffNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [StaffNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [StaffNotificationController::class, 'markRead'])->name('notifications.read');

        Route::get('/reservations', [ManagementController::class, 'reservations'])->name('reservations');
        Route::get('/reservations/availability', [ManagementController::class, 'checkReservationAvailability'])->name('reservations.availability');
        Route::get('/reservations/{reservation}', [ManagementController::class, 'reservationDetails'])->name('reservations.show');
        Route::post('/reservations', [ManagementController::class, 'storeEmployeeReservation'])->name('reservations.store');
        Route::patch('/reservations/{reservation}', [ManagementController::class, 'updateReservation'])->name('reservations.update');
        Route::get('/rentals', [ManagementController::class, 'rentals'])->name('rentals');
        Route::post('/rentals/{reservation}/release', [ManagementController::class, 'releaseGown'])->name('rentals.release');
        Route::post('/rentals/{reservation}/return', [ManagementController::class, 'returnGown'])->name('rentals.return');
        Route::post('/rentals/{reservation}/release-id', [ManagementController::class, 'releaseIdCollateral'])->name('rentals.release-id');
        Route::post('/cleaning/{cleaning}/complete', [ManagementController::class, 'completeCleaning'])->name('cleaning.complete');
        Route::get('/maintenance', [ManagementController::class, 'maintenance'])->name('maintenance');
        Route::get('/maintenance/gowns', [ManagementController::class, 'maintenanceGowns'])->name('maintenance.gowns');
        Route::post('/maintenance', [ManagementController::class, 'createMaintenance'])->name('maintenance.store');
        Route::post('/maintenance/{maintenance}/complete', [ManagementController::class, 'completeMaintenance'])->name('maintenance.complete');

        // Inventory reporting: damage log + customer gown purchases
        Route::get('/damages', [InventoryReportController::class, 'damages'])->name('damages');
        Route::get('/archive/damages', [InventoryReportController::class, 'archivedDamages'])->name('damages.archive');
        Route::get('/damages/gowns', [InventoryReportController::class, 'damageGowns'])->name('damages.gowns');
        Route::post('/damages', [InventoryReportController::class, 'storeDamage'])->name('damages.store');
        Route::patch('/damages/{damage}/final-cost', [InventoryReportController::class, 'updateDamageFinalCost'])->name('damages.final-cost');
        Route::get('/damages/{damage}/photo', [InventoryReportController::class, 'damagePhoto'])->name('damages.photo');
        Route::patch('/damages/{damage}/archive', [InventoryReportController::class, 'archiveDamage'])->name('damages.archive.store');
        Route::patch('/damages/{damage}/restore', [InventoryReportController::class, 'restoreDamage'])->name('damages.restore');
        Route::get('/purchases', [InventoryReportController::class, 'purchases'])->name('purchases');
        Route::get('/purchases/gowns', [InventoryReportController::class, 'purchaseGowns'])->name('purchases.gowns');
        Route::post('/purchases', [InventoryReportController::class, 'storePurchase'])->name('purchases.store');
        Route::get('/customers', [ManagementController::class, 'customers'])->name('customers');
        Route::patch('/customers/{customer}', [ManagementController::class, 'updateCustomer'])->name('customers.update');
        Route::get('/customers/{customer}', [ManagementController::class, 'customerDetails'])->name('customers.show');
        Route::get('/payments', [ManagementController::class, 'payments'])->name('payments');
        Route::post('/reservations/{reservation}/payments', [ManagementController::class, 'recordPayment'])->name('payments.store');
        Route::patch('/payments/{payment}', [ManagementController::class, 'verifyPayment'])->name('payments.update');
        Route::get('/catalog', [CustomerDashboardController::class, 'catalog'])->name('catalog');
        Route::get('/catalog/{gown}', [CustomerDashboardController::class, 'details'])->name('catalog.show');
        Route::post('/catalog/{gown}/cart', [ManagementController::class, 'addToCart'])->name('cart.add');
        Route::get('/cart', [ManagementController::class, 'cart'])->name('cart');
        Route::delete('/cart/gowns/{gown}', [ManagementController::class, 'removeFromCart'])->name('cart.remove');
        Route::delete('/cart', [ManagementController::class, 'clearCart'])->name('cart.clear');
        Route::get('/catalog/{gown}/reserve', [ManagementController::class, 'employeeReservationForm'])->name('catalog.reserve');
        Route::get('/reserve', [ManagementController::class, 'employeeReservationForm'])->name('reserve');
        Route::get('/employees', [ManagementController::class, 'employees'])->name('employees');
        Route::get('/employees/create', [ManagementController::class, 'createEmployeeForm'])->name('employees.create');
        Route::get('/employees/{employee}', [ManagementController::class, 'employeeDetails'])->name('employees.show');
        Route::post('/employees', [ManagementController::class, 'createEmployee'])->name('employees.store');
        Route::put('/employees/{employee}', [ManagementController::class, 'updateEmployee'])->name('employees.update');
        Route::patch('/employees/{employee}/toggle', [ManagementController::class, 'toggleEmployee'])->name('employees.toggle');
        Route::get('/reports/export', [ManagementController::class, 'exportReports'])->name('reports.export');
        Route::get('/reports/pdf', [ManagementController::class, 'exportReportsPdf'])->name('reports.pdf');
        Route::get('/reports', [ManagementController::class, 'reports'])->name('reports');
        Route::get('/settings', [ManagementController::class, 'settings'])->name('settings');
        Route::put('/settings', [ManagementController::class, 'saveSettings'])->name('settings.save');

        Route::resource('categories', CategoryController::class);
        Route::patch('/categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::resource('gowns', GownController::class)->except('destroy');
        Route::get('/archive', [GownController::class, 'archiveIndex'])->name('archive');
        Route::post('/gowns/{gown}/archive', [GownController::class, 'archive'])->name('gowns.archive');
        Route::patch('/gowns/{gown}/restore', [GownController::class, 'restore'])->name('gowns.restore');
        Route::resource('accessories', AccessoryController::class);

    });

/*
|--------------------------------------------------------------------------
| Employee
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:employee'])
    ->prefix('employee')
    ->name('employee.')
    ->group(function () {

        Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('dashboard');
        Route::get('/notifications', [StaffNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [StaffNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [StaffNotificationController::class, 'markRead'])->name('notifications.read');

        // Reservations (table + add reservation -> catalog)
        Route::get('/reservations', [ManagementController::class, 'reservations'])->name('reservations');
        Route::get('/reservations/availability', [ManagementController::class, 'checkReservationAvailability'])->name('reservations.availability');
        Route::get('/reservations/{reservation}', [ManagementController::class, 'reservationDetails'])->name('reservations.show');
        Route::post('/reservations', [ManagementController::class, 'storeEmployeeReservation'])->name('reservations.store');
        Route::patch('/reservations/{reservation}', [ManagementController::class, 'updateReservation'])->name('reservations.update');

        // Rentals & returns
        Route::get('/rentals', [ManagementController::class, 'rentals'])->name('rentals');
        Route::post('/rentals/{reservation}/release', [ManagementController::class, 'releaseGown'])->name('rentals.release');
        Route::post('/rentals/{reservation}/return', [ManagementController::class, 'returnGown'])->name('rentals.return');
        Route::post('/rentals/{reservation}/release-id', [ManagementController::class, 'releaseIdCollateral'])->name('rentals.release-id');
        Route::post('/cleaning/{cleaning}/complete', [ManagementController::class, 'completeCleaning'])->name('cleaning.complete');

        // Payments
        Route::get('/payments', [ManagementController::class, 'payments'])->name('payments');
        Route::post('/reservations/{reservation}/payments', [ManagementController::class, 'recordPayment'])->name('payments.store');
        Route::patch('/payments/{payment}', [ManagementController::class, 'verifyPayment'])->name('payments.update');

        // Catalog (reserve for walk-in customer or bulk-add to the in-store cart)
        Route::get('/catalog', [CustomerDashboardController::class, 'catalog'])->name('catalog');
        Route::get('/catalog/{gown}', [CustomerDashboardController::class, 'details'])->name('catalog.show');
        Route::post('/catalog/{gown}/cart', [ManagementController::class, 'addToCart'])->name('cart.add');
        Route::get('/cart', [ManagementController::class, 'cart'])->name('cart');
        Route::delete('/cart/gowns/{gown}', [ManagementController::class, 'removeFromCart'])->name('cart.remove');
        Route::delete('/cart', [ManagementController::class, 'clearCart'])->name('cart.clear');
        Route::get('/catalog/{gown}/reserve', [ManagementController::class, 'employeeReservationForm'])->name('catalog.reserve');
        Route::get('/reserve', [ManagementController::class, 'employeeReservationForm'])->name('reserve');
    });

/*
|--------------------------------------------------------------------------
| Customer
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:customer'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function () {

        Route::get('/dashboard', [
            CustomerDashboardController::class,
            'index',
        ])->name('dashboard');

        Route::get('/catalog', [CustomerDashboardController::class, 'catalog'])->name('catalog');
        Route::get('/gowns/{gown}', [CustomerDashboardController::class, 'details'])->name('gowns.show');
        Route::get('/gowns/{gown}/reserve', [CustomerDashboardController::class, 'reserveGown'])->name('reserve.gown');
        Route::get('/gowns/{gown}/availability', [CustomerDashboardController::class, 'checkAvailability'])->name('reserve.availability');
        Route::post('/gowns/{gown}/cart', [CustomerDashboardController::class, 'addToCart'])->name('cart.add');
        Route::get('/cart', [CustomerDashboardController::class, 'cart'])->name('cart');
        Route::delete('/cart/gowns/{gown}', [CustomerDashboardController::class, 'removeFromCart'])->name('cart.remove');
        Route::delete('/cart', [CustomerDashboardController::class, 'clearCart'])->name('cart.clear');
        Route::get('/reserve', [CustomerDashboardController::class, 'reserve'])->name('reserve');
        Route::post('/reserve', [CustomerDashboardController::class, 'storeReservation'])->name('reserve.store');
        Route::get('/reservations', [CustomerDashboardController::class, 'reservations'])->name('reservations');
        Route::get('/reservations/{reservation}', [CustomerDashboardController::class, 'showReservation'])->name('reservations.show');
        Route::patch('/reservations/{reservation}/cancel', [CustomerDashboardController::class, 'cancelReservation'])->name('reservations.cancel');
        Route::post('/reservations/{reservation}/payments', [CustomerDashboardController::class, 'submitPayment'])->name('reservations.payments.store');
        Route::get('/notifications', [CustomerNotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/read-all', [CustomerNotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [CustomerNotificationController::class, 'markRead'])->name('notifications.read');

    });

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/payments/{payment}/proof', [ManagementController::class, 'paymentProof'])->name('payments.proof');
    Route::get('/reservations/{reservation}/collateral-photo/{type}', [ManagementController::class, 'collateralPhoto'])->name('reservations.collateral-photo');
});
