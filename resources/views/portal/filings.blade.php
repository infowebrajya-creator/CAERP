@extends('layouts.portal')

@section('title', 'Filings & Deadlines')

@section('content')
<h1 class="h4 fw-bold mb-3" style="color: #1e3a5f;">GST, ITR, TDS Filing Status &amp; Deadlines</h1>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">GST Returns</h2>
            <table class="table table-sm mb-0">
                <thead><tr><th>Return</th><th>Period</th><th>Due Date</th><th>Status</th><th>ARN</th></tr></thead>
                <tbody>
                    @forelse($gstReturns as $gst)
                        <tr><td>{{ $gst->return_type_code }}</td><td>{{ $gst->tax_period }}</td><td>{{ $gst->due_date->format('d M Y') }}</td><td><x-status-badge :status="$gst->status" :label="$gst->statusLabel()" /></td><td class="small">{{ $gst->arn ?? '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No GST returns found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">ITR Filings</h2>
            <table class="table table-sm mb-0">
                <thead><tr><th>AY</th><th>Form</th><th>Status</th><th>Ack No.</th><th>Refund</th></tr></thead>
                <tbody>
                    @forelse($itrFilings as $itr)
                        <tr><td>{{ $itr->assessment_year }}</td><td>{{ $itr->return_type }}</td><td><x-status-badge :status="$itr->filing_status" :label="$itr->filingStatusLabel()" /></td><td class="small">{{ $itr->acknowledgement_number ?? '—' }}</td><td class="small">{{ $itr->refundStatusLabel() }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No ITR filings found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="ca-card p-3">
            <h2 class="h6 fw-bold mb-3">Upcoming Compliance Deadlines</h2>
            <table class="table table-sm mb-0">
                <thead><tr><th>Deadline</th><th>Type</th><th>Due Date</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($deadlines as $dl)
                        <tr><td class="fw-medium">{{ $dl->title }}</td><td>{{ $dl->typeLabel() }}</td><td>{{ $dl->due_date->format('d M Y') }}</td><td><x-status-badge :status="$dl->status" :label="$dl->statusLabel()" /></td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center py-3">No deadlines scheduled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

