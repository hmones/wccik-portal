<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Application\ApplicationPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ApplicationPdfController extends Controller
{
    public function __invoke(Request $request, ApplicationPdfService $pdfs): Response
    {
        $applicant = Auth::guard('applicant')->user();

        $application = $applicant->applications()->latest('id')->first();

        abort_if($application === null, 404);

        return response(
            $pdfs->pdfBytesFor($application),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$pdfs->filename($application).'"',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
