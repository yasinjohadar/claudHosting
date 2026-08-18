{{-- The three JS deps every embedded WHM panel needs: the toast helper, the
     copy-to-clipboard binder and the lazy deliverability loader.
     @push('scripts') this once per page.
     Needed because whm-toast and copy-email-script are plain IIFEs assigning
     window.* — they are not @once on their own.
     Do NOT add this to admin/whm/accounts/show.blade.php: that page already
     includes the three individually. --}}
@once
@include('admin.whm.accounts.partials.whm-toast')
@include('admin.whm.accounts.partials.copy-email-script')
@include('admin.whm.accounts.partials.email-deliverability-script')
@endonce
