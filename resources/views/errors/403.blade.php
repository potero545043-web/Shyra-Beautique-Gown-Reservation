@php
    $role = auth()->check() ? auth()->user()->role : null;
    $home = match ($role) {
        'owner' => route('owner.dashboard'),
        'employee' => route('employee.dashboard'),
        'customer' => route('customer.dashboard'),
        default => url('/'),
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access restricted | Shyra Beautique</title>
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600|playfair-display:500,600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f8f0d6; font-family: 'DM Sans', sans-serif; color: #4a3b35; }
        .box { width: 100%; max-width: 460px; padding: 40px 36px; text-align: center; background: #fbf5e3; border: 1px solid #d9c089; border-radius: 1.75rem; box-shadow: 0 25px 70px rgba(107, 0, 32, .14); }
        .box img { width: 64px; height: 64px; object-fit: cover; border-radius: 14px; border: 1px solid #d9c089; background: #fff; margin-bottom: 18px; }
        .kicker { display: block; margin-bottom: 10px; color: #a67c2e; font-size: 12px; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; }
        h1 { font: 600 38px/1.1 'Playfair Display', serif; color: #6b0020; }
        h1 em { color: #a67c2e; font-weight: 500; }
        p { margin: 12px 0 26px; color: #7a6a60; font-size: 15px; line-height: 1.6; }
        a { display: inline-block; padding: 13px 28px; border-radius: 999px; background: #6b0020; color: #fff; font-weight: 600; font-size: 14px; text-decoration: none; }
        a:hover { background: #4f0018; }
    </style>
</head>
<body>
    <main class="box">
        <img src="{{ asset('images/Logo.png') }}" alt="Shyra Beautique">
        <span class="kicker">403 · Access restricted</span>
        <h1>Not <em>allowed.</em></h1>
        <p>Your account doesn't have access to this page. If you think this is a mistake, please ask the owner.</p>
        <a class="sb-back-btn" href="{{ $home }}">Back to dashboard</a>
    </main>
</body>
</html>
