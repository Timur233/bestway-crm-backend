<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR заказа {{ $orderCode }}</title>
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
</head>
<body>
    <div
        id="order-qr-app"
        data-order-code="{{ $orderCode }}"
        data-contact-url="{{ $contactUrl }}"
    ></div>

    <script src="{{ mix('/js/app.js') }}"></script>
</body>
</html>
