<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0;url={{ $destination }}">
    <title>Перенаправлення…</title>
</head>
<body>
    <script>window.location.replace(@json($destination));</script>
    <noscript>
        <p>Перенаправлення… Якщо цього не сталося автоматично, <a href="{{ $destination }}">натисніть тут</a>.</p>
    </noscript>
</body>
</html>
