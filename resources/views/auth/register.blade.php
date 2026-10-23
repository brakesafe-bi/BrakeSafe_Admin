<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>BRAKESAFE — Register</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Rajdhani:wght@600&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --bg-1:#030814; --bg-2:#071426; --panel:#08172a;
            --accent:#ef4444; --accent-2:#f87171; --success:#34d399; --danger:#fb7185; --info:#60a5fa;
            --muted:#a5b4fc; --glass-bg:rgba(8, 17, 34, 0.82);
        }
        html,body{height:100%;margin:0;font-family:Inter,system-ui,Arial;color:#fff;background:var(--bg-1);-webkit-font-smoothing:antialiased}
        .scene{position:fixed;inset:0;overflow:hidden;z-index:0;background:linear-gradient(180deg,#050506 0%,#07121a 60%);}
        .mountain{position:absolute;left:0;right:0;bottom:0;height:22%;background:linear-gradient(180deg,#0b1118 0%,transparent 100%);filter:blur(6px);opacity:0.6}
        .fog{position:absolute;inset:0;background:radial-gradient(ellipse at 50% 60%,rgba(255,255,255,0.02),transparent 40%);pointer-events:none;mix-blend-mode:screen}
        .grid-overlay{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.01) 1px, transparent 1px);background-size:24px 24px,24px 24px;opacity:0.18;transform:translateZ(0);pointer-events:none}
        .scanlines{position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px);background-size:100% 18px;mix-blend-mode:overlay;opacity:0.06;animation:scan 8s linear infinite}
        @keyframes scan{from{background-position:0 0}to{background-position:0 360px}}
        .particles{position:absolute;inset:0;pointer-events:none}
        .hotspot{position:absolute;border-radius:999px;background:radial-gradient(circle at 50% 50%, rgba(239,68,68,0.45), rgba(239,68,68,0.08) 40%, transparent 60%);filter:blur(6px);animation:pulse 3s ease-in-out infinite}
        @keyframes pulse{0%{transform:scale(.9);opacity:.9}50%{transform:scale(1.12);opacity:1}100%{transform:scale(.9);opacity:.9}}
        .radar{position:absolute;right:8%;top:8%;width:220px;height:220px;border-radius:50%;overflow:hidden;mix-blend-mode:screen;opacity:0.12}
        .radar:before{content:"";position:absolute;inset:0;background:conic-gradient(from 0deg, rgba(239,68,68,0.14), transparent 30%);transform-origin:center;animation:radar-rot 6s linear infinite}
        @keyframes radar-rot{from{transform:rotate(0)}to{transform:rotate(360deg)}}

        .center-wrap{position:relative;z-index:10;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:28px}
        .login-card{width:min(520px,94vw);max-width:100%;background:var(--glass-bg);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-radius:20px;border:1px solid rgba(239,68,68,0.24);box-shadow:0 20px 60px rgba(239,68,68,0.08);padding:36px}
        .header{display:flex;align-items:center;gap:14px;margin-bottom:18px}
        .status-row{display:flex;flex-wrap:wrap;justify-content:center;gap:10px;margin:10px 0 18px}
        .status-item{flex:1 1 140px;min-width:130px;display:flex;align-items:center;gap:8px;padding:8px 10px;background:rgba(255,255,255,0.02);border-radius:8px;font-size:12px;color:var(--muted)}
        .brand-mark{width:64px;height:64px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent-2));display:flex;align-items:center;justify-content:center;font-family:Orbitron,monospace;font-weight:700;color:#081010;font-size:28px}
        .titles{line-height:1}
        .titles h1{font-family:Orbitron,Rajdhani,monospace;margin:0;font-size:18px;letter-spacing:3px;text-transform:uppercase;color:var(--accent-2)}
        .titles p{margin:6px 0 0;color:#BFBFBF;font-size:12px}
        .led{width:10px;height:10px;border-radius:999px;box-shadow:0 0 6px rgba(0,0,0,0.6)}
        .led.on{background:var(--success);box-shadow:0 0 8px rgba(46,204,113,0.25);animation:led-pulse 2s infinite}
        .led.warn{background:var(--accent);box-shadow:0 0 8px rgba(56,189,248,0.18);animation:led-pulse 2s infinite}
        .led.off{background:var(--danger);box-shadow:0 0 8px rgba(255,64,64,0.12)}
        @keyframes led-pulse{0%{transform:scale(.95)}50%{transform:scale(1.08)}100%{transform:scale(.95)}}

        .form-group{margin-bottom:14px}
        label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px}
        .field{display:flex;align-items:center;gap:10px;background:#0b0f13;border:1px solid rgba(239,68,68,0.12);padding:10px 12px;border-radius:10px}
        .field input{background:transparent;border:0;outline:none;color:#fff;width:100%;font-size:14px}
        .field svg{opacity:0.9;color:var(--accent)}
        .field:focus-within{box-shadow:0 0 20px rgba(239,68,68,0.18);border-color:rgba(239,68,68,0.38)}

        .error-box{background:rgba(255,64,64,0.12);border:1px solid rgba(255,64,64,0.25);padding:12px;border-radius:10px;color:#ffdede;display:flex;gap:10px;align-items:flex-start;animation:fade-in .28s ease}
        @keyframes fade-in{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none}}

        .btn{width:100%;padding:14px;border-radius:12px;border:0;font-weight:700;background:linear-gradient(90deg,var(--accent),var(--accent-2));color:#fff;cursor:pointer;box-shadow:0 18px 40px rgba(239,68,68,0.18);transition:transform .12s ease,box-shadow .12s ease}
        .btn:hover{transform:scale(1.02);box-shadow:0 20px 48px rgba(239,68,68,0.22)}
        .btn:disabled{opacity:0.7;cursor:default;transform:none}

        .small{font-size:12px;color:var(--muted)}
        .footer-links{display:flex;justify-content:space-between;margin-top:12px;align-items:center}
        .spinner{border:3px solid rgba(0,0,0,0.08);border-left-color:#081010;border-radius:50%;width:18px;height:18px;display:inline-block;vertical-align:middle;animation:spin .8s linear infinite}
        @keyframes spin{to{transform:rotate(360deg)}}

        @media(max-width:520px){
            .login-card{padding:28px;width:min(92vw,520px)}
            .radar{display:none}
            .header{flex-direction:column;align-items:flex-start;gap:12px}
            .status-row{justify-content:center}
            .field{min-width:0}
        }
    </style>
</head>
<body>
    <div class="scene" aria-hidden="true">
        <div class="mountain"></div>
        <div class="grid-overlay"></div>
        <div class="scanlines"></div>
        <div class="hotspot" style="left:22%;top:46%;width:160px;height:160px;animation-delay:0s"></div>
        <div class="hotspot" style="left:62%;top:36%;width:120px;height:120px;animation-delay:.6s"></div>
        <div class="hotspot" style="left:42%;top:66%;width:90px;height:90px;animation-delay:.3s"></div>
        <div class="radar"></div>
        <svg class="particles" width="0" height="0" style="position:absolute;inset:0"></svg>
    </div>
    <main class="center-wrap">
        <section class="login-card" role="main" aria-labelledby="registerTitle">
            <header style="display:flex;align-items:center;gap:14px;margin-bottom:12px">
                <img src="{{ asset('administrator/logo.png') }}" alt="BrakeSafe logo" style="width:64px;height:auto;border-radius:14px;box-shadow:0 10px 24px rgba(0,0,0,0.18)">
                <div class="titles">
                    <h1 id="registerTitle">BRAKESAFE</h1>
                    <p>Administrator Registration</p>

                </div>
            </header>

            <div class="status-row">
                <div class="status-item"><span class="led" style="background:#2ECC71"></span><span>GPS ONLINE</span></div>
                <div class="status-item"><span class="led" style="background:#2ECC71"></span><span>SERVER CONNECTED</span></div>
                <div class="status-item"><span class="led" style="background:var(--accent)"></span><span>AI DETECTION READY</span></div>
            </div>

            @if ($adminCount >= 2)
                <div class="error-box" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M12 9v4" stroke="#FF4040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17h.01" stroke="#FF4040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.29 3.86L1.82 18.05A2 2 0 0 0 3.62 21h16.76a2 2 0 0 0 1.8-2.95L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="#FF4040" stroke-width="0.6" fill="rgba(255,64,64,0.06)"/></svg>
                    <div><strong>Admin slots full.</strong> Maximum 2 administrators allowed.</div>
                </div>
            @else
                <div style="background:rgba(46,204,113,0.06);border:1px solid rgba(46,204,113,0.12);padding:10px;border-radius:10px;color:#c8f5dd;margin-bottom:10px">{{ 2 - $adminCount }} admin slot(s) available</div>
            @endif

            @if ($errors->any())
                <div class="error-box" role="alert" aria-live="polite">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M12 9v4" stroke="#FF4040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 17h.01" stroke="#FF4040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.29 3.86L1.82 18.05A2 2 0 0 0 3.62 21h16.76a2 2 0 0 0 1.8-2.95L13.71 3.86a2 2 0 0 0-3.42 0z" stroke="#FF4040" stroke-width="0.6" fill="rgba(255,64,64,0.06)"/></svg>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <form id="register-form" method="POST" action="/register" class="mt-4">
                @csrf
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <div class="field"><input id="full_name" name="full_name" type="text" required value="{{ old('full_name') }}"></div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="field"><input id="email" name="email" type="email" required value="{{ old('email') }}"></div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="field" style="position:relative">
                        <input id="password" name="password" type="password" required>
                        <button type="button" data-password-toggle="password" aria-label="Show password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:0;color:var(--accent);font-weight:600">
                            <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <div class="field" style="position:relative">
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                        <button type="button" data-password-toggle="password_confirmation" aria-label="Show password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:0;color:var(--accent);font-weight:600">
                            <svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden>
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div style="margin-top:6px">
                    <button id="registerBtn" type="submit" {{ $adminCount >= 2 ? 'disabled' : '' }} class="btn {{ $adminCount >= 2 ? 'opacity-50 cursor-not-allowed' : '' }}">
                        <span id="regText">Create Account</span>
                        <span id="regSpinner" style="display:none;margin-left:10px" aria-hidden><span class="spinner"></span></span>
                    </button>
                </div>
            </form>

            <p class="text-center" style="color:var(--muted);margin-top:12px;font-size:13px">Already have an account? <a href="/login" style="color:var(--accent);text-decoration:underline">Sign in</a></p>
        </section>
    </main>

    <script>
        // robust password toggle for register page
        (function(){
            const svgShow = '<svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            const svgHide = '<svg class="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M3 3l18 18" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.58 10.58a3 3 0 0 0 4.24 4.24" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2.5 12s3.5-6 9.5-6c2.08 0 3.98.52 5.5 1.36" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            document.querySelectorAll('[data-password-toggle]').forEach(btn=>{
                if(!btn.innerHTML.trim()) btn.innerHTML = svgShow;
                btn.style.cursor = 'pointer';
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    const id = this.getAttribute('data-password-toggle');
                    const el = document.getElementById(id);
                    if(!el) return;
                    try{
                        if(el.getAttribute('data-visible') === '1'){
                            el.type = 'password'; el.removeAttribute('data-visible'); this.innerHTML = svgShow; this.setAttribute('aria-label','Show password'); el.style.webkitTextSecurity = '';
                        } else {
                            el.type = 'text'; el.setAttribute('data-visible','1'); this.innerHTML = svgHide; this.setAttribute('aria-label','Hide password');
                            try{ el.style.webkitTextSecurity = 'none'; }catch(_){}
                        }
                        el.style.color = '#ffffff'; el.focus();
                    }catch(err){console.error('toggle password',err)}
                });
            });
        })();
        (function(){
            const form = document.getElementById('register-form');
            const btn = document.getElementById('registerBtn');
            const text = document.getElementById('regText');
            const spinner = document.getElementById('regSpinner');
            form.addEventListener('submit', function(){
                btn.disabled = true; text.textContent = 'CREATING...'; spinner.style.display = 'inline-block';
            });
        })();
    </script>
</body>
</html>
