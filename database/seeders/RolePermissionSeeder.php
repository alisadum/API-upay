<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definisikan dan Buat Izin (Permissions)
        $permissions = [
            'view dashboard', 'access admin panel',
            'manage users', 'manage merchants', 'approve promotions', 'view all reports',
            'create promotion', 'edit own promotion', 'delete own promotion', 'view merchant dashboard', 'redeem user voucher', 'view own reports',
            'view promotions', 'purchase promotion', 'view own vouchers', 'use cashback',
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Definisikan dan Buat Peran (Roles), lalu berikan izin
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(['view dashboard', 'access admin panel', 'manage users', 'manage merchants', 'approve promotions', 'view all reports']);

        $merchantRole = Role::firstOrCreate(['name' => 'merchant']);
        $merchantRole->syncPermissions(['view dashboard', 'create promotion', 'edit own promotion', 'delete own promotion', 'view merchant dashboard', 'redeem user voucher', 'view own reports']);

        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions(['view dashboard', 'view promotions', 'purchase promotion', 'view own vouchers', 'use cashback']);

        // 3. Buat Pengguna Default dan Tetapkan Peran (Ini adalah pengguna yang akan Anda gunakan untuk Postman)
        $adminUser = User::firstOrCreate(['email' => 'adminpay@gmail.com'], ['name' => 'Upay Admin', 'password' => Hash::make('password123')]);
        $adminUser->assignRole('admin');

        $merchantUser = User::firstOrCreate(['email' => 'upay@gmail.com'], ['name' => 'Upay Merchant', 'password' => Hash::make('password123')]);
        $merchantUser->assignRole('merchant');

        $regularUser = User::firstOrCreate(['email' => 'upoy@gmail.com'], ['name' => 'tiara', 'password' => Hash::make('password123')]);
        $regularUser->assignRole('user');
    }
}
