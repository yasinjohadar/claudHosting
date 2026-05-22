<?php

namespace App\Support;

use App\Models\Invoice;

trait GeneratesInvoiceNumbers
{
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV-';
        $year = date('Y');
        $month = date('m');

        $lastInvoice = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->whereNotNull('invoice_number')
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice && preg_match('/\d{4}$/', $lastInvoice->getAttributes()['invoice_number'] ?? $lastInvoice->invoicenum ?? '', $matches)) {
            $newNumber = str_pad((int) $matches[0] + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix.$year.$month.$newNumber;
    }
}
