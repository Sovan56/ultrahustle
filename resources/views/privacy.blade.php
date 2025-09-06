@include('common.header')
@section('title', $title ?? 'Privacy Policy')

<div class="main-content">
  <section class="section">
    <div class="section-body container my-3">
      <h4 style="color: #Ceff1b;" class="my-2">{{ $title ?? 'Privacy Policy' }}</h4>
      <div>
        {!! $html ?? '<p>No privacy policy published yet.</p>' !!}
      </div>
    </div>
  </section>
</div>

@include('common.footer')