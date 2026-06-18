<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('service_packages')) {
            return;
        }

        Schema::create('service_packages', static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('features')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('type', 50)->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
