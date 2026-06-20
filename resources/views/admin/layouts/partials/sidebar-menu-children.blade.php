@foreach($children as $child)
    @if(($child['type'] ?? '') === 'subgroup')
        @php $subOpen = adminMenuGroupActive((array) ($child['active'] ?? [])); @endphp
        <li class="slide has-sub {{ $subOpen ? 'open' : '' }}">
            <a href="javascript:void(0);" class="side-menu__item side-menu__item--subgroup">
                @if(!empty($child['icon']))
                    @include('admin.layouts.partials.side-menu-icon', [
                        'color' => $child['color'] ?? 'secondary',
                        'icon' => $child['icon'],
                        'size' => 'sm',
                    ])
                @endif
                <span class="side-menu__label">{{ $child['label'] }}</span>
                <i class="fe fe-chevron-right side-menu__angle"></i>
            </a>
            <ul class="slide-menu child{{ $depth + 1 }}">
                @include('admin.layouts.partials.sidebar-menu-children', [
                    'children' => $child['children'] ?? [],
                    'depth' => $depth + 1,
                ])
            </ul>
        </li>
    @elseif(($child['type'] ?? '') === 'category')
        <li class="slide__category">{{ $child['label'] }}</li>
    @elseif(($child['type'] ?? '') === 'link')
        @php
            $childActive = adminMenuIsActive(
                $child['active'] ?? null,
                isset($child['active_exclude']) ? (array) $child['active_exclude'] : null
            );
        @endphp
        <li class="slide">
            <a href="{{ route($child['route']) }}" class="side-menu__item {{ $childActive ? 'active' : '' }}">
                @include('admin.layouts.partials.side-menu-icon', [
                    'color' => $child['color'] ?? 'secondary',
                    'icon' => $child['icon'] ?? 'fe fe-circle',
                    'size' => 'sm',
                ])
                <span class="side-menu__label">{{ $child['label'] }}</span>
            </a>
        </li>
    @endif
@endforeach
