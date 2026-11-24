<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $business->name }} - {{ $label }} Standee</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=Kantumruy+Pro:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* Top Action Bar (Hidden on Print) */
        .no-print-bar {
            width: 100%;
            max-width: 440px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 700;
            border-radius: 12px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #ea580c;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3);
        }
        .btn-primary:hover {
            background: #c2410c;
        }
        .btn-secondary {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #f8fafc;
        }

        /* Standee Card */
        .standee-card {
            background: #ffffff;
            border: 3px dashed #ea580c;
            border-radius: 28px;
            padding: 36px 28px;
            text-align: center;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
        }

        .brand-badge {
            display: inline-block;
            background: #ffedd5;
            color: #c2410c;
            font-weight: 800;
            font-size: 12px;
            letter-spacing: 1.5px;
            padding: 6px 16px;
            border-radius: 9999px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }

        .logo-wrap {
            width: 60px;
            height: 60px;
            margin: 0 auto 12px;
            border-radius: 16px;
            overflow: hidden;
            background: #ffedd5;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        }
        .logo-wrap img {
            width: 100%;
            height: 100%;
            object-cover: cover;
        }
        .logo-wrap span {
            font-size: 24px;
            font-weight: 900;
            color: #ea580c;
        }

        h1 {
            font-size: 24px;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .label-badge {
            display: inline-block;
            font-size: 20px;
            font-weight: 800;
            color: #ea580c;
            margin-bottom: 16px;
        }

        .qr-frame {
            background: #ffffff;
            display: inline-block;
            padding: 16px;
            border: 2px solid #f1f5f9;
            border-radius: 22px;
            margin-bottom: 18px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        }
        .qr-frame svg {
            width: 220px !important;
            height: 220px !important;
            display: block;
        }

        .instructions {
            font-size: 13px;
            color: #334155;
            font-weight: 700;
            line-height: 1.6;
            margin-bottom: 12px;
        }
        .instructions-km {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 2px;
        }

        .footer-tag {
            font-size: 10px;
            color: #94a3b8;
            letter-spacing: 1.2px;
            font-weight: 700;
            text-transform: uppercase;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-top: 10px;
        }

        @media print {
            @page {
                size: A5 portrait;
                margin: 0;
            }
            body {
                background: #ffffff !important;
                padding: 0 !important;
                height: 100vh;
                justify-content: center;
            }
            .no-print-bar {
                display: none !important;
            }
            .standee-card {
                box-shadow: none !important;
                border: 3px dashed #ea580c !important;
                max-width: 90% !important;
                margin: auto !important;
            }
        }
    </style>
</head>
<body>

    <!-- Controls Bar (Hidden when printing or saving as PDF) -->
    <div class="no-print-bar">
        <a href="{{ route('business.tables.index') }}" class="btn btn-secondary">
            ← Back to Tables
        </a>
        <button type="button" onclick="window.print()" class="btn btn-primary">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <!-- Printable Standee Card -->
    <div class="standee-card">
        @if ($business->logo_url)
            <div class="logo-wrap">
                <img src="{{ $business->logo_url }}" alt="{{ $business->name }}">
            </div>
        @else
            <div class="logo-wrap">
                <span>{{ strtoupper(substr($business->name, 0, 1)) }}</span>
            </div>
        @endif

        <div class="brand-badge">{{ $business->name }}</div>
        <div>
            <span class="label-badge">{{ $label }}</span>
        </div>

        <div class="qr-frame">
            @php
                try {
                    $standeeQrSvg = SimpleSoftwareIO\QrCode\Facades\QrCode::encoding('UTF-8')->size(220)->color(15, 23, 42)->generate($targetUrl);
                } catch (\Throwable $e) {
                    $standeeQrSvg = null;
                }
            @endphp
            @if($standeeQrSvg)
                {!! $standeeQrSvg !!}
            @else
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data={{ urlencode($targetUrl) }}" alt="QR" style="width: 220px; height: 220px; margin: 0 auto; display: block;">
            @endif
        </div>

        <div class="instructions">
            <div>📱 Scan with phone camera to view menu &amp; order</div>
            <div class="instructions-km">ស្កេនជាមួយទូរស័ព្ទដើម្បីមើលម៉ឺនុយ និងកុម្ម៉ង់</div>
        </div>

        <div class="footer-tag">
            SABAYRA CAMBODIA • SMART CONTACTLESS MENU
        </div>
    </div>

    <script>
        // Automatically open the native Print dialog (which allows "Save as PDF")
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
