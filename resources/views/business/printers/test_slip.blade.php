<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Print - {{ $printer->name }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 8px 12px;
            max-width: 80mm;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .double-divider { border-top: 2px dashed #000; margin: 8px 0; }
        .no-print {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
            padding: 8px;
            background: #f1f5f9;
            border-radius: 6px;
            justify-content: center;
        }
        .btn-print {
            padding: 6px 14px;
            font-weight: bold;
            cursor: pointer;
            background: #f97316;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 4px 6px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">
            🖨️ Print Test Slip
        </button>
        <button onclick="window.close()" style="padding: 6px 14px; cursor: pointer; border: none; border-radius: 4px;">
            Close
        </button>
    </div>

    <div class="text-center">
        <h2 style="margin: 0 0 4px; font-size: 16px; font-weight: bold;">SABAYRA POS</h2>
        <div style="font-size: 13px; font-weight: bold;">{{ $business->name }}</div>
        <div style="font-size: 11px; margin-top: 2px;">PRINTER SELF-TEST SLIP</div>
    </div>

    <div class="double-divider"></div>

    <table style="width: 100%; font-size: 11px;">
        <tr>
            <td style="font-weight: bold; width: 45%;">PRINTER NAME:</td>
            <td>{{ $printer->name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">PRINTER TYPE:</td>
            <td style="text-transform: uppercase;">{{ str_replace('_', ' ', $printer->printer_type ?? 'receipt') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">CONNECTION:</td>
            <td style="text-transform: uppercase;">{{ $printer->connection_type }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">PAPER WIDTH:</td>
            <td>{{ $printer->paper_size }}</td>
        </tr>
        @if ($printer->ip_address)
            <tr>
                <td style="font-weight: bold;">IP / PORT:</td>
                <td>{{ $printer->ip_address }}:{{ $printer->port }}</td>
            </tr>
        @endif
        <tr>
            <td style="font-weight: bold;">BRANCH:</td>
            <td>{{ $printer->branch ? $printer->branch->name : 'All Outlets' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">PURPOSE:</td>
            <td style="text-transform: uppercase;">{{ str_replace('_', ' ', $printer->printing_purpose ?? 'both') }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">TEST DATE/TIME:</td>
            <td>{{ now()->format('d/m/Y H:i:s') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div style="font-size: 10px; line-height: 1.4;">
        <div>CHARACTERS TEST:</div>
        <div>ABCDEFGHIJKLMNOPQRSTUVWXYZ</div>
        <div>abcdefghijklmnopqrstuvwxyz</div>
        <div>0123456789 !@#$%^&*()_+</div>
        <div style="margin-top: 4px;">KHMER TEST:</div>
        <div>សួស្តីកម្ពុជា - ប្រព័ន្ធគ្រប់គ្រងការលក់ SABAYRA</div>
    </div>

    <div class="double-divider"></div>

    <div class="text-center" style="font-size: 10px;">
        *** PRINTER TEST SUCCESSFUL ***<br>
        SABAYRA POS &bull; www.sabayra.com
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            if (new URLSearchParams(window.location.search).get('auto') === '1') {
                window.print();
            }
        });
    </script>
</body>
</html>
