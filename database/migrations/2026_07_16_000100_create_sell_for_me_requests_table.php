<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sell_for_me_requests')) {
            return;
        }

        Schema::create('sell_for_me_requests', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->string('full_name', 150);
            $table->string('phone_number', 30);
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('car_model_id')->constrained('car_models')->restrictOnDelete();
            $table->unsignedSmallInteger('model_year');
            $table->string('car_variant', 150);
            $table->string('car_condition', 10);
            $table->string('registration_area', 30);
            $table->string('visit_area', 255);
            $table->date('visit_date');
            $table->time('visit_start_time');
            $table->time('visit_end_time');
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index('status');
            $table->index('visit_date');
            $table->index(['status', 'visit_date'], 'sell_for_me_requests_queue_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sell_for_me_requests');
    }
};
