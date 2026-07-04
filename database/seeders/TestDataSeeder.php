<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Lead\Models\Lead;
use Modules\Lead\Models\LeadStatus;
use Modules\CRM\Models\Conversation;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseInventory;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing references
        $lead = Lead::find(1); // Ahmed El-Sayed
        $clinic = Clinic::find(4); // Alexandria Health Center
        $user = User::find(5); // Dr. Ahmed El-Sayed
        $convertedStatus = LeadStatus::where('key', 'converted')->first();

        // Create a conversation for lead 1 (needed for visit flow confirm)
        $conversation = Conversation::create([
            'lead_id' => $lead->id,
            'assigned_user_id' => $user->id,
            'platform' => 'whatsapp',
            'lead_status' => 'new',
        ]);

        // Ensure warehouse inventory has enough stock
        WarehouseInventory::where('sku', 'MED-001-PM')
            ->update(['quantity' => 100]);
    }
}
