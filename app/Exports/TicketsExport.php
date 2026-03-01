<?php

namespace App\Exports;

use App\Models\Ticket;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $filters = [];

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build query for tickets
     */
    public function query()
    {
        $query = Ticket::query();

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }

        if (!empty($this->filters['department'])) {
            $query->where('department', $this->filters['department']);
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
    public function map($ticket): array
    {
        return [
            $ticket->whmcs_id,
            $ticket->tid,
            $ticket->customer->fullname ?? 'N/A',
            $ticket->subject,
            $ticket->status,
            $ticket->priority,
            $ticket->department,
            $ticket->admin,
            $ticket->date?->format('Y-m-d H:i:s') ?? '',
            $ticket->lastreply?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    /**
     * Column headings
     */
    public function headings(): array
    {
        return [
            'معرف WHMCS',
            'رقم التذكرة',
            'العميل',
            'الموضوع',
            'الحالة',
            'الأولوية',
            'القسم',
            'الموظف',
            'تاريخ الإنشاء',
            'آخر رد',
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
