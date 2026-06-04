<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            $table->string('encounter_id')->nullable(); // Tambahkan kolom ini
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_logs', function (Blueprint $table) {
            //
        });
    }
};
