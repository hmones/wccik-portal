@extends('pdfs._layout', [
    'title' => 'WCCIK New Member Application',
    'brandBar' => 'New Member Application (Annex 1)',
])

@section('content')
    @php
        $v = fn ($value, string $fallback = '____________________') => $value !== null && $value !== '' ? e($value) : $fallback;
        $check = fn (bool $marked) => $marked ? '☒' : '☐';
        $classification = $application->company_classification?->value;
        $membershipClass = $application->membership_class?->value;
        $industry = $application->industry?->value;
    @endphp

    <h1>WCCIK Membership Form</h1>
    <div class="meta">To the Secretary General, WCCIK — Membership application for review</div>

    <p style="font-size: 9pt; margin: 2mm 0 3mm 0;">
        Dear Madam,<br>
        I/We hereby agree to abide the Memorandum and Articles of Association, bye-laws, rules,
        if my/our membership application is approved.
    </p>

    <h2>Class of Membership</h2>
    <div class="checkbox-row">
        <span class="opt {{ $membershipClass === 'corporate' ? 'selected' : '' }}">
            {{ $check($membershipClass === 'corporate') }} Corporate member
        </span>
        <span class="opt {{ $membershipClass === 'associate' ? 'selected' : '' }}">
            {{ $check($membershipClass === 'associate') }} Associate member
        </span>
    </div>

    <table class="definition-box">
        <tr class="defn-row">
            <td>
                <strong>Corporate member</strong> means a member of a trade organization which is either a body corporate, a multinational with office in Pakistan, a sales-tax registered manufacturer, or a sales-tax-registered business with turnover of Rs. 50 million or above.
            </td>
            <td>
                <strong>Associate member</strong> means a member of a trade organization which is not a body corporate or multinational, not a sales-tax registered manufacturer, and not a business with turnover of Rs. 50 million or above.
            </td>
        </tr>
    </table>

    <h2>Company &amp; Representative</h2>
    <table class="form-grid">
        <tr>
            <td class="label">Company / Firm Name</td>
            <td class="value">{{ $v($application->company_name) }}</td>
        </tr>
        <tr>
            <td class="label">Authorized Representative</td>
            <td class="value">{{ $v($application->authorized_representative_name) }}</td>
        </tr>
        <tr>
            <td class="label">Email</td>
            <td class="value">{{ $v($application->email) }}</td>
        </tr>
        <tr>
            <td class="label">Website</td>
            <td class="value">{{ $v($application->website) }}</td>
        </tr>
        <tr>
            <td class="label">Established Year</td>
            <td class="value">{{ $v($application->established_year) }}</td>
        </tr>
    </table>

    <h2>Industry</h2>
    <div class="checkbox-row">
        @foreach ([
            'trading' => 'Trading',
            'services' => 'Services',
            'manufacturing' => 'Manufacturing',
        ] as $key => $label)
            <span class="opt {{ $industry === $key ? 'selected' : '' }}">
                {{ $check($industry === $key) }} {{ $label }}
            </span>
        @endforeach
    </div>

    <h2>Business Status</h2>
    <div class="checkbox-row">
        @foreach ([
            'proprietorship' => 'Proprietorship',
            'partnership' => 'Partnership',
            'private_ltd' => 'Private Ltd. Co.',
            'public_ltd' => 'Public Ltd. Co.',
            'aop' => 'AOP',
        ] as $key => $label)
            <span class="opt {{ $classification === $key ? 'selected' : '' }}">
                {{ $check($classification === $key) }} {{ $label }}
            </span>
        @endforeach
    </div>

    <h2>Identity &amp; Tax</h2>
    <table class="form-grid">
        <tr>
            <td class="label">CNIC of Authorized Representative</td>
            <td class="value">{{ $v($application->cnic) }}</td>
        </tr>
        <tr>
            <td class="label">CNIC Expiry Date</td>
            <td class="value">{{ $v($application->cnic_expiry_date?->format('d M Y')) }}</td>
        </tr>
        <tr>
            <td class="label">Turnover (PKR)</td>
            <td class="value">{{ $v($application->turnover_pkr ? number_format((int) $application->turnover_pkr) : null) }}</td>
        </tr>
        <tr>
            <td class="label">No. of Employees</td>
            <td class="value">{{ $v($application->employees_count) }}</td>
        </tr>
        <tr>
            <td class="label">National Tax No. (NTN)</td>
            <td class="value">{{ $v($application->ntn_number) }}</td>
        </tr>
        <tr>
            <td class="label">Sales Tax No.</td>
            <td class="value">{{ $v($application->sales_tax_no) }}</td>
        </tr>
    </table>

    <h2>Address</h2>
    <table class="form-grid">
        <tr>
            <td class="label">Address (Karachi)</td>
            <td class="value multi">{{ $v($application->address) }}</td>
        </tr>
        <tr>
            <td class="label">Postal Code</td>
            <td class="value">{{ $v($application->postal_code) }}</td>
        </tr>
        <tr>
            <td class="label">District</td>
            <td class="value">{{ $v($application->district) }}</td>
        </tr>
    </table>

    <h2>Contact</h2>
    <table class="form-grid">
        <tr>
            <td class="label">Contact No.</td>
            <td class="value">{{ $v($application->phone) }}</td>
        </tr>
        <tr>
            <td class="label">Cell No.</td>
            <td class="value">{{ $v($application->cell) }}</td>
        </tr>
        <tr>
            <td class="label">WhatsApp No.</td>
            <td class="value">{{ $v($application->whatsapp) }}</td>
        </tr>
        <tr>
            <td class="label">Alternate No.</td>
            <td class="value">{{ $v($application->alternate_no) }}</td>
        </tr>
        <tr>
            <td class="label">Other Chamber / Association Membership</td>
            <td class="value multi">{{ $v($application->other_chamber_memberships) }}</td>
        </tr>
    </table>

    <div class="declaration">
        <strong>Declaration:</strong>
        (i) That the applicant is a sole Proprietor/Partner/Director/(General Manager of public limited company) and authorized representative to make the application.
        (ii) That the applicant&rsquo;s business fits within the defined business scope and jurisdiction of WCCIK and has valid NTN &amp; GST.
        (iii) That the prospective applicant has no criminal conviction.
    </div>

    <table class="signature-row">
        <tr>
            <td>
                <div class="signature-line">Signature of Authorized Representative (Proprietor / Partner / Director)</div>
            </td>
            <td>
                <div class="signature-line">Date &amp; Firm / Company stamp</div>
            </td>
        </tr>
    </table>

    <div class="notice">
        <strong>Fees:</strong> Admission Fee (one-time) Rs. 5,000 &middot; Membership Fee (annual) Rs. 1,000.
        Membership runs from April to March and expires on 31st March.
    </div>

    <div class="documents">
        <strong style="color:#1C4C81;">Required documents to submit with this application:</strong>
        <ol>
            <li>Copy of CNIC of authorized representative</li>
            <li>Two passport-size photographs</li>
            <li>Company profile</li>
            <li>Proof of latest filing of Income Tax return</li>
            <li>NTN certificate</li>
            <li>Membership fee payment slip / receipt &mdash; attach now, hand to the office with the physical form, or upload later via the portal once your documents are approved.</li>
            <li>Specimen signature form</li>
        </ol>
    </div>
@endsection
