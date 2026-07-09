<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                @if($activeTab === 'missing')
                    <th style="width:40px">
                        <input type="checkbox" class="form-check-input" id="evo-compare-select-all" title="تحديد الكل">
                    </th>
                @endif
                @if($activeTab === 'wa_only')
                    <th>#</th>
                    <th>رقم الهاتف</th>
                    <th>JID</th>
                    <th>الدور</th>
                    <th class="text-end">إجراءات</th>
                @elseif($activeTab === 'no_phone')
                    <th>الاسم</th>
                    <th>البريد</th>
                    <th>الهاتف (خام)</th>
                    <th>المجموعات</th>
                    <th class="text-end">الملف</th>
                @else
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>البريد</th>
                    <th>مجموعات المنصة</th>
                    <th>الكورسات</th>
                    @if($activeTab === 'missing')
                        <th class="text-end">إجراءات</th>
                    @endif
                @endif
            </tr>
        </thead>
        <tbody>
        @forelse($rows as $index => $row)
            @if($activeTab === 'wa_only')
                <tr>
                    <td class="text-muted">{{ $index + 1 }}</td>
                    <td class="fw-semibold">{{ $row['phone'] ?: '—' }}</td>
                    <td><code class="small">{{ $row['phone_jid'] ?? '—' }}</code></td>
                    <td>
                        @if($row['is_admin'] ?? false)
                            <span class="badge bg-warning-transparent text-warning">مشرف</span>
                        @else
                            <span class="badge bg-light text-dark border">عضو</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($row['phone'])
                            <a href="{{ route('admin.evolution-api.send.text', ['to' => $row['phone']]) }}"
                               class="btn btn-sm btn-outline-success"><i class="ri-send-plane-line"></i></a>
                        @endif
                    </td>
                </tr>
            @elseif($activeTab === 'no_phone')
                <tr>
                    <td class="fw-semibold">{{ $row['name'] }}</td>
                    <td>{{ $row['email'] ?? '—' }}</td>
                    <td><span class="text-danger">{{ $row['phone_display'] ?? '—' }}</span></td>
                    <td><small>{{ implode('، ', $row['groups'] ?? []) ?: '—' }}</small></td>
                    <td class="text-end">
                        <a href="{{ route('users.student-details', $row['id']) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="ri-user-line"></i>
                        </a>
                    </td>
                </tr>
            @else
                <tr>
                    @if($activeTab === 'missing')
                        <td>
                            <input type="checkbox" class="form-check-input evo-compare-check" value="{{ $row['id'] }}">
                        </td>
                    @endif
                    <td class="fw-semibold">{{ $row['name'] }}</td>
                    <td>
                        <span>{{ $row['phone_display'] ?? $row['phone'] }}</span>
                        @if($row['phone_digits'] ?? null)
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 evo-copy-btn" data-copy="{{ $row['phone_digits'] }}">
                                <i class="ri-file-copy-line"></i>
                            </button>
                        @endif
                    </td>
                    <td><small>{{ $row['email'] ?? '—' }}</small></td>
                    <td><small>{{ implode('، ', $row['groups'] ?? []) ?: '—' }}</small></td>
                    <td><small>{{ implode('، ', $row['courses'] ?? []) ?: '—' }}</small></td>
                    @if($activeTab === 'missing')
                        <td class="text-end">
                            @if($row['phone_digits'] ?? null)
                                <button type="button" class="btn btn-sm btn-success"
                                        data-bs-toggle="modal" data-bs-target="#evoMemberMessageModal"
                                        data-to="{{ $row['phone_digits'] }}">
                                    <i class="ri-send-plane-line me-1"></i> مراسلة
                                </button>
                            @endif
                            <a href="{{ route('users.student-details', $row['id']) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="ri-user-line"></i>
                            </a>
                        </td>
                    @endif
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="8" class="text-center py-5 text-muted">لا توجد سجلات في هذا التبويب</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@include('admin.pages.evolution-api.groups.partials.member-message-modal')

<script>
document.querySelectorAll('.evo-copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(btn.dataset.copy || '');
    });
});
</script>
