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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->string('group')->default('general');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default permissions
        $permissions = [
            // User Management
            ['name' => 'view_users', 'display_name' => 'Voir les utilisateurs', 'group' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Créer des utilisateurs', 'group' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Modifier les utilisateurs', 'group' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Supprimer des utilisateurs', 'group' => 'users'],
            
            // Role Management
            ['name' => 'view_roles', 'display_name' => 'Voir les rôles', 'group' => 'roles'],
            ['name' => 'create_roles', 'display_name' => 'Créer des rôles', 'group' => 'roles'],
            ['name' => 'edit_roles', 'display_name' => 'Modifier les rôles', 'group' => 'roles'],
            ['name' => 'delete_roles', 'display_name' => 'Supprimer des rôles', 'group' => 'roles'],
            
            // Accommodation Management
            ['name' => 'view_accommodations', 'display_name' => 'Voir les hébergements', 'group' => 'accommodations'],
            ['name' => 'create_accommodations', 'display_name' => 'Créer des hébergements', 'group' => 'accommodations'],
            ['name' => 'edit_accommodations', 'display_name' => 'Modifier les hébergements', 'group' => 'accommodations'],
            ['name' => 'delete_accommodations', 'display_name' => 'Supprimer des hébergements', 'group' => 'accommodations'],
            
            // Booking Management
            ['name' => 'view_bookings', 'display_name' => 'Voir les réservations', 'group' => 'bookings'],
            ['name' => 'create_bookings', 'display_name' => 'Créer des réservations', 'group' => 'bookings'],
            ['name' => 'edit_bookings', 'display_name' => 'Modifier les réservations', 'group' => 'bookings'],
            ['name' => 'delete_bookings', 'display_name' => 'Annuler des réservations', 'group' => 'bookings'],
            
            // Activity Management
            ['name' => 'view_activities', 'display_name' => 'Voir les activités', 'group' => 'activities'],
            ['name' => 'create_activities', 'display_name' => 'Créer des activités', 'group' => 'activities'],
            ['name' => 'edit_activities', 'display_name' => 'Modifier les activités', 'group' => 'activities'],
            ['name' => 'delete_activities', 'display_name' => 'Supprimer des activités', 'group' => 'activities'],
            
            // Gallery Management
            ['name' => 'view_galleries', 'display_name' => 'Voir la galerie', 'group' => 'galleries'],
            ['name' => 'upload_media', 'display_name' => 'Téléverser des médias', 'group' => 'galleries'],
            ['name' => 'delete_media', 'display_name' => 'Supprimer des médias', 'group' => 'galleries'],
            
            // System Settings
            ['name' => 'manage_settings', 'display_name' => 'Gérer les paramètres', 'group' => 'settings', 'is_system' => true],
            ['name' => 'view_dashboard', 'display_name' => 'Voir le tableau de bord', 'group' => 'system', 'is_system' => true],
        ];

        // Add timestamps to each permission
        $now = now();
        $permissions = array_map(function($permission) use ($now) {
            return array_merge($permission, [
                'created_at' => $now,
                'updated_at' => $now,
                'is_system' => $permission['is_system'] ?? false
            ]);
        }, $permissions);

        DB::table('permissions')->insert($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
