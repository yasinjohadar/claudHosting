@if($client ?? null)
    <a href="{{ route('admin.customers.show', $client->id) }}" class="cf-project-client__link text-primary text-decoration-none">{{ $client->name }}</a>
@else
    <span class="cf-project-client__empty">بدون عميل</span>
@endif

