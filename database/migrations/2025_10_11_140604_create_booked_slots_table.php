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
        Schema::create('booked_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designer_profile_id')->constrained('designer_profiles')->onDelete('cascade');
            
            // Détails du créneau
            $table->string('title');
            $table->text('description')->nullable();
            
            // Période du créneau
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            
            // Type de réservation (consultation, réunion, etc.)
            $table->string('type')->default('consultation');
            
            // Statut du créneau (disponible, réservé, annulé, etc.)
            $table->enum('status', ['available', 'booked', 'cancelled', 'completed'])->default('available');
            
            // Informations sur la réservation
            $table->foreignId('booked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('booked_name')->nullable();
            $table->string('booked_email')->nullable();
            $table->string('booked_phone')->nullable();
            
            // Détails de la récurrence
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekly, monthly, custom
            $table->dateTime('recurrence_ends_at')->nullable();
            
            // Lien de la réunion (Zoom, Google Meet, etc.)
            $table->string('meeting_link')->nullable();
            
            // Notes internes
            $table->text('internal_notes')->nullable();
            
            // Raison de l'annulation
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Métadonnées
            $table->string('timezone')->default('UTC');
            $table->string('locale', 10)->default('fr');
            
            // Suivi
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('follow_up_sent_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['designer_profile_id', 'start_time', 'end_time']);
            $table->index(['status', 'start_time']);
            $table->index('booked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booked_slots', function (Blueprint $table) {
            $table->dropForeign(['designer_profile_id']);
            $table->dropForeign(['booked_by']);
            $table->dropForeign(['cancelled_by']);
        });
        
        Schema::dropIfExists('booked_slots');
    }
};
