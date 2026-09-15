<div class="tab-pane fade" id="siteTabTechnical" role="tabpanel">
@if(! empty($isClientPanel ?? false))
    <div class="alert alert-info small border-0 shadow-sm mb-0">هذا التبويب متاح فقط للإدارة.</div>
@else
    @php $dbEnv = $site->metadata['database_env'] ?? []; @endphp

    <div class="row g-3 site-show-tab-grid">
        @if(!empty($site->metadata['last_api']))
        <div class="col-12">
            <h6 class="site-show-section-title">تشخيص API</h6>
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'danger',
                'icon' => 'fe fe-alert-circle',
                'label' => 'آخر خطأ Coolify',
                'desc' => ($site->metadata['last_api']['step'] ?? '—').(!empty($site->metadata['last_api']['http_status']) ? ' · HTTP '.$site->metadata['last_api']['http_status'] : ''),
                'highlight' => 'فشل',
            ])
            <div class="site-show-log-card mb-0 mt-3">
                @if(!empty($site->metadata['last_api']['payload']))
                <div class="px-3 py-2 border-bottom small fw-semibold">البيانات المرسلة</div>
                <pre class="site-show-log-pre border-0 rounded-0 mb-0" dir="ltr">{{ json_encode($site->metadata['last_api']['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
                @if(!empty($site->metadata['last_api']['body']))
                <div class="px-3 py-2 border-bottom small fw-semibold">استجابة Coolify</div>
                <pre class="site-show-log-pre border-0 rounded-0 mb-0" dir="ltr">{{ json_encode($site->metadata['last_api']['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </div>
        </div>
        @endif

        @if(!empty($dbEnv))
        <div class="col-12">
            <h6 class="site-show-section-title">قاعدة البيانات</h6>
            <div class="site-show-panel-table">
                <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                    <div class="coolify-widget-icon coolify-accent-warning" style="width:40px;height:40px;font-size:1.1rem;" aria-hidden="true"><i class="fe fe-database"></i></div>
                    <div>
                        <div class="fw-bold small">متغيرات الخدمة</div>
                        <div class="text-muted" style="font-size:0.76rem;">من إعدادات Coolify</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 site-show-env-table">
                        <thead>
                            <tr><th class="w-35">المتغير</th><th>القيمة</th></tr>
                        </thead>
                        <tbody>
                        @foreach($dbEnv as $key => $val)
                        @php $isSecret = (bool) preg_match('/PASSWORD|SECRET|KEY/i', (string) $key); @endphp
                        <tr>
                            <td><code>{{ $key }}</code></td>
                            <td class="env-val">
                                @if($isSecret)
                                <span class="env-val__masked">••••••••</span>
                                <span class="env-val__real d-none" dir="ltr">{{ $val }}</span>
                                <button type="button" class="btn btn-link btn-sm p-0 ms-1 env-val__toggle" title="إظهار/إخفاء">
                                    <i class="fe fe-eye"></i>
                                </button>
                                @else
                                {{ $val }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="col-12">
            <h6 class="site-show-section-title">معرّفات النظام</h6>
            @include('admin.coolify.partials.info-widget', [
                'accent' => 'secondary',
                'icon' => 'fe fe-hash',
                'label' => 'Coolify UUIDs',
                'desc' => 'مرجع تقني للموقع',
                'rows' => [
                    ['label' => 'service', 'value' => $site->service_uuid ?? '—', 'mono' => true],
                    ['label' => 'project', 'value' => $site->project_uuid ?? '—', 'mono' => true],
                    ['label' => 'server', 'value' => $site->server_uuid ?? '—', 'mono' => true],
                    ['label' => 'موقع', 'value' => $uuid, 'mono' => true],
                ],
                'copyText' => $site->service_uuid ?? $uuid,
            ])
        </div>
    </div>
    <script>
        document.querySelectorAll('.env-val__toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var cell = btn.closest('.env-val');
                cell.querySelector('.env-val__masked').classList.toggle('d-none');
                cell.querySelector('.env-val__real').classList.toggle('d-none');
            });
        });
    </script>
@endif
</div>
