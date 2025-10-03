<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', config('app.name', 'Yirwaliso'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
      :root { --brand:#8b5e34; --brand-dark:#5f4225; --bg:#0f0f0d; --fg:#f5f3ef; --muted:#b8b4ad; }
      body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, 'Helvetica Neue', Arial, 'Apple Color Emoji', 'Segoe UI Emoji'; background: var(--bg); color: var(--fg); }
      .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
      .site-header { position: sticky; top:0; z-index:40; background: rgba(15,15,13,0.8); backdrop-filter: blur(8px); border-bottom: 1px solid #2a2a27; }
      .nav { display:flex; align-items:center; justify-content:space-between; height:64px; }
      .brand { font-family: 'Playfair Display', serif; font-weight:700; letter-spacing: .5px; color: var(--fg); }
      .menu { display:flex; gap:1.25rem; align-items:center; }
      .menu a { color: var(--fg); opacity:.9; text-decoration:none; font-weight:500; }
      .menu a:hover { color:#fff; opacity:1; }
      .btn-primary { background: var(--brand); color:#fff; padding:.6rem 1rem; border-radius:.25rem; font-weight:600; border:1px solid #0000; }
      .btn-primary:hover { background: var(--brand-dark); }
      footer { border-top:1px solid #2a2a27; color: var(--muted); padding:3rem 0; }
      .hero { position:relative; display:grid; place-items:center; min-height:72vh; overflow:hidden; }
      .hero img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; filter: brightness(.6) contrast(1.05); }
      .hero .overlay { position:relative; z-index:1; text-align:center; padding: 3rem 1rem; }
      h1.display { font-family:'Playfair Display', serif; font-size: clamp(2rem, 6vw, 4rem); line-height:1.05; margin-bottom: .75rem; }
      .subtitle { color: var(--muted); font-size: clamp(.95rem, 2vw, 1.125rem); }
      .section { padding: 3rem 0; }
      .cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
      .card { background:#141411; border:1px solid #262621; border-radius:.5rem; overflow:hidden; }
      .card img { width:100%; height:180px; object-fit:cover; }
      .card .body { padding:1rem; }
      .card h3 { font-family:'Playfair Display', serif; font-size:1.2rem; margin-bottom:.35rem; }
      .muted { color: var(--muted); }
    </style>
  </head>
  <body>
    <header class="site-header">
      <div class="container nav">
        <a href="{{ route('home') }}" class="brand">YIRWALISO</a>
        <nav class="menu">
          <a href="{{ route('experiences.index') }}">Experiences</a>
          <a href="{{ route('dining') }}">Dining</a>
          <a href="{{ route('gallery') }}">Gallery</a>
          <a href="{{ route('faq') }}">FAQs</a>
          <a href="{{ route('about') }}">Our Story</a>
          <a href="{{ route('contact') }}">Contact</a>
          <a href="{{ route('booking') }}" class="btn-primary">Book now</a>
        </nav>
      </div>
    </header>

    <main>
      @yield('content')
    </main>

    <footer>
      <div class="container">
        <div style="display:flex;flex-wrap:wrap;gap:2rem;justify-content:space-between;align-items:center">
          <div>
            <div class="brand">YIRWALISO</div>
            <div class="muted" style="margin-top:.5rem">© {{ date('Y') }} Yirwaliso. All rights reserved.</div>
          </div>
          <nav class="menu" style="gap:1rem">
            <a href="{{ route('privacy') }}">Privacy</a>
            <a href="{{ route('contact') }}">Contact</a>
            <a href="{{ route('booking') }}">Book now</a>
          </nav>
        </div>
      </div>
    </footer>
  </body>
</html>
