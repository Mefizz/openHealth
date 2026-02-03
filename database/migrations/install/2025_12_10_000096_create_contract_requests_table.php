<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_requests', static function (Blueprint $table) {
            $table->id();

            // eHealth ID
            $table->uuid('uuid')->nullable()->unique();

            // Relations
            $table->uuid('contractor_legal_entity_id')->index();
            $table->uuid('contractor_owner_id')->index();

            // Link to Contract (if exists)
            $table->uuid('contract_id')->nullable()->index();
            $table->uuid('previous_request_id')->nullable()->index();
            $table->uuid('parent_contract_id')->nullable()->index();

            // Basic fields
            $table->string('contractor_base')->nullable();
            $table->string('contract_number')->nullable();
            $table->string('id_form')->nullable();
            $table->string('status')->default('NEW')->index();
            $table->text('status_reason')->nullable();
            $table->string('type')->nullable(); // CAPITATION / REIMBURSEMENT
            $table->string('issue_city')->nullable(); // City of stacking
            $table->text('printout_content')->nullable(); // HTML Printed Form

            // Details
            $table->integer('contractor_rmsp_amount')->nullable(); // POPULATION
            $table->boolean('external_contractor_flag')->default(false);

            // JSONB fields
            $table->jsonb('contractor_payment_details')->nullable();
            $table->jsonb('external_contractors')->nullable();
            $table->jsonb('contractor_employee_divisions')->nullable();
            $table->jsonb('contractor_divisions')->nullable();
            $table->jsonb('medical_programs')->nullable(); // List of programs

            // Raw response data storage
            $table->jsonb('data')->nullable();

            // Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // NHS (NSZU) side data
            $table->uuid('nhs_legal_entity_id')->nullable();
            $table->uuid('nhs_signer_id')->nullable();
            $table->string('nhs_signer_base')->nullable();
            $table->double('nhs_contract_price')->nullable();
            $table->string('nhs_payment_method')->nullable();
            $table->date('nhs_signed_date')->nullable();

            // Metadata & Timestamps (Explicitly defined to match API)
            $table->uuid('assignee_id')->nullable();
            $table->uuid('ehealth_inserted_by')->nullable();
            $table->timestamp('ehealth_inserted_at')->nullable();
            $table->uuid('ehealth_updated_by')->nullable();
            $table->timestamp('ehealth_updated_at')->nullable();

            $table->boolean('contractor_signed')->default(false);

            // Standard timestamps for DB (inserted_at comes from API)
            $table->timestamp('inserted_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent(); // Local creation time
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_requests');
    }
};
