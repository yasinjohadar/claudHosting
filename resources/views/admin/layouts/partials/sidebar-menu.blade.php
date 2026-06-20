@php
    if (! function_exists('adminMenuIsActive')) {
        function adminMenuIsActive($active, ?array $exclude = null): bool
        {
            if ($active === null) {
                return false;
            }
            if ($exclude) {
                foreach ((array) $exclude as $pattern) {
                    if (request()->routeIs($pattern)) {
                        return false;
                    }
                }
            }
            foreach ((array) $active as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        }
    }

    if (! function_exists('adminMenuGroupActive')) {
        function adminMenuGroupActive(array $patterns): bool
        {
            foreach ($patterns as $pattern) {
                if (request()->routeIs($pattern)) {
                    return true;
                }
            }

            return false;
        }
    }

    $menuItems = config('admin-menu', []);
@endphp

@foreach($menuItems as $item)
    @if($item['type'] === 'link')
        <li class="slide">
            <a href="{{ route($item['route']) }}" class="side-menu__item {{ adminMenuIsActive($item['active'] ?? null) ? 'active' : '' }}">
                @include('admin.layouts.partials.side-menu-icon', ['color' => $item['color'], 'icon' => $item['icon']])
                <span class="side-menu__label">{{ $item['label'] }}</span>
            </a>
        </li>
    @elseif($item['type'] === 'external')
        <li class="slide">
            <a href="{{ url($item['url']) }}" target="_blank" rel="noopener noreferrer" class="side-menu__item" title="{{ $item['label'] }}">
                @include('admin.layouts.partials.side-menu-icon', ['color' => $item['color'], 'icon' => $item['icon']])
                <span class="side-menu__label">{{ $item['label'] }}</span>
            </a>
        </li>
    @elseif($item['type'] === 'group')
        @php $groupOpen = adminMenuGroupActive((array) ($item['active'] ?? [])); @endphp
        <li class="slide has-sub {{ $groupOpen ? 'open' : '' }}">
            <a href="javascript:void(0);" class="side-menu__item">
                @include('admin.layouts.partials.side-menu-icon', ['color' => $item['color'], 'icon' => $item['icon']])
                <span class="side-menu__label">{{ $item['label'] }}</span>
                <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child1">
                @include('admin.layouts.partials.sidebar-menu-children', [
                    'children' => $item['children'] ?? [],
                    'depth' => 1,
                ])
            </ul>
        </li>
    @endif
@endforeach
