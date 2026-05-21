<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ihs_lookups', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->enum('tipe', ['pasien', 'dokter']);
            $table->string('ihs_number')->nullable();
            $table->boolean('ditemukan')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ihs_lookups');
    }
};