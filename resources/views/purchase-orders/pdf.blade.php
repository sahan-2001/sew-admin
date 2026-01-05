<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order {{ $purchaseOrderDetails['id'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header, .footer { text-align: center; }
        .details, .provider-details { margin-top: 20px; width: 100%; float: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .signature { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature div { width: 45%; text-align: center; }
        .signature-line { border-top: 1px dotted black; margin: 10px auto; width: 80%; }
        .qr-code {width: 100%;margin-top: 20px;text-align: center;}

    </style>
</head>
<body>
    <!-- Company Header -->
    <div class="header">
        <h1>{{ $companyDetails['name'] }}</h1>
        <p>{{ $companyDetails['address'] }}</p>
        <p>Phone: {{ $companyDetails['phone'] }} | Email: {{ $companyDetails['email'] }}</p>
    </div>

    <!-- Header -->
    <div class="report_header">
        <h1>Purchase Order</h1>
        <hr>
    </div>

    <div class="provider-details">
        <h3>Order Information</h3>
        <table>
            <tr><th>Status</th><td>{{ ucfirst($purchaseOrderDetails['status']) }}</td></tr>
            <tr><th>PO ID</th><td>{{ str_pad($purchaseOrderDetails['id'], 5, '0', STR_PAD_LEFT) }}</td></tr>
            <tr><th>VAT Base</th><td>{{ ucfirst(str_replace('_', ' ', $purchaseOrderDetails['vat_base'])) }}</td></tr>
        </table>
    </div>

    <br>

    <div class="provider-details">
        <h3>Supplier Information</h3>
        <table>
            <tr><th>Supplier ID</th><td>{{ $purchaseOrderDetails['supplier_id'] }}</td></tr>
            <tr><th>Supplier Name</th><td>{{ $purchaseOrderDetails['supplier_name'] }}</td></tr>
            <tr><th>Email</th><td>{{ $purchaseOrderDetails['supplier_email'] }}</td></tr>
            <tr><th>Phone</th><td>{{ $purchaseOrderDetails['supplier_phone'] }}</td></tr>
        </table>
    </div>

    <div class="provider-details">
        <h3>Delivery Information</h3>
        <table>
            <tr>
                <th>Wanted Delivery Date</th>
                <td>{{ $purchaseOrderDetails['wanted_delivery_date'] }}</td>
            </tr>
            <tr>
                <th>Promised Delivery Date</th>
                <td>{{ $purchaseOrderDetails['promised_delivery_date'] ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="provider-details">
        <h3>VAT & Audit Information</h3>
        <table>
            <tr><th>Created At</th><td>{{ $purchaseOrderDetails['created_at'] }}</td></tr>
            <tr><th>Created By (User ID)</th><td>{{ $purchaseOrderDetails['created_by'] }}</td></tr>

            @if($purchase_order->vat_base === 'supplier_vat')
                <tr>
                    <th>Supplier VAT Rate</th>
                    <td>{{ $purchase_order->supplier_vat_rate }}%</td>
                </tr>
                <tr>
                    <th>VAT Amount</th>
                    <td>Rs. {{ number_format($purchase_order->vat_amount, 2) }}</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Purchase Order Items -->
    <div style="clear: both; margin-top: 30px;">
        <h2>Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Subtotal</th>
                    
                    @if($purchase_order->vat_base === 'item_vat')
                        <th class="text-right">Inv. VAT Rate</th>
                        <th class="text-right">VAT</th>
                        <th class="text-right">Grand Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php
                    $orderSubtotal = 0;
                    $orderVat = 0;
                    $orderGrandTotal = 0;
                @endphp
                @foreach ($purchaseOrderItems as $item)
                    <tr>
                        <td>{{ $item->inventory_item_id }}</td>
                        <td>{{ $item->inventoryItem?->item_code ?? 'N/A' }}</td>
                        <td>{{ $item->inventoryItem?->name ?? 'N/A' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->item_subtotal, 2) }}</td>
                    
                        @if($purchase_order->vat_base === 'item_vat')
                            <td class="text-right">{{ number_format($item->inventory_vat_rate, 2) }}</td>
                            <td class="text-right">{{ number_format($item->item_vat_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($item->item_grand_total, 2) }}</td>
                        @endif
                    </tr>

                    @php
                        $orderSubtotal += $item->item_subtotal;
                        $orderVat += $item->item_vat_amount;
                        $orderGrandTotal += $item->item_grand_total;
                    @endphp
                @endforeach
            </tbody>
        </table>

        <div style="width: 40%; float: right; margin-top: 20px;">
            <table>
                <tbody>
                    <tr>
                        <th class="text-right">Subtotal</th>
                        <td class="text-right">
                            Rs. {{ number_format($orderSubtotal, 2) }}
                        </td>
                    </tr>

                    @if($purchase_order->vat_base === 'item_vat')
                        <tr>
                            <th class="text-right">Total VAT</th>
                            <td class="text-right">
                                Rs. {{ number_format($orderVat, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-right">Grand Total</th>
                            <td class="text-right">
                                Rs. {{ number_format($orderGrandTotal, 2) }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <th class="text-right">VAT Amount</th>
                            <td class="text-right">
                                Rs. {{ number_format($purchase_order->vat_amount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-right">Grand Total</th>
                            <td class="text-right">
                                Rs. {{ number_format($purchase_order->grand_total, 2) }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

    <div style="clear: both;"></div>

    </div>


    <!-- Signatures -->
    <div class="signature">
        <div>
            <p>Authorized Signature</p>
            <div class="signature-line"></div>
        </div>
        <div>
            <p>Received By</p>
            <div class="signature-line"></div>
        </div>
    </div>

    <!-- QR Code -->
    <div class="qr-code">
        <h4>Scan to View PO</h4>

        <img src="{{ $qrCodePath }}"
            width="100"
            height="100"
            style="border:1px solid #eee; display:block; margin:0 auto;">

        <div style="margin-top: 8px; font-size:12px;">
            <a href="{{ $qrCodeData }}" style="color:#3490dc; text-decoration:none;">
                View Online
            </a>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
