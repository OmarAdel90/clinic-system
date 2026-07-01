<?php

use App\Providers\AppServiceProvider;
use Modules\Auth\Providers\AuthServiceProvider;
use Modules\Clinic\Providers\ClinicServiceProvider;
use Modules\Patient\Providers\PatientServiceProvider;
use Modules\Doctor\Providers\DoctorServiceProvider;
use Modules\Visit\Providers\VisitServiceProvider;
use Modules\TreatmentPlan\Providers\TreatmentPlanServiceProvider;
use Modules\Invoice\Providers\InvoiceServiceProvider;
use Modules\Pharmaceutical\Providers\PharmaceuticalServiceProvider;
use Modules\Supplier\Providers\SupplierServiceProvider;
use Modules\Warehouse\Providers\WarehouseServiceProvider;
use Modules\Transaction\Providers\TransactionServiceProvider;
use Modules\CRM\Providers\CRMServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ClinicServiceProvider::class,
    PatientServiceProvider::class,
    //DoctorServiceProvider::class,
    VisitServiceProvider::class,
    TreatmentPlanServiceProvider::class,
    InvoiceServiceProvider::class,
    PharmaceuticalServiceProvider::class,
    SupplierServiceProvider::class,
    WarehouseServiceProvider::class,
    TransactionServiceProvider::class,
    CRMServiceProvider::class,
    \Modules\Admin\Providers\AdminServiceProvider::class,
];
