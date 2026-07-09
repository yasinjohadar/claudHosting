@extends('admin.pages.evolution-api.layout')

@php
    $evoPageTitle = 'المحادثات';
    $evoTitle = 'المحادثات';
    $evoSubtitle = count($chats) . ' محادثة';
    $evoBreadcrumb = 'المحادثات';
@endphp

@section('evo-content')
@if($error)
    <div class="alert alert-warning border-0 shadow-sm mb-3"><i class="ri-alert-line me-2"></i>{{ $error }}</div>
@endif

<div class="card custom-card border-0 shadow-sm">
    <div class="card-header bg-transparent"><div class="card-title mb-0"><i class="ri-chat-3-line me-2 text-success"></i>المحادثات</div></div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:560px">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top"><tr><th>JID</th><th>الاسم</th><th>غير مقروء</th><th>آخر رسالة</th></tr></thead>
                <tbody>
                @forelse($chats as $chat)
                    <tr>
                        <td><code class="small">{{ $chat['id'] ?? $chat['remoteJid'] ?? '' }}</code></td>
                        <td>{{ $chat['name'] ?? $chat['pushName'] ?? '—' }}</td>
                        <td>@if(($chat['unreadCount'] ?? 0) > 0)<span class="badge bg-danger">{{ $chat['unreadCount'] }}</span>@else 0 @endif</td>
                        <td class="small text-muted">{{ Str::limit($chat['lastMessage']['conversation'] ?? data_get($chat, 'lastMessage.message.conversation', '—'), 50) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">لا محادثات</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
