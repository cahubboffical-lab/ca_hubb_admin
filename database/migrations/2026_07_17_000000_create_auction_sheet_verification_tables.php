<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('auction_sheet_verification_prices')) {
            Schema::create('auction_sheet_verification_prices', static function (Blueprint $table) {
                $table->id();
                $table->decimal('price_amount', 12, 2)->default(2950);
                $table->char('currency_code', 3)->default('PKR');
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            DB::table('auction_sheet_verification_prices')->insert([
                'id' => 1,
                'price_amount' => 2950,
                'currency_code' => 'PKR',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('auction_sheet_verification_requests')) {
            Schema::create('auction_sheet_verification_requests', static function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('chassis_number', 50);
                $table->string('phone_number', 30);
                $table->string('phone_number_normalized', 30);
                $table->string('status', 30)->default('pending');
                $table->text('report_url')->nullable();
                $table->text('admin_notes')->nullable();
                $table->string('notification_status', 20)->default('pending');
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->decimal('price_amount', 12, 2)->nullable();
                $table->char('currency_code', 3)->nullable();
                $table->timestamps();

                $table->index('chassis_number', 'auction_verification_chassis_index');
                $table->index('phone_number', 'auction_verification_phone_index');
                $table->index('phone_number_normalized', 'auction_verification_phone_normalized_index');
                $table->index('status', 'auction_verification_status_index');
                $table->index('notification_status', 'auction_verification_notification_index');
                $table->index(['status', 'created_at'], 'auction_verification_queue_index');
                $table->index(
                    ['chassis_number', 'phone_number_normalized', 'status'],
                    'auction_verification_duplicate_index'
                );
            });
        }

        if (Schema::hasTable('permissions')) {
            $now = now();
            DB::table('permissions')->upsert([
                [
                    'name' => 'auction-sheet-verification-request-list',
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'auction-sheet-verification-request-update',
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ], ['name', 'guard_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'auction-sheet-verification-request-list',
                'auction-sheet-verification-request-update',
            ])->delete();
        }

        Schema::dropIfExists('auction_sheet_verification_requests');
        Schema::dropIfExists('auction_sheet_verification_prices');
    }
};
