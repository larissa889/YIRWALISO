@extends('layouts.app')

@section('title', 'Contact Us - Yirwaliso')

@section('content')
  <section class="section">
    <div class="container">
      <h1 class="display" style="margin-bottom:1rem">Contact</h1>
      <div class="card">
        <div class="body">
          <form method="post" action="#">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem">
              <div>
                <label>Name</label>
                <input name="name" type="text" style="width:100%;padding:.7rem;border:1px solid #2a2a27;background:#111; color:#eee;border-radius:.25rem"/>
              </div>
              <div>
                <label>Email</label>
                <input name="email" type="email" style="width:100%;padding:.7rem;border:1px solid #2a2a27;background:#111; color:#eee;border-radius:.25rem"/>
              </div>
            </div>
            <div style="margin-bottom:1rem">
              <label>Message</label>
              <textarea name="message" rows="5" style="width:100%;padding:.7rem;border:1px solid #2a2a27;background:#111; color:#eee;border-radius:.25rem"></textarea>
            </div>
            <button class="btn-primary" type="submit">Send</button>
          </form>
        </div>
      </div>
    </div>
  </section>
@endsection
