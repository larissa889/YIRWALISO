<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBooking extends Notification implements ShouldQueue
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
                    ->subject('Nouvelle réservation')
                    ->line('Vous avez une nouvelle réservation.')
                    ->line('Détails de la réservation :')
                    ->line('Client : ' . $this->booking->user->name)
                    ->line('Service : ' . $this->booking->service->name)
                    ->line('Date : ' . $this->booking->formatted_date)
                    ->line('Heure : ' . $this->booking->formatted_time)
                    ->action('Voir la réservation', route('admin.bookings.show', $this->booking))
                    ->line('Merci d\'utiliser notre service !');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'Nouvelle réservation de ' . $this->booking->user->name .
                        ' pour le ' . $this->booking->formatted_date . ' à ' .
                        $this->booking->formatted_time,
            'booking_id' => $this->booking->id,
            'type' => 'new_booking',
        ];
    }
}
