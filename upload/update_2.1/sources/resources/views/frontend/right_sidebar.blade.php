<div class="row rightSideBarToggler d-hidden">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-body text-end">
                <button class="btn" onclick="toggleRightSideBar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>
</div>
{{-- @php
    
    $media_files = \App\Models\Media_files::where('user_id', Auth()->user()->id)
        ->whereNull('story_id')
        ->whereNull('product_id')
        ->whereNull('page_id')
        ->whereNull('group_id')
        ->whereNull('chat_id')
        ->take(9)
        ->orderBy('id', 'desc')
        ->get();

@endphp --}}
<aside class="sidebar mt-0 sidebarToggle d-hidden" id="sidebarToggle">
    @if (Route::currentRouteName() == 'timeline' ||
            Route::currentRouteName() == 'pages' ||
            Route::currentRouteName() == 'allproducts' ||
            Route::currentRouteName() == 'filter.product' ||
            Route::currentRouteName() == 'userproduct' ||
            Route::currentRouteName() == 'single.product' ||
            Route::currentRouteName() == 'videos' ||
            Route::currentRouteName() == 'shorts' ||
            Route::currentRouteName() == 'save.all.view' ||
            Route::currentRouteName() == 'event' ||
            Route::currentRouteName() == 'userevent' ||
            Route::currentRouteName() == 'event' ||
            Route::currentRouteName() == 'blogs' ||
            Route::currentRouteName() == 'category.blog' ||
            Route::currentRouteName() == 'myblog' ||
            Route::currentRouteName() == 'notifications' ||
            Route::currentRouteName() == 'general.timeline' ||
            Route::currentRouteName() == 'page.view' ||
            Route::currentRouteName() == 'memories' ||
            Route::currentRouteName() == 'badge' || 
            Route::currentRouteName() == 'badge.info' ||
            Route::currentRouteName() == 'fundraiser.index' ||
            Route::currentRouteName() == 'fundraiser.category' ||
            Route::currentRouteName() == 'fundraiser.myactivity' ||
            Route::currentRouteName() == 'fundraiser.donor' ||
            Route::currentRouteName() == 'fundraiser.payment' ||
            Route::currentRouteName() == 'fundraiser.create'||
            Route::currentRouteName() == 'fundraiser.edit' || 
            Route::currentRouteName() == 'fundraiser.search' ||
            Route::currentRouteName() == 'creator.payout')
        <div class="widget">
            <div class="d-flex align-items-center">
                @php

                    $tz = auth()->user()->timezone;
                    if (!empty($tz)) {
                        $timestamp = time();
                        $dt = new DateTime('now', new DateTimeZone($tz)); //first argument "must" be a string
                        $dt->setTimestamp($timestamp);
                        $current_hour = $dt->format('H');
                    } else {
                        $current_hour = date('H', time());
                    }
                @endphp

                @if ($current_hour >= 5 && $current_hour < 12)
                    <img class="img-fluid" src="{{ asset('assets/frontend/images/m-sun.png') }}" height="30px"
                        width="30px" alt="">
                @elseif($current_hour >= 12 && $current_hour < 17)
                    <img class="img-fluid" src="{{ asset('storage/images/cloud-sun.png') }}" alt="">
                @else
                    <img class="img-fluid" src="{{ asset('assets/frontend/images/moon.png') }}" height="30px"
                        width="30px" alt="">
                @endif
                <h3 class="h6 ms-2">{{ get_phrase('Hi') }}, {{ Auth()->user()->name }}
                    @if ($current_hour >= 5 && $current_hour < 12)
                        <span class="d-block text-primary">{{ get_phrase('Good Morning') }}!</span>
                    @elseif($current_hour >= 12 && $current_hour < 17)
                        <span class="d-block text-primary">{{ get_phrase('Good Afternoon') }}!</span>
                    @else
                        <span class="d-block text-primary">{{ get_phrase('Good Evening') }}!</span>
                    @endif
                </h3>
            </div>
        </div> <!-- Widget End -->
        <div class="widget sponser_widget">
            <div class="d-flex align-items-center justify-content-between">
                <h3 class="widget-title">{{ get_phrase('Sponsored') }} </h3>

            </div>
            <div class="sponsors">
                @php

                    $sponsorPost = \App\Models\Sponsor::orderBy('id', 'desc')

                        ->where(function ($query) {
                            $query->where('start_date', '<', date('Y-m-d H:i:s'))->orWhere(function ($query) {
                                $query->where('start_date', '=', date('Y-m-d H:i:s'))->whereTime('start_date', '<=', date('Y-m-d H:i:s'));
                            });
                        })
                        ->where(function ($query) {
                            $query->where('end_date', '>', date('Y-m-d H:i:s'))->orWhere(function ($query) {
                                $query->where('end_date', '=', date('Y-m-d H:i:s'))->whereTime('end_date', '>=', date('Y-m-d H:i:s'));
                            });
                        })
                        ->where('status', 1)
                        ->limit('6')
                        ->get();
                @endphp
                @foreach ($sponsorPost as $sponsor)
                    <a target="_blank" href="{{ $sponsor->ext_url }}">
                        <div class="sponsor d-flex d-md-block d-xl-flex  mb-1 text-lg-center text-xl-start">
                            <img src="{{ get_sponsor_image($sponsor->image, 'thumbnail') }}"
                                class="sponsor_post_image_size " alt="">
                            <div class="sponsor-txt ms-2 ">
                                <h6>{{ ellipsis($sponsor->name, 30) }}</h6>
                                <p class="ellipsis-line-3 pe-2 text-dark">
                                    {{ ellipsis(strip_tags($sponsor->description, 100)) }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div> <!-- Widget End -->
        <div class="widget">
            <div class="d-flex align-items-center justify-content-between">
                <h3 class="widget-title">{{ get_phrase('Active users') }} </h3>
                <div class="d-flex align-items-center widget-controls">

                </div>
            </div>
            <div class="contact-lists mt-3">
                @php
                    $friends = \App\Models\Friendships::where(function ($query) {
                        $query->where('accepter', auth()->user()->id)->orWhere('requester', auth()->user()->id);
                    })
                        ->where('is_accepted', 1)
                        ->get();
                @endphp
                @foreach ($friends as $friend)
                    @if ($friend->requester == auth()->user()->id)
                        {{-- @if ($friend->getFriendAccepter->isOnline()) --}}
                        @if (!empty($friend->getFriendAccepter) && $friend->getFriendAccepter->isOnline())
                            @if ($friend->getFriendAccepter->id != auth()->user()->id)
                                <div class="single-contact d-flex align-items-center justify-content-between">
                                    <div class="avatar d-flex">
                                        <a href="{{ route('chat', $friend->getFriendAccepter->id) }}"
                                            class="d-flex align-items-center">
                                            <div class="avatar me-2">
                                                <img src="{{ get_user_image($friend->getFriendAccepter->photo, 'optimized') }}"
                                                    class="rounded-circle w-45px" alt="">
                                                <span class="online-status active"></span>
                                            </div>
                                            <h4>{{ $friend->getFriendAccepter->name }}</h4>
                                        </a>
                                    </div>
                                    <div class="login-time">

                                    </div>
                                </div>
                            @endif
                        @endif
                    @else
                        @if ($friend->getFriend->isOnline())
                            @if ($friend->getFriend->id != auth()->user()->id)
                                <div class="single-contact d-flex align-items-center justify-content-between">
                                    <div class="avatar d-flex">
                                        <a href="{{ route('chat', $friend->getFriend->id) }}"
                                            class="d-flex align-items-center">
                                            <div class="avatar me-2">
                                                <img src="{{ get_user_image($friend->getFriend->photo, 'optimized') }}"
                                                    class="rounded-circle w-45px" alt="">
                                                <span class="online-status active"></span>
                                            </div>
                                            <h4>{{ $friend->getFriend->name }}</h4>
                                        </a>
                                    </div>
                                    <div class="login-time">

                                    </div>
                                </div>
                            @endif
                        @endif
                    @endif
                @endforeach
            </div>
        </div> <!-- Widget End -->
    @endif
    @if (Route::currentRouteName() == 'profile' ||
            Route::currentRouteName() == 'profile.photos' ||
            Route::currentRouteName() == 'profile.videos' ||
            Route::currentRouteName() == 'profile.friends')
        @include('frontend.profile.profile_info')
    @endif
    @if (Route::currentRouteName() == 'groups' || Route::currentRouteName() == 'search.group')
        @include('frontend.groups.right-sidebar')
    @endif
    @if (Route::currentRouteName() == 'single.group' ||
            Route::currentRouteName() == 'group.people.info' ||
            Route::currentRouteName() == 'group.event.view' ||
            Route::currentRouteName() == 'single.group.photos' ||
            Route::currentRouteName() == 'all.people.group.view')
        @include('frontend.groups.bio')
    @endif
    @if (Route::currentRouteName() == 'single.page' ||
            Route::currentRouteName() == 'single.page.photos' ||
            Route::currentRouteName() == 'page.videos')
        @include('frontend.pages.bio')
    @endif
    @if (Route::currentRouteName() == 'single.event')
        <aside class="sidebar  event_side border-none">
            <div class="widget guest">
                <div class="d-flex justify-content-between">
                    <h3 class="widget-title">{{ get_phrase('Guests') }}</h3>
                    <a href="javascript:void(0)"
                        onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.events.view-all', 'event_id' => $event->id]) }}', '{{ get_phrase('All Going And Interested User') }}');"
                        data-bs-toggle="modal" data-bs-target="#viewAll"
                        class="fw-bold text-primary">{{ get_phrase('View All') }}</a>
                </div>
                <div class="d-flex gt_control justify-content-center  mt-3">
                    <div class="going">
                        @php
                            $directly_going_data = json_decode($event->going_users_id) != null ? count(json_decode($event->going_users_id)) : '0';
                            $invite_going_data = $invited_friend_going;
                            $total = $directly_going_data + $invite_going_data;
                        @endphp
                        <span class="rounded-2">{{ $total }} </span>
                        Going
                    </div>
                    <div class="going">
                        <span
                            class="rounded-2">{{ json_decode($event->interested_users_id) != null ? count(json_decode($event->interested_users_id)) : '0' }}</span>
                        Interested
                    </div>
                </div>
            </div> <!-- Widget End -->
            <div class="widget">
                <h3 class="widget-title">{{ get_phrase('Go With Friends') }}</h3>
                <div class="gr-search">
                    <form action="#">
                        <input type="text" class="bg-secondary rounded" id="myInputSearch"
                            onkeyup="mySearchFunction()" placeholder="Search">
                        <span class="i fa fa-search"></span>
                    </form>
                </div>

                <div class="invite-wrap nw_wrap overflow-auto mt-3">
                    <table id="myTable" class="w-100">
                        <tbody class="searchTbody">
                            @foreach ($friends as $friend)
                                {{--  asiging user as requester or getting request as friend whos are inviteable --}}
                                @php $invited_friend_id = $friend->requester==auth()->user()->id ? $friend->accepter:$friend->requester; @endphp
                                {{--  getiing user data for view   --}}
                                @php
                                    $inviteablefrienddetails = DB::table('users')
                                        ->where('id', $invited_friend_id)
                                        ->first();
                                @endphp
                                {{--  chekcing invite is already done or not   --}}
                                @php
                                    $invite_details = DB::table('invites')
                                        ->where('invite_reciver_id', $invited_friend_id)
                                        ->where('event_id', $event->id)
                                        ->first();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-between s-invite">
                                            <div class="ava-img d-flex align-items-center">
                                                <a
                                                    href="{{ route('user.profile.view', $inviteablefrienddetails->id) }}"><img
                                                        width="40" class="user-round"
                                                        src="{{ get_user_image($inviteablefrienddetails->photo, 'optimized') }}"
                                                        alt=""></a>
                                                <h3 class="h6 mb-0"><a
                                                        href="{{ route('user.profile.view', $inviteablefrienddetails->id) }}">{{ ellipsis($inviteablefrienddetails->name, 20) }}</a>
                                                </h3>
                                            </div>
                                            <div class="invite_button_css">
                                                @if (
                                                    !empty($invite_details) &&
                                                        $invite_details->invite_reciver_id == $invited_friend_id &&
                                                        $invite_details->is_accepted != '1')
                                                    <button class="btn btn-secondary px-1 py-0 me-1 text-primary"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        data-bs-title="{{ get_phrase('Invited') }}"> Invited</button>
                                                @elseif (
                                                    !empty($invite_details) &&
                                                        $invite_details->invite_reciver_id == $invited_friend_id &&
                                                        $invite_details->is_accepted == '1')
                                                    <button data-bs-toggle="tooltip" data-bs-placement="top"
                                                        data-bs-title="{{ get_phrase('Going') }}"
                                                        class="btn px-1 py-0 me-1 text-success"> <i
                                                            class="far fa-calendar-check"></i> </button>
                                                @else
                                                    <a data-bs-toggle="tooltip" data-bs-placement="top"
                                                        data-bs-title="{{ get_phrase('Send invitations') }}"
                                                        href="javascript:void(0)"
                                                        onclick="ajaxAction('<?php echo route('event.invite', ['invited_friend_id' => $invited_friend_id, 'requester_id' => auth()->user()->id, 'event_id' => $event->id]); ?>')"
                                                        class="btn btn-primary px-1 py-0 me-1">invite</a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div><!-- Widget End -->

            <div class="widget p-event-widget">
                <h3 class="widget-title mb-3">{{ get_phrase('Popular Events') }}</h3>
                @php $index=1; @endphp
                @foreach ($popularevents as $key => $popularevent)
                    <div class="popular-event">
                        <div class="p-2 border rounded-3">
                            <img class="img-fluid w-100"
                                src="{{ viewImage('event', $popularevent['banner'], 'thumbnail') }}" alt="">
                            <div class="pp-info">
                                <span class="text-primary">{{ date('l', strtotime($popularevent['event_date'])) }},
                                    {{ date('d F Y', strtotime($popularevent['event_date'])) }}</span>
                                <h6><a href="{{ route('single.event', $popularevent['id']) }}">
                                        {{ ellipsis($popularevent['title'], 50) }}</a></h6>
                                <div class="d-flex mt-2">
                                    <a href="{{ route('user.profile.view', $popularevent['user_id']) }}"><img
                                            src="{{ get_user_image($popularevent['photo'], 'optimized') }}"
                                            width="30" class="cicle user-round" alt=""></a>
                                    <div class="ava-info ms-2">
                                        <h3 class="h6 mb-0"><a href="#">{{ $popularevent['post_user'] }}</a>
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- Event Widget End -->
                    @php
                        if ($index == '5') {
                            break;
                        } else {
                            $index++;
                        }
                    @endphp
                @endforeach
            </div><!-- Widget End -->
        </aside>
    @endif
    @if (Route::currentRouteName() == 'single.blog')
        <aside class="sidebar">
            <div class="widget">
                <a href="{{ url()->previous() }}" class="btn back_btns btn-primary">
                    <i class="fa-solid fa-left-long"></i>
                    {{ get_phrase('Back') }}</a>
            </div>
            <div class="widget recent-posts blog_searchs">
                <div class=" search-widget mb-14">
                    <h3 class="widget-title mb-12">{{ get_phrase('Search') }}</h3>
                    <form action="#" class="search-form">
                        <input class="bg-secondary" type="search" id="searchblogfield" placeholder="Search">
                        <span><i class="fa fa-search"></i></span>
                    </form>
                </div>
                <h3 class="widget-title mb-12">{{ get_phrase('Recent Post') }}</h3>
                <div class="posts-wrap" id="searchblogviewsection">
                    @foreach ($recent_posts as $post)
                        <div class="post-entry d-flex mb-8">
                            <div class="post-thumb"><img class="img-fluid rounded"
                                    src="{{ get_blog_image($post->thumbnail, 'thumbnail') }}" alt="Recent Post">
                            </div>
                            <div class="post-txt ms-2">
                                <h3 class="mb-0"><a class="ellipsis-line-2"
                                        href="{{ route('single.blog', $post->id) }}">{{ $post->title }}</a></h3>
                                <div class="post-meta border-none">
                                    <span class="date-meta"><a
                                            href="#">{{ $post->created_at->format('d-M-Y') }}</a></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div> <!-- Recent Post Widget End -->
            <div class="widget tag-widget">
                <h3 class="widget-title mb-3">{{ get_phrase('Categories') }}</h3>
                <div class="tags">
                    @foreach ($categories as $category)
                        <a href="{{ route('category.blog', $category->id) }}"
                            class="@if ($post->category_id == $category->id) active @endif">{{ $category->name }}
                            ({{ DB::table('blogs')->where('category_id', $category->id)->get()->count() }})
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>
    @endif
    @if (Route::currentRouteName() == 'user.profile.view' ||
            Route::currentRouteName() == 'user.friends' ||
            Route::currentRouteName() == 'user.photos' ||
            Route::currentRouteName() == 'user.videos')
        @include('frontend.user.single_user.user_info')
    @endif

    {{-- Paid Contant --}}
    @if (addon_status('paid_content') == 1)
        @if (Route::currentRouteName() == 'paid.content' ||
                Route::currentRouteName() == 'creator.timeline' ||
                Route::currentRouteName() == 'creator' ||
                Route::currentRouteName() == 'settings' ||
                Route::currentRouteName() == 'creator.subscribers' ||
                Route::currentRouteName() == 'creator.package' ||
                Route::currentRouteName() == 'post.type')
            <div class="sidebar">
                <div class="widget paid_sidebar">
                    @php
                        $creator = DB::table('paid_content_creators')
                            ->where('user_id', auth()->user()->id)
                            ->first();
                        $social_media = json_decode($creator->social_accounts);
                    @endphp

                    <h4 class="widget-title">{{ get_phrase('Intro') }}</h4>
                    <p class="intro_text mb-8 mt-8">
                        {{ $creator->description }}
                    </p>

                    <div class="mt-8 mb-8 creator-boi h-auto">
                        <h4 class="widget-title">{{ get_phrase('Creator Bio') }}</h4>
                        <p class="intro_text ">{{ $creator->bio }}</p>
                    </div>

                    {{-- edit personal settings --}}
                    <a href="{{ route('settings') }}" class="page-s-btn">{{ get_phrase('Edit bio') }}</a>

                    <h4 class="widget-title pt-30 pb-20">{{ get_phrase('Membership') }}</h4>
                    <div class="member-img">
                        <img src="{{ asset('assets/frontend/paid-content/images/new/Notification-icon.png') }}"
                            alt="" />
                    </div>
                    {{-- <h4 class="widget-title pt-20">
                       {{get_phrase('Choose what to offer')}}
                    </h4> --}}
                    {{-- <p class="intro_text">
                        Lorem Ipsum available, but the majority have suffered
                        alteration in some form by injected humour
                    </p>
                    <a href="javascript: void(0);" class="page-s-btn">Set up benefits</a> --}}
                    <h4 class="widget-title pt-30 pb-20">
                        {{ get_phrase('Social profiles') }}
                    </h4>
                    <ul class="social-links">

                        <li>
                            <a href="@if ($social_media->facebook == '') javascript: void(0);
                                @else {{ $social_media->facebook }} @endif"
                                target="_blank">
                                <i class="fa-brands fa-facebook-f"></i></a>
                        </li>
                        <li>
                            <a href="@if ($social_media->twitter == '') javascript: void(0);
                                @else {{ $social_media->twitter }} @endif"
                                target="_blank">
                                <i class="fa-brands fa-twitter"></i></a>
                        </li>
                        <li>
                            <a href="@if ($social_media->instagram == '') javascript: void(0);
                                @else {{ $social_media->instagram }} @endif"
                                target="_blank">
                                <i class="fa-brands fa-instagram"></i></a>
                        </li>
                        <li>
                            <a href="@if ($social_media->linkedin == '') javascript: void(0);
                                @else {{ $social_media->linkedin }} @endif"
                                target="_blank">
                                <i class="fa-brands fa-linkedin-in"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif

    @endif

</aside>

