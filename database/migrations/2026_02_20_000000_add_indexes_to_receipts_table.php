<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mysql';

    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            // Paginated list: WHERE status_request IN (...) ORDER BY id DESC
            $table->index(['status_request', 'id'], 'receipts_status_request_id_index');
            // Search + online payment lookups
            $table->index('reference');
            $table->index('ref');
            // Report: filter by release_date
            $table->index('release_date');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropIndex('receipts_status_request_id_index');
            $table->dropIndex(['reference']);
            $table->dropIndex(['ref']);
            $table->dropIndex(['release_date']);
        });
    }
};
