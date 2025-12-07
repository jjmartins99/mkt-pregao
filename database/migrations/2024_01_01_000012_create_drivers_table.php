<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('set null');
            $table->string('driving_license');
            $table->string('license_photo');
            $table->enum('status', ['pending', 'active', 'suspended', 'inactive'])->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_ratings')->default(0);
            $table->integer('total_deliveries')->default(0);
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela para veículos dos motoristas
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->string('make');
            $table->string('model');
            $table->string('year');
            $table->string('color');
            $table->string('plate_number')->unique();
            $table->enum('type', ['car', 'motorcycle', 'bicycle', 'truck', 'van']);
            $table->decimal('capacity_kg', 8, 2)->nullable();
            $table->decimal('capacity_volume', 8, 2)->nullable();
            $table->string('insurance_number')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('vehicle_photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        //VAI para outra tabela de migração
        /*
        // Tabela para tracking de entregas
        Schema::create('delivery_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 10, 8)->nullable();
            $table->string('location_address')->nullable();
            $table->enum('status', ['assigned', 'picked_up', 'on_route', 'delivered', 'failed']);
            $table->text('notes')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
        });
        */
    }

    public function down()
    {
        //Schema::dropIfExists('delivery_tracking');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
    }
};