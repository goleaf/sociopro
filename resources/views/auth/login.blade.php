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
                <div class="login-txt auth-panel ms-s ms-lg-5">

                    @if($message = Session::get('error_message'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>{{get_phrase('Public sign up are not allowed')}}!</strong> {{get_phrase('You should contact the site administrator')}}.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <h3>{{get_phrase('Login')}}</h3>
                    <p class="text-muted mb-4">
                        {{ get_phrase('Welcome back. Sign in to continue to your timeline, messages, groups, and account tools.') }}
                    </p>
                    <ul class="auth-value-list" aria-label="{{ get_phrase('Account shortcuts') }}">
                        <li>{{ get_phrase('Timeline') }}</li>
                        <li>{{ get_phrase('Messages') }}</li>
                        <li>{{ get_phrase('Groups') }}</li>
                    </ul>
                       

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
            
                       
                        <x-ui.auth-text-field
                            id="login-email"
                            type="email"
                            name="email"
                            icon="form-email"
                            :label="get_phrase('Email')"
                            :placeholder="get_phrase('Enter your email address')"
                            :error="$errors->first('email')"
                            autocomplete="email"
                            autocapitalize="none"
                            inputmode="email"
                        />
                        <x-ui.auth-password-field
                            id="login-password"
                            name="password"
                            :label="get_phrase('Password')"
                            :placeholder="get_phrase('Your password')"
                            :error="$errors->first('password')"
                            autocomplete="current-password"
                        />
            
                        <!-- Remember Me -->
                        <div class="mb-3 form-check">
                            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                            <label class="form-check-label" for="remember_me">{{ get_phrase('Remember me') }}</label>
                          </div>

                        <x-ui.button variant="auth-primary" type="submit" name="submit" id="submit" class="my-3">
                            {{ get_phrase('Log In') }}
                        </x-ui.button>

                        
                        <div class="flex items-center justify-end mt-2">
                            @if (Route::has('password.request'))
                                <a class="" href="{{ route('password.request') }}">
                                    {{ get_phrase('Forgot your password?') }}
                                </a>
                            @endif
                        </div>

                    </form>

                </div>
            </div>
        </div>

    </div> <!-- container end -->
</main>
<!-- Main End -->

@include('auth.layout.footer')
