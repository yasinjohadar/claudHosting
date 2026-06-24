@php
    $uuid = $uuid ?? '';
    $name = $name ?? $uuid;
    $total = (int) ($total ?? 0);
    $canDelete = (bool) ($canDelete ?? false);
    $rowIndex = $rowIndex ?? 0;
    $showUrl = $uuid !== '' ? route('admin.coolify.projects.show', $uuid) : '#';
    $resourcesUrl = $uuid !== '' ? route('admin.coolify.projects.resources', $uuid) : '#';
@endphp

<div class="cf-project-actions">
    @if ($uuid !== '')
        <a href="{{ $showUrl }}" class="cf-project-actions__btn cf-project-actions__btn--view" title="عرض المشروع">
            <i class="fe fe-eye"></i>
            <span class="d-none d-xl-inline">عرض</span>
        </a>

        <div class="dropdown">
            <button type="button"
                class="cf-project-actions__btn cf-project-actions__btn--manage dropdown-toggle"
                data-bs-toggle="dropdown"
                data-bs-auto-close="outside"
                aria-expanded="false"
                title="قائمة الإدارة">
                <i class="fe fe-more-vertical"></i>
                <span class="d-none d-lg-inline">إدارة</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end cf-project-actions-menu">
                <h6 class="dropdown-header">إدارة المشروع</h6>
                <a class="dropdown-item" href="{{ $showUrl }}">
                    <i class="fe fe-layers text-primary"></i>
                    تفاصيل المشروع
                </a>
                @if ($total > 0)
                    <a class="dropdown-item" href="{{ $resourcesUrl }}">
                        <i class="fe fe-grid text-info"></i>
                        الموارد ({{ $total }})
                    </a>
                @endif
                <div class="cf-project-actions-menu__panel">
                    @include('admin.partials.asset-client-assign-inline', [
                        'layout' => 'panel',
                        'assignUrl' => route('admin.coolify.projects.assign-client', $uuid),
                        'payloadKey' => 'project_name',
                        'payloadValue' => $name,
                        'clientUsers' => $clientUsers ?? [],
                        'selectedUserId' => $selectedUserId ?? null,
                        'cellSelector' => '.cf-project-row-' . $rowIndex . ' .cf-project-client-cell',
                        'saveButtonLabel' => 'حفظ الربط',
                    ])
                </div>
                @if ($canDelete)
                    <div class="dropdown-divider my-1"></div>
                    <form action="{{ route('admin.coolify.projects.destroy', $uuid) }}" method="POST"
                        onsubmit="return confirm(@json('حذف المشروع «'.$name.'» من Coolify؟ المشروع فارغ ولا يحتوي موارد.'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item dropdown-item--danger border-0 bg-transparent w-100 text-start">
                            <i class="fe fe-trash-2"></i>
                            حذف المشروع
                        </button>
                    </form>
                @elseif ($total > 0)
                    <div class="dropdown-divider my-1"></div>
                    <span class="dropdown-item text-muted small disabled" title="احذف الموارد أولاً">
                        <i class="fe fe-trash-2"></i>
                        الحذف غير متاح (يوجد موارد)
                    </span>
                @endif
            </div>
        </div>
    @endif
</div>
