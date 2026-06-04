<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->string('nik_pasien');
            $table->string('ihs_pasien');
            $table->string('nama_pasien');
            $table->string('ihs_dokter');
            $table->string('nama_dokter');
            $table->string('location_id_satusehat');
            $table->string('encounter_id_satusehat')->unique()->nullable(); // nullable dulu, diisi setelah response 201
            $table->string('nomor_internal');                               // format: ENC-YYYYMMDD-001
            $table->string('waktu_kunjungan');                              // UTC+0 ISO8601
            $table->string('waktu_masuk_ruang')->nullable();                // diisi saat in-progress
            $table->string('status')->default('arrived');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};