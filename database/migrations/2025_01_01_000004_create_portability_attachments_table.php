<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $prefix = config('alize.table_prefix', 'alize_');

        Schema::create($prefix . 'portability_attachments', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->unsignedBigInteger('portability_id');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedInteger('file_size'); // Size in bytes
            $table->string('path'); // Storage path
            $table->timestamps();

            $table->foreign('portability_id')
                  ->references('id')
                  ->on($prefix . 'portabilities')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $prefix = config('alize.table_prefix', 'alize_');
        Schema::dropIfExists($prefix . 'portability_attachments');
    }
};
