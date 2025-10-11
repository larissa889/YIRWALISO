<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('category')->default('general');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accommodation_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['accommodation_id', 'amenity_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('accommodation_amenity');
        Schema::dropIfExists('amenities');
    }
};
