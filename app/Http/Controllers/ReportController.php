<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService) {}

    public function index(Request $request): View
    {
        $type = $request->query('type', 'client_compliance');
        if (! array_key_exists($type, ReportService::TYPES)) {
            $type = 'client_compliance';
        }
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $report = $this->reportService->build($type, $fromDate, $toDate);
        $reportTypes = ReportService::TYPES;

        return view('reports.index', compact('type', 'fromDate', 'toDate', 'report', 'reportTypes'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $type = $request->query('type', 'client_compliance');
        if (! array_key_exists($type, ReportService::TYPES)) {
            $type = 'client_compliance';
        }

        return $this->reportService->streamCsv(
            $type,
            $request->query('from_date'),
            $request->query('to_date')
        );
    }
}

