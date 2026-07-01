<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    @php
        $system_name = \App\Models\Setting::where('type', 'system_name')->value('description');
        $system_favicon = \App\Models\Setting::where('type', 'system_fav_icon')->value('description');
    @endphp
    <title>{{ $system_name }}</title>

    <!-- CSRF Token for ajax for submission -->
    <meta name="csrf_token" content="{{ csrf_token() }}" />

    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="shortcut icon" href="{{ get_system_logo_favicon($system_favicon, 'favicon') }}" />

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fontawesome/all.min.css') }}">
    <!-- CSS Library -->

    <link rel="stylesheet" href="{{ asset('assets/frontend/css/owl.carousel.min.css') }}">

    <!-- Style css -->
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/nice-select.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/plyr/plyr.css') }}">
    <link href="{{ asset('assets/frontend/leafletjs/leaflet.css') }}" rel="stylesheet">

    <link href="{{ asset('assets/frontend/css/plyr_cdn_dw.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/frontend/css/tagify.css') }}" rel="stylesheet">

    <link href="{{ asset('assets/frontend/uploader/file-uploader.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/frontend/css/jquery-rbox.css') }}" rel="stylesheet">

    <!-- paid content start -->
    <link rel="apple-touch-icon" href="images/favicon.png" />
    <link rel="shortcut icon" href="images/favicon.ico" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/paid-content/css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/addon_layout.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/paid-content/css/new_scss/new_style.css') }}" />
    <!-- paid content end -->




    <link rel="stylesheet" href="{{ asset('assets/frontend/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/gallery/justifiedGallery.min.css') }}">
    <link href="{{ asset('assets/frontend/toaster/toaster.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('assets/frontend/summernote-0.8.18-dist/summernote-lite.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/own.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/pc.style.css') }}" />

    {{-- fundraiser --}}
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/style_make.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/custom_style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/new-style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/new-responsive.css') }}" />
    
   
    <!-- New -->
    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/new_scss/new_style.css') }}" />

    <link rel="apple-touch-icon" href="{{ asset('assets/frontend/css/fundraiser/images/favicon.png') }}" />

    {{-- end fundraiser --}}

    <link rel="stylesheet" href="{{ asset('assets/frontend/css/daterangepicker.css') }}">


    <link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/custom_new.css') }}" />

    {{-- Job Addon Css --}}
    @if (addon_status('job') == 1)
     <link rel="stylesheet" href="{{ asset('assets/frontend/css/job/style.css') }}" />
    @endif
    {{-- Job Addon Css --}}
	<link rel="stylesheet" href="{{ asset('assets/frontend/css/fundraiser/css/custom_responsive.css') }}" />
    <script src="{{ asset('assets/frontend/js/jquery-3.6.0.min.js') }}"></script>


</head>
    @if(Session::get('theme_color'))
        @php
            $theme_color = Session::get('theme_color');
            if ($theme_color === 'dark') {
                $image = asset('assets/frontend/images/white_sun.svg');
            } else {
               
                $image = asset('assets/frontend/images/white_moon.svg');
            }
        @endphp
  @else
        @php
            $theme_color = 'default';
            $image = asset('assets/frontend/images/white_moon.svg');
        @endphp
 @endif

@php
    $themeColor = App\Models\Setting::where('type', 'theme_color')->value('description');
