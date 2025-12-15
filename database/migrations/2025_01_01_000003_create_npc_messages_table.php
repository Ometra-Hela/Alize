<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $prefix = config('alize.table_prefix', 'alize_');

        Schema::create($prefix . 'npc_messages', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->string('port_id', 21)->index();
            $table->integer('message_id');
            $table->enum('direction', ['IN', 'OUT'])->index();
            $table->integer('type_code');
            $table->string('sender', 10)->nullable();
            $table->text('raw_xml');
            $table->json('parsed_data')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('ack_status', 20)->nullable();
            $table->text('ack_text')->nullable();
            $table->integer('retry_count')->default(0);
            $table->string('idempotency_key', 64)->unique()->nullable();
            $table->timestamp('last_retry_at')->nullable();
            $table->timestamps();

            $table->index(['port_id', 'type_code']);
            $table->index(['direction', 'ack_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('alize.table_prefix', 'alize_');
        Schema::dropIfExists($prefix . 'npc_messages');
    }
};
