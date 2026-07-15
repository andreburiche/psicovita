<?php

namespace App\Services\Exports;

use App\Support\PortalBrand;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TabularListExportService
{
    /**
     * @param  array{
     *   title: string,
     *   filename_prefix: string,
     *   professional_name: string,
     *   subtitle?: string|null,
     *   filter_summary?: list<string>,
     *   columns: list<string>,
     *   rows: list<list<string|int|float|null>>,
     *   generated_at: \Carbon\CarbonInterface,
     * }  $context
     */
    public function downloadPdf(array $context): Response
    {
        return PdfFacade::loadView('exports.pdf.tabular-list', [
            'context' => $context,
            'logoDataUri' => PortalBrand::logoDataUri(),
            'appName' => PortalBrand::appName(),
        ])
            ->setPaper('a4', 'landscape')
            ->download($this->filename($context, 'pdf'));
    }

    /**
     * @param  array{
     *   title: string,
     *   filename_prefix: string,
     *   professional_name: string,
     *   subtitle?: string|null,
     *   filter_summary?: list<string>,
     *   columns: list<string>,
     *   rows: list<list<string|int|float|null>>,
     *   generated_at: \Carbon\CarbonInterface,
     * }  $context
     */
    public function downloadExcel(array $context): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($context);
        $filename = $this->filename($context, 'xlsx');

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{filename_prefix: string}  $context
     */
    private function filename(array $context, string $extension): string
    {
        return sprintf(
            '%s-%s.%s',
            $context['filename_prefix'],
            now()->format('Y-m-d-His'),
            $extension,
        );
    }

    /**
     * @param  array{
     *   title: string,
     *   professional_name: string,
     *   subtitle?: string|null,
     *   filter_summary?: list<string>,
     *   columns: list<string>,
     *   rows: list<list<string|int|float|null>>,
     *   generated_at: \Carbon\CarbonInterface,
     * }  $context
     */
    private function buildSpreadsheet(array $context): Spreadsheet
    {
        $sheet = new Spreadsheet;
        $active = $sheet->getActiveSheet();
        $active->setTitle(__('Dados'));

        $colCount = max(1, count($context['columns']));
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        $active->setCellValue('A1', PortalBrand::appName());
        $active->mergeCells('A1:'.$lastCol.'1');
        $active->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        ]);

        $active->setCellValue('A2', $context['title']);
        $active->mergeCells('A2:'.$lastCol.'2');
        $active->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
        ]);

        $active->setCellValue('A3', __('Profissional').': '.$context['professional_name']);
        $active->setCellValue('A4', __('Gerado em').': '.$context['generated_at']->format('d/m/Y H:i'));
        $active->mergeCells('A3:'.$lastCol.'3');
        $active->mergeCells('A4:'.$lastCol.'4');

        $row = 5;
        if (filled($context['subtitle'] ?? null)) {
            $active->setCellValue('A'.$row, (string) $context['subtitle']);
            $active->mergeCells('A'.$row.':'.$lastCol.$row);
            $row++;
        }

        foreach ($context['filter_summary'] ?? [] as $filterLine) {
            $active->setCellValue('A'.$row, $filterLine);
            $active->mergeCells('A'.$row.':'.$lastCol.$row);
            $row++;
        }

        $row++;
        $active->fromArray($context['columns'], null, 'A'.$row);
        $active->getStyle('A'.$row.':'.$lastCol.$row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $row++;

        if ($context['rows'] === []) {
            $active->setCellValue('A'.$row, __('Nenhum registo para exportar.'));
            $active->mergeCells('A'.$row.':'.$lastCol.$row);
        } else {
            foreach ($context['rows'] as $dataRow) {
                $active->fromArray(array_map(fn ($v) => $v ?? '—', $dataRow), null, 'A'.$row);
                $row++;
            }
        }

        for ($i = 1; $i <= $colCount; $i++) {
            $active->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        return $sheet;
    }
}
