<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Docket #{{ $order->order_number }} - {{ $business->name }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
            font-size: 13px;
            line-height: 1.35;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 8px 12px;
            max-width: 80mm;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .text-lg { font-size: 18px; }
        .text-xl { font-size: 22px; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }
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
        .btn-close {
            padding: 6px 14px;
            font-weight: bold;
            cursor: pointer;
            background: #64748b;
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
            👨‍🍳 Print Kitchen Docket
        </button>
        <button onclick="window.close()" class="btn-close">
            Close
        </button>
    </div>

    <!-- Kitchen Ticket Header -->
    <div class="text-center">
        <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;">
            *** KITCHEN ORDER DOCKET ***
        </div>
        <div class="font-bold text-xl" style="margin: 4px 0;">
            #{{ $order->order_number }}
        </div>
        <div style="font-size: 12px;">{{ $business->name }}</div>
    </div>

    <div class="double-divider"></div>

    <!-- Order Metadata -->
    <table style="width: 100%; font-size: 12px;">
        <tr>
            <td style="font-weight: bold; width: 40%;">DESTINATION:</td>
            <td style="font-weight: bold; font-size: 15px;">
                @if ($order->order_type === 'dine_in')
                    TABLE {{ $order->table ? ($order->table->name ?? $order->table->table_number) : 'DINE-IN' }}
                @elseif ($order->order_type === 'takeaway')
                    TAKEAWAY / PICKUP
                @else
                    DELIVERY
                @endif
            </td>
        </tr>
        <tr>
            <td>GUEST:</td>
            <td style="font-weight: bold;">{{ $order->customer_name ?: 'Walk-in Guest' }}</td>
        </tr>
        <tr>
            <td>TIME IN:</td>
            <td>{{ $order->created_at->format('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td>ELAPSED:</td>
            <td>{{ (int) $order->created_at->diffInMinutes(now()) }} mins ago</td>
        </tr>
    </table>

    @if ($order->notes)
        <div class="divider"></div>
        <div style="background: #f8fafc; border: 1px dashed #000; padding: 4px 6px; font-weight: bold;">
            ⚠️ SPECIAL NOTE: {{ $order->notes }}
        </div>
    @endif

    <div class="double-divider"></div>

    <!-- Items Section (No financial/pricing information) -->
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid #000; text-align: left; font-size: 12px;">
                <th style="width: 18%; font-weight: bold;">QTY</th>
                <th style="width: 72%; font-weight: bold;">ITEM / MODIFIERS</th>
                <th style="width: 10%; text-align: center;">DONE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr style="border-bottom: 1px dotted #ccc;">
                    <td style="vertical-align: top; font-weight: bold; font-size: 16px; padding: 6px 0;">
                        {{ (int) $item->quantity }}x
                    </td>
                    <td style="vertical-align: top; padding: 6px 0;">
                        <div style="font-weight: bold; font-size: 14px;">
                            {{ $item->product_name }}
                        </div>

                        @if ($item->variant)
                            <div style="font-size: 11px; font-weight: bold; color: #333;">
                                &bull; Variant: {{ $item->variant->name }}
                            </div>
                        @endif

                        @if ($item->addons && $item->addons->count() > 0)
                            <div style="font-size: 11px; color: #444; padding-left: 4px;">
                                @foreach ($item->addons as $addon)
                                    <div>+ {{ max(1, (int) ($addon->quantity ?? 1)) }}x {{ $addon->name ?? $addon->addon_name }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if ($item->notes)
                            <div style="font-size: 11px; font-style: italic; background: #eee; padding: 2px 4px; margin-top: 2px;">
                                Note: {{ $item->notes }}
                            </div>
                        @endif
                    </td>
                    <td style="vertical-align: top; text-align: center; padding: 6px 0;">
                        <span style="display: inline-block; width: 14px; height: 14px; border: 1px solid #000; border-radius: 2px;"></span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="text-center" style="font-size: 10px; margin-top: 6px;">
        TOTAL ITEMS: {{ $order->items->sum('quantity') }} &bull; SABAYRA POS KITCHEN DOCKET
    </div>

    @if (request()->boolean('auto_print'))
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                window.print();
            });
        </script>
    @endif
</body>
</html>
