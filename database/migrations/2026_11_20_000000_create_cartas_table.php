<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartas', function (Blueprint $table) {
            $table->id();
            $table->string('remitente', 60);
            $table->unsignedTinyInteger('edad');
            $table->string('ciudad', 100)->index();
            $table->json('regalos');
            $table->string('estado', 20)->default('RECIBIDA')->index();
            $table->string('comportamiento', 30)->nullable();
            $table->dateTime('fecha_recepcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas');
    }
};
