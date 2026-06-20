@extends('admin.layouts.master')
@section('page-title') معالج لقطة مشروع @stop
@section('content')
<div class="main-content app-content">
    <div class="container-fluid">
        @include('admin.coolify.backups.partials.tabs-nav', ['tab' => 'projects'])
        <div class="d-md-flex justify-content-between my-4">
            <h4>معالج إنشاء لقطة</h4>
            <a href="{{ route('admin.coolify.backups.projects.index') }}" class="btn btn-secondary btn-sm">رجوع</a>
        </div>
        @include('admin.coolify.partials.alerts')

        <div class="card custom-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-4">
                    <span class="badge bg-primary" id="stepBadge">الخطوة 1 من 4</span>
                </div>

                <div id="step1">
                    <h5>النطاق</h5>
                    <div class="mb-3">
                        <label class="form-check"><input type="radio" name="scope" value="all_projects" class="form-check-input"> كل المشاريع</label>
                        <label class="form-check"><input type="radio" name="scope" value="single_project" class="form-check-input" checked> مشروع واحد</label>
                        <label class="form-check"><input type="radio" name="scope" value="custom" class="form-check-input"> موارد مخصصة (من مشروع)</label>
                        <label class="form-check"><input type="radio" name="scope" value="server" class="form-check-input" @if(!empty($preselectedServer)) checked @endif> سيرفر كامل (كل موارد المشاريع على السيرفر)</label>
                    </div>
                    <div class="mb-3 d-none" id="serverUuidWrap">
                        <label class="form-label">UUID سيرفر Coolify</label>
                        <input type="text" id="serverUuid" class="form-control" value="{{ $preselectedServer ?? '' }}" placeholder="معرّف السيرفر من Coolify">
                    </div>
                    <div class="mb-3" id="projectSelectWrap">
                        <label class="form-label">المشروع</label>
                        <select id="projectUuid" class="form-select">
                            @foreach($projects as $p)
                                <option value="{{ $p['uuid'] ?? '' }}" data-name="{{ $p['name'] ?? '' }}" {{ ($preselectedProject ?? '') === ($p['uuid'] ?? '') ? 'selected' : '' }}>{{ $p['name'] ?? $p['uuid'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div id="step2" class="d-none">
                    <h5>الخيارات</h5>
                    <div class="row g-2">
                        <div class="col-md-6"><label class="form-check"><input type="checkbox" id="includeDatabases" checked class="form-check-input"> قواعد البيانات (API)</label></div>
                        <div class="col-md-6"><label class="form-check"><input type="checkbox" id="includeApplications" checked class="form-check-input"> التطبيقات (SSH volumes)</label></div>
                        <div class="col-md-6"><label class="form-check"><input type="checkbox" id="includeServices" checked class="form-check-input"> الخدمات</label></div>
                        <div class="col-12"><p class="small text-info mb-0"><i class="fe fe-cloud"></i> جميع اللقطات تُخزَّن على S3 فقط (إعدادات Coolify). لا تخزين دائم على السيرفر.</p></div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">تكرار (للجدولة الدورية)</label>
                        <select id="frequency" class="form-select">
                            @foreach($frequencies as $k => $label)
                                @if(in_array($k, ['hourly', 'daily', 'weekly', 'monthly'], true))
                                <option value="{{ $k }}" {{ $k === 'daily' ? 'selected' : '' }}>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-2">
                        <label class="form-check">
                            <input type="checkbox" id="createSchedule" class="form-check-input"> إنشاء جدولة دورية لهذا المشروع
                        </label>
                        <p class="small text-muted mb-0">عند التفعيل تُنشأ جدولة تلقائية بالإضافة للقطة الفورية</p>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">اسم اللقطة</label>
                        <input type="text" id="snapshotName" class="form-control" value="لقطة {{ now()->format('Y-m-d H:i') }}">
                    </div>
                </div>

                <div id="step3" class="d-none">
                    <h5>مراجعة الخطة</h5>
                    <div id="planLoading" class="text-muted">جاري بناء الخطة...</div>
                    <div id="planError" class="alert alert-danger d-none"></div>
                    <div id="planEmpty" class="alert alert-warning d-none">لم يُعثر على موارد في المشروع المحدد. تأكد من وجود تطبيقات أو قواعد بيانات أو خدمات في بيئات المشروع داخل Coolify.</div>
                    <div class="table-responsive d-none" id="planTableWrap">
                        <table class="table table-sm">
                            <thead><tr><th></th><th>المورد</th><th>النوع</th><th>الاستراتيجية</th><th>السيرفر</th></tr></thead>
                            <tbody id="planTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <div id="step4" class="d-none">
                    <p>اضغط «تنفيذ» لبدء اللقطة في الخلفية (يتطلب queue worker).</p>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="btn btn-secondary" id="btnPrev" disabled>السابق</button>
                    <button type="button" class="btn btn-primary" id="btnNext">التالي</button>
                    <form id="executeForm" method="POST" action="{{ route('admin.coolify.backups.projects.snapshots.store') }}" class="d-none">
                        @csrf
                        <input type="hidden" name="scope" id="formScope">
                        <input type="hidden" name="project_uuid" id="formProjectUuid">
                        <input type="hidden" name="server_uuid" id="formServerUuid">
                        <input type="hidden" name="project_name" id="formProjectName">
                        <input type="hidden" name="name" id="formName">
                        <input type="hidden" name="frequency" id="formFrequency">
                        <input type="hidden" name="create_schedule" id="formCreateSchedule" value="0">
                        <input type="hidden" name="save_s3" id="formSaveS3" value="1">
                        <div id="planInputs"></div>
                        <button type="submit" class="btn btn-success" id="btnExecute">تنفيذ اللقطة</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function() {
    let step = 1;
    let plan = [];
    const planUrl = @json(route('admin.coolify.backups.projects.plan'));

    function showStep(n) {
        step = n;
        document.getElementById('stepBadge').textContent = 'الخطوة ' + n + ' من 4';
        [1,2,3,4].forEach(i => document.getElementById('step'+i).classList.toggle('d-none', i !== n));
        document.getElementById('btnPrev').disabled = n === 1;
        document.getElementById('btnNext').classList.toggle('d-none', n === 4);
        document.getElementById('executeForm').classList.toggle('d-none', n !== 4);
    }

    async function loadPlan() {
        document.getElementById('planLoading').classList.remove('d-none');
        document.getElementById('planTableWrap').classList.add('d-none');
        document.getElementById('planError').classList.add('d-none');
        document.getElementById('planEmpty').classList.add('d-none');
        const scope = document.querySelector('input[name="scope"]:checked').value;
        const projectEl = document.getElementById('projectUuid');
        const serverEl = document.getElementById('serverUuid');
        const body = {
            scope,
            project_uuid: (scope === 'all_projects' || scope === 'server') ? null : projectEl.value,
            server_uuid: scope === 'server' ? (serverEl?.value || '') : null,
            include_databases: document.getElementById('includeDatabases').checked ? 1 : 0,
            include_applications: document.getElementById('includeApplications').checked ? 1 : 0,
            include_services: document.getElementById('includeServices').checked ? 1 : 0,
        };
        let data = {};
        try {
            const res = await fetch(planUrl, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json'},
                body: JSON.stringify(body)
            });
            data = await res.json();
        } catch (e) {
            document.getElementById('planLoading').classList.add('d-none');
            document.getElementById('planError').textContent = 'تعذّر الاتصال بالخادم لبناء الخطة.';
            document.getElementById('planError').classList.remove('d-none');
            plan = [];
            return;
        }
        if (!data.success) {
            document.getElementById('planLoading').classList.add('d-none');
            document.getElementById('planError').textContent = data.message || 'فشل بناء الخطة.';
            document.getElementById('planError').classList.remove('d-none');
            plan = [];
            return;
        }
        plan = data.plan || [];
        const tbody = document.getElementById('planTableBody');
        tbody.innerHTML = '';
        if (plan.length === 0) {
            document.getElementById('planLoading').classList.add('d-none');
            document.getElementById('planEmpty').classList.remove('d-none');
            return;
        }
        plan.forEach((row, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><input type="checkbox" class="plan-enabled" data-idx="${idx}" checked></td>
                <td>${row.resource_name || row.resource_uuid}</td>
                <td>${row.resource_type}</td>
                <td>${row.strategy}</td>
                <td>${row.server_host || '—'}</td>`;
            tbody.appendChild(tr);
        });
        document.getElementById('planLoading').classList.add('d-none');
        document.getElementById('planTableWrap').classList.remove('d-none');
    }

    function fillExecuteForm() {
        const scope = document.querySelector('input[name="scope"]:checked').value;
        const projectEl = document.getElementById('projectUuid');
        document.getElementById('formScope').value = scope;
        document.getElementById('formProjectUuid').value = (scope === 'all_projects' || scope === 'server') ? '' : projectEl.value;
        document.getElementById('formServerUuid').value = scope === 'server' ? (document.getElementById('serverUuid')?.value || '') : '';
        document.getElementById('formProjectName').value = scope === 'all_projects' ? '' : (projectEl.selectedOptions[0]?.dataset?.name || '');
        document.getElementById('formName').value = document.getElementById('snapshotName').value;
        document.getElementById('formFrequency').value = document.getElementById('frequency').value;
        document.getElementById('formCreateSchedule').value = document.getElementById('createSchedule').checked ? '1' : '0';
        document.getElementById('formSaveS3').value = '1';
        const wrap = document.getElementById('planInputs');
        wrap.innerHTML = '';
        let formIdx = 0;
        document.querySelectorAll('.plan-enabled').forEach(cb => {
            if (!cb.checked) return;
            const idx = parseInt(cb.dataset.idx, 10);
            const row = plan[idx];
            if (!row) return;
            ['resource_uuid','resource_type','resource_name','project_uuid','server_uuid','server_host','strategy'].forEach(key => {
                const inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = `plan[${formIdx}][${key}]`;
                inp.value = row[key] ?? '';
                wrap.appendChild(inp);
            });
            const en = document.createElement('input');
            en.type = 'hidden';
            en.name = `plan[${formIdx}][enabled]`;
            en.value = '1';
            wrap.appendChild(en);
            formIdx++;
        });
    }

    document.getElementById('btnNext').addEventListener('click', async () => {
        if (step === 2) await loadPlan();
        if (step === 3) fillExecuteForm();
        if (step < 4) showStep(step + 1);
    });
    document.getElementById('btnPrev').addEventListener('click', () => { if (step > 1) showStep(step - 1); });
    document.querySelectorAll('input[name="scope"]').forEach(el => {
        el.addEventListener('change', () => {
            const v = document.querySelector('input[name="scope"]:checked').value;
            document.getElementById('projectSelectWrap').classList.toggle('d-none', v === 'all_projects' || v === 'server');
            document.getElementById('serverUuidWrap')?.classList.toggle('d-none', v !== 'server');
        });
    });
    const initialScope = document.querySelector('input[name="scope"]:checked')?.value;
    if (initialScope === 'server') {
        document.getElementById('projectSelectWrap').classList.add('d-none');
        document.getElementById('serverUuidWrap')?.classList.remove('d-none');
    }
})();
</script>
@endpush
@endsection

