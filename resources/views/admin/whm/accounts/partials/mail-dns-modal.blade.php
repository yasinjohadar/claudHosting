{{-- Singleton modal for the mail-DNS preview/apply flow. One per page regardless of how
     many accounts (accordion cards) offer the button — the JS fills in the URLs from the
     button that opened it. --}}
@once
<div class="modal fade" id="whm-mail-dns-modal" tabindex="-1" aria-hidden="true"
    data-whm-dns-modal
    data-preview-url=""
    data-apply-url=""
    data-plan-hash=""
    data-acks="[]">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content whm-account-page">
            <div class="modal-header">
                <h6 class="modal-title mb-0">
                    <i class="fe fe-cloud me-1"></i>تركيب سجلات البريد في Cloudflare
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="text-center text-muted small py-4" data-whm-dns-loading>
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>جارٍ مقارنة منطقة cPanel مع Cloudflare…
                </div>
                <div data-whm-dns-body></div>
            </div>
            <div class="modal-footer justify-content-between flex-wrap gap-2">
                <div class="form-check mb-0 d-none" data-whm-dns-ack-wrap>
                    <input class="form-check-input" type="checkbox" id="whm-mail-dns-ack" data-whm-dns-ack>
                    <label class="form-check-label small" for="whm-mail-dns-ack">
                        أُقِرّ بالتحذيرات أعلاه وأريد المتابعة
                    </label>
                </div>
                <div class="d-flex gap-2 ms-auto">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-whm-dns-refresh>
                        <span class="whm-btn-label"><i class="fe fe-refresh-cw me-1"></i>أعد المعاينة</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-whm-dns-apply disabled>
                        <span class="whm-btn-label"><i class="fe fe-upload-cloud me-1"></i>تطبيق على Cloudflare</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
@include('admin.whm.accounts.partials.mail-dns-script')
@endpush
@endonce
