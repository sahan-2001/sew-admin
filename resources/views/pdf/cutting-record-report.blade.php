<!DOCTYPE html>
<html>
<head>
    <title>Cutting Labels - Record #{{ $cuttingRecord->id }}</title>
    <style>
        /* First Page Styles */
        .details-page {
            font-family: sans-serif;
            font-size: 14px;
            margin: 20px;
            page-break-after: always;
        }
        .details-header {
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: bold;
            width: 150px;
        }
        
        /* Labels Page Styles */
        .labels-page {
            font-family: sans-serif;
            font-size: 11px;
            margin: 15px;
        }
        .label-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 10px;
            margin-top: 15px;
        }
        .label-box {
            border: 1px solid #000;
            padding: 8px;
            height: 140px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .label-info {
            margin-bottom: 5px;
        }
        .barcode-container {
            text-align: center;
            margin-top: auto;
        }
        .barcode {
            width: 100%;
            height: 35px;
            object-fit: contain;
        }
        .page-break {
            page-break-after: always;
        }
        .no-barcode {
            text-align: center;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- First Page - Cutting Record Details -->
    <div class="details-page">
        <div class="details-header">
            <h2>Cutting Record #{{ $cuttingRecord->id }}</h2>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Station:</span>
            <span>{{ $cuttingRecord->cuttingStation->name ?? 'N/A' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Order Type:</span>
            <span>{{ $cuttingRecord->order_type }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Order ID:</span>
            <span>{{ $cuttingRecord->order_id }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Created At:</span>
            <span>{{ $cuttingRecord->created_at->format('Y-m-d H:i') }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Total Labels:</span>
            <span>{{ $labels->count() }}</span>
        </div>
        
        <!-- Add more details as needed -->
    </div>

    <!-- Labels Pages (start from second page) -->
    @foreach($labels->chunk(20) as $pageLabels) <!-- 20 labels per page (5 rows of 4) -->
        <div class="labels-page">
            <h3>Cut Piece Labels (Record #{{ $cuttingRecord->id }})</h3>
            <div class="label-grid">
                @foreach($pageLabels as $label)
                    <div class="label-box">
                        <div class="label-info">
                            <strong>Label:</strong> {{ $label->label }}<br>
                            <strong>Order Type:</strong> {{ $label->order_type }}<br>
                            <strong>Order ID:</strong> {{ $label->order_id }}<br>
                            <strong>Item ID:</strong> {{ $label->order_item_id }}<br>
                            <strong>Variation ID:</strong> {{ $label->order_variation_id ?? '—' }}<br>
                        </div>
                        <div class="barcode-container">
                            @if ($label->barcode_base64)
                                <img class="barcode" src="{{ $label->barcode_base64 }}" style="height: 40px; width: 100%;" alt="Barcode">
                                <div class="barcode-id">{{ $label->barcode_id }}</div>
                            @else
                                <div class="missing-barcode">
                                    <p>Barcode Missing</p>
                                    <p>ID: {{ $label->barcode_id ?? 'N/A' }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(!$loop->last)
                <div class="page-break"></div>
            @endif
        </div>
    @endforeach
</body>
</html>