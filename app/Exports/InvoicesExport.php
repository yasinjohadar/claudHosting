<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $filters = [];

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build query for invoices
     */
    public function query()
    {
        $query = Invoice::query();

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['paymentmethod'])) {
            $query->where('paymentmethod', $this->filters['paymentmethod']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->where('date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->where('date', '<=', $this->filters['date_to']);
        }

        return $query->orderBy('date', 'desc');
    }

    /**
     * Map data for export
     */
    public function map($invoice): array
    {
        return [
            $invoice->whmcs_id,
            $invoice->invoice_number,
            $invoice->customer->fullname ?? 'N/A',
            $invoice->date?->format('Y-m-d') ?? '',
            $invoice->duedate?->format('Y-m-d') ?? '',
            $invoice->datepaid?->format('Y-m-d') ?? '',
            $invoice->subtotal,
            $invoice->tax,
            $invoice->total,
            $invoice->status,
            $invoice->paymentmethod,
        ];
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'معرف WHMCS',
            'رقم الفاتورة',
            'العميل',
            'التاريخ',
            'تاريخ الاستحقاق',
            'تاريخ الدفع',
            'الإجمالي الفرعي',
            'الضريبة',
            'الإجمالي',
            'الحالة',
            'طريقة الدفع',
        ];
    }

    /**
     * Style the spreadsheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '366092']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }
}
