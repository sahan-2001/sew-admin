<!DOCTYPE html>
<html>
<head>
    <title>Cutting Labels - Record #{{ $cuttingRecord->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 14px; /* base font size similar to PO */
        }
        .header, .footer {
            text-align: center;
        }
        .header h1 {
            margin-bottom: 5px;
            font-size: 32px; /* h1 size from PO */
        }
        .labels-page h3 {
            font-size: 24px; /* h2 equivalent */
            margin-top: 0;
            margin-bottom: 15px;
        }
        .details, .record-details {
            margin-top: 20px;
            width: 48%;
            float: left;
            border-collapse: collapse;
            font-size: 14px;
        }
        .details th, .details td, 
        .record-details th, .record-details td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        .details th, .record-details th {
            background-color: #f2f2f2;
            width: 150px;
            font-weight: 600;
        }
        .page-break {
            page-break-after: always;
            clear: both;
        }

        /* Label pages */
        .labels-page {
            font-family: Arial, sans-serif;
            margin: 15px;
            clear: both;
        }

        /* Container for the labels */
        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 15px;
        }
        .label-box {
            padding: 10px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: {{ $labelSettings['label_height'] }};
            width: calc({{ $labelSettings['label_width'] }} - 6px);
            font-size: 14px;
            @if($labelSettings['show_border'])
                border: 1px solid #000;
            @endif
        }
        .label-info {
            margin-bottom: 5px;
            line-height: 1.2;
            font-size: 14px;
        }
        .label-info strong {
            font-weight: 600;
        }
        .barcode-container {
            text-align: center;
            margin-top: auto;
        }
        .barcode {
            width: auto;
            max-width: 100%;
            height: 30px;
            object-fit: contain;
        }
        .barcode-id {
            font-size: 12px; /* small text for barcode id */
            margin-top: 5px;
        }
        .no-barcode {
            text-align: center;
            font-style: italic;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>

    <!-- First Page: Company + Cutting Record Details -->
    @if($labelSettings['include_company_header'])
    <div class="header">
        <h1>{{ $companyDetails['name'] ?? 'Company Name' }}</h1>
        <p>{{ $companyDetails['address'] ?? 'Company Address' }}</p>
        <p>Phone: {{ $companyDetails['phone'] ?? 'N/A' }} | Email: {{ $companyDetails['email'] ?? 'N/A' }}</p>
    </div>
    @endif

    <table class="details">
        <thead>
            <tr><th colspan="2">Cutting Record Details</th></tr>
        </thead>
        <tbody>
            <tr><th>Cutting Record ID</th><td>#{{ str_pad($cuttingRecord->id, 5, '0', STR_PAD_LEFT) }}</td></tr>
            <tr><th>Cutting Station</th><td>{{ $cuttingRecord->cuttingStation->name ?? 'N/A' }}</td></tr>
            <tr><th>Order Type</th><td>{{ $cuttingRecord->order_type }}</td></tr>
            <tr><th>Order ID</th><td>#{{ str_pad($cuttingRecord->order_id, 5, '0', STR_PAD_LEFT) }}</td></tr>
            <tr><th>Created At</th><td>{{ $cuttingRecord->created_at->format('Y-m-d H:i') }}</td></tr>
            <tr><th>Total Labels</th><td>{{ $labels->count() }}</td></tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- Labels Pages -->
    @foreach($labels->chunk($labelSettings['labels_per_page']) as $pageLabels)
        <div class="labels-page">
            <h3>Cut Piece Labels (Record #{{ $cuttingRecord->id }}) - Page {{ $loop->iteration }}</h3>

            <div class="labels-container">
                @foreach($pageLabels as $label)
                    <div class="label-box">
                        <div class="label-info">
                            <strong>Label ID:</strong> {{ $label->quantity }}<br>
                            <strong>Label:</strong> {{ $label->label }}<br>
                            <strong>Order Type:</strong> {{ $label->order_type }}<br>
                            <strong>Order ID:</strong> {{ $label->order_id }}<br>
                            <strong>Item ID:</strong> {{ $label->order_item_id }}<br>
                            <strong>Variation ID:</strong> {{ $label->order_variation_id ?? '—' }}<br>
                        </div>
                        <div class="barcode-container">
                            @if ($label->barcode_base64)
                                <img class="barcode" src="{{ $label->barcode_base64 }}" alt="Barcode">
                                <div class="barcode-id">{{ $label->barcode_id }}</div>
                            @else
                                <div class="no-barcode">
                                    <p>Barcode Missing</p>
                                    <p>ID: {{ $label->barcode_id ?? 'N/A' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Add empty label boxes to fill the last row if needed -->
            @if($pageLabels->count() % $labelSettings['columns'] != 0)
                @for($i = 0; $i < ($labelSettings['columns'] - ($pageLabels->count() % $labelSettings['columns'])); $i++)
                    <div class="label-box" style="visibility: hidden;"></div>
                @endfor
            @endif

            @if(!$loop->last)
                <div class="page-break"></div>
            @endif
        </div>
    @endforeach

</body>
</html>
