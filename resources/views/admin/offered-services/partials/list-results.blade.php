<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 48px;">#</th>
                <th class="domain-list-table__domain">الاسم</th>
                <th>النوع</th>
                <th>السعر</th>
                <th>مدة التنفيذ</th>
                <th>الحالة</th>
                <th class="domain-list-table__action">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
            <tr>
                <td class="text-muted">{{ $service->id }}</td>
                <td class="domain-list-table__domain">
                    <a href="{{ route('admin.offered-services.show', $service) }}" class="domain-name-link">
                        <span class="domain-name-link__icon"><i class="fe fe-layers"></i></span>
                        <span class="domain-name-link__text">{{ $service->name }}</span>
                    </a>
                    @if($service->description)
                    <span class="text-muted small d-block mt-1">{{ \Illuminate\Support\Str::limit($service->description, 60) }}</span>
                    @endif
                </td>
                <td>
                    @if($service->serviceType)
                    <span class="domain-mini-badge domain-mini-badge--yes">{{ $service->serviceType->name }}</span>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td dir="ltr">{{ $service->formatted_price }}</td>
                <td>{{ $service->execution_duration ?? ($service->execution_days ? $service->execution_days.' يوم' : '—') }}</td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $service->is_active ? 'active' : 'expired' }}">
                        {{ $service->is_active ? 'نشط' : 'غير نشط' }}
                    </span>
                </td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        <a href="{{ route('admin.offered-services.show', $service) }}" class="domain-action-btn" title="عرض">
                            <i class="fe fe-eye"></i>
                        </a>
                        <a href="{{ route('admin.offered-services.edit', $service) }}" class="domain-action-btn domain-action-btn--info" title="تعديل">
                            <i class="fe fe-edit-2"></i>
                        </a>
                        <button type="button"
                            class="domain-action-btn domain-action-btn--danger delete-offered-service"
                            title="حذف"
                            data-url="{{ route('admin.offered-services.destroy', $service) }}">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد خدمات — <a href="{{ route('admin.offered-services.create') }}">أضف خدمة جديدة</a></p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($services->hasPages())
<div class="domain-list-footer offered-services-pagination">
    {{ $services->withQueryString()->links() }}
</div>
@endif
