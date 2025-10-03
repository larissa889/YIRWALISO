@extends('layouts.app')

@section('title', 'Frequently Asked Questions | Yirwaliso')

@section('content')
  <section class="section">
    <div class="container">
      <h1 class="display" style="margin-bottom:1rem">FAQs</h1>
      <div class="card" style="overflow:hidden">
        <div class="body">
          <details open>
            <summary><strong>When is the best time to visit?</strong></summary>
            <p class="muted">June to October for the Great Migration; year-round wildlife.</p>
          </details>
          <details>
            <summary><strong>How do I get to Yirwaliso?</strong></summary>
            <p class="muted">Private transfers from Nairobi; airstrip options available.</p>
          </details>
          <details>
            <summary><strong>Is Yirwaliso family friendly?</strong></summary>
            <p class="muted">Yes. We welcome families and offer tailored activities.</p>
          </details>
        </div>
      </div>
    </div>
  </section>
@endsection
