<div class="card custom-card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="card-title mb-0">الجداول</div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <input type="search" id="sysdbTableSearch" class="form-control form-control-sm" placeholder="بحث باسم الجدول..." style="min-width:200px;" dir="ltr">
            <span class="small text-muted" id="sysdbTableCount">{{ count($tables) }} جدول</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="sysdbTablesTable">
                <thead>
                    <tr>
                        <th class="sysdb-sortable ps-3" data-sort="name">الجدول</th>
                        <th class="sysdb-sortable text-end" data-sort="rows">الصفوف</th>
                        <th class="sysdb-sortable" data-sort="data">البيانات</th>
                        <th class="sysdb-sortable" data-sort="index">الفهارس</th>
                        <th class="sysdb-sortable" data-sort="total">الإجمالي</th>
                        <th>الحجم %</th>
                        <th class="sysdb-sortable" data-sort="engine">المحرك</th>
                        <th class="pe-3 text-end">إجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tables as $t)
                    <tr class="sysdb-table-row"
                        data-name="{{ $t['name'] }}"
                        data-rows="{{ $t['rows'] }}"
                        data-data="{{ $t['data_bytes'] }}"
                        data-index="{{ $t['index_bytes'] }}"
                        data-total="{{ $t['total_bytes'] }}"
                        data-engine="{{ $t['engine'] ?? '' }}">
                        <td class="ps-3">
                            <code dir="ltr">{{ $t['name'] }}</code>
                            @if($t['rows_approximate'] ?? false)
                                <span class="badge bg-warning-transparent text-warning ms-1" title="تقدير InnoDB">~</span>
                            @endif
                        </td>
                        <td class="text-end" dir="ltr">{{ $t['rows_label'] }}</td>
                        <td dir="ltr">{{ $t['data_size'] }}</td>
                        <td dir="ltr">{{ $t['index_size'] }}</td>
                        <td dir="ltr"><strong>{{ $t['total_size'] }}</strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="sysdb-size-bar flex-grow-1">
                                    <span style="width:{{ min(100, $t['size_percent'] ?? 0) }}%"></span>
                                </div>
                                <span class="small text-muted" dir="ltr">{{ $t['size_percent'] ?? 0 }}%</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $t['engine'] ?: '—' }}</span></td>
                        <td class="pe-3 text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary sysdb-detail-btn" data-table="{{ $t['name'] }}">
                                <i class="fe fe-eye"></i> تفاصيل
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">لا توجد جداول أو فشل الاتصال.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
