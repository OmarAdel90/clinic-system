<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop all foreign keys referencing `patients` or `doctors` before altering columns
        $fks = [
            'reports'              => ['reports_patient_id_foreign', 'reports_doctor_id_foreign'],
            'patient_feedback'     => ['patient_feedback_patient_id_foreign', 'patient_feedback_doctor_id_foreign'],
            'invoices'             => ['invoices_patient_id_foreign'],
            'medical_records'      => ['medical_records_patient_id_foreign'],
            'visits'               => ['visits_patient_id_foreign', 'visits_doctor_id_foreign'],
            'treatment_plans'      => ['treatment_plans_patient_id_foreign', 'treatment_plans_doctor_id_foreign'],
            'leads'                => ['leads_patient_id_foreign'],
        ];

        foreach ($fks as $table => $constraints) {
            foreach ($constraints as $fk) {
                if (Schema::hasTable($table)) {
                    try {
                        Schema::table($table, function (Blueprint $table) use ($fk) {
                            $table->dropForeign($fk);
                        });
                    } catch (\Throwable $e) {
                        // FK may have already been dropped — skip
                    }
                }
            }
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'SSN')) {
                $table->string('SSN')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('SSN');
            }
            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('users', 'salary')) {
                $table->decimal('salary', 10, 2)->nullable()->after('location');
            }
            if (!Schema::hasColumn('users', 'commission')) {
                $table->decimal('commission', 10, 2)->nullable()->after('salary');
            }
            if (!Schema::hasColumn('users', 'hired_at')) {
                $table->timestamp('hired_at')->nullable()->after('commission');
            }
            if (!Schema::hasColumn('users', 'title')) {
                $table->string('title')->nullable()->after('hired_at');
            }
            if (!Schema::hasColumn('users', 'arabic_name')) {
                $table->string('arabic_name')->nullable()->after('title');
            }
            if (!Schema::hasColumn('users', 'specialization')) {
                $table->string('specialization')->nullable()->after('arabic_name');
            }
            if (!Schema::hasColumn('users', 'accessible_clinics')) {
                $table->json('accessible_clinics')->nullable()->after('specialization');
            }
        });

        if (Schema::hasColumn('reports', 'doctor_id')) {
            try {
                Schema::table('reports', function (Blueprint $table) {
                    $table->dropForeign(['doctor_id']);
                });
            } catch (\Throwable $e) {
                // FK may have already been dropped
            }
            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn('doctor_id');
            });
        }
        if (Schema::hasColumn('reports', 'patient_id')) {
            try {
                Schema::table('reports', function (Blueprint $table) {
                    $table->dropForeign(['patient_id']);
                });
            } catch (\Throwable $e) {
                // FK may have already been dropped
            }
            Schema::table('reports', function (Blueprint $table) {
                $table->dropColumn('patient_id');
            });
        }
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('reports', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->after('lead_id');
            }
            if (!Schema::hasColumn('reports', 'cost_known')) {
                $table->boolean('cost_known')->default(false)->after('status');
            }
        });

        if (Schema::hasColumn('visits', 'patient_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->renameColumn('patient_id', 'lead_id');
            });
        }
        if (Schema::hasColumn('visits', 'doctor_id')) {
            Schema::table('visits', function (Blueprint $table) {
                $table->renameColumn('doctor_id', 'user_id');
            });
        }

        if (Schema::hasColumn('treatment_plans', 'patient_id')) {
            Schema::table('treatment_plans', function (Blueprint $table) {
                $table->renameColumn('patient_id', 'lead_id');
            });
        }
        if (Schema::hasColumn('treatment_plans', 'doctor_id')) {
            Schema::table('treatment_plans', function (Blueprint $table) {
                $table->renameColumn('doctor_id', 'user_id');
            });
        }

        if (Schema::hasColumn('invoices', 'patient_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->renameColumn('patient_id', 'lead_id');
            });
        }

        if (Schema::hasColumn('medical_records', 'patient_id')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->renameColumn('patient_id', 'lead_id');
            });
        }

        if (Schema::hasColumn('patient_feedback', 'patient_id')) {
            Schema::table('patient_feedback', function (Blueprint $table) {
                $table->renameColumn('patient_id', 'lead_id');
            });
        }
        if (Schema::hasColumn('patient_feedback', 'doctor_id')) {
            Schema::table('patient_feedback', function (Blueprint $table) {
                $table->renameColumn('doctor_id', 'user_id');
            });
        }

        if (Schema::hasColumn('leads', 'patient_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('patient_id');
            });
        }

        Schema::dropIfExists('patients');
        Schema::dropIfExists('call_center');
        Schema::dropIfExists('doctors');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
    }
};
