<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Insert default roles
        DB::table('roles')->insert([
            ['name' => 'admin', 'description' => 'Administrateur du système', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'staff', 'description' => 'Personnel du centre', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'client', 'description' => 'Client du centre', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
