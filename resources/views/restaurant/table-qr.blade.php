<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR {{ $table->number }} - {{ $table->restaurant->name }}</title>
    <script src="{{ asset('vendor/qrcodejs/qrcode.min.js') }}" defer></script>
    <style>body{font-family:Arial,sans-serif;text-align:center;padding:40px;background:#f7f3ec}main{max-width:420px;margin:auto;background:#fff;padding:32px;border-radius:20px;box-shadow:0 10px 30px #0001}#qrcode{display:flex;justify-content:center;margin:24px 0}button{padding:12px 24px;border:0;border-radius:10px;background:#111;color:#fff;cursor:pointer}</style>
</head>
<body>
<main>
    <h1>{{ $table->restaurant->name }}</h1>
    <h2>الطاولة {{ $table->number }}</h2>
    <div id="qrcode"></div>
    <p>امسح الرمز لفتح القائمة والطلب من الطاولة.</p>
    <button onclick="window.print()">طباعة QR</button>
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new QRCode(document.getElementById('qrcode'), { text: @json($url), width: 260, height: 260 });
    });
</script>
</body>
</html>