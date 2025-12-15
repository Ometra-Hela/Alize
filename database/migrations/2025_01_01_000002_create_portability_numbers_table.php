<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        $prefix = config('alize.table_prefix', 'alize_');

        Schema::create($prefix . 'portability_numbers', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->unsignedBigInteger('portability_id');
            $table->string('msisdn_ported', 15)->index();

            // Allow CASCADE delete if portability is deleted
            $table->foreign('portability_id')
                  ->references('id_portability') // Assuming primary key of portabilities is id_portability
                  ->on($prefix . 'portabilities')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $prefix = config('alize.table_prefix', 'alize_');
        Schema::dropIfExists($prefix . 'portability_numbers');
    }
};
