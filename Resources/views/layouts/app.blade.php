@extends('app')
@section('title', __('skyops::skyops.module_name'))

@section('content')
{{-- Fonts (shared with Airline Pulse) --}}
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

{{-- Theme detection — adds .ap-light / .ap-dark to <html> (identical to Airline Pulse) --}}
<script>
(function(){
  function detectTheme(){
    var h=document.documentElement,b=document.body;
    var isDark=h.getAttribute('data-bs-theme')==='dark'
      ||h.getAttribute('data-theme')==='dark'
      ||b.getAttribute('data-bs-theme')==='dark'
      ||b.getAttribute('data-theme')==='dark'
      ||b.classList.contains('dark-mode')||b.classList.contains('dark')
      ||h.classList.contains('dark-mode')||h.classList.contains('dark')
      ||(!h.getAttribute('data-bs-theme')&&!b.getAttribute('data-bs-theme')
         &&!h.getAttribute('data-theme')&&!b.getAttribute('data-theme')
         &&!b.classList.contains('light-mode')&&!b.classList.contains('light')
         &&!h.classList.contains('light-mode')&&!h.classList.contains('light')
         &&window.matchMedia('(prefers-color-scheme:dark)').matches);
    h.classList.toggle('ap-dark',isDark);
    h.classList.toggle('ap-light',!isDark);
  }
  detectTheme();
  new MutationObserver(detectTheme).observe(document.documentElement,{attributes:true,attributeFilter:['data-bs-theme','data-theme','class']});
  new MutationObserver(detectTheme).observe(document.body,{attributes:true,attributeFilter:['data-bs-theme','data-theme','class']});
  window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change',detectTheme);
})();
</script>

@include('skyops::partials._styles')

<div class="so-wrap{{ config('skyops.theme.glass_mode', true) ? ' so-glass' : '' }}">
    {{-- Navigation Tabs --}}
    <nav class="so-nav">
        @if(config('skyops.landing') === 'dashboard')
        <a href="{{ route('skyops.index') }}" class="{{ ($currentPage ?? '') === 'dashboard' ? 'active' : '' }}">
            {{ __('skyops::skyops.dashboard') }}
        </a>
        @endif
        <a href="{{ route('skyops.pireps') }}" class="{{ ($currentPage ?? '') === 'pireps' ? 'active' : '' }}">
            {{ __('skyops::skyops.pirep_list') }}
        </a>
        <a href="{{ route('skyops.fleet') }}" class="{{ ($currentPage ?? '') === 'fleet' ? 'active' : '' }}">
            {{ __('skyops::skyops.fleet') }}
        </a>
        <a href="{{ route('skyops.pilots') }}" class="{{ ($currentPage ?? '') === 'pilots' ? 'active' : '' }}">
            {{ __('skyops::skyops.pilot_stats') }}
        </a>
        <a href="{{ route('skyops.airlines') }}" class="{{ ($currentPage ?? '') === 'airlines' ? 'active' : '' }}">
            {{ __('skyops::skyops.airlines') }}
        </a>
        @if(config('skyops.features.show_finance_link', false))
        <a href="/dynamicfares/finance" class="">
            {{ __('skyops::skyops.finance') }}
        </a>
        @endif
        <a href="{{ route('skyops.departures') }}" class="{{ ($currentPage ?? '') === 'departures' ? 'active' : '' }}">
            {{ __('skyops::skyops.departures') }}
        </a>
        <a href="{{ route('skyops.guide') }}" class="so-nav-guide {{ ($currentPage ?? '') === 'guide' ? 'active' : '' }}" title="{{ __('skyops::skyops.guide_title') }}">
            ?
        </a>
    </nav>

    {{-- Page-specific content --}}
    @yield('skyops-content')

    {{-- Footer --}}
    <div class="so-footer">
        <a href="https://github.com/MANFahrer-GF/phpvms-SkyOps" target="_blank" title="SkyOps on GitHub">SkyOps</a>
        — crafted with <span class="so-heart">♥</span> in Germany by Thomas Kant
    </div>
</div>
@endsection
