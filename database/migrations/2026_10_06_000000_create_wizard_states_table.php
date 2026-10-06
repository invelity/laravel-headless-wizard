<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        $connection = Config::get('wizard.stores.database.connection');

        return is_string($connection) ? $connection : null;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();
            $table->string('wizard');
            $table->string('scope');
            $table->longText('state');
            $table->timestamps();

            $table->unique(['wizard', 'scope']);
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    /**
     * Get the name of the table.
     */
    private function table(): string
    {
        return Config::string('wizard.stores.database.table', 'wizard_states');
    }
};
