@php
    /** @var \App\Models\Application $application */
    /** @var string $locale */
    use App\Enums\CompanyClassification;
    use App\Enums\Industry;
    use App\Enums\MembershipClass;

    $v = function ($value, string $fallback = '____________________') {
        return $value !== null && $value !== '' ? e($value) : $fallback;
    };
    $check = function (bool $marked) {
        return $marked ? '☒' : '☐';
    };
    $logoPath = public_path('images/wccik-logo.png');
    $logo = file_exists($logoPath) ? $logoPath : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'WCCIK Application' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: dejavusans, sans-serif;
            color: #0A0A0A;
            font-size: 10pt;
            line-height: 1.35;
        }
        h1 {
            color: #1C4C81;
            font-size: 15pt;
            margin: 0 0 2mm 0;
            text-align: center;
            letter-spacing: 0.5pt;
        }
        h2 {
            color: #1C4C81;
            font-size: 11pt;
            margin: 5mm 0 2mm 0;
            border-bottom: 0.5pt solid #1C4C81;
            padding-bottom: 1mm;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        .meta {
            color: #52525b;
            font-size: 8pt;
            text-align: center;
            margin-bottom: 4mm;
        }
        .brand-bar {
            background: #1C4C81;
            color: #ffffff;
            padding: 3mm 4mm;
            margin-bottom: 4mm;
            font-size: 8pt;
            letter-spacing: 1pt;
            text-transform: uppercase;
        }
        .brand-bar .dot {
            display: inline-block;
            width: 2mm;
            height: 2mm;
            background: #00FF00;
            border-radius: 50%;
            margin-right: 2mm;
            vertical-align: middle;
        }
        .header-row { width: 100%; }
        .header-row .logo { width: 25mm; }
        .header-row .org-title {
            color: #1C4C81;
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
        }
        .header-row .org-addr {
            color: #52525b;
            font-size: 8pt;
            text-align: center;
        }
        table.form-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        table.form-grid td {
            padding: 1.5mm 2mm;
            vertical-align: top;
            border-bottom: 0.3pt solid #e4e4e7;
        }
        table.form-grid td.label {
            color: #52525b;
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            white-space: nowrap;
            width: 42%;
        }
        table.form-grid td.value {
            color: #0A0A0A;
            font-size: 10pt;
            font-weight: bold;
        }
        table.form-grid td.value.multi {
            white-space: pre-wrap;
        }
        .checkbox-row {
            margin: 2mm 0;
            font-size: 10pt;
        }
        .checkbox-row .opt {
            margin-right: 6mm;
            display: inline-block;
            white-space: nowrap;
        }
        .checkbox-row .opt.selected { color: #1C4C81; font-weight: bold; }
        .definition-box {
            border: 0.5pt solid #d4d4d8;
            padding: 2mm 3mm;
            font-size: 8pt;
            color: #52525b;
            margin: 2mm 0 4mm 0;
        }
        .definition-box .defn-row td {
            width: 50%;
            padding: 0 2mm;
            vertical-align: top;
        }
        .declaration {
            font-size: 8pt;
            color: #27272a;
            border-top: 0.5pt solid #d4d4d8;
            padding-top: 2mm;
            margin-top: 4mm;
            line-height: 1.4;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            color: #a1a1aa;
            font-size: 7pt;
            text-align: center;
            padding: 2mm;
            border-top: 0.3pt solid #e4e4e7;
        }
        .signature-row { margin-top: 6mm; }
        .signature-row td {
            width: 50%;
            padding: 1mm 2mm;
            vertical-align: top;
        }
        .signature-line {
            border-top: 0.5pt solid #0A0A0A;
            padding-top: 1mm;
            font-size: 8pt;
            color: #52525b;
            text-align: center;
        }
        .documents {
            background: #f4f4f5;
            border: 0.3pt solid #e4e4e7;
            padding: 3mm 4mm;
            margin-top: 6mm;
        }
        .documents ol {
            margin: 1mm 0;
            padding-left: 5mm;
            font-size: 9pt;
        }
        .documents li {
            margin-bottom: 1mm;
        }
        .notice {
            background: #fff7ed;
            border-left: 1.5mm solid #ea580c;
            padding: 3mm 4mm;
            margin-top: 4mm;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <table class="header-row">
        <tr>
            <td class="logo">
                @if($logo)
                    <img src="{{ $logo }}" width="90" alt="WCCIK logo">
                @endif
            </td>
            <td>
                <div class="org-title">WOMEN CHAMBER OF COMMERCE AND INDUSTRY KORANGI</div>
                <div class="org-addr">
                    A/10, 1st Floor, Cause Way Apartment, Causeway Belt, Plot no. L-194, Sector 6A,<br>
                    Mehran Town Korangi Industrial Area, Karachi &middot;
                    <strong>Phone:</strong> (+92) 329&nbsp;226&nbsp;4691 &middot;
                    <strong>Email:</strong> info@wccik.org.pk &middot;
                    <strong>Web:</strong> www.wccik.org.pk
                </div>
            </td>
        </tr>
    </table>

    <div class="brand-bar">
        <span class="dot"></span>
        {{ $brandBar ?? 'WCCIK Membership Application' }}
    </div>

    {{ $slot ?? '' }}
    @yield('content')

    <div class="footer">
        Generated by the WCCIK Membership Portal &middot; Application #{{ $application->id }}
        @if($application->submitted_at)
            &middot; Submitted {{ $application->submitted_at->format('d M Y, H:i') }}
        @endif
    </div>
</body>
</html>
