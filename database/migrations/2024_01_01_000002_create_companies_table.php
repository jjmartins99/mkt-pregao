<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nif')->unique();
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->string('city');
            $table->string('country')->default('Angola');
            $table->string('postal_code');
            $table->string('logo')->nullable();
            $table->enum('type', ['individual', 'collective']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela pivô para users e companies
        Schema::create('company_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'manager', 'employee'])->default('employee');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['company_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_users');
        Schema::dropIfExists('companies');
    }
};