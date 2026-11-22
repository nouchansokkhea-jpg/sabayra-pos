<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Receipt') }} - {{ $order->order_number }}</title>
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
            padding: 10px 14px;
            max-width: 80mm;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 3px 0;
            vertical-align: top;
        }
        .no-print {
            display: block;
            margin-bottom: 12px;
            padding: 8px;
            background: #f1f5f9;
            text-align: center;
            border-radius: 6px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 4px; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 6px 14px; font-weight: bold; cursor: pointer; background: #059669; color: #fff; border: none; border-radius: 4px;">
            🖨️ {{ __('Print Receipt (80mm)') }}
        </button>
    </div>

    <div class="text-center">
        <h2 style="margin: 0 0 4px; font-size: 16px;" class="font-bold">{{ $business->name }}</h2>
        @if($business->address)
            <div style="font-size: 11px;">{{ $business->address }}</div>
        @endif
        @if($business->phone)
            <div style="font-size: 11px;">Tel: {{ $business->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <div>
        <div><strong>{{ __('Order #') }}:</strong> {{ $order->order_number }}</div>
        <div><strong>{{ __('Date') }}:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</div>
        <div><strong>{{ __('Type') }}:</strong>
            @if($order->order_type === 'dine_in')
                Dine-In (Table #{{ $order->table?->table_number ?? 'Dine-In' }})
            @elseif($order->order_type === 'takeaway')
                Takeaway / Pickup
            @else
                Delivery
            @endif
        </div>
        <div><strong>{{ __('Customer') }}:</strong> {{ $order->customer_name }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr style="border-bottom: 1px solid #000;">
                <th class="text-left font-bold" style="width: 50%;">Item</th>
                <th class="text-center font-bold" style="width: 15%;">Qty</th>
                <th class="text-right font-bold" style="width: 35%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>
                        <div class="font-bold">{{ $item->product_name }}</div>
                        @if($item->variant_name)
                            <div style="font-size: 10px;">({{ $item->variant_name }})</div>
                        @endif
                        @foreach($item->addons as $addon)
                            <div style="font-size: 10px;">+ {{ $addon->name }}</div>
                        @endforeach
                    </td>
                    <td class="text-center font-bold">{{ (int)$item->quantity }}</td>
                    <td class="text-right font-bold">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="double-divider"></div>

    <table>
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
        </tr>
        @if($order->discount_amount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-${{ number_format($order->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if($order->tax_amount > 0)
            <tr>
                <td>Tax ({{ $business->tax_percent }}%):</td>
                <td class="text-right">${{ number_format($order->tax_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="font-bold" style="font-size: 14px;">
            <td style="padding-top: 6px;">GRAND TOTAL:</td>
            <td class="text-right" style="padding-top: 6px;">${{ number_format($order->total_amount, 2) }}</td>
        </tr>
        <tr class="font-bold" style="font-size: 13px;">
            <td>Total in Riel:</td>
            <td class="text-right">{{ number_format($order->total_khr) }} ៛</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div style="font-size: 11px;">
        <div><strong>{{ __('Payment') }}:</strong> {{ strtoupper($order->payment_method) }} ({{ strtoupper($order->payment_status) }})</div>
        <div><strong>Rate:</strong> 1 USD = {{ number_format($order->exchange_rate_khr) }} KHR</div>
    </div>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 11px; margin-top: 8px;">
        <div>សូមអរគុណ! Thank you for your visit!</div>
        <div style="margin-top: 4px; font-size: 9px; color: #555;">Powered by SABAYRA Cambodia</div>
    </div>

    <script>
        // Optional auto-print trigger
        window.addEventListener('load', () => {
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        });
    </script>
</body>
</html>