@endphp
<body class="{{$themeColor}} {{$theme_color}}">
    @php $user_info = Auth()->user() @endphp

    <div class="custom-progress-bar">
        <div class="custom-progress"></div>
    </div>
    <header class="header header-default py-3">
        <nav class="navigation">
            <div class="container">
                <div class="row">
                    <div class="col-lg-2 col-sm-4">
                        <div class="logo-branding">
                            <button class="d-lg-none" type="button" data-bs-toggle="offcanvas"
                                data-bs-target="#offcanvasRight" aria-controls="offcanvasRight"><i
                                    class="fw-bold fa-solid fa-sliders-h"></i></button>
                            <!-- logo -->
                            @php
                                $system_light_logo = \App\Models\Setting::where('type', 'system_light_logo')->value('description');
                            @endphp
                            <a class="navbar-brand mt-2" href="{{ route('timeline') }}"><img
                                    src="{{ get_system_logo_favicon($system_light_logo, 'light') }}"
                                    class="logo_height_width" alt="logo" /></a>
                        </div>
                    </div>
                    <div class="col-lg-6 d-none d-lg-block">
                        <div class="header-search">
                            <a href="{{ route('timeline') }}">
                                <div class="sc-home rounded">
                                    <i class="fa-solid fa-house"></i>
                                </div>
                            </a>
                            <div class="sc-search">
                                <form action="{{ route('search') }}" method="GET" id="form_id">
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <mask id="mask0_16_3776" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="14" height="14">
                                        <path d="M14 0H0V14H14V0Z" fill="white"/>
                                        </mask>
                                        <g mask="url(#mask0_16_3776)">
                                        <path d="M13.3183 13.9465C13.1728 13.8595 13.033 13.7609 12.9049 13.6506C12.0022 12.763 11.1054 11.8696 10.2143 10.9762C10.1794 10.9414 10.1503 10.9008 10.1212 10.8602C7.50636 13.0357 3.622 12.6876 1.43814 10.0828C-0.739895 7.48374 -0.396301 3.60841 2.21851 1.43288C4.82749 -0.742649 8.71768 -0.394565 10.9015 2.21027C12.8117 4.49022 12.8117 7.80282 10.9015 10.0828C10.9481 10.1118 10.9889 10.1466 11.0297 10.1814C11.9207 11.069 12.8117 11.9624 13.7027 12.85C13.8134 12.9777 13.9124 13.1169 13.9997 13.2619V13.5056C13.924 13.7087 13.7668 13.8653 13.5629 13.9407H13.3183V13.9465ZM1.14114 6.12041C1.12949 8.88188 3.37159 11.1328 6.14363 11.1444C8.91568 11.156 11.1752 8.92249 11.1869 6.16102C11.1985 3.39955 8.96227 1.14861 6.1844 1.13701C3.41235 1.1254 1.15278 3.35894 1.13531 6.12041" fill="white"/>
                                        </g>
                                        </svg>
                                        
                                    <input type="search"  class="rounded white-placeholder hashtag-link" name="search"
                                        value="@isset($_GET['search']){{ $_GET['search'] }}@endisset"
                                        placeholder="Search">
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-8">
                        <div class="header-controls">
                            <div class="align-items-center d-flex justify-content-end g-12">
    
                                <div class="group-control">
                                    {{-- <a href="javascript:;" class="notification-button"><img id="dark" src="{{$image}}" alt=""></a> --}}
                                </div>
                                <div class="group-control">
                                    <a href="{{ route('profile.friends') }}" class="notification-button"><i
                                            class="fa-solid fa-user-group"></i></a>
                                </div>
                                @php
                                    $last_msg = \App\Models\Chat::where('sender_id', auth()->user()->id)
                                        ->orWhere('reciver_id', auth()->user()->id)
                                        ->orderBy('id', 'DESC')
                                        ->limit('1')
                                        ->first();
                                    if (!empty($last_msg)) {
                                        if ($last_msg->sender_id == auth()->user()->id) {
                                            $msg_to = $last_msg->reciver_id;
                                        } else {
                                            $msg_to = $last_msg->sender_id;
                                        }
                                    }
                                    
                                    $unread_msg = \App\Models\Chat::where('reciver_id', auth()->user()->id)
                                        ->where('read_status', '0')
                                        ->count();
                                @endphp
                                <div class="inbox-control">
                                    <a href="@if(isset($msg_to)) {{ route('chat', $msg_to) }} @else {{route('chat','all')}} @endif"
                                        class="message_custom_button position-relative">
                                        <i class="fa-brands fa-rocketchat"></i>
                                        @if ($unread_msg > 0)
                                            <span
                                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill notificatio_counter_bg">
                                                {{ get_phrase($unread_msg) }}
                                            </span>
                                        @endif
                                    </a>
                                </div>
                                @php
                                    $unread_notification = \App\Models\Notification::where('reciver_user_id', auth()->user()->id)
                                        ->where('status', '0')
                                        ->count();
                                @endphp
                                <div class="notify-control ">
                                    <a class="notification-button position-relative" href="{{ route('notifications') }}">
                                        <i class="fa-solid fa-bell"></i>
                                        @if ($unread_notification > 0)
                                            <span
                                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill notificatio_counter_bg">
                                                {{ get_phrase($unread_notification) }}
                                            </span>
                                        @endif
                                    </a>
                                </div>
                                <div class="profile-control dropdown">
                                    <button class="dropdown-toggle" type="button" id="dropdownMenuButton1"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <img src="{{ get_user_image(auth()->user()->photo, 'optimized') }}"
                                            class="rounded-circle" alt="">
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                        <li><a class="dropdown-item"
                                                href="{{ route('profile') }}">{{ get_phrase('My Profile') }}</a></li>
                                        @if (auth()->user()->user_role == 'admin')
                                            <li><a class="dropdown-item"
                                                    href="{{ route('admin.dashboard') }}">{{ get_phrase('Go to admin panel') }}</a>
                                            </li>
                                        @endif
    
                                        @if (auth()->user()->user_role == 'general')
                                            <li><a class="dropdown-item"
                                                    href="{{ route('user.dashboard') }}">{{ get_phrase('Dashboard') }}</a>
                                            </li>
                                        @endif
    
                                        {{-- <li>
                                            <a class="dropdown-item"
                                                href="{{ route('addons.manager') }}">{{ get_phrase('Addons') }}
                                            </a>
                                        </li> --}}
                                        <li>
                                            <a class="dropdown-item"
                                                href="{{ route('user.settings') }}">{{ get_phrase('Payment Settings') }}
                                            </a>
                                        </li>
                                        
                                        @if (auth()->user()->status == 1)
                                            <li>
                                                <a href="javascript:void(0)" 
                                                onclick="confirmAction('{{ route('user.status', ['id' => auth()->user()->id]) }}', true)" 
                                                class="dropdown-item">{{ get_phrase('Account Deactivate') }}</a>
    
                                                {{-- <a class="dropdown-item" href="javascript:void(0)"
                                        onclick="confirmAction('<?php echo route('post.delete', ['post_id' => $post->post_id]); ?>', true)"> <i
                                            class="fa-solid fa-trash-can"></i> {{ get_phrase('Delete') }}</a> --}}
                                            </li>
                                        @endif
    
    
                                        <li><a class="dropdown-item"
                                                href="{{ route('user.password.change') }}">{{ get_phrase('Change Password') }}</a>
                                        </li>
                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <a class="dropdown-item" href="route('logout')"
                                                    onclick="event.preventDefault();
                                                                        this.closest('form').submit();">
                                                    {{ get_phrase('Log Out') }}
                                                </a>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    <!-- Header End -->

    <!-- Main Start -->
    <main class="main my-4 mt-12">
        <div class="container">
            <div class="row">

                @if (isset($layout))
                    @include($view_path)
                @else
                    <div class="col-lg-3">
                      
                    </div>
                    <!-- Timeline Navigation End -->

                    <!-- Content Section Start -->
                    @php
                       $acc_act_req = DB::table('account_active_requests')
                     ->where('user_id', auth()->user()->id)
                     ->first();
                          
                    @endphp
                    <div class="col-lg-6 col-sm-12 order-3 order-lg-2">
                        <div class="newsfeed-form single-entry mt-5">
                            <div class="entry-inner current-entry">
                                <h4 class="widget-title">{{get_phrase('Your account has been Deactivate')}}</h4>
                                <p class="ellipsis-line-3 pe-2 text-dark p-2 ">{{get_phrase('Click the button to request account activation from the admin.')}}</p>
                                @if($acc_act_req)
                                    @if($acc_act_req->status == 'pending')
                                        <button class="btn common_btn w-100 mb-4">{{ get_phrase('Account Active Request Pending') }}</button>
                                    @else
                                        <form action="{{ route('frontend.account_enble_req', ['id' => auth()->user()->id]) }}">
                                            <button type="submit" class="btn common_btn w-100 mb-4">{{ get_phrase('Account Active Request') }}</button>
                                        </form>
                                    @endif
                                @else
                                    <form action="{{ route('frontend.account_enble_req', ['id' => auth()->user()->id]) }}">
                                        <button type="submit" class="btn common_btn w-100">{{ get_phrase('Request Account Activation') }}</button>
                                    </form>
                                @endif
                            </div> 
                        </div> 
                    </div>
                    <div class="col-lg-3 order-2 order-lg-3">
                       
                    </div>
                @endif

            </div> <!-- row end -->

        </div> <!-- container end -->
    </main>
    <!-- Main End -->

    <!-- Common modals -->
    @include('frontend.modal')



    <!--Javascript
    ========================================================-->
    <script src="{{ asset('assets/frontend/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/venobox.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/timepicker.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/jquery.datepicker.min.js') }}"></script>


    <script src="{{ asset('assets/frontend/js/jquery.nice-select.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/plyr/plyr.js') }}"></script>
    <script src="{{ asset('assets/frontend/jquery-form/jquery.form.min.js') }}"></script>

    <script src="{{ asset('assets/frontend/leafletjs/leaflet.js') }}"></script>
    <script src="{{ asset('assets/frontend/leafletjs/leaflet-search.js') }}"></script>
    <script src="{{ asset('assets/frontend/toaster/toaster.js') }}"></script>

    <script src="{{ asset('assets/frontend/gallery/jquery.justifiedGallery.min.js') }}"></script>

    <script src="{{ asset('assets/frontend/js/jQuery.tagify.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/jquery-rbox.js') }}"></script>


    <script src="{{ asset('assets/frontend/js/plyr_cdn_dw.js') }}"></script>

    <script src="{{ asset('js/share.js') }}"></script>

    <script src="{{ asset('assets/frontend/uploader/file-uploader.js') }}"></script>

    <script src="{{ asset('assets/frontend/summernote-0.8.18-dist/summernote-lite.min.js') }}"></script>


    {{-- fundraiser start----- --}}
    {{-- <script src="{{ asset('assets/frontend/css/fundraiser/js/custom.js') }}"></script> --}}
    <script src="{{ asset('assets/frontend/css/fundraiser/js/custom_btn.js') }}"></script>
    <script src="{{ asset('assets/frontend/css/fundraiser/js/new-script.js') }}"></script>
    {{-- <script src="{{ asset('assets/frontend/css/fundraiser/js/profile-table.js') }}"></script> --}}
    {{-- fundraiser end---- --}}
    

    
    {{-- paid content start --}}
    <script src="{{ asset('assets/frontend/paid-content/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/paid-content/js/ckeditor.js') }}"></script>
    <script src="{{ asset('assets/frontend/paid-content/js/jquery-tjgallery.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/paid-content/js/custom.js') }}"></script>
    <script src="{{ asset('assets/frontend/paid-content/js/script.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/addon_layout.js') }}"></script>
    {{-- paid content end --}}

    <script src="{{ asset('assets/frontend/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/frontend/js/daterangepicker.min.js') }}"></script>

    {{-- <script src="{{ asset('assets/frontend/js/custom.js') }}"></script> --}}

    <script src="{{ asset('assets/frontend/js/initialize.js') }}"></script>



    @include('frontend.common_scripts')

    @include('frontend.toaster')

    @include('frontend.initialize')

    <script>
        "use strict";

        $(document).ready(function() {
            $('[name=tag]').tagify({
                duplicates: false
            });
        });

    </script>

