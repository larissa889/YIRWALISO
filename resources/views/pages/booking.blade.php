@extends('layouts.app')

@section('title', 'Online Booking for Stays | Yirwaliso')

@section('content')
  <section class="section">
    <div class="container">
      <h1 class="display" style="margin-bottom:1rem">Book your stay</h1>
      <div class="card">
        <div class="body">
          <p class="muted">Booking engine coming soon. For now, contact us to reserve.</p>
          <a href="{{ route('contact') }}" class="btn-primary" style="margin-top:1rem;display:inline-block">Contact us</a>
        </div>
      </div>
    </div>
  </section>
@endsection
