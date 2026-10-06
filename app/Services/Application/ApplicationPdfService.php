<?php

namespace App\Services\Application;

use App\Enums\ApplicationType;
use App\Models\Application;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Renders a filled WCCIK application form as a PDF.
 *
 * The visual layout is a HTML/CSS recreation (not a byte-identical overlay of
 * the original printed form). This keeps the templates maintainable — layout
 * changes are a Blade edit, not a hand-measured coordinate shuffle.
 */
class ApplicationPdfService
{
    public function pdfBytesFor(Application $application, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $view = $application->type === ApplicationType::Renewal
            ? 'pdfs.renewal-application'
            : 'pdfs.new-member-application';

        $html = view($view, [
            'application' => $application,
            'locale' => $locale,
        ])->render();

        $pdf = $this->makeMpdf($locale);
        $pdf->WriteHTML($html);

        return $pdf->Output('', 'S');
    }

    public function filename(Application $application): string
    {
        $kind = $application->type === ApplicationType::Renewal ? 'renewal' : 'new-member';
        $slug = str($application->company_name ?? 'application')
            ->slug()
            ->toString();

        return "wccik-{$kind}-{$slug}-{$application->id}.pdf";
    }

    private function makeMpdf(string $locale): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $defaultFontConfig = (new FontVariables)->getDefaults();

        return new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_left' => 15,
            'margin_right' => 15,
            'default_font' => 'dejavusans',
            'fontDir' => array_merge($defaultConfig['fontDir'], []),
            'fontdata' => $defaultFontConfig['fontdata'],
            'tempDir' => storage_path('app/mpdf-tmp'),
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);
    }
}
