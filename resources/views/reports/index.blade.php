@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: #1e3a5f;">Practice Reports &amp; Exports</h1>
        <p class="text-muted small mb-0">Filter compliance, productivity, revenue, and document collection datasets and export directly to Excel/CSV.</p>
    </div>
    <a href="{{ route('reports.export_csv', ['type' => $type, 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="btn btn-sm btn-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV / Excel
    </a>
</div>

<div class="ca-card p-3 mb-3">
    <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small text-muted mb-1">Report Type</label>
            <select name="type" class="form-select form-select-sm">
                @foreach($reportTypes as $k => $label)
                    <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">From Date</label>
            <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">To Date</label>
            <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Generate Report</button>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="ca-card overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold mb-0">{{ $reportTypes[$type] }}</h2>
        <span class="badge badge-soft-primary">{{ count($report['rows']) }} Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    @foreach($report['headers'] as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td class="small">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['headers']) }}" class="text-center text-muted py-4">No records found for this report criteria.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

