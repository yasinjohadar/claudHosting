@php
    $instructions = $site->metadata['dns_manual_instructions'] ?? [];
    $note = $site->metadata['dns_manual_note'] ?? null;
@endphp
@if (! empty($instructions) && is_array($instructions))
<div class="alert alert-warning border mb-3">
    <h6 class="alert-heading"><i class="fe fe-edit-3 me-1"></i> إعداد DNS يدوي</h6>
    @if ($note)
        <p class="small mb-2">{{ $note }}</p>
    @endif
    <p class="small mb-2">أضف السجلات التالية عند مسجّل الدومين أو في Cloudflare بعد إضافة المنطقة:</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0 bg-white">
            <thead>
                <tr>
                    <th>الغرض</th>
                    <th>النوع</th>
                    <th>الاسم</th>
                    <th>FQDN</th>
                    <th>القيمة</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($instructions as $row)
                <tr>
                    <td>{{ $row['label'] ?? '—' }}</td>
                    <td><code>{{ $row['type'] ?? '—' }}</code></td>
                    <td dir="ltr"><code>{{ $row['name'] ?? '—' }}</code></td>
                    <td dir="ltr"><code>{{ $row['fqdn'] ?? '—' }}</code></td>
                    <td dir="ltr" class="small"><code>{{ $row['value'] ?? '—' }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
