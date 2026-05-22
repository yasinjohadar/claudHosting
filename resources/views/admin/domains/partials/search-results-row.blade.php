<tr>
    <td class="ps-4">
        <strong dir="ltr">{{ $row['display_name'] }}</strong>
        <span class="badge bg-light text-muted ms-1">اقتراح</span>
    </td>
    <td class="text-center provider-cell provider-cf">
        @if($row['cloudflare'] ?? null)
            @php $cf = $row['cloudflare']; @endphp
            @if($cf['available'] ?? false)
                <span class="badge badge-available d-block mb-1">متاح</span>
                @if($cf['price'] !== null)
                    <span class="price-tag price-available d-block" dir="ltr">${{ number_format($cf['price'], 2) }}</span>
                    @if($cf['renewal'] !== null && $cf['renewal'] != $cf['price'])
                        <small class="text-muted d-block" dir="ltr">تجديد ${{ number_format($cf['renewal'], 2) }}</small>
                    @endif
                @endif
                @if($cf['premium'] ?? false)<small class="text-warning d-block">Premium</small>@endif
            @else
                <span class="badge bg-secondary-transparent text-secondary">غير متاح</span>
                @if(!empty($cf['note']))<small class="text-muted d-block">{{ $cf['note'] }}</small>@endif
            @endif
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="text-center provider-cell provider-namecom">
        @if($row['namecom'] ?? null)
            @php $nc = $row['namecom']; @endphp
            @if($nc['available'] ?? false)
                <span class="badge badge-available d-block mb-1">متاح</span>
                @if($nc['price'] !== null)
                    <span class="price-tag price-available d-block" dir="ltr">${{ number_format($nc['price'], 2) }}</span>
                    @if($nc['renewal'] !== null && $nc['renewal'] != $nc['price'])
                        <small class="text-muted d-block" dir="ltr">تجديد ${{ number_format($nc['renewal'], 2) }}</small>
                    @endif
                @endif
                @if($nc['premium'] ?? false)<small class="text-warning d-block">Premium</small>@endif
            @else
                <span class="badge bg-secondary-transparent text-secondary">غير متاح</span>
            @endif
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="pe-4 small text-muted">
        @if($row['any_available'] ?? false)
            متاح لدى {{ ($row['cloudflare']['available'] ?? false) && ($row['namecom']['available'] ?? false) ? 'الاثنين' : (($row['cloudflare']['available'] ?? false) ? 'Cloudflare' : 'name.com') }}
        @else
            غير متاح في المصدرين المعروضين
        @endif
    </td>
</tr>
