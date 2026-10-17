<x-guest-layout wide>
    <div class="form-header">
        <div class="form-eyebrow"><span class="form-eyebrow-line"></span> New faculty</div>
        <h1 class="form-title">Request an Account</h1>
        <p class="form-sub">Fill in your details. Your Dean or the Administrator reviews the request before you can sign in.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" id="register-form">
        @csrf

        <p class="form-section">Your name</p>
        <div class="form-grid cols-3">
            @foreach ([['last_name', 'Last name', 'Dela Cruz', true], ['first_name', 'First name', 'Juan', true], ['middle_name', 'Middle name', 'Santos', false]] as [$field, $label, $hint, $required])
                <div class="field @error($field) has-error @enderror">
                    <label class="field-label" for="{{ $field }}">{{ $label }} @unless($required)<span class="opt">(optional)</span>@endunless</label>
                    <div class="field-wrap">
                        <i class="fas fa-user field-ico"></i>
                        <input id="{{ $field }}" class="field-input" type="text" name="{{ $field }}" value="{{ old($field) }}"
                               placeholder="{{ $hint }}" maxlength="100" @if($required) required @endif @if($loop->first) autofocus @endif
                               autocomplete="{{ ['last_name' => 'family-name', 'first_name' => 'given-name', 'middle_name' => 'additional-name'][$field] }}">
                    </div>
                    @error($field)<div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
                </div>
            @endforeach
        </div>

        <hr class="form-divider">
        <p class="form-section">Account</p>

        <div class="field @error('email') has-error @enderror">
            <label class="field-label" for="email">Email address</label>
            <div class="field-wrap">
                <i class="fas fa-envelope field-ico"></i>
                <input id="email" class="field-input" type="email" name="email" value="{{ old('email') }}"
                       placeholder="you@essu.edu.ph" maxlength="255" required autocomplete="username">
            </div>
            @error('email')<div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
        </div>

        <div class="form-grid cols-2">
            <div class="field @error('password') has-error @enderror">
                <label class="field-label" for="password">Password</label>
                <div class="field-wrap">
                    <i class="fas fa-lock field-ico"></i>
                    <input id="password" class="field-input has-eye" type="password" name="password"
                           placeholder="At least 8 characters" required autocomplete="new-password">
                    <button type="button" class="eye-btn" data-toggle="password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                </div>
                @error('password')<div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
            </div>
            <div class="field @error('password_confirmation') has-error @enderror">
                <label class="field-label" for="password_confirmation">Confirm password</label>
                <div class="field-wrap">
                    <i class="fas fa-lock field-ico"></i>
                    <input id="password_confirmation" class="field-input has-eye" type="password" name="password_confirmation"
                           placeholder="Re-enter password" required autocomplete="new-password">
                    <button type="button" class="eye-btn" data-toggle="password_confirmation" aria-label="Show password"><i class="fas fa-eye"></i></button>
                </div>
                @error('password_confirmation')<div class="field-err"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
            </div>
        </div>

        <button type="submit" class="btn-submit" id="submit-btn" style="margin-top:6px;">
            <span class="btn-label"><i class="fas fa-paper-plane"></i> Submit request</span>
            <span class="btn-spinner"><span class="spinner-ring"></span></span>
        </button>
    </form>

    <p class="form-alt">Already have an account? <a href="{{ route('login') }}" class="link-forgot">Sign in</a></p>

    <div class="card-foot"><i class="fas fa-shield-halved"></i> Every request is reviewed before access is granted</div>

    @push('scripts')
        @include('auth.partials.form-scripts', ['form' => 'register-form'])
    @endpush
</x-guest-layout>
