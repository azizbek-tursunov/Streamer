<?php

namespace App\Exports;

use App\Models\LessonFeedback;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FeedbackExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    private int $row = 1;

    public function __construct(private Builder $query) {}

    public function query()
    {
        return $this->query->with(['user', 'auditorium']);
    }

    public function headings(): array
    {
        return [
            '#',
            'Sana',
            'Bino',
            'Auditoriya',
            'Fan',
            "O'qituvchi",
            'Guruh',
            'Vaqt',
            'Holati',
            'Mulohaza',
            'Kiritdi',
            'Rasm',
        ];
    }

    public function map($feedback): array
    {
        $snapshotUrl = $feedback->snapshot_path
            ? url('storage/'.$feedback->snapshot_path)
            : '';

        return [
            $this->row++,
            $feedback->created_at->format('d.m.Y H:i'),
            $feedback->auditorium?->building_name ?? '',
            $feedback->auditorium?->name ?? '',
            $feedback->lesson_name ?? '',
            $feedback->employee_name ?? '',
            $feedback->group_name ?? '',
            ($feedback->start_time ? substr($feedback->start_time, 0, 5) : '?').' - '.($feedback->end_time ? substr($feedback->end_time, 0, 5) : '?'),
            $feedback->type === 'good' ? 'Ijobiy' : 'Salbiy',
            $feedback->message ?? '',
            $feedback->user?->name ?? '',
            $snapshotUrl,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Bold header row
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);

        // Auto-size columns
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Make snapshot URLs clickable
        $lastRow = $sheet->getHighestRow();
        for ($i = 2; $i <= $lastRow; $i++) {
            $url = $sheet->getCell("L{$i}")->getValue();
            if ($url) {
                $sheet->getCell("L{$i}")->getHyperlink()->setUrl($url);
                $sheet->getStyle("L{$i}")->getFont()->setColor(
                    new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE)
                )->setUnderline(true);
            }
        }

        return [];
    }
}
