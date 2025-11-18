<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
        <style>
            .auth-container{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--light-bg);padding:20px}.auth-card{background:white;padding:40px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-width:400px;width:100%}.auth-card h2{text-align:center;color:var(--accent);margin-bottom:30px}.auth-card .btn{width:100%;margin-top:10px}.auth-card a{color:var(--accent);text-decoration:none}.auth-card a:hover{text-decoration:underline}
        </style>
    </head>
    <body>
        <div class="auth-container">
            <div class="auth-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
