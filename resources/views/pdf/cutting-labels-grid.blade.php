<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cutting Labels</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        font-size: 10px;
        margin: 0;
        padding: 0;
    }
    .label-box {
        width: 100%;
        border: 1px solid #000;
        padding: 5px 8px;
        margin-bottom: 10px;
        box-sizing: border-box;
        height: auto;
    }
    .barcode-container {
        text-align: center;
        margin-top: 5px;
    }
    .barcode-img {
        width: 80px;
        height: 40px;
        object-fit: contain;
        text-align: center;
    }
</style>

<body>
    @foreach ($labels as $label)
        <div class="label-box">
            <strong>Label:</strong> {{ $label->label }}<br>
            <strong>Index:</strong> {{ $label->quantity }}<br>
            <strong>Barcode ID:</strong> {{ $label->barcode_id }}

            @if(!empty($label->barcode_id))
                <div class="barcode-container">
                    <?php 
                        $dns1d = new \Milon\Barcode\DNS1D();
                        echo $dns1d->getBarcodeHTML($label->barcode_id, 'C128', 1.1, 30);
                    ?>
                </div>
            @endif
        </div>
    @endforeach
</body>

</html>