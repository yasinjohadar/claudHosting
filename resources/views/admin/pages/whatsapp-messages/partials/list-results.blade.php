<div class="table-responsive">
    <table class="domain-dns-table domain-list-table">
        <thead>
            <tr>
                <th style="width: 48px;">#</th>
                <th>الاتجاه</th>
                <th>جهة الاتصال</th>
                <th class="domain-list-table__domain">الرسالة</th>
                <th>الحالة</th>
                <th>التاريخ</th>
                <th class="domain-list-table__action">الإجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($messages as $message)
                @php
                    $statusClass = match ($message->status) {
                        \App\Models\WhatsAppMessage::STATUS_SENT => 'active',
                        \App\Models\WhatsAppMessage::STATUS_DELIVERED => 'info',
                        \App\Models\WhatsAppMessage::STATUS_READ => 'active',
                        \App\Models\WhatsAppMessage::STATUS_FAILED => 'expired',
                        \App\Models\WhatsAppMessage::STATUS_QUEUED => 'warning',
                        default => 'info',
                    };
                    $statusLabel = match ($message->status) {
                        \App\Models\WhatsAppMessage::STATUS_SENT => 'مرسل',
                        \App\Models\WhatsAppMessage::STATUS_DELIVERED => 'مستلم',
                        \App\Models\WhatsAppMessage::STATUS_READ => 'مقروء',
                        \App\Models\WhatsAppMessage::STATUS_FAILED => 'فشل',
                        \App\Models\WhatsAppMessage::STATUS_QUEUED => 'في الانتظار',
                        default => $message->status,
                    };
                @endphp
                <tr>
                    <td class="text-muted">{{ $message->id }}</td>
                    <td>
                        <span class="domain-status-badge domain-status-badge--{{ $message->direction === 'inbound' ? 'info' : 'active' }}">
                            {{ $message->direction === 'inbound' ? 'واردة' : 'صادرة' }}
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold" dir="ltr">{{ $message->contact?->name ?? '—' }}</div>
                        <code class="small text-muted" dir="ltr">{{ $message->contact?->wa_id ?? '—' }}</code>
                    </td>
                    <td class="domain-list-table__domain">
                        <span class="text-truncate d-inline-block" style="max-width: 280px;" title="{{ $message->body }}">
                            {{ \Illuminate\Support\Str::limit($message->body ?? '—', 80) }}
                        </span>
                    </td>
                    <td>
                        <span class="domain-status-badge domain-status-badge--{{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td dir="ltr">{{ $message->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="domain-list-table__action">
                        <div class="customer-actions">
                            <a href="{{ route('admin.whatsapp-messages.show', $message) }}" class="domain-action-btn" title="عرض">
                                <i class="fe fe-eye"></i>
                            </a>
                            @if($message->status === \App\Models\WhatsAppMessage::STATUS_FAILED)
                                <form action="{{ route('admin.whatsapp-messages.retry', $message) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="domain-action-btn" title="إعادة المحاولة">
                                        <i class="fe fe-refresh-cw"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="domain-list-empty">
                        <i class="fe fe-message-square"></i>
                        <p>لا توجد رسائل</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($messages->hasPages())
    <div class="domain-list-footer whatsapp-messages-pagination">
        {{ $messages->withQueryString()->links() }}
    </div>
@endif
