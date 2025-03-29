<!-- filepath: resources/views/purchase_orders/qr.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code</title>
</head>
<body>
    <h1>Purchase Order QR Code</h1>
    <img src="{{ $qrCodePath }}" alt="QR Code">
    <br>
    <a href="{{ $qrCodePath }}" download>Download QR Code</a>
</body>
</html>