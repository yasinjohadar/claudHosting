@if($connected ?? false)
<div class="card custom-card mb-3 border-success">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <span><i class="fe fe-check-circle text-success"></i> <strong>اتصال API:</strong> مضبوط من هذه الصفحة</span>
            <span>
                @if($readiness['app_storage'] ?? false)<i class="fe fe-check text-success"></i>@else<i class="fe fe-x text-danger"></i>@endif
                <strong>تخزين اللقطات (S3):</strong>
                {{ ($readiness['app_storage'] ?? false) ? 'مضبوط' : 'مطلوب — ربط الأقراص' }}
            </span>
            <span>
                @if($readiness['coolify_s3'] ?? false)<i class="fe fe-check text-success"></i>@else<i class="fe fe-x text-warning"></i>@endif
                <strong>S3 في Coolify (للـ DB):</strong>
                {{ ($readiness['coolify_s3'] ?? false) ? 'مضبوط' : 'مطلوب أو يُكتشف من نسخ DB' }}
            </span>
        </div>
        @if(!empty($synced))
            <p class="small text-success mb-0 mt-2">تم ضبط تلقائياً من Coolify: {{ implode('، ', $synced) }}</p>
        @endif
        @if($readiness['ready'] ?? false)
            <p class="small text-success mb-0 mt-2">يمكنك إنشاء لقطات <strong>التطبيقات/volumes</strong> الآن. لنسخ <strong>قواعد البيانات</strong> في اللقطة أدخل UUID S3 في Coolify أدناه.</p>
        @else
            <p class="small text-muted mb-0 mt-2">إدارة المشاريع تعمل باتصال API. لـ <strong>لقطات المشاريع</strong> اختر سجل S3 من تبويب «النسخ واللقطات».</p>
        @endif
    </div>
</div>
@endif
