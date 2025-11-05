<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wizard_progress', function (Blueprint $table) {
            $table->id();
            $table->string('wizard_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('current_step')->nullable();
            $table->string('current_step_id')->nullable();
            $table->json('completed_steps');
            $table->text('step_data');
            $table->enum('status', ['in_progress', 'completed', 'abandoned'])->default('in_progress')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wizard_id', 'user_id']);
            $table->index(['wizard_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wizard_progress');
    }
};
