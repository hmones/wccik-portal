<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\Application\ApplicationPdfService;
use Illuminate\Http\Response;

class ApplicationPdfController extends Controller
{
    public function __invoke(Application $application, ApplicationPdfService $pdfs): Response
    {
        return response(
            $pdfs->pdfBytesFor($application),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$pdfs->filename($application).'"',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
