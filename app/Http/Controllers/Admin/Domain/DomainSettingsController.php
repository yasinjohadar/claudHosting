<?php

namespace App\Http\Controllers\Admin\Domain;

use App\Http\Controllers\Controller;
use App\Services\Domain\DomainSettingsService;
use Illuminate\Http\Request;

class DomainSettingsController extends Controller
{
    public function __construct(
        protected DomainSettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->settings->initializeDefaults();
        $billing = $this->settings->getBillingConfig();

        return view('admin.domains.settings.index', compact('billing'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'renewal_amount' => 'nullable|numeric|min:0',
            'invoice_due_days' => 'nullable|integer|min:1|max:90',
        ]);

        $this->settings->updateBillingSettings([
            'renewal_amount' => $validated['renewal_amount'] ?? 0,
            'invoice_due_days' => $validated['invoice_due_days'] ?? 7,
        ]);

        return redirect()->route('admin.domains.settings.index')
            ->with('success', 'تم حفظ إعدادات فوترة النطاقات');
    }
}
