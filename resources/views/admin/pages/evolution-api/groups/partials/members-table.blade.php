@php
    $standalone = $standalone ?? false;
    $groupInfo = $groupInfo ?? ['name' => '—', 'jid' => $groupJid ?? ''];
@endphp

<div class="card custom-card group-show-members-card border-0 shadow-sm {{ $standalone ? '' : 'mb-4' }}">
    <div class="card-header border-0 pb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="card-title mb-0">
            <i class="ri-team-line me-2 text-success"></i>أعضاء المجموعة
            <span class="badge bg-success-transparent text-success ms-1" id="evo-members-count">{{ count($members) }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(!$standalone)
                <a href="{{ route('admin.evolution-api.groups.members', ['jid' => $groupJid]) }}"
                   class="btn btn-sm btn-outline-primary" target="_blank" title="فتح في صفحة مستقلة">
                    <i class="ri-external-link-line me-1"></i> صفحة مستقلة
                </a>
            @else
                <a href="{{ route('admin.evolution-api.groups.show', ['jid' => $groupJid]) }}"
                   class="btn btn-sm btn-outline-secondary">
                    <i class="ri-arrow-right-line me-1"></i> تفاصيل المجموعة
                </a>
            @endif
            <button type="button" class="btn btn-sm btn-outline-success" id="evo-members-refresh">
                <i class="ri-refresh-line me-1"></i> تحديث
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                    <input type="search" class="form-control" id="evo-members-search" placeholder="بحث بالرقم أو المعرف...">
                </div>
            </div>
            <div class="col-md-6">
                <select class="form-select form-select-sm" id="evo-members-role-filter">
                    <option value="">كل الأدوار</option>
                    <option value="admin">مشرفون فقط</option>
                    <option value="member">أعضاء فقط</option>
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="evo-members-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>رقم الهاتف</th>
                        <th>المعرف (JID)</th>
                        <th>الدور</th>
                        <th class="text-end">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $index => $member)
                    <tr data-phone="{{ $member['phone'] }}"
                        data-jid="{{ $member['phone_jid'] }}"
                        data-role="{{ $member['is_admin'] ? 'admin' : 'member' }}">
                        <td class="text-muted">{{ $index + 1 }}</td>
                        <td>
                            <span class="fw-semibold evo-member-phone">{{ $member['phone'] ?: '—' }}</span>
                            @if($member['phone'])
                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 evo-copy-btn"
                                        data-copy="{{ $member['phone'] }}" title="نسخ الرقم">
                                    <i class="ri-file-copy-line"></i>
                                </button>
                            @endif
                        </td>
                        <td><code class="small text-muted">{{ $member['phone_jid'] ?: $member['id'] }}</code></td>
                        <td>
                            @if($member['is_admin'])
                                <span class="badge bg-warning-transparent text-warning">
                                    <i class="ri-shield-star-line me-1"></i>{{ $member['role'] === 'superadmin' ? 'Super Admin' : 'مشرف' }}
                                </span>
                            @else
                                <span class="badge bg-light text-dark border">عضو</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($member['phone'] || $member['phone_jid'])
                                <button type="button"
                                        class="btn btn-sm btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#evoMemberMessageModal"
                                        data-to="{{ $member['phone'] ?: $member['phone_jid'] }}">
                                    <i class="ri-send-plane-line me-1"></i> مراسلة
                                </button>
                                <a href="{{ route('admin.evolution-api.send.text', ['to' => $member['phone'] ?: $member['phone_jid']]) }}"
                                   class="btn btn-sm btn-outline-secondary" title="فتح صفحة الإرسال">
                                    <i class="ri-external-link-line"></i>
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr id="evo-members-empty-row">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ri-user-unfollow-line fs-48 opacity-50 d-block mb-2"></i>
                            لا يوجد أعضاء أو تعذّر تحميل القائمة
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0 mt-2" id="evo-members-filter-note"></p>
    </div>
</div>

@include('admin.pages.evolution-api.groups.partials.member-message-modal')
