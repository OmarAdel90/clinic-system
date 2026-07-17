<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $models = [
            'assignment_state',
            'call_center_queue_entry',
            'call_center_performance_metrics',
            'campaign',
            'campaign_cost',
            'clinic',
            'conversation',
            'departments',
            'follow_up',
            'lead',
            'lead_feedback',
            'lead_status',
            'lead_status_history',
            'medical_record',
            'message',
            'patient_feedback',
            'pharmaceutical',
            'report',
            'role',
            'service',
            'supplier',
            'supplier_payment_history',
            'user',
            'warehouse',
            'warehouse_inventory',
            'warehouse_supplier_transaction',
            'invoice',
            'treatment_plan',
            'visit',
        ];

        $actions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
        ];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$action}_{$model}", 'web');
            }
        }

        Permission::findOrCreate('view_dashboard', 'web');

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::all());
    }
}
