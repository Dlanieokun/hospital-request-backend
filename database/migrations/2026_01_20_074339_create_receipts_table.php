<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->string('name');
            $table->string('mode');
            $table->integer('paid');
            $table->string('ref')->nullable();
            $table->string('status')->default('Pending');
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->integer('case_id')->nullable();
            $table->timestamp('request_date')->nullable();
            $table->timestamp('release_date')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('status_request')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
