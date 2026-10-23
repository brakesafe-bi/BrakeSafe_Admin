<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset user password - BrakeSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans flex min-h-screen items-center justify-center p-4">
    <main class="w-full max-w-md">
        <section class="glass space-y-6 rounded-2xl p-6 sm:p-8">
            <header class="text-center">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 h-20 w-20 rounded-3xl">
                <h1 class="text-2xl font-bold">Reset your BrakeSafe password</h1>
                <p class="mt-2 text-sm text-muted-foreground">Request a verification code by email, then use it below to reset your password.</p>
            </header>

            @if(session('status'))
                <div role="status" class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div role="alert" class="rounded-lg border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('users.password.request-code') }}" class="space-y-4 rounded-xl border border-border p-4">
                @csrf
                <div>
                    <label for="request-reset-email" class="mb-1 block text-sm font-medium">Email address</label>
                    <input id="request-reset-email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit" class="w-full rounded-lg border border-primary px-4 py-3 font-medium text-primary hover:bg-primary/10">Email verification code</button>
            </form>

            <form method="POST" action="{{ route('users.password.reset-with-code') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="reset-email" class="mb-1 block text-sm font-medium">Email address</label>
                    <input id="reset-email" type="email" name="email" required autocomplete="email" value="{{ old('email') }}" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="verification-code" class="mb-1 block text-sm font-medium">Six-digit verification code</label>
                    <input id="verification-code" type="text" name="verification_code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 font-mono tracking-[0.35em] text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="new-password" class="mb-1 block text-sm font-medium">New password</label>
                    <input id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="confirm-password" class="mb-1 block text-sm font-medium">Confirm new password</label>
                    <input id="confirm-password" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <p class="text-xs text-muted-foreground">Use at least 8 characters with uppercase and lowercase letters, a number, and a symbol. The code expires after 10 minutes and can be used once.</p>
                <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 font-medium text-card hover:bg-primary/90">Reset password</button>
            </form>

            <a href="/forgot-password" class="block text-center text-sm text-primary hover:underline">Back to password recovery</a>
        </section>
    </main>
</body>
</html>
