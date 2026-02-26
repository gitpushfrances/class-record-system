<x-guest-layout>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="alert-ok">
            <i class="fas fa-circle-check"></i>
            {{ session('status') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="form-header">
        <div class="form-eyebrow">Secure Access</div>
        <h1 class="form-title">Sign In</h1>
        <p class="form-sub">Enter your credentials to continue.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div class="field">
            <label class="field-label" for="email">Email Address</label>
            <div class="field-wrap">
                <input
                    id="email"
                    class="field-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="you@school.edu.ph"
                    required autofocus
                    autocomplete="username"
                    style="padding-left:2.4rem;"
                />
                <i class="fas fa-envelope field-ico"></i>
            </div>
            @error('email')
                <div class="field-err">
                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="field">
            <label class="field-label" for="password">Password</label>
            <div class="field-wrap">
                <input
                    id="password"
                    class="field-input"
                    type="password"
                    name="password"
                    placeholder="••••••••••"
                    required
                    autocomplete="current-password"
                    style="padding-left:2.4rem; padding-right:2.5rem;"
                />
                <i class="fas fa-lock field-ico"></i>
                <button type="button" class="eye-btn" onclick="togglePwd()" title="Toggle password">
                    <i class="fas fa-eye" id="eyeIco"></i>
                </button>
            </div>
            @error('password')
                <div class="field-err">
                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Remember + Forgot --}}
        <div class="form-row">
            <label class="check-label" for="remember_me">
                <input type="checkbox" name="remember" id="remember_me" {{ old('remember') ? 'checked' : '' }}>
                Remember me
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-forgot">Forgot password?</a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-submit">
            <span class="btn-ico">
                <i class="fas fa-arrow-right-to-bracket"></i>
            </span>
            Sign In to Portal
        </button>
    </form>

    <div class="card-foot">
        <i class="fas fa-shield-halved"></i>
        Authorized faculty and staff only
    </div>

</x-guest-layout>

<script>
function togglePwd() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('eyeIco');
    const hidden = inp.type === 'password';
    inp.type = hidden ? 'text' : 'password';
    ico.classList.toggle('fa-eye',       !hidden);
    ico.classList.toggle('fa-eye-slash',  hidden);
}
</script>
