<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Access Request - BrakeSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans flex min-h-screen items-center justify-center p-4">
    <main class="w-full max-w-md">
        <section class="glass space-y-5 rounded-2xl p-6 shadow-xl sm:p-8">
            <header class="text-center">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 h-20 w-20 rounded-3xl">
                <h1 class="text-2xl font-bold">Verify your email</h1>
                <p class="mt-2 text-sm text-muted-foreground">Access request for {{ $registrationRequest->email }}</p>
            </header>

            @if ($errors->any())
                <div class="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if (session('status'))
                <p class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ session('status') }}</p>
            @endif

            @switch($registrationRequest->status)
                @case('pending_verification')
                    <p class="text-sm text-muted-foreground">Enter the six-digit code sent to your email. It expires in 10 minutes.</p>
                    <form method="POST" action="{{ route('registration-requests.verify.submit', ['token' => $registrationRequest->public_token]) }}" class="space-y-4">
                        @csrf
                        <label for="verification-code" class="block text-sm font-medium">Verification code</label>
                        <input id="verification-code" name="verification_code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-center font-mono text-xl tracking-[0.4em] text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                        <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 font-semibold text-card transition hover:bg-primary/90">Verify email</button>
                    </form>
                    @break
                @case('pending_review')
                    <p class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-700 dark:text-amber-300">Your email is verified. Your request is waiting for admin approval.</p>
                    @break
                @case('completed')
                    <p class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">Your request was approved. Contact the BrakeSafe administrator for your one-time account setup code.</p>
                    @break
                @case('rejected')
                    <p class="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400">Your access request was not approved.</p>
                    @break
                @default
                    <p class="rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400">This verification code is no longer active. Please submit a new access request.</p>
            @endswitch

            <a href="{{ route('login') }}" class="block text-center text-sm text-primary hover:underline">Back to login</a>
        </section>
    </main>
</body>
</html>
