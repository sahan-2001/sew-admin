<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 20px; }
        .page-break { page-break-after: always; }
        .label-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            grid-gap: 10px;
            margin-top: 20px;
        }
        .label-box {
            border: 1px solid #000;
            padding: 10px;
            height: 150px;
            box-sizing: border-box;
        }
        .barcode {
            margin-top: 5px;
            width: 100%;
            height: 40px;
            object-fit: contain;
        }
    </style>
</head>
<body>

    {{-- Page 1: Cutting Record Details --}}
    <h2>Cutting Record ID: {{ $cuttingRecord->id }}</h2>
    <p>Station: {{ $cuttingRecord->cuttingStation->name ?? 'N/A' }}</p>
    <p>Order Type: {{ $cuttingRecord->order_type }}</p>
    <p>Order ID: {{ $cuttingRecord->order_id }}</p>
    {{-- Add more details as needed --}}
    
    <div class="page-break"></div>

    {{-- Page 2: Labels Grid --}}
    <h2>Cut Piece Labels</h2>
    <div class="label-grid">
        @foreach ($labels as $label)
            <div class="label-box">
                <strong>Label:</strong> {{ $label->label }}<br>
                <strong>Order Type:</strong> {{ $label->order_type }}<br>
                <strong>Order ID:</strong> {{ $label->order_id }}<br>
                <strong>Item ID:</strong> {{ $label->order_item_id }}<br>
                <strong>Variation ID:</strong> {{ $label->order_variation_id ?? '—' }}<br>
                @if ($label->barcode && file_exists(public_path($label->barcode)))
                    <img class="barcode" src="{{ public_path($label->barcode) }}" alt="Barcode">
                @else
                    <div class="barcode">[No Barcode]</div>
                @endif
            </div>
        @endforeach
    </div>

</body>
</html>
