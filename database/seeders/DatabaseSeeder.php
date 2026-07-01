<?php

namespace Database\Seeders;

use Modules\Auth\Models\User;
use Modules\Lead\Models\LeadStatus;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $statuses = collect([
            ['label' => 'New', 'key' => 'new', 'color' => '#6b7280', 'is_qualified' => false, 'sort_order' => 1],
            ['label' => 'Contacted', 'key' => 'contacted', 'color' => '#3b82f6', 'is_qualified' => false, 'sort_order' => 2],
            ['label' => 'Qualified', 'key' => 'qualified', 'color' => '#f59e0b', 'is_qualified' => true, 'sort_order' => 3],
            ['label' => 'Converted', 'key' => 'converted', 'color' => '#10b981', 'is_qualified' => true, 'sort_order' => 4],
            ['label' => 'Lost', 'key' => 'lost', 'color' => '#ef4444', 'is_qualified' => false, 'sort_order' => 5],
        ])->each(fn ($s) => LeadStatus::firstOrCreate(['key' => $s['key']], $s));

        $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->first();

        $admin = User::firstOrCreate(
            ['email' => 'super@clinic.com'],
            [
                'name'     => 'Admin User',
                'password' => bcrypt('password123'),
            ]
        );
        $admin->update(['role_id' => $adminRole?->id]);
        $admin->assignRole('admin');
    }
}
