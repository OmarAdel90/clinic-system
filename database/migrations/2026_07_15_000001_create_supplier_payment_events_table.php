<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_payment_history_id')->constrained('supplier_payment_history')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $rows = DB::table('supplier_payment_history')
            ->select(['id', 'total_paid', 'created_at', 'updated_at'])
            ->whereNotNull('total_paid')
            ->where('total_paid', '>', 0)
            ->get();

        foreach ($rows as $row) {
            DB::table('supplier_payment_events')->insert([
                'supplier_payment_history_id' => $row->id,
                'amount' => $row->total_paid,
                'paid_at' => $row->updated_at ?? $row->created_at ?? now(),
                'recorded_by' => null,
                'notes' => 'Legacy imported payment total.',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_events');
    }
};
