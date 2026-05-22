<div class="card custom-card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">سجلات DNS (WHM)</span>
        <span class="badge bg-warning-transparent text-dark">قراءة فقط</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>النوع</th>
                    <th>الاسم</th>
                    <th>القيمة</th>
                    <th>TTL</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td><code>{{ $rec['type'] ?? $rec['record_type'] ?? '—' }}</code></td>
                        <td dir="ltr" class="small">{{ $rec['name'] ?? $rec['dname'] ?? '@' }}</td>
                        <td dir="ltr" class="small text-break">{{ $rec['address'] ?? $rec['cname'] ?? $rec['exchange'] ?? $rec['txtdata'] ?? $rec['target'] ?? '—' }}</td>
                        <td>{{ $rec['ttl'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">{{ $error ?? 'لا سجلات أو فشل الجلب' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
