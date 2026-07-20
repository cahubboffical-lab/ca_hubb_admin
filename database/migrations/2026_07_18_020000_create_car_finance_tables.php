<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('car_finance_banks')) {
            Schema::create('car_finance_banks', static function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->decimal('finance_rate', 7, 4);
                $table->decimal('insurance_rate', 7, 4);
                $table->unsignedBigInteger('processing_fee');
                $table->text('logo_url')->nullable();
                $table->char('accent_color', 7)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'display_order'], 'finance_banks_active_order_index');
            });

            $this->insertInitialBanks();
        }

        if (! Schema::hasTable('car_finance_requests')) {
            Schema::create('car_finance_requests', static function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('car_finance_bank_id')->constrained('car_finance_banks')->restrictOnDelete();
                $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
                $table->foreignId('car_model_id')->constrained('car_models')->restrictOnDelete();
                $table->string('finance_type', 20);
                $table->unsignedSmallInteger('model_year')->nullable();
                $table->string('car_variant', 150)->nullable();
                $table->unsignedBigInteger('used_car_price')->nullable();
                $table->unsignedBigInteger('vehicle_price');
                $table->string('price_source', 30);
                $table->unsignedSmallInteger('tenure_years');
                $table->decimal('down_payment_percent', 5, 2);
                $table->decimal('finance_rate', 7, 4);
                $table->decimal('insurance_rate', 7, 4);
                $table->unsignedBigInteger('processing_fee');
                $table->unsignedBigInteger('down_payment_amount');
                $table->unsignedBigInteger('bank_loan');
                $table->unsignedBigInteger('first_year_insurance');
                $table->unsignedBigInteger('monthly_installment');
                $table->unsignedBigInteger('total_initial_deposit');
                $table->string('status', 30)->default('pending');
                $table->text('admin_notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('canceled_at')->nullable();
                $table->timestamps();

                $table->index('finance_type', 'finance_requests_type_index');
                $table->index('status', 'finance_requests_status_index');
                $table->index(['status', 'created_at'], 'finance_requests_queue_index');
                $table->index(['user_id', 'status'], 'finance_requests_user_status_index');
                $table->index(
                    ['user_id', 'car_model_id', 'car_finance_bank_id', 'finance_type', 'status'],
                    'finance_requests_duplicate_index'
                );
            });
        }

        if (Schema::hasTable('permissions')) {
            $now = now();
            DB::table('permissions')->upsert([
                $this->permission('car-finance-bank-list', $now),
                $this->permission('car-finance-bank-create', $now),
                $this->permission('car-finance-bank-update', $now),
                $this->permission('car-finance-bank-delete', $now),
                $this->permission('car-finance-request-list', $now),
                $this->permission('car-finance-request-update', $now),
            ], ['name', 'guard_name'], ['updated_at']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->whereIn('name', [
                'car-finance-bank-list',
                'car-finance-bank-create',
                'car-finance-bank-update',
                'car-finance-bank-delete',
                'car-finance-request-list',
                'car-finance-request-update',
            ])->delete();
        }

        Schema::dropIfExists('car_finance_requests');
        Schema::dropIfExists('car_finance_banks');
    }

    private function insertInitialBanks(): void
    {
        $now = now();
        DB::table('car_finance_banks')->insert([
            $this->bank('faysal', 'Faysal Car Finance', 15.6400, 1.5000, 12000, '#1B6B9A', 1, $now),
            $this->bank('micar', 'MI Car', 14.6400, 1.2900, 8000, '#1F7A3E', 2, $now),
            $this->bank('dib', 'DIB Auto Finance', 14.6400, 1.7500, 8350, '#0E8D6A', 3, $now),
            $this->bank('mcb', 'MCB Car4U', 15.6400, 1.7500, 12000, '#1D8E49', 4, $now),
            $this->bank('albaraka', 'Al Baraka Carsaaz', 15.7200, 1.5000, 8120, '#C84B31', 5, $now),
            $this->bank('alfalah', 'Alfalah Car Financing', 14.9500, 1.6000, 10000, '#D62828', 6, $now),
        ]);
    }

    private function bank(string $code, string $name, float $financeRate, float $insuranceRate, int $fee, string $color, int $order, $now): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'finance_rate' => $financeRate,
            'insurance_rate' => $insuranceRate,
            'processing_fee' => $fee,
            'logo_url' => null,
            'accent_color' => $color,
            'is_active' => true,
            'display_order' => $order,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function permission(string $name, $now): array
    {
        return ['name' => $name, 'guard_name' => 'web', 'created_at' => $now, 'updated_at' => $now];
    }
};
