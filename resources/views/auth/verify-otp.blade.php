<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - BrakeSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 w-20 h-20 rounded-3xl shadow-[0_16px_40px_rgba(0,0,0,0.18)]">
            <h1 class="text-3xl font-bold text-foreground">BRAKESAFE</h1>
            <p class="text-muted-foreground mt-2">Verify Your Email</p>
        </div>

        <div class="glass p-8 rounded-2xl space-y-6 glow-cyan">
            <div class="text-center">
                <p class="text-muted-foreground">We sent a 6-digit code to</p>
                <p class="font-mono font-bold text-primary">{{ $email }}</p>
            </div>

            @if ($errors->any())
                <div class="p-4 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form id="otp-form" method="POST" action="/register/verify-otp" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-foreground mb-2">Enter Code</label>
                    <input type="text" name="otp" inputmode="numeric" placeholder="000000" maxlength="6" required class="w-full px-4 py-3 bg-secondary border border-border rounded-lg text-center text-2xl font-mono tracking-widest text-foreground focus:outline-none focus:ring-2 focus:ring-primary" autofocus>
                </div>

                <button type="submit" class="w-full py-2 px-4 bg-primary hover:bg-primary/90 text-card font-medium rounded-lg transition-all">
                    Verify
                </button>
            </form>

            <p class="text-center text-muted-foreground text-sm">
                Didn't receive code?
                <button type="button" class="text-primary hover:underline">Resend</button>
            </p>
        </div>
    </div>
</body>
</html>
