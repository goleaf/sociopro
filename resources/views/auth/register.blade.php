@include('auth.layout.header')
    

    <!-- Main Start -->
    <main class="main auth-entry-main my-4 p-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="login-img auth-visual-frame">
                        <img class="img-fluid" src="{{ asset('assets/frontend/images/login.png') }} " alt="">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-txt auth-panel ms-0 ms-lg-5">
                        <h3>{{get_phrase('Sign Up')}}</h3>
                        <p class="text-muted mb-4">
                            {{ get_phrase('Create your account to start posting, joining groups, messaging friends, and managing your community profile.') }}
                        </p>
                        <ul class="auth-value-list" aria-label="{{ get_phrase('Account benefits') }}">
                            <li>{{ get_phrase('Profile') }}</li>
                            <li>{{ get_phrase('Groups') }}</li>
                            <li>{{ get_phrase('Messages') }}</li>
                        </ul>
                        

                        <form action="{{ route('register') }}" method="POST">
                            @csrf
                            <x-ui.auth-text-field
                                id="register-name"
                                type="text"
                                name="name"
                                icon="form-name"
                                :label="get_phrase('Full Name')"
                                :placeholder="get_phrase('Your full name')"
                                :error="$errors->first('name')"
                                autocomplete="name"
                                maxlength="255"
                            />
                            <x-ui.auth-text-field
                                id="register-email"
                                type="email"
                                name="email"
                                icon="form-email"
                                :label="get_phrase('Email')"
                                :placeholder="get_phrase('Enter your email address')"
                                :error="$errors->first('email')"
                                autocomplete="email"
                                autocapitalize="none"
                                inputmode="email"
                                maxlength="255"
                            />
                            <x-ui.auth-password-field
                                id="register-password"
                                name="password"
                                :label="get_phrase('Password')"
                                :placeholder="get_phrase('Your password')"
                                :error="$errors->first('password')"
                                autocomplete="new-password"
                            />

                            <x-ui.auth-password-field
                                id="register-password-confirmation"
                                name="password_confirmation"
                                :label="get_phrase('Confirm Password')"
                                :placeholder="get_phrase('Confirm password')"
                                autocomplete="new-password"
                            />
                            <input type="hidden" name="timezone" id="timezone" value="">
                            <div class="mb-3 form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    name="check1"
                                    id="exampleCheck1"
                                    required
                                    @checked(old('check1'))
                                    aria-describedby="signup-terms-help @error('check1') signup-terms-error @enderror"
                                    @error('check1') aria-invalid="true" @enderror
                                >
                                <label class="form-check-label" for="exampleCheck1">{{get_phrase('I accept the')}} <a href="{{ route('term.view') }}">{{get_phrase('Terms and Conditions')}}</a></label>
                                <p id="signup-terms-help" class="text-muted mt-1 mb-0">
                                    {{ get_phrase('Accept the terms to create your account.') }}
                                </p>
                                @error('check1')
                                    <p id="signup-terms-error" class="text-danger mt-1 mb-0" aria-live="polite">{{ $message }}</p>
                                @enderror
                              </div>
                            <x-ui.button
                                variant="auth-primary"
                                type="submit"
                                name="submit"
                                id="submit"
                                class="my-3"
                                aria-describedby="signup-terms-help"
                            >
                                {{ get_phrase('Sign Up') }}
                            </x-ui.button>

                        </form>

                    </div>
                </div>
            </div>

        </div> <!-- container end -->
    </main>
    <!-- Main End -->



@include('auth.layout.footer')
