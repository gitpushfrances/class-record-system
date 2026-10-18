{{-- ESSU brand: favicon and Tailwind indigo → ESSU green (the compiled CSS keeps indigo classes; this maps them without a rebuild). --}}
<link rel="icon" type="image/png" href="{{ asset('images/rog/essu-seal.png') }}">
<style>
    :root { --essu-green: #0a5b05; --essu-green-dark: #0b3d0b; --essu-gold: #d4af37; --essu-gold-bright: #f3eb21; --essu-cream: #fbf3d0; }
    mark.search-hit { padding:0 1px; color:inherit; background:#fde68a; border-radius:2px; }
    .bg-indigo-600, .bg-indigo-700 { background-color: #0a5b05 !important; }
    .hover\:bg-indigo-700:hover, .hover\:bg-indigo-800:hover { background-color: #084a04 !important; }
    .bg-indigo-50, .hover\:bg-indigo-50:hover { background-color: #eef7ec !important; }
    .bg-indigo-100 { background-color: #dcefd8 !important; }
    .text-indigo-500, .text-indigo-600, .text-indigo-700, .text-indigo-800,
    .hover\:text-indigo-600:hover, .hover\:text-indigo-700:hover { color: #0a5b05 !important; }
    .text-indigo-200 { color: #cfe8c9 !important; }
    .border-indigo-300, .border-indigo-400, .border-indigo-500, .border-indigo-600, .border-indigo-700 { border-color: #3f8f3a !important; }
    .focus\:ring-indigo-400:focus, .focus\:ring-indigo-500:focus { --tw-ring-color: rgba(10, 91, 5, .45) !important; }
</style>
{{-- SweetAlert2 dialogs use the ESSU green unless a dialog sets its own colors. --}}
<script>if (window.Swal) { window.Swal = window.Swal.mixin({ confirmButtonColor: '#0a5b05', cancelButtonColor: '#6b7280' }); }</script>
