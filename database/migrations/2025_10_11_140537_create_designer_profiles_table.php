<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesignerProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('designer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('specialty')->default('Designer');
            $table->text('bio')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->dateTime('next_available_date')->nullable();
            
            // Tableau des compétences
            $table->json('skills')->nullable();
            
            // Informations de contact
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            
            // Horaires de travail
            $table->json('working_hours')->nullable();
            
            // Jours de congé
            $table->json('unavailable_dates')->nullable();
            
            // Statistiques
            $table->integer('completed_projects')->default(0);
            $table->decimal('average_rating', 3, 1)->default(0);
            $table->integer('total_reviews')->default(0);
            
            // Métadonnées
            $table->string('avatar')->nullable();
            $table->string('cover_photo')->nullable();
            
            // Statut du profil
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            // SEO
            $table->string('slug')->unique()->nullable();
            $table->text('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('is_available');
            $table->index('status');
            $table->index('average_rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('designer_profiles', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('designer_profiles');
    }
}
