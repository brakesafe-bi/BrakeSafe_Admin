<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request User Access - BrakeSafe</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans min-h-screen flex items-center justify-center p-4">
    <main class="w-full max-w-xl">
        <section class="glass rounded-2xl p-6 shadow-xl sm:p-8">
            <header class="mb-6 text-center">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 h-20 w-20 rounded-3xl">
                <h1 class="text-2xl font-bold">Request user access</h1>
                <p class="mt-2 text-sm text-muted-foreground">Submit your details, verify your email, and wait for an admin to review your request.</p>
            </header>

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-400" role="alert">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('registration-requests.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first-name" class="mb-1 block text-sm font-medium">First name</label>
                        <input id="first-name" name="first_name" type="text" required maxlength="100" autocomplete="given-name" value="{{ old('first_name') }}" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label for="last-name" class="mb-1 block text-sm font-medium">Last name</label>
                        <input id="last-name" name="last_name" type="text" required maxlength="100" autocomplete="family-name" value="{{ old('last_name') }}" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label for="birthday" class="mb-1 block text-sm font-medium">Birthday</label>
                    <input id="birthday" name="birthday" type="date" required max="{{ now()->subYears(18)->toDateString() }}" value="{{ old('birthday') }}" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    <p id="age-status" class="mt-1 text-xs text-muted-foreground" aria-live="polite">You must be at least 18 years old.</p>
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" required maxlength="255" autocomplete="email" value="{{ old('email') }}" class="w-full rounded-lg border border-border bg-secondary px-4 py-3 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit" class="w-full rounded-lg bg-primary px-4 py-3 font-semibold text-card transition hover:bg-primary/90">Submit request and send verification code</button>
            </form>

            <a href="{{ route('login') }}" class="mt-5 block text-center text-sm text-primary hover:underline">Back to login</a>
        </section>
    </main>
    <script>
        const birthdayInput = document.getElementById('birthday');
        birthdayInput.addEventListener('change', () => {
            const [year, month, day] = birthdayInput.value.split('-').map(Number);
            if (!year || !month || !day) return;
            const now = new Date();
            let age = now.getFullYear() - year;
            if (now.getMonth() + 1 < month || (now.getMonth() + 1 === month && now.getDate() < day)) age--;
            document.getElementById('age-status').textContent = age >= 18
                ? `Age: ${age} years`
                : `Age: ${age} years — you must be at least 18.`;
        });
    </script>
</body>
</html>
