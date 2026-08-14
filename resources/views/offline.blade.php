<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $themeColor }}">
    <title>{{ $title }} — {{ $name }}</title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: ui-sans-serif, system-ui, sans-serif;
        }

        body {
            align-items: center;
            background: {{ $backgroundColor }};
            display: flex;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
            padding: 1.5rem;
        }

        main {
            max-width: 32rem;
            text-align: center;
        }

        .status {
            background: {{ $themeColor }};
            border-radius: 9999px;
            height: 4rem;
            margin: 0 auto 1.5rem;
            width: 4rem;
        }

        h1 {
            color: #18181b;
            font-size: 1.875rem;
            margin-bottom: .75rem;
        }

        p {
            color: #52525b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        button {
            background: {{ $themeColor }};
            border: 0;
            border-radius: .5rem;
            color: white;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
            padding: .75rem 1.25rem;
        }
    </style>
</head>
<body>
    <main>
        <div class="status" aria-hidden="true"></div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        <button type="button" onclick="window.location.reload()">{{ $retryLabel }}</button>
    </main>
</body>
</html>
