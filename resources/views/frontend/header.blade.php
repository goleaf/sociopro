<?php
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

        $date = Carbon::today();
        $new_notification = Notification::where('reciver_user_id', auth()->user()->id)->where('status', '0')
            ->orderBy('id', 'DESC')->get();
        $older_notification = Notification::where('reciver_user_id', auth()->user()->id)->where('created_at', '<', $date)->orderBy('id', 'DESC')->get();

?>

<!-- header -->
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
                                <a href="{{ route('ai_image.image_generator') }}" class="notification-button" title="AI image generator"><i class="fa-solid fa-robot"></i></a>
                            </div>

                            <div class="group-control">
                                <a href="javascript:;" class="notification-button"><img id="dark" src="{{$image}}" alt=""></a>
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
                                <a class="notification-button position-relative" id="notification-button" href="javascript:;">
                                    <i class="fa-solid fa-bell"></i>
                                    @if ($unread_notification > 0)
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill notificatio_counter_bg">
                                            {{ get_phrase($unread_notification) }}
                                        </span>
                                    @endif
                                </a>
                                <div class="notification_panel" id="notification_panel">
                                    @include('frontend.notification.notification')
                                </div>
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
                                            <a href="{{route('all_settings.view')}}" 
                                            class="dropdown-item">{{ get_phrase('Settings') }}</a>
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

    $(document).ready(function(){
        $("#notification-button").click(function(){
            $("#notification_panel").slideToggle();
        });
    })
    
    
    </script>