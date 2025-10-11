<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Designer extends Model
{
    protected $fillable = [
        'user_id',
        'specialty',
        'bio',
        'photo',
        'working_days',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'working_days' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isAvailableOn($date, $time, $duration)
    {
        $dateTime = \Carbon\Carbon::parse("$date $time");
        $endDateTime = (clone $dateTime)->addMinutes($duration);

        // Vérifier si c'est un jour de travail
        $dayOfWeek = $dateTime->dayOfWeek;
        if (!in_array($dayOfWeek, $this->working_days ?? [])) {
            return false;
        }

        // Vérifier les heures de travail
        $startWork = \Carbon\Carbon::parse($this->start_time);
        $endWork = \Carbon\Carbon::parse($this->end_time);

        if ($dateTime->format('H:i') < $startWork->format('H:i') ||
            $endDateTime->format('H:i') > $endWork->format('H:i')) {
            return false;
        }

        // Vérifier les réservations existantes
        $conflictingBooking = $this->bookings()
            ->where(function($query) use ($dateTime, $endDateTime) {
                $query->whereBetween('start_time', [$dateTime, $endDateTime->copy()->subMinute()])
                      ->orWhereBetween('end_time', [$dateTime->copy()->addMinute(), $endDateTime])
                      ->orWhere(function($q) use ($dateTime, $endDateTime) {
                          $q->where('start_time', '<=', $dateTime)
                            ->where('end_time', '>=', $endDateTime);
                      });
            })
            ->whereIn('status', ['confirmed', 'pending'])
            ->exists();

        return !$conflictingBooking;
    }
}
