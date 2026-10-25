<?php

namespace Database\Seeders;

use App\Support\AuthIdentity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        $shopId = DB::table('shops')->insertGetId([
            'name' => 'posKhata Shop',
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
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $branchId = DB::table('branches')->insertGetId([
            'shop_id' => $shopId,
            'name' => 'Main',
            'code' => 'MAIN',
            'address' => null,
            'phone' => null,
            'is_default' => true,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $userId = DB::table('users')->insertGetId([
            'shop_id' => $shopId,
            'branch_id' => $branchId,
            'name' => 'Superadmin',
            'email' => AuthIdentity::emailFromUsername('superadmin'),
            'email_verified_at' => $now,
            'password' => Hash::make('password'),
            'role' => 'superadmin',
            'is_active' => true,
            'remember_token' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('branch_user')->insert([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'created_at' => $now,
        ]);

        foreach ([
            ['name' => 'Piece', 'code' => 'pcs'],
            ['name' => 'Kilogram', 'code' => 'kg'],
            ['name' => 'Liter', 'code' => 'l'],
            ['name' => 'Dozen', 'code' => 'dozen'],
            ['name' => 'Pack', 'code' => 'pack'],
        ] as $unit) {
            DB::table('units')->insert([
                'shop_id' => $shopId,
                'name' => $unit['name'],
                'code' => $unit['code'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ([
            ['name' => 'Admin', 'username' => 'admin', 'role' => 'admin'],
            ['name' => 'Cashier', 'username' => 'cashier', 'role' => 'cashier'],
        ] as $staff) {
            $staffId = DB::table('users')->insertGetId([
                'shop_id' => $shopId,
                'branch_id' => $branchId,
                'name' => $staff['name'],
                'email' => AuthIdentity::emailFromUsername($staff['username']),
                'email_verified_at' => $now,
                'password' => Hash::make('password'),
                'role' => $staff['role'],
                'is_active' => true,
                'remember_token' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('branch_user')->insert([
                'branch_id' => $branchId,
                'user_id' => $staffId,
                'created_at' => $now,
            ]);
        }

        $this->call(DummyDataSeeder::class);
    }
}
