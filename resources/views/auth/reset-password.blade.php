<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — BrakeSafe</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.0/dist/sweetalert2.all.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground font-sans min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md animate-fade-in">
        <div class="glass p-8 rounded-2xl space-y-6 glow-green">
            <div class="text-center">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" class="mx-auto mb-4 w-20 h-20 rounded-3xl shadow-[0_16px_40px_rgba(0,0,0,0.18)]">
                <h1 class="text-3xl font-bold text-foreground">Reset Password</h1>
                <p class="text-muted-foreground mt-2">Your email is verified. Choose and confirm your new password.</p>
            </div>

            @if (session('status'))
                <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="/reset-password/new" class="space-y-4" id="reset-password-form">
                @csrf
                <div>
                    <label for="new-password" class="block text-sm font-medium text-foreground mb-2">New Password</label>
                    <div class="relative">
                        <input id="new-password" type="password" name="password" required minlength="8" autocomplete="new-password" class="w-full px-4 py-2 pr-12 bg-secondary border border-border rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="••••••••">
                        <button type="button" data-password-visibility-toggle aria-label="Show new password" aria-controls="new-password" class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-lg text-muted-foreground transition hover:text-foreground focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary" title="Show password">
                            <svg data-eye-icon viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-muted-foreground">Password security</span>
                            <span id="password-strength" class="font-semibold text-slate-400" aria-live="polite">Enter a password</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-500/20" role="img" aria-label="Password security level">
                            <div id="password-strength-bar" class="h-full rounded-full bg-slate-500 transition-all duration-200" style="width: 0%"></div>
                        </div>
                        <ul class="grid grid-cols-1 gap-1 text-xs text-muted-foreground sm:grid-cols-2" aria-label="Password requirements">
                            <li id="password-rule-length" data-password-rule="length">At least 8 characters</li>
                            <li id="password-rule-uppercase" data-password-rule="uppercase">An uppercase letter</li>
                            <li id="password-rule-lowercase" data-password-rule="lowercase">A lowercase letter</li>
                            <li id="password-rule-number" data-password-rule="number">A number</li>
                            <li id="password-rule-symbol" data-password-rule="symbol">A symbol</li>
                        </ul>
                    </div>
                </div>

                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-foreground mb-2">Confirm Password</label>
                    <div class="relative">
                        <input id="confirm-password" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="w-full px-4 py-2 pr-12 bg-secondary border border-border rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-primary" placeholder="••••••••">
                        <button type="button" data-password-visibility-toggle aria-label="Show confirmation password" aria-controls="confirm-password" class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-lg text-muted-foreground transition hover:text-foreground focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary" title="Show password">
                            <svg data-eye-icon viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    <p id="password-match" class="mt-2 text-xs font-medium text-slate-400" aria-live="polite">Enter your password again to confirm it.</p>
                </div>

                <button id="update-password-button" type="submit" class="w-full py-2 px-4 bg-primary hover:bg-primary/90 text-card font-medium rounded-lg disabled:cursor-not-allowed disabled:opacity-50">Update Password</button>
            </form>

            <a href="/login" class="block text-center text-sm text-primary hover:underline">Back to login</a>
        </div>
    </div>
    <script>
        @if (session('status'))
            document.addEventListener('DOMContentLoaded', () => {
                if (!window.Swal) {
                    console.error('SweetAlert is unavailable; password reset status is shown on the page.');
                    return;
                }

                window.Swal.fire({
                    icon: 'success',
                    title: 'Verification successful',
                    text: @json(session('status')),
                    confirmButtonText: 'Continue',
                });
            });
        @endif

        document.querySelectorAll('[data-password-visibility-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.getAttribute('aria-controls'));
                if (!input) return;

                const isVisible = input.type === 'password';
                input.type = isVisible ? 'text' : 'password';
                const label = button.getAttribute('aria-controls') === 'new-password'
                    ? 'new password'
                    : 'confirmation password';
                button.setAttribute('aria-label', `${isVisible ? 'Hide' : 'Show'} ${label}`);
                button.title = `${isVisible ? 'Hide' : 'Show'} password`;
                button.innerHTML = isVisible
                    ? '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.1 0 9.5 7 9.5 7a15.4 15.4 0 0 1-3 3.8M6.2 6.2C3.9 7.8 2.5 12 2.5 12s3.4 7 9.5 7c1.2 0 2.3-.3 3.3-.7"/></svg>'
                    : '<svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
            });
        });
    </script>
    <script>
        const newPasswordInput = document.getElementById('new-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const strengthLabel = document.getElementById('password-strength');
        const strengthBar = document.getElementById('password-strength-bar');
        const matchLabel = document.getElementById('password-match');
        const updatePasswordButton = document.getElementById('update-password-button');
        const passwordRules = {
            length: (value) => value.length >= 8,
            uppercase: (value) => /[A-Z]/.test(value),
            lowercase: (value) => /[a-z]/.test(value),
            number: (value) => /[0-9]/.test(value),
            symbol: (value) => /[^\p{L}\p{N}\s]/u.test(value),
        };
        const strengthLevels = [
            { label: 'Very weak', color: '#f87171' },
            { label: 'Weak', color: '#fb923c' },
            { label: 'Fair', color: '#facc15' },
            { label: 'Good', color: '#a3e635' },
            { label: 'Strong', color: '#22c55e' },
        ];

        const updatePasswordFeedback = () => {
            const password = newPasswordInput.value;
            const confirmation = confirmPasswordInput.value;
            let passedRules = 0;

            Object.entries(passwordRules).forEach(([rule, check]) => {
                const passed = check(password);
                if (passed) passedRules++;
                const ruleElement = document.querySelector(`[data-password-rule="${rule}"]`);
                ruleElement.classList.toggle('text-emerald-500', passed);
                ruleElement.classList.toggle('text-muted-foreground', !passed);
                ruleElement.textContent = `${passed ? '✓' : '○'} ${{
                    length: 'At least 8 characters',
                    uppercase: 'An uppercase letter',
                    lowercase: 'A lowercase letter',
                    number: 'A number',
                    symbol: 'A symbol',
                }[rule]}`;
            });

            const level = strengthLevels[Math.max(0, passedRules - 1)];
            strengthLabel.textContent = password ? level.label : 'Enter a password';
            strengthLabel.style.color = password ? level.color : '';
            strengthBar.style.width = `${passedRules * 20}%`;
            strengthBar.style.backgroundColor = password ? level.color : '';

            const passwordsMatch = confirmation !== '' && password === confirmation;
            const confirmationIsIncomplete = confirmation === '';
            matchLabel.textContent = passwordsMatch
                ? 'Passwords match.'
                : confirmationIsIncomplete
                    ? 'Enter your password again to confirm it.'
                    : 'Passwords do not match.';
            matchLabel.classList.toggle('text-emerald-500', passwordsMatch);
            matchLabel.classList.toggle('text-rose-500', !passwordsMatch && !confirmationIsIncomplete);
            matchLabel.classList.toggle('text-slate-400', confirmationIsIncomplete);
            updatePasswordButton.disabled = passedRules !== Object.keys(passwordRules).length || !passwordsMatch;
        };

        newPasswordInput.addEventListener('input', updatePasswordFeedback);
        confirmPasswordInput.addEventListener('input', updatePasswordFeedback);
        updatePasswordFeedback();
    </script>
</body>
</html>
