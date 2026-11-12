<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Client Code</label>
        <input type="text" name="client_code" value="{{ old('client_code', $client?->client_code ?? $nextCode) }}" class="form-control">
    </div>
    <div class="col-md-5">
        <label class="form-label small fw-semibold">Client / Entity Name *</label>
        <input type="text" name="name" value="{{ old('name', $client?->name) }}" class="form-control" required>
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Category *</label>
        <select name="category" class="form-select" required>
            @foreach(\App\Models\Client::CATEGORIES as $k => $v)
                <option value="{{ $k }}" @selected(old('category', $client?->category ?? 'business') === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Status *</label>
        <select name="status" class="form-select" required>
            @foreach(\App\Models\Client::STATUSES as $k => $v)
                <option value="{{ $k }}" @selected(old('status', $client?->status ?? 'active') === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="email" value="{{ old('email', $client?->email) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Mobile Number</label>
        <input type="text" name="mobile" value="{{ old('mobile', $client?->mobile) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Registration Date</label>
        <input type="date" name="registration_date" value="{{ old('registration_date', $client?->registration_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="form-control">
    </div>

    <div class="col-md-3">
        <label class="form-label small fw-semibold">PAN</label>
        <input type="text" name="pan" value="{{ old('pan', $client?->pan) }}" class="form-control text-uppercase" placeholder="ABCDE1234F">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">GSTIN</label>
        <input type="text" name="gstin" value="{{ old('gstin', $client?->gstin) }}" class="form-control text-uppercase" placeholder="27ABCDE1234F1Z5">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">TAN</label>
        <input type="text" name="tan" value="{{ old('tan', $client?->tan) }}" class="form-control text-uppercase">
    </div>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">CIN</label>
        <input type="text" name="cin" value="{{ old('cin', $client?->cin) }}" class="form-control text-uppercase">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Business Type</label>
        <input type="text" name="business_type" value="{{ old('business_type', $client?->business_type) }}" class="form-control" placeholder="Private Limited, LLP, Proprietorship">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Industry</label>
        <input type="text" name="industry" value="{{ old('industry', $client?->industry) }}" class="form-control" placeholder="Manufacturing, SaaS, Retail...">
    </div>
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Assigned CA / Relationship Manager</label>
        <select name="assigned_ca_id" class="form-select">
            <option value="">-- Select CA / Manager --</option>
            @foreach($staff as $member)
                <option value="{{ $member->id }}" @selected((string) old('assigned_ca_id', $client?->assigned_ca_id) === (string) $member->id)>
                    {{ $member->name }} ({{ $member->roleLabel() }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Address</label>
        <input type="text" name="address" value="{{ old('address', $client?->address) }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">City</label>
        <input type="text" name="city" value="{{ old('city', $client?->city) }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">State</label>
        <input type="text" name="state" value="{{ old('state', $client?->state) }}" class="form-control">
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Pincode</label>
        <input type="text" name="pincode" value="{{ old('pincode', $client?->pincode) }}" class="form-control">
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Additional Assigned Team Members</label>
        @php($selectedStaff = old('staff_ids', $client?->staff->pluck('id')->toArray() ?? []))
        <select name="staff_ids[]" class="form-select" multiple size="3">
            @foreach($staff as $member)
                <option value="{{ $member->id }}" @selected(in_array($member->id, $selectedStaff))>{{ $member->name }} ({{ $member->roleLabel() }})</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Compliance Applicability</label>
        <div class="border rounded p-2 d-flex flex-wrap gap-3 bg-light">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_gst_registered" value="1" id="chkGst" @checked(old('is_gst_registered', $client?->is_gst_registered ?? false))>
                <label class="form-check-label small" for="chkGst">GST Registered</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_itr_applicable" value="1" id="chkItr" @checked(old('is_itr_applicable', $client?->is_itr_applicable ?? true))>
                <label class="form-check-label small" for="chkItr">ITR Applicable</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_tds_applicable" value="1" id="chkTds" @checked(old('is_tds_applicable', $client?->is_tds_applicable ?? false))>
                <label class="form-check-label small" for="chkTds">TDS Applicable</label>
            </div>
        </div>
        @if(!$client?->user_id)
            <div class="mt-2 border rounded p-2 bg-light">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="create_portal_login" value="1" id="chkPortal">
                    <label class="form-check-label small fw-semibold" for="chkPortal">Create Client Portal Login (uses client email)</label>
                </div>
                <input type="text" name="portal_password" class="form-control form-control-sm mt-1" placeholder="Initial portal password (default: Client@123)">
            </div>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Internal Notes</label>
        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $client?->notes) }}</textarea>
    </div>
</div>

