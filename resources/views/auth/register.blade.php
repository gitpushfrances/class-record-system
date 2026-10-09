<x-guest-layout>

    {{-- Header --}}
    <div class="form-header">
        <div class="form-eyebrow">
            <span class="form-eyebrow-line"></span>
            New Faculty Access
        </div>
        <h1 class="form-title">Request an Account</h1>
        <p class="form-sub">Enter your details below. An administrator will review your request before you can sign in.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf

        {{-- Name --}}
        @foreach ([['last_name', 'Last Name', 'Dela Cruz', true, 'fa-user'], ['first_name', 'First Name', 'Juan', true, 'fa-user'], ['middle_name', 'Middle Name (optional)', 'Miguel', false, 'fa-user']] as [$field, $label, $hint, $req, $ico])
            <div class="field">
                <label class="field-label" for="{{ $field }}">{{ $label }}</label>
                <div class="field-wrap">
                    <i class="fas {{ $ico }} field-ico"></i>
                    <input id="{{ $field }}" class="field-input" type="text" name="{{ $field }}"
                           value="{{ old($field) }}" placeholder="{{ $hint }}" maxlength="100"
                           @if($req) required @endif @if($loop->first) autofocus @endif />
                </div>
                @error($field)
                    <div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>
                @enderror
            </div>
        @endforeach

        {{-- Email --}}
        <div class="field">
            <label class="field-label" for="email">Email Address</label>
            <div class="field-wrap">
                <i class="fas fa-envelope field-ico"></i>
                <input
                    id="email"
                    class="field-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="you@essu.edu.ph"
                    maxlength="255"
                    required
                    autocomplete="username"
                />
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
                <i class="fas fa-lock field-ico"></i>
                <input
                    id="password"
                    class="field-input"
                    type="password"
                    name="password"
                    placeholder="Minimum 8 characters"
                    required
                    autocomplete="new-password"
                    style="padding-right: 2.6rem;"
                />
                <button type="button" class="eye-btn" onclick="togglePwd('password', 'eyeIco1')" title="Toggle password visibility">
                    <i class="fas fa-eye" id="eyeIco1"></i>
                </button>
            </div>
            @error('password')
                <div class="field-err">
                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="field">
            <label class="field-label" for="password_confirmation">Confirm Password</label>
            <div class="field-wrap">
                <i class="fas fa-lock field-ico"></i>
                <input
                    id="password_confirmation"
                    class="field-input"
                    type="password"
                    name="password_confirmation"
                    placeholder="Re-enter your password"
                    required
                    autocomplete="new-password"
                    style="padding-right: 2.6rem;"
                />
                <button type="button" class="eye-btn" onclick="togglePwd('password_confirmation', 'eyeIco2')" title="Toggle password visibility">
                    <i class="fas fa-eye" id="eyeIco2"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="field-err">
                    <i class="fas fa-circle-exclamation"></i> {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-submit" id="submit-btn" style="margin-top:1.25rem;">
            <span class="btn-label">
                <span class="btn-label-ico">
                    <i class="fas fa-user-plus"></i>
                </span>
                Submit Request
            </span>
            <span class="btn-spinner">
                <span class="spinner-ring"></span>
            </span>
        </button>

    </form>

    <div style="text-align:center; margin-top:1rem;">
        <a href="{{ route('login') }}" class="link-forgot">Already have an account? Sign in</a>
    </div>

    <div class="card-foot">
        <i class="fas fa-shield-halved"></i>
        Requests are reviewed before access is granted
    </div>

</x-guest-layout>

<script>
function togglePwd(inputId, icoId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(icoId);
    const hidden = inp.type === 'password';
    inp.type = hidden ? 'text' : 'password';
    ico.classList.toggle('fa-eye',       !hidden);
    ico.classList.toggle('fa-eye-slash',  hidden);
}

document.getElementById('register-form').addEventListener('submit', function () {
    document.getElementById('submit-btn').classList.add('loading');
});
</script>
