@php
    if (! function_exists('clientMenuIsActive')) {
        function clientMenuIsActive($active): bool
        {
            if ($active === null) {
                return false;
            }
            foreach ((array) $active as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        }
    }

    $menuItems = config('client-menu', []);
@endphp

@foreach($menuItems as $item)
    @if($item['type'] === 'link')
        @php
            $href = route($item['route']).($item['url_hash'] ?? '');
            $isActive = clientMenuIsActive($item['active'] ?? null);
        @endphp
        <li class="slide">
            <a href="{{ $href }}" class="side-menu__item {{ $isActive ? 'active' : '' }}">
                @include('admin.layouts.partials.side-menu-icon', ['color' => $item['color'], 'icon' => $item['icon']])
                <span class="side-menu__label">{{ $item['label'] }}</span>
            </a>
        </li>
    @elseif($item['type'] === 'external')
        <li class="slide">
            <a href="{{ url($item['url']) }}" target="_blank" rel="noopener noreferrer" class="side-menu__item">
                @include('admin.layouts.partials.side-menu-icon', ['color' => $item['color'], 'icon' => $item['icon']])
                <span class="side-menu__label">{{ $item['label'] }}</span>
            </a>
        </li>
    @endif
@endforeach
