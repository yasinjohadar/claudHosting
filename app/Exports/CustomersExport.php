<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $filters = [];

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build query for customers
     */
    public function query()
    {
        $query = Customer::query();

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['country'])) {
            $query->where('country', $this->filters['country']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('fullname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('companyname', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Map data for export
     */
    public function map($customer): array
    {
        return [
            $customer->whmcs_id,
            $customer->fullname,
            $customer->email,
            $customer->companyname,
            $customer->phonenumber,
            $customer->city,
            $customer->state,
            $customer->country,
            $customer->status,
            $customer->created_at?->format('Y-m-d H:i:s') ?? '',
            $customer->date_created?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'معرف WHMCS',
            'الاسم الكامل',
            'البريد الإلكتروني',
            'اسم الشركة',
            'رقم الهاتف',
            'المدينة',
            'الولاية',
            'الدولة',
            'الحالة',
            'تم الإنشاء في',
            'تاريخ الإنشاء الأصلي',
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
