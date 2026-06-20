<div class="modal fade" id="snapshotOperationModal" tabindex="-1" aria-labelledby="snapshotOperationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content snapshot-operation-modal-content border-0 shadow">
            <div class="modal-body text-center p-4 p-md-5">
                <div class="snapshot-operation-icon mb-3" id="snapshotOpIcon" aria-hidden="true"></div>
                <h5 class="fw-bold mb-2" id="snapshotOpTitle">—</h5>
                <p class="text-muted mb-3" id="snapshotOpSummary">—</p>
                <div class="snapshot-operation-stats row g-2 text-start mb-3 d-none" id="snapshotOpStats"></div>
                <div class="text-start d-none" id="snapshotOpErrorsWrap">
                    <button class="btn btn-link btn-sm p-0 mb-2 text-danger" type="button" data-bs-toggle="collapse" data-bs-target="#snapshotOpErrorsList">
                        عرض التفاصيل (<span id="snapshotOpErrorsCount">0</span>)
                    </button>
                    <div class="collapse" id="snapshotOpErrorsList">
                        <ul class="list-unstyled small mb-0 snapshot-op-error-list" id="snapshotOpErrorsListUl"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">إغلاق</button>
                <button type="button" class="btn btn-primary" id="snapshotOpRefreshBtn">تحديث الصفحة</button>
            </div>
        </div>
    </div>
</div>
