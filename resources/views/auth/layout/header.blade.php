<!DOCTYPE html>
<html lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $systemName }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="{{ get_system_logo_favicon($systemFavicon,'favicon') }}">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fontawesome/all.min.css') }}">
    <!-- CSS Library -->
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/venobox.min.css') }}">

    <!-- Style css -->
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/own.css') }}">

    <style>
        body.login {
            background: #f6f6f9;
            color: #101010;
            min-height: 100vh;
        }

        .login .header {
            background: #fff;
            border-bottom: 1px solid #dedede;
        }

        .login .auth-entry-main {
            margin-top: 0 !important;
            padding-bottom: 50px !important;
            padding-top: 30px !important;
        }

        .login .auth-entry-main .row {
            min-height: calc(100vh - 150px);
        }

        .login .auth-visual-frame {
            background: #dfd9f6;
            border: 1px solid #dedede;
            border-radius: 20px;
            overflow: hidden;
            padding: 30px;
            position: relative;
        }

        .login .auth-visual-frame::after {
            background: #5a2ff9;
            border-radius: 50%;
            bottom: 24px;
            content: "";
            height: 86px;
            opacity: .12;
            position: absolute;
            right: 24px;
            width: 86px;
        }

        .login .auth-visual-frame img {
            position: relative;
            z-index: 1;
        }

        .login .auth-panel {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, .06);
            margin-left: auto;
            max-width: 520px;
            padding: 30px;
        }

        .login .auth-panel h3 {
            color: #101010;
            font-size: 42px;
            font-weight: 600;
            line-height: 1.12;
            margin-bottom: 12px;
        }

        .login .auth-panel .text-muted {
            color: #6c757d !important;
            line-height: 1.6;
        }

        .login .auth-value-list {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            list-style: none;
            margin: 0 0 24px;
            padding: 0;
        }

        .login .auth-value-list li {
            background: #dfd9f6;
            border-radius: 50px;
            color: #4929c2;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.2;
            overflow-wrap: break-word;
            padding: 9px 10px;
            text-align: center;
        }

        .login .auth-panel .btn-primary {
            min-height: 44px;
            padding-inline: 32px;
        }

        .password-toggle-field input {
            padding-right: 46px;
        }

        .password-toggle-button {
            align-items: center;
            background: transparent;
            border: 0;
            color: #949494;
            display: inline-flex;
            height: 40px;
            justify-content: center;
            padding: 0;
            position: absolute;
            right: 8px;
            top: 72%;
            transform: translateY(-50%);
            width: 40px;
            z-index: 2;
        }

        .password-toggle-button:hover,
        .password-toggle-button:focus-visible {
            color: #5a2ff9;
        }

        .password-toggle-button:focus-visible {
            outline: 2px solid #5a2ff9;
            outline-offset: 2px;
        }

        @media (max-width: 991px) {
            .login .auth-entry-main {
                padding: 20px !important;
            }

            .login .auth-entry-main .row {
                min-height: auto;
            }

            .login .auth-visual-frame {
                margin-bottom: 20px;
            }

            .login .auth-panel {
                margin-left: 0;
                max-width: none;
                padding: 24px;
            }

            .login .auth-panel h3 {
                font-size: 34px;
            }
        }

        @media (max-width: 575px) {
            .login .auth-value-list {
                grid-template-columns: 1fr;
            }
        }
    </style>

   
</head>

<body class="bg-white login">


<!-- header -->
    <header class="header header-default py-3">
    <nav class="navigation">
        <div class="container">
            <div class="row">
                <div class="col-auto col-lg-6">
                    <div class="logo-branding mt-1">
                        <a class="navbar-brand d-xs-hidden" href="{{ $homeUrl }}">
                            <img src="{{ get_system_logo_favicon($systemLightLogo,'light') }}" height="35px" class="max-width-250px d-xs-hidden" alt="logo" />
                        </a>

                        <a class="navbar-brand d-block" href="{{ $homeUrl }}">
                            <img src="{{ get_system_logo_favicon($systemLightLogo,'favicon') }}" height="35px" class="max-width-250px d-hidden d-xs-show mt--5px" alt="logo" />
                        </a>
                    </div>
                </div>

                <div class="col-auto col-lg-6 ms-auto">
                    <div class="login-btns ms-5">
                        <a href="{{ route('login') }}" class="btn @if(Route::currentRouteName() == 'login') active @endif">{{  __('Login') }}</a>
                        @if(get_settings('public_signup') == 1)
                            <a href="{{ route('register') }}" class="btn @if(Route::currentRouteName() == 'register') active @endif">{{ __('Sign up')  }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
<!-- Header End -->
