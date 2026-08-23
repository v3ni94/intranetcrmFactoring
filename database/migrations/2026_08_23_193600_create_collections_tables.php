<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dunning_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
            $table->string('case_type')->comment('mahnung|streitfall|rueckgriff|ausfall');
            $table->unsignedTinyInteger('dunning_level')->default(1);
            $table->string('status')->default('offen')->comment('offen|in_klaerung|zahlungsvereinbarung|inkasso|geschlossen');
            $table->text('reason')->nullable();
            $table->decimal('open_amount', 19, 4);
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('next_action_date')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dunning_cases');
    }
};
