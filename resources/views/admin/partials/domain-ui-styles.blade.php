{{-- Central admin hub UI — domains, customers, list pages. @include on any page needing this design. --}}
<link rel="stylesheet" href="{{ asset('assets/css/admin-domain-ui.css') }}?v={{ @filemtime(public_path('assets/css/admin-domain-ui.css')) ?: '1' }}">
