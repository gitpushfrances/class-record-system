<x-guest-layout>
    @if (session('status'))
        <div class="alert-ok"><i class="fas fa-circle-check"></i> {{ session('status') }}</div>
    @endif

    <div class="form-header">
        <div class="form-eyebrow"><span class="form-eyebrow-line"></span> Secure access</div>
        <h1 class="form-title">Sign In</h1>
        <p class="form-sub">Use your username and password.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" id="login-form">
        @csrf

        <div class="field @error('username') has-error @enderror">
            <label class="field-label" for="username">Username</label>
            <div class="field-wrap">
                <i class="fas fa-user field-ico"></i>
                <input id="username" class="field-input" type="text" name="username" value="{{ old('username') }}"
                       placeholder="your.username" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
            </div>
            @error('username')<div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
        </div>

        <div class="field @error('password') has-error @enderror">
            <label class="field-label" for="password">Password</label>
            <div class="field-wrap">
                <i class="fas fa-lock field-ico"></i>
                <input id="password" class="field-input has-eye" type="password" name="password"
                       placeholder="Enter your password" required autocomplete="current-password">
                <button type="button" class="eye-btn" data-toggle="password" aria-label="Show password"><i class="fas fa-eye"></i></button>
            </div>
            @error('password')<div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
        </div>

        <div class="form-row">
            <label class="check-label" for="remember_me">
                <input type="checkbox" name="remember" id="remember_me" @checked(old('remember'))> Remember me
            </label>
        </div>

        <button type="submit" class="btn-submit" id="submit-btn">
            <span class="btn-label"><i class="fas fa-arrow-right-to-bracket"></i> Sign In to Portal</span>
            <span class="btn-spinner"><span class="spinner-ring"></span></span>
        </button>
    </form>

    <p class="form-alt">Forgot your password? Ask your administrator to reset it.</p>

    @if (Route::has('register'))
        <p class="form-alt">New faculty member? <a href="{{ route('register') }}" class="link-forgot">Request an account</a></p>
    @endif

    <div class="card-foot"><i class="fas fa-shield-halved"></i> Authorized ESSU faculty and staff only</div>

    @push('scripts')
        @include('auth.partials.form-scripts', ['form' => 'login-form'])
    @endpush
</x-guest-layout>
