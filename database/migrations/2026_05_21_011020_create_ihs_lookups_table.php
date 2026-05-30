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
            $table->string('nik', 16);              // FIX 2: hapus ->unique() dari sini
            $table->enum('tipe', ['pasien', 'dokter']);
            $table->string('ihs_number')->nullable();
            $table->string('nama')->nullable();     // FIX 3: tambah kolom nama
            $table->boolean('ditemukan')->default(false);
            $table->timestamps();

            $table->unique(['nik', 'tipe']);        // FIX 2: unique per kombinasi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ihs_lookups');
    }
};