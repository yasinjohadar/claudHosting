<div class="btn-group" role="group">
    <form action="{{ $startRoute }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-success"><i class="fe fe-play"></i> تشغيل</button>
    </form>
    <form action="{{ $stopRoute }}" method="POST" class="d-inline" onsubmit="return confirm('إيقاف المورد؟');">
        @csrf
        <button type="submit" class="btn btn-sm btn-warning"><i class="fe fe-pause"></i> إيقاف</button>
    </form>
    <form action="{{ $restartRoute }}" method="POST" class="d-inline" onsubmit="return confirm('إعادة التشغيل؟');">
        @csrf
        <button type="submit" class="btn btn-sm btn-info"><i class="fe fe-refresh-cw"></i> إعادة تشغيل</button>
    </form>
</div>

