@extends('layouts.app')

@section('title', 'Masai Mara Kenya Safaris Experiences | Yirwaliso')

@section('content')
  <section class="hero">
    <img src="https://images.unsplash.com/photo-1558981403-c5f9899a28c2?q=80&w=1920&auto=format&fit=crop" alt="Experiences" />
    <div class="overlay">
      <div class="container">
        <h1 class="display">Experiences</h1>
        <p class="subtitle">Choose your adventure across the plains.</p>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="cards">
        <a href="{{ route('experiences.cultural') }}" class="card">
          <img src="https://images.unsplash.com/photo-1547413779-8e25da1d5148?q=80&w=1200&auto=format&fit=crop" alt="Cultural" />
          <div class="body"><h3>Cultural Encounters</h3><p class="muted">Heritage and hospitality.</p></div>
        </a>
        <a href="{{ route('experiences.walks') }}" class="card">
          <img src="https://images.unsplash.com/photo-1525268771113-32d9e9021a97?q=80&w=1200&auto=format&fit=crop" alt="Walks" />
          <div class="body"><h3>Guided Bush Walks</h3><p class="muted">On foot with expert rangers.</p></div>
        </a>
        <a href="{{ route('experiences.balloon') }}" class="card">
          <img src="https://images.unsplash.com/photo-1518998053901-5348d3961a04?q=80&w=1200&auto=format&fit=crop" alt="Balloon" />
          <div class="body"><h3>Hot Air Balloon Safari</h3><p class="muted">A sunrise to remember.</p></div>
        </a>
        <a href="{{ route('experiences.drives') }}" class="card">
          <img src="https://images.unsplash.com/photo-1570900808796-6a6239532e29?q=80&w=1200&auto=format&fit=crop" alt="Drives" />
          <div class="body"><h3>Panoramic Game Drives</h3><p class="muted">Close encounters responsibly.</p></div>
        </a>
        <a href="{{ route('experiences.wellbeing') }}" class="card">
          <img src="https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?q=80&w=1200&auto=format&fit=crop" alt="Wellbeing" />
          <div class="body"><h3>Wellbeing</h3><p class="muted">Restore mind and body.</p></div>
        </a>
      </div>
    </div>
  </section>
@endsection
