<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['car_inspection_requests', 'sell_for_me_requests'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'admin_notes')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->text('admin_notes')->nullable()->after('status');
                });
            }
        }

        if (Schema::hasTable('auction_sheet_verification_requests')
            && Schema::hasColumn('auction_sheet_verification_requests', 'report_url')) {
            Schema::table('auction_sheet_verification_requests', function (Blueprint $table) {
                $table->dropColumn('report_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('auction_sheet_verification_requests')
            && ! Schema::hasColumn('auction_sheet_verification_requests', 'report_url')) {
            Schema::table('auction_sheet_verification_requests', function (Blueprint $table) {
                $table->text('report_url')->nullable()->after('status');
            });
        }

        foreach (['car_inspection_requests', 'sell_for_me_requests'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'admin_notes')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('admin_notes');
                });
            }
        }
    }
};
