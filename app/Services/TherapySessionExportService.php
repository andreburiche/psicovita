<?php

namespace App\Services;

use App\Models\ScheduleBlock;
use App\Models\TherapySession;
use App\Support\PortalBrand;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TherapySessionExportService
{
    /**
     * @param  array{
     *     source: string,
     *     title: string,
     *     professional: \App\Models\User,
     *     month: \Carbon\Carbon,
     *     filters: array,
     *     stats: array,
     *     sessions: \Illuminate\Support\Collection,
     *     blocks: \Illuminate\Support\Collection,
     *     generated_at: \Carbon\Carbon,
     * }  $context
     */
    public function downloadPdf(array $context): Response
    {
        return PdfFacade::loadView('exports.pdf.sessions-schedule-report', [
            'context' => $context,
            'logoDataUri' => PortalBrand::logoDataUri(),
            'appName' => PortalBrand::appName(),
        ])
            ->setPaper('a4', 'portrait')
            ->download($this->filename($context, 'pdf'));
    }

    /**
     * @param  array{
     *     source: string,
     *     title: string,
     *     professional: \App\Models\User,
     *     month: \Carbon\Carbon,
     *     filters: array,
     *     stats: array,
     *     sessions: \Illuminate\Support\Collection,
     *     blocks: \Illuminate\Support\Collection,
     *     generated_at: \Carbon\Carbon,
     * }  $context
     */
    public function downloadExcel(array $context): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($context);
        $filename = $this->filename($context, 'xlsx');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{source: string, month: \Carbon\Carbon}  $context
     */
    private function filename(array $context, string $extension): string
    {
        $prefix = $context['source'] === 'schedule' ? 'agenda' : 'sessoes';

        return sprintf('%s-%s.%s', $prefix, $context['month']->format('Y-m'), $extension);
    }

    /**
     * @param  array{
     *     title: string,
     *     professional: \App\Models\User,
     *     stats: array,
     *     sessions: \Illuminate\Support\Collection,
     *     blocks: \Illuminate\Support\Collection,
     *     generated_at: \Carbon\Carbon,
     * }  $context
     */
    private function buildSpreadsheet(array $context): Spreadsheet
    {
        $sheet = new Spreadsheet;
        $active = $sheet->getActiveSheet();
        $active->setTitle(__('Relatório'));

        $headerFont = ['bold' => true, 'color' => ['rgb' => 'FFFFFF']];
        $headerFill = [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '7C3AED'],
        ];

        $active->setCellValue('A1', PortalBrand::appName());
        $active->mergeCells('A1:F1');
        $active->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $active->getRowDimension(1)->setRowHeight(28);

        $active->setCellValue('A2', $context['title']);
        $active->mergeCells('A2:F2');
        $active->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
        ]);

        $active->setCellValue('A3', __('Profissional').': '.$context['professional']->name);
        $active->setCellValue('D3', __('Período').': '.$context['stats']['period_label']);
        $active->setCellValue('A4', __('Gerado em').': '.$context['generated_at']->format('d/m/Y H:i'));
        $active->mergeCells('A3:C3');
        $active->mergeCells('D3:F3');
        $active->mergeCells('A4:F4');
        $active->getStyle('A3:F4')->getFont()->setSize(10)->getColor()->setRGB('475569');

        $row = 6;
        $statLabels = [
            __('Total') => $context['stats']['total'],
            __('Agendadas') => $context['stats']['scheduled'],
            __('Concluídas') => $context['stats']['completed'],
            __('Canceladas') => $context['stats']['cancelled'],
            __('Online') => $context['stats']['online'],
            __('Presencial') => $context['stats']['in_person'],
            __('Pacientes') => $context['stats']['unique_patients'],
            __('Bloqueios') => $context['stats']['blocks'],
        ];

        $col = 'A';
        foreach ($statLabels as $label => $value) {
            $active->setCellValue($col.$row, $label);
            $active->setCellValue($col.($row + 1), $value);
            $active->getStyle($col.$row)->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '64748B']],
            ]);
            $active->getStyle($col.($row + 1))->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '4F46E5']],
            ]);
            $col++;
        }

        $row += 4;
        $active->setCellValue('A'.$row, __('Sessões'));
        $active->mergeCells('A'.$row.':F'.$row);
        $active->getStyle('A'.$row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
        ]);
        $row++;

        $sessionHeaders = [__('Data'), __('Horário'), __('Paciente'), __('Status'), __('Tipo'), __('Notas')];
        $active->fromArray($sessionHeaders, null, 'A'.$row);
        $active->getStyle('A'.$row.':F'.$row)->applyFromArray([
            'font' => $headerFont,
            'fill' => $headerFill,
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        $row++;

        /** @var TherapySession $session */
        foreach ($context['sessions'] as $session) {
            $active->fromArray([
                $session->session_date->format('d/m/Y'),
                $this->formatTime($session->session_time),
                $session->displayLabel(),
                $session->status->label(),
                $session->type->label(),
                $session->notes ?? '—',
            ], null, 'A'.$row);
            $row++;
        }

        if ($context['sessions']->isEmpty()) {
            $active->setCellValue('A'.$row, __('Nenhuma sessão no período.'));
            $active->mergeCells('A'.$row.':F'.$row);
            $row++;
        }

        $row += 1;
        $active->setCellValue('A'.$row, __('Bloqueios de agenda'));
        $active->mergeCells('A'.$row.':D'.$row);
        $active->getStyle('A'.$row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
        ]);
        $row++;

        $blockHeaders = [__('Data'), __('Início'), __('Fim'), __('Motivo')];
        $active->fromArray($blockHeaders, null, 'A'.$row);
        $active->getStyle('A'.$row.':D'.$row)->applyFromArray([
            'font' => $headerFont,
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ]);
        $row++;

        /** @var ScheduleBlock $block */
        foreach ($context['blocks'] as $block) {
            $active->fromArray([
                $block->block_date->format('d/m/Y'),
                substr((string) $block->start_time, 0, 5),
                substr((string) $block->end_time, 0, 5),
                $block->reason ?? '—',
            ], null, 'A'.$row);
            $row++;
        }

        if ($context['blocks']->isEmpty()) {
            $active->setCellValue('A'.$row, __('Sem bloqueios no período.'));
            $active->mergeCells('A'.$row.':D'.$row);
        }

        foreach (range('A', 'F') as $column) {
            $active->getColumnDimension($column)->setAutoSize(true);
        }

        $active->getStyle('A1:F'.max($row, 8))->getAlignment()->setWrapText(true);

        return $sheet;
    }

    private function formatTime(mixed $time): string
    {
        if (is_string($time)) {
            return substr($time, 0, 5);
        }

        return $time->format('H:i');
    }
}
