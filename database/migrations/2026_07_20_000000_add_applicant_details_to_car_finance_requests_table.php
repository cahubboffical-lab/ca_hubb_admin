<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_finance_requests', function (Blueprint $table) {
            $table->string('full_name', 150)->nullable()->after('user_id');
            $table->string('phone_number', 30)->nullable()->after('full_name');
            $table->string('email', 150)->nullable()->after('phone_number');
            $table->text('cnic')->nullable()->after('email');
            $table->string('income_source', 30)->nullable()->after('cnic');
            $table->string('monthly_income', 50)->nullable()->after('income_source');
            $table->string('current_bank', 150)->nullable()->after('monthly_income');
            $table->boolean('has_credit_cards_or_loans')->nullable()->after('current_bank');
            $table->string('processing_time', 50)->nullable()->after('has_credit_cards_or_loans');

            $table->index('phone_number');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('car_finance_requests', function (Blueprint $table) {
            $table->dropIndex(['phone_number']);
            $table->dropIndex(['email']);
            $table->dropColumn([
                'full_name',
                'phone_number',
                'email',
                'cnic',
                'income_source',
                'monthly_income',
                'current_bank',
                'has_credit_cards_or_loans',
                'processing_time',
            ]);
        });
    }
};
