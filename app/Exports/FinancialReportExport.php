<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialReportExport implements FromArray, ShouldAutoSize, WithDrawings, WithStyles, WithTitle
{
    protected $data;

    protected $period;

    protected $isDetailed;

    protected $system;

    public function __construct($data, $period, $isDetailed = false)
    {
        $this->data = $data;
        $this->period = $period;
        $this->isDetailed = $isDetailed;
        $this->system = DB::table('system')->first();
    }

    public function title(): string
    {
        return $this->isDetailed ? 'Daily Sales' : 'Profit & Loss';
    }

    public function array(): array
    {
        if ($this->isDetailed) {
            return $this->getDetailedArray();
        }

        return $this->getSummaryArray();
    }

    protected function getDetailedArray(): array
    {
        $rows = [
            [''], [''], // Logo rows
            [$this->system->systemname ?? config('app.name')],
            ['Date: '.($this->data['date'] ?? '-')],
            ['Time', 'Item', 'Qty', 'Price', 'Payment'],
        ];

        $totalSales = 0;

        foreach ($this->data['items'] ?? [] as $item) {
            $totalSales += $item['price'];
            $rows[] = [
                str_replace(':', ',', $item['time']),
                $item['item'],
                $item['qty'],
                $item['price'],
                $item['payment'],
            ];
        }

        $rows[] = ['TOTAL SALES', '', '', $totalSales, ''];

        return $rows;
    }

    protected function getSummaryArray(): array
    {
        $exportData = $this->data['data'] ?? $this->data;
        $label = $exportData['month_label'] ?? ($exportData['year_label'] ?? 'Financial Report');

        $rows = [
            [''], [''], // Logo
            ['PROFIT & LOSS STATEMENT'],
            [$this->system->systemname ?? config('app.name')],
            [$label],
            ['Account No', 'Description', 'Subtotal', 'Total'],
        ];

        $totalRevenue = 0;
        foreach ($exportData['revenue'] ?? [] as $rev) {
            $totalRevenue += (float) $rev['amount'];
            $rows[] = [
                $rev['code'],
                $rev['label'],
                $rev['amount'] > 0 ? (float) $rev['amount'] : '-',
                '',
            ];
        }

        $rows[] = ['Total Revenue', '', '', $totalRevenue];
        $rows[] = ['']; // Spacer

        $totalExpense = 0;
        foreach ($exportData['expenses'] ?? [] as $exp) {
            $totalExpense += (float) $exp['amount'];
            $rows[] = [
                $exp['code'],
                $exp['label'],
                $exp['amount'] > 0 ? (float) ($exp['amount'] * -1) : '-',
                '',
            ];
        }

        $rows[] = ['Total Expenses', '', (float) ($totalExpense * -1), ''];
        $rows[] = ['NET PROFIT / LOSS', '', '', (float) ($totalRevenue - $totalExpense)];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:E')->getFont()->setName('Times New Roman');
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(40);

        if ($this->isDetailed) {
            $lastRow = count($this->data['items'] ?? []) + 6;

            // Header (System Name & Date)
            $sheet->mergeCells('A3:E3');
            $sheet->mergeCells('A4:E4');

            $sheet->getStyle('A3:E5')->getFont()->setBold(true)->setName('Arial');
            $sheet->getStyle('A3:E5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A3')->getFont()->setSize(14);
            $sheet->getStyle('A5:E5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            $sheet->getStyle('A5:E'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Alignment
            $sheet->getStyle('A6:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C6:C'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E6:E'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Numbers
            $sheet->getStyle('D6:D'.$lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0');

            // Total Row
            $sheet->mergeCells('A'.$lastRow.':C'.$lastRow);
            $sheet->getStyle('A'.$lastRow.':E'.$lastRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$lastRow.':E'.$lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
            $sheet->getStyle('A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            return [];
        }

        // Summary Styles (4 columns: A-D)
        $lastRow = $sheet->getHighestRow();

        // Header Titles (Merge and Center)
        $sheet->mergeCells('A3:D3');
        $sheet->mergeCells('A4:D4');
        $sheet->mergeCells('A5:D5');

        $sheet->getStyle('A3:D6')->getFont()->setBold(true)->setName('Arial');
        $sheet->getStyle('A3:D6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFont()->setSize(14);
        $sheet->getStyle('A6:D6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');

        // Borders for the main sections
        $sheet->getStyle('A6:D'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Currencies
        $sheet->getStyle('C7:D'.$lastRow)->getNumberFormat()->setFormatCode('"Rp"#,##0;[Red]"(Rp"#,##0")"');

        // Total Rows styling based on content
        for ($i = 7; $i <= $lastRow; $i++) {
            $val = $sheet->getCell('A'.$i)->getValue();
            if (in_array($val, ['Total Revenue', 'Total Expenses', 'NET PROFIT / LOSS'])) {
                $sheet->getStyle('A'.$i.':D'.$i)->getFont()->setBold(true)->setName('Arial');
                $sheet->getStyle('A'.$i.':D'.$i)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($val === 'NET PROFIT / LOSS' ? 'FFD9D9D9' : 'FFF2F2F2');
                if ($val !== 'NET PROFIT / LOSS') {
                    $sheet->mergeCells('A'.$i.':B'.$i);
                } else {
                    $sheet->mergeCells('A'.$i.':C'.$i);
                }
                $sheet->getStyle('A'.$i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        return [];
    }

    public function drawings()
    {
        $drawings = [];
        if ($this->system && $this->system->systemlogo) {
            $parsedUrl = parse_url($this->system->systemlogo);
            $path = $parsedUrl['path'] ?? '';
            
            // Handle /POS/ subdirectory prefix if present
            if (str_starts_with($path, '/POS/')) {
                $path = substr($path, 4);
            }
            
            $localPath = public_path($path);
            
            if (file_exists($localPath)) {
                $drawing = new Drawing;
                $drawing->setPath($localPath);
                $drawing->setHeight(60);
                if ($this->isDetailed) {
                    $drawing->setCoordinates('C1');
                    $drawing->setOffsetX(-40); // Try to center over 5 columns
                } else {
                    $drawing->setCoordinates('C1');
                    $drawing->setOffsetX(-85);
                }
                $drawing->setOffsetY(10);
                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }
}
