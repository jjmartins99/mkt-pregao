<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address');
            $table->string('city');
            $table->string('country')->default('Angola');
            $table->string('postal_code');
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->decimal('area', 8, 2)->nullable();
            $table->enum('type', ['main', 'secondary', 'transit', 'quarantine'])->default('main');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warehouses');
    }
};