<script>
    $("document").ready(function(){
        var dark = document.getElementById('dark');
        var storedThemeColor = sessionStorage.getItem('theme_color'); 
        if (storedThemeColor) {
            document.body.classList.add(storedThemeColor); 
        }

        dark.onclick = function(){
            document.body.classList.toggle('dark');
            var themeColor = document.body.classList.contains('dark') ? 'dark' : 'default';
            var url = "<?php echo route('update-theme-color') ?>";
            $.ajax({
                type: 'POST',
                url: url,
                data: { 
                    themeColor: themeColor
                },
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    sessionStorage.setItem('theme_color', themeColor);
                    if (themeColor === 'dark') {
                        $('#dark').attr('src', '{{ asset("assets/frontend/images/white_sun.svg") }}');
                } else {
                    
                    $('#dark').attr('src', '{{ asset("assets/frontend/images/white_moon.svg") }}');
                }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating theme color:', error);
                }
            });
            return false;
        }
    });
</script>


<script>
$(document).ready(function(){
    $('#dark').click(function(){
        console.log("Dark button clicked"); 
        $('.webgl body').toggleClass('test');
        console.log("Class 'test' toggled on .webgl elements"); 
    });
});



</script>


</body>

</html>


<!-- header -->




<script>
jQuery(document).ready(function($) {
    $('body').on('click', 'a.hashtag-link', function(e) {
        e.preventDefault();
        var hashtag = $(this).text();
        $('input[name="search"]').val(hashtag);
        $('#form_id').submit();
    });
});




</script>

<script>
    $(document).ready(function(){
        $('#dark').click(function(){
            console.log("Dark button clicked"); // Debugging statement
            $('.webgl body').toggleClass('test');
            console.log("Class 'test' toggled on .webgl elements"); // Debugging statement
        });
    });
    
    
    
    </script>