@extends('layouts.app')

@section('title', 'Gallery of Yirwaliso')

@section('content')
  <section class="hero">
    <img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=1920&auto=format&fit=crop" alt="Gallery" />
    <div class="overlay">
      <div class="container">
        <h1 class="display">Gallery</h1>
        <p class="subtitle">A glimpse into Yirwaliso.</p>
      </div>
    </div>
  </section>
  <section class="section">
    <div class="container">
      <div class="cards">
        @for ($i = 0; $i < 8; $i++)
          <div class="card">
            <img src="https://picsum.photos/seed/{{ $i + 10 }}/800/600" alt="Gallery item" />
            <div class="body"><h3>Scene {{ $i + 1 }}</h3><p class="muted">Moments from the savannah.</p></div>
          </div>
        @endfor
      </div>
    </div>
  </section>
@endsection
