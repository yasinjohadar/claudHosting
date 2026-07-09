@php
    $tabs = [
        ['route' => 'admin.evolution-api.settings.index', 'pattern' => 'admin.evolution-api.settings.*', 'label' => 'الإعدادات', 'icon' => 'ri-settings-3-line'],
        ['route' => 'admin.evolution-api.instances.index', 'pattern' => 'admin.evolution-api.instances.*', 'label' => 'Instances', 'icon' => 'ri-smartphone-line'],
        ['route' => 'admin.evolution-api.send.text', 'pattern' => 'admin.evolution-api.send.*', 'label' => 'إرسال', 'icon' => 'ri-send-plane-line'],
        ['route' => 'admin.evolution-api.groups.index', 'pattern' => 'admin.evolution-api.groups.index|admin.evolution-api.groups.show|admin.evolution-api.groups.members', 'label' => 'المجموعات', 'icon' => 'ri-group-line'],
        ['route' => 'admin.evolution-api.groups.compare', 'pattern' => 'admin.evolution-api.groups.compare|admin.evolution-api.groups.compare.*', 'label' => 'مقارنة المجموعات', 'icon' => 'ri-git-merge-line'],
        ['route' => 'admin.evolution-api.contacts.index', 'pattern' => 'admin.evolution-api.contacts.*', 'label' => 'جهات الاتصال', 'icon' => 'ri-contacts-line'],
        ['route' => 'admin.evolution-api.chats.index', 'pattern' => 'admin.evolution-api.chats.*', 'label' => 'المحادثات', 'icon' => 'ri-chat-3-line'],
        ['route' => 'admin.evolution-api.messages.index', 'pattern' => 'admin.evolution-api.messages.*', 'label' => 'سجل الرسائل', 'icon' => 'ri-mail-line'],
        ['route' => 'admin.evolution-api.webhook.index', 'pattern' => 'admin.evolution-api.webhook.*', 'label' => 'Webhook', 'icon' => 'ri-webhook-line'],
    ];
@endphp

<div class="card custom-card border-0 shadow-sm mb-4">
    <div class="card-body py-3 px-3">
        <ul class="nav nav-pills nav-fill flex-wrap gap-1 evo-nav-pills" role="tablist">
            @foreach($tabs as $tab)
                @php $active = request()->routeIs($tab['pattern']); @endphp
                <li class="nav-item" role="presentation">
                    <a href="{{ route($tab['route']) }}"
                       class="nav-link rounded-pill px-3 py-2 {{ $active ? 'active bg-success' : 'text-muted' }}">
                        <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
