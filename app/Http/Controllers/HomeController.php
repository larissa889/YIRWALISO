<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Designer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $services = Service::all();
        $designers = Designer::with('user')->get();
        $today = now()->format('Y-m-d');

        return view('home', compact('services', 'designers', 'today'));
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'service_id' => 'required|exists:services,id',
            'designer_id' => 'nullable|exists:designers,id',
        ]);

        $date = $request->input('date');
        $service = Service::findOrFail($request->input('service_id'));
        $designerId = $request->input('designer_id');

        $designers = $designerId
            ? Designer::where('id', $designerId)->get()
            : Designer::all();

        $availableSlots = collect();

        foreach ($designers as $designer) {
            $slots = $this->generateTimeSlots($date, $service, $designer);
            if ($slots->isNotEmpty()) {
                $availableSlots = $availableSlots->merge($slots);
            }
        }

        $availableSlots = $availableSlots->sortBy('start_time')
            ->values()
            ->map(function ($slot) {
                return [
                    'time' => Carbon::parse($slot['start_time'])->format('H:i'),
                    'designer_id' => $slot['designer_id'],
                    'designer_name' => $slot['designer_name'],
                ];
            });

        return response()->json([
            'slots' => $availableSlots
        ]);
    }

    private function generateTimeSlots($date, $service, $designer)
    {
        $slots = collect();
        $start = Carbon::parse($date . ' ' . $designer->start_time);
        $end = Carbon::parse($date . ' ' . $designer->end_time);
        $interval = 30; // minutes

        $current = $start->copy();
        while ($current->addMinutes($interval)->lte($end)) {
            $endTime = (clone $current)->addMinutes($service->duration);

            if ($endTime->lte($end) &&
                $designer->isAvailableOn($date, $current->format('H:i'), $service->duration)) {

                $slots->push([
                    'start_time' => $current->format('H:i'),
                    'end_time' => $endTime->format('H:i'),
                    'designer_id' => $designer->id,
                    'designer_name' => $designer->user->name,
                ]);
            }
        }

        return $slots;
    }
}
