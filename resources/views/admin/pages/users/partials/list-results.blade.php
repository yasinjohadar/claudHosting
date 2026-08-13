@php
    $statusLabels = [
        'active' => 'مفعل',
        'inactive' => 'موقوف',
        'banned' => 'محظور',
    ];
@endphp
<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 40px;">#</th>
                <th class="domain-list-table__domain">اسم المستخدم</th>
                <th>البريد</th>
                <th>الهاتف</th>
                <th>آخر دخول</th>
                <th class="text-center">cPanel</th>
                <th>الأدوار</th>
                <th>الحالة</th>
                <th>التفعيل</th>
                <th class="domain-list-table__action">العمليات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
            @php
                $userSessions = $sessions->get($user->id);
                $lastSession = $userSessions ? $userSessions->first() : null;
                $initials = mb_strtoupper(mb_substr($user->name ?? 'U', 0, 1).mb_substr(strstr($user->name ?? '', ' ') ?: '', 1, 1));
                $statusKey = $user->status ?? 'active';
                $statusClass = match(true) {
                    $statusKey === 'banned' => 'expired',
                    $statusKey === 'inactive' => 'warning',
                    default => 'active',
                };
            @endphp
            <tr>
                <td class="text-muted">{{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}</td>
                <td class="domain-list-table__domain">
                    <a href="{{ route('users.show', $user->id) }}" class="domain-name-link">
                        <span class="customer-avatar">{{ $initials ?: '?' }}</span>
                        <span class="domain-name-link__text">{{ $user->name }}</span>
                    </a>
                </td>
                <td>
                    @if($user->email)
                    <a href="mailto:{{ $user->email }}" class="customer-contact-value text-decoration-none" dir="ltr" title="إرسال بريد">
                        {{ $user->email }}
                    </a>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($user->phone)
                    <span class="customer-contact-value" dir="ltr">
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $user->phone) }}"
                            target="_blank" rel="noopener" class="text-success text-decoration-none me-1" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        {{ $user->phone }}
                    </span>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($lastSession)
                    <span class="text-muted small">{{ \Carbon\Carbon::createFromTimestamp($lastSession->last_activity)->diffForHumans() }}</span>
                    @else
                    <span class="text-muted small">لا توجد جلسات</span>
                    @endif
                </td>
                <td class="text-center">
                    @if(($user->whm_accounts_count ?? 0) > 0)
                    <a href="{{ route('admin.whm.accounts.index', ['user_id' => $user->id]) }}"
                        class="domain-mini-badge domain-mini-badge--yes text-decoration-none"
                        title="حسابات الاستضافة">
                        {{ $user->whm_accounts_count }}
                    </a>
                    @else
                    <span class="domain-mini-badge domain-mini-badge--no">0</span>
                    @endif
                </td>
                <td>
                    @forelse($user->getRoleNames() as $role)
                    <span class="domain-mini-badge domain-mini-badge--yes">{{ $role }}</span>
                    @empty
                    <span class="text-muted">—</span>
                    @endforelse
                </td>
                <td>
                    <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                        {{ $statusLabels[$statusKey] ?? $statusKey }}
                    </span>
                </td>
                <td>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input toggle-status"
                            type="checkbox"
                            data-user-id="{{ $user->id }}"
                            {{ $user->is_active ? 'checked' : '' }}
                            style="cursor: pointer;">
                        <label class="form-check-label small">
                            {{ $user->is_active ? 'نشط' : 'غير نشط' }}
                        </label>
                    </div>
                </td>
                <td class="domain-list-table__action">
                    <div class="customer-actions">
                        @if(auth()->user()?->isAdminPanelUser() && ! $user->isAdminPanelUser())
                        <button type="button"
                            class="domain-action-btn domain-action-btn--warning js-impersonate-client"
                            data-url="{{ route('admin.users.impersonation-token', $user) }}"
                            data-name="{{ $user->name }}"
                            title="دخول كعميل">
                            <i class="fe fe-log-in"></i>
                        </button>
                        @endif
                        <a href="{{ route('users.edit', $user->id) }}"
                            class="domain-action-btn domain-action-btn--info" title="تعديل">
                            <i class="fe fe-edit-2"></i>
                        </a>
                        <button type="button" class="domain-action-btn domain-action-btn--muted"
                            data-bs-toggle="modal" data-bs-target="#change_password{{ $user->id }}"
                            title="كلمة المرور">
                            <i class="fe fe-lock"></i>
                        </button>
                        <button type="button" class="domain-action-btn domain-action-btn--danger"
                            data-bs-toggle="modal" data-bs-target="#delete{{ $user->id }}"
                            title="حذف">
                            <i class="fe fe-trash-2"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="domain-list-empty">
                    <i class="fe fe-inbox"></i>
                    <p>لا توجد بيانات مطابقة — جرّب تغيير الفلاتر.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($users->hasPages())
<div class="domain-list-footer users-pagination">
    {{ $users->withQueryString()->links() }}
</div>
@endif
<div class="users-modals">
    @foreach($users as $user)
        @include('admin.pages.users.delete')
        @include('admin.pages.users.change_password')
    @endforeach
</div>
