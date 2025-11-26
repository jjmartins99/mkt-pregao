<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone');
            $table->string('email');
            $table->text('address');
            $table->string('city');
            $table->string('country')->default('Angola');
            $table->string('postal_code');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
};