<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workstreams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('code', 2)->comment('A bis J gemaess Abschnitt 14.1');
            $table->string('title');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deputy_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deliverables')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('geplant')->comment('geplant|in_arbeit|blockiert|abgeschlossen');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });

        Schema::create('project_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workstream_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('probability')->comment('niedrig|mittel|hoch');
            $table->string('impact')->comment('niedrig|mittel|hoch');
            $table->text('mitigation')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('offen')->comment('offen|in_bearbeitung|gemindert|eingetreten|geschlossen');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_risks');
        Schema::dropIfExists('workstreams');
    }
};
