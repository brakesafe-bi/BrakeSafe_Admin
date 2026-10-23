<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset your password - BrakeSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-xl">
        <div class="glass p-6 sm:p-8 rounded-2xl space-y-6 glow-cyan">
            <div class="text-center">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 w-20 h-20 rounded-3xl shadow-[0_16px_40px_rgba(0,0,0,0.18)]">
                <h1 class="text-2xl font-bold">Reset your password</h1>
                <p class="mt-2 text-sm text-muted-foreground">Choose the option that matches your BrakeSafe account.</p>
            </div>

            @if (session('status'))
                <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="p-4 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <section aria-labelledby="admin-recovery-title" class="space-y-4 rounded-xl border border-border p-4 sm:p-5">
                <div>
                    <h2 id="admin-recovery-title" class="text-lg font-semibold">Administrator account</h2>
                    <p class="text-sm text-muted-foreground">Enter your administrator email address and we’ll send a six-digit verification code. The code expires after 10 minutes.</p>
                </div>
                <form id="forgot-password-form" method="POST" action="/forgot-password" class="space-y-4">
                    @csrf
                    <div>
                        <label for="admin-recovery-email" class="mb-1 block text-sm font-medium">Administrator email</label>
                        <input id="admin-recovery-email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}" placeholder="admin@brakesafe.com" class="w-full px-4 py-3 bg-secondary border border-border rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <button id="admin-reset-code-button" type="submit" data-lockout-seconds="{{ $lockoutSeconds }}" @disabled($lockoutSeconds > 0) class="w-full py-3 px-4 bg-primary hover:bg-primary/90 text-card font-medium rounded-lg disabled:cursor-not-allowed disabled:opacity-50">Send Code</button>
                    <p id="admin-reset-code-lockout" class="text-center text-sm text-muted-foreground" role="status" aria-live="polite" @if($lockoutSeconds <= 0) hidden @endif></p>
                </form>
            </section>

            <div class="space-y-2">
                <a href="{{ route('login') }}" class="block text-center text-sm text-primary hover:underline">Back to login</a>
            </div>
        </div>
    </div>
    <script>
        const sendCodeButton = document.getElementById('admin-reset-code-button');
        const lockoutMessage = document.getElementById('admin-reset-code-lockout');
        const lockoutSeconds = Number(sendCodeButton.dataset.lockoutSeconds);

        if (lockoutSeconds > 0) {
            const unlockAt = Date.now() + lockoutSeconds * 1000;
            const updateLockout = () => {
                const remainingSeconds = Math.max(0, Math.ceil((unlockAt - Date.now()) / 1000));
                if (remainingSeconds === 0) {
                    sendCodeButton.disabled = false;
                    lockoutMessage.hidden = true;
                    return;
                }

                lockoutMessage.textContent = `You can send another code in ${remainingSeconds} seconds.`;
                window.setTimeout(updateLockout, 1000);
            };

            updateLockout();
        }
    </script>
</body>
</html>
