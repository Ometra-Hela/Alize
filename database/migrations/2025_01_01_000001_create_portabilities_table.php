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

        Schema::create($prefix . 'portabilities', function (Blueprint $table) {
            $table->id('id_portability'); // Keeping ID name if desired, or just 'id'

            // Core IDs
            $table->string('port_id', 21)->unique()->nullable();
            $table->string('folio_id', 18)->nullable();

            // State
            $table->string('state', 50)->nullable()->index();

            // Portability Details
            $table->string('port_type', 20)->nullable();    // MOBILE, FIXED
            $table->string('subscriber_type', 20)->nullable(); // INDIVIDUAL, BUSINESS

            // Participants
            $table->string('dida', 10)->nullable(); // Donating IDA
            $table->string('dcr', 10)->nullable();  // Donating Region
            $table->string('rida', 10)->nullable(); // Recipient IDA
            $table->string('rcr', 10)->nullable();  // Recipient Region

            // Dates
            $table->timestamp('subs_req_date')->nullable(); // Subscription Request Date
            $table->timestamp('req_port_exec_date')->nullable();
            $table->timestamp('port_exec_date')->nullable();

            // Timers
            $table->timestamp('t1_expires_at')->nullable();
            $table->timestamp('t3_expires_at')->nullable();
            $table->timestamp('t4_expires_at')->nullable();
            $table->timestamp('t5_expires_at')->nullable();

            // Auth
            $table->string('pin', 10)->nullable();

            // External Links (to Host App)
            $table->unsignedBigInteger('external_client_id')->nullable()->index();
            $table->string('external_reference', 100)->nullable()->index();

            // Metadata
            $table->text('comments')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['t1_expires_at', 't3_expires_at', 't4_expires_at'], 'idx_alize_port_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $prefix = config('alize.table_prefix', 'alize_');
        Schema::dropIfExists($prefix . 'portabilities');
    }
};
