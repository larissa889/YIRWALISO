<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            
            // Foreign keys
            $table->foreign('permission_id')
                  ->references('id')
                  ->on('permissions')
                  ->onDelete('cascade');
                  
            $table->foreign('role_id')
                  ->references('id')
                  ->on('roles')
                  ->onDelete('cascade');
            
            // Composite primary key
            $table->primary(['permission_id', 'role_id']);
            
            // Timestamps
            $table->timestamps();
        });
        
        // Assign all permissions to admin role
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $permissions = DB::table('permissions')->pluck('id')->toArray();
            $rolePermissions = array_map(function($permissionId) use ($adminRole) {
                return [
                    'permission_id' => $permissionId,
                    'role_id' => $adminRole->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $permissions);
            
            DB::table('permission_role')->insert($rolePermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
