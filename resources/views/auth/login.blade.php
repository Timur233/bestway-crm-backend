<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход в Bestway CRM</title>
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
</head>
<body class="auth-page">
    <main class="auth-card">
        <div class="auth-copy">
            <p class="auth-kicker">Bestway CRM</p>
            <h1>Вход для администратора</h1>
            <p>Страница списка заказов закрыта авторизацией. Вход выполняется через стандартную Laravel session auth.</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="auth-form">
            @csrf

            <label class="auth-label">
                <span>Email</span>
                <input class="auth-input" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>

            <label class="auth-label">
                <span>Пароль</span>
                <input class="auth-input" type="password" name="password" required>
            </label>

            <label class="auth-checkbox">
                <input type="checkbox" name="remember" value="1">
                <span>Запомнить меня</span>
            </label>

            @if ($errors->any())
                <div class="auth-error">{{ $errors->first() }}</div>
            @endif

            <button type="submit" class="auth-button">Войти</button>
        </form>
    </main>
</body>
</html>
