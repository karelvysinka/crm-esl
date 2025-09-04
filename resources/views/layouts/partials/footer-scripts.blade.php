<!-- App js -->
@vite(['resources/js/app.js'])

<!-- Alpine.js for lightweight interactivity (used by chat widget) -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

@yield('scripts')
@yield('script')
@stack('scripts')