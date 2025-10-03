@extends('layouts.app')

@section('title', 'Masai Mara National Park Accommodation & Lodges | Yirwaliso')

@section('content')
  <section class="hero">
    <img src="https://images.unsplash.com/photo-1603605888340-8bf49b2a938f?q=80&w=1920&auto=format&fit=crop" alt="Savannah" />
    <div class="overlay">
      <div class="container">
        <h1 class="display">Discover Yirwaliso</h1>
        <p class="subtitle">Timeless serenity in the heart of the savannah. Luxury stays, curated experiences, and authentic cuisine.</p>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem">
        <h2 style="font-family:'Playfair Display',serif;font-size:2rem">Experiences</h2>
        <a href="{{ route('experiences.index') }}" class="muted">See all →</a>
      </div>
      <div class="cards">
        <a href="{{ route('experiences.cultural') }}" class="card">
          <img src="https://images.unsplash.com/photo-1578348800833-f47ce1a64d56?q=80&w=1200&auto=format&fit=crop" alt="Cultural" />
          <div class="body">
            <h3>Cultural Encounters</h3>
            <p class="muted">Meet local communities and traditions.</p>
          </div>
        </a>
        <a href="{{ route('experiences.walks') }}" class="card">
          <img src="https://images.unsplash.com/photo-1558980664-10a5ab6aa7ad?q=80&w=1200&auto=format&fit=crop" alt="Bush walks" />
          <div class="body">
            <h3>Guided Bush Walks</h3>
            <p class="muted">Slow adventures with expert guides.</p>
          </div>
        </a>
        <a href="{{ route('experiences.balloon') }}" class="card">
          <img src="https://images.unsplash.com/photo-1511234706561-1e3e5291b9b9?q=80&w=1200&auto=format&fit=crop" alt="Balloon" />
          <div class="body">
            <h3>Hot Air Balloon Safari</h3>
            <p class="muted">Sunrise vistas over the plains.</p>
          </div>
        </a>
        <a href="{{ route('experiences.drives') }}" class="card">
          <img src="https://images.unsplash.com/photo-1552410260-c5f8a4b1523e?q=80&w=1200&auto=format&fit=crop" alt="Game drives" />
          <div class="body">
            <h3>Panoramic Game Drives</h3>
            <p class="muted">Big Five in their natural habitat.</p>
          </div>
        </a>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1rem">
        <h2 style="font-family:'Playfair Display',serif;font-size:2rem">Dining</h2>
        <a href="{{ route('dining') }}" class="muted">Explore menu →</a>
      </div>
      <div class="cards">
        <a href="{{ route('dining') }}" class="card">
          <img src="https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?q=80&w=1200&auto=format&fit=crop" alt="Cuisine" />
          <div class="body">
            <h3>Savor</h3>
            <p class="muted">Authentic cuisine with seasonal flavors.</p>
          </div>
        </a>
        <a href="{{ route('gallery') }}" class="card">
          <img src="https://images.unsplash.com/photo-1514510165229-539b82657b34?q=80&w=1200&auto=format&fit=crop" alt="Gallery" />
          <div class="body">
            <h3>Gallery</h3>
            <p class="muted">Scenes from Yirwaliso and beyond.</p>
          </div>
        </a>
      </div>
    </div>
  </section>
@endsection
