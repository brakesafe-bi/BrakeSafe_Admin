<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Password Reset — BrakeSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md animate-fade-in">
        <div class="glass p-8 rounded-2xl space-y-6 glow-cyan">
            <div class="text-center">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 w-20 h-20 rounded-3xl shadow-[0_16px_40px_rgba(0,0,0,0.18)]">
                <h1 class="text-3xl font-bold text-foreground">Verify Password Reset</h1>
                <p class="text-muted-foreground mt-2">Enter the six-digit code sent to your administrator email. The code expires after 10 minutes.</p>
            </div>

            @if (session('status'))
                <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-sm" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/reset-password" class="space-y-4">
                @csrf
                <div>
                    <label for="reset-code" class="block text-sm font-medium text-foreground mb-2">Six-digit verification code</label>
                    <input id="reset-code" type="text" name="code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus class="w-full px-4 py-3 bg-secondary border border-border rounded-lg font-mono tracking-[0.35em] text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="123456">
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-card font-medium rounded-lg">Verify code</button>
            </form>

            <a href="/forgot-password" class="block text-center text-sm text-primary hover:underline">Request another verification code</a>
            <a href="/login" class="block text-center text-sm text-primary hover:underline">Back to login</a>
        </div>
    </div>
</body>
</html>
