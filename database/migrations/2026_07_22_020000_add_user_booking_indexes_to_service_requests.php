<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'car_inspection_requests',
        'sell_for_me_requests',
        'car_registration_requests',
        'car_ownership_requests',
        'auction_sheet_verification_requests',
        'car_finance_requests',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->index(
                    ['user_id', 'created_at'],
                    $this->indexName($tableName)
                );
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($this->indexName($tableName));
            });
        }
    }

    private function indexName(string $tableName): string
    {
        return match ($tableName) {
            'car_inspection_requests' => 'inspection_user_created_index',
            'sell_for_me_requests' => 'sell_for_me_user_created_index',
            'car_registration_requests' => 'registration_user_created_index',
            'car_ownership_requests' => 'ownership_user_created_index',
            'auction_sheet_verification_requests' => 'auction_sheet_user_created_index',
            'car_finance_requests' => 'finance_user_created_index',
        };
    }
};
