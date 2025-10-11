<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortfolioItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_profile_id')->constrained('designer_profiles')->onDelete('cascade');
            
            // Informations de base
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('excerpt')->nullable();
            
            // Détails du projet
            $table->date('project_date');
            $table->date('completion_date')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_website')->nullable();
            $table->string('project_url')->nullable();
            $table->string('repository_url')->nullable();
            
            // Technologies utilisées
            $table->json('technologies')->nullable();
            
            // Catégorisation
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            
            // Métadonnées
            $table->boolean('is_featured')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            
            // Statut de publication
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            
            // Images
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            
            // Informations supplémentaires
            $table->text('challenge')->nullable();
            $table->text('solution')->nullable();
            $table->text('testimonial')->nullable();
            $table->string('testimonial_author')->nullable();
            $table->string('testimonial_role')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['designer_profile_id', 'status']);
            $table->index(['category', 'is_featured']);
            $table->index('project_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('portfolio_items', function (Blueprint $table) {
            $table->dropForeign(['designer_profile_id']);
        });
        
        Schema::dropIfExists('portfolio_items');
    }
}
