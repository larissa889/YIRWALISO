<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Confirmation de votre réservation')
                    ->line('Votre réservation a été confirmée.')
                    ->line('Détails de la réservation :')
                    ->line('Service : ' . $this->booking->service->name)
                    ->line('Date : ' . $this->booking->formatted_date)
                    ->line('Heure : ' . $this->booking->formatted_time)
                    ->line('Coiffeur : ' . $this->booking->designer->user->name)
                    ->action('Voir ma réservation', route('bookings.show', $this->booking))
                    ->line('Merci d\'utiliser notre service !');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Votre réservation pour le ' . $this->booking->formatted_date . ' à ' .
                        $this->booking->formatted_time . ' a été confirmée.',
            'booking_id' => $this->booking->id,
            'type' => 'booking_confirmed',
        ];
    }
}
