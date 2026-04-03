<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Остатки</title>
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
</head>
<body>
    <div
        id="product-remains-app"
        data-remains-endpoint="{{ $remainsEndpoint }}"
        data-logout-endpoint="{{ $logoutEndpoint }}"
        data-current-user='@json(["name" => $currentUser->name, "email" => $currentUser->email])'
    ></div>

    <script src="{{ mix('/js/app.js') }}"></script>
</body>
</html>
