<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $filters = [];

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build query for products
     */
    public function query()
    {
        $query = Product::query();

        // Apply filters
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Map data for export
     */
    public function map($product): array
    {
        return [
            $product->whmcs_id,
            $product->name,
            $product->type,
            $product->description,
            $product->paytype,
            $product->recurring,
            $product->qty,
            $product->status,
            $product->created_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'معرف WHMCS',
            'الاسم',
            'النوع',
            'الوصف',
            'نوع الدفع',
            'متكرر',
            'الكمية',
            'الحالة',
            'تم الإنشاء في',
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
