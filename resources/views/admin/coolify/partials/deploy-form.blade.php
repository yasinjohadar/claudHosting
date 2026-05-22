{{-- expects: $deployRoute, $applicationUuid optional --}}
<div class="card custom-card mb-3">
    <div class="card-header"><div class="card-title">نشر</div></div>
    <div class="card-body">
        <form method="POST" action="{{ $deployRoute }}" onsubmit="return confirm('بدء النشر؟');">
            @csrf
            @if(empty($applicationUuid))
            <div class="mb-2">
                <label class="form-label">UUID التطبيق</label>
                <input type="text" name="uuid" class="form-control" required dir="ltr">
            </div>
            @endif
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-check"><input type="checkbox" name="force" value="1" class="form-check-input"> إجبار (force)</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="tag" class="form-control" placeholder="tag (اختياري)" dir="ltr">
                </div>
                <div class="col-md-4">
                    <input type="text" name="pr" class="form-control" placeholder="PR id (اختياري)" dir="ltr">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-2"><i class="fe fe-upload-cloud"></i> نشر الآن</button>
        </form>
    </div>
</div>
