@php
    if (Schema::hasTable('paid_content_creators')) {
        $creator = DB::table('paid_content_creators')
            ->where('user_id', auth()->user()->id)
            ->where('status', 1)
            ->exists();
    } else {
        $creator = false;
    }
    if (Schema::hasTable('fundraisers')) {
        $fundraiser = DB::table('fundraisers')
            ->where('user_id', auth()->user()->id)
            ->exists();

        $donate = DB::table('fundraiser_donations')
            ->where('doner_id', auth()->user()->id)
            ->exists();
    }
@endphp

<div class="menu-panel nf_menu">
    <div class="user-profile">
        <div class="user-image">
            <img src="{{ get_user_image(auth()->user()->id, 'optimized') }}" width="100%">
        </div>
        <div class="user-details">
            <h4 class="m-0">{{ $user_info->name }}</h4>
            <p class="mb-1">{{ $user_info->email }}</p>
        </div>

        <div class="user-settings">
            <div class="rounded-pill border border-dark px-4 py-2 d-flex align-items-center gap-2"
                style="font-size: 14px; font-weight: 400">
                <span>
                    <i class="fa-solid fa-gear text-muted"></i>
                </span>
                <a href="{{ route('user.settings') }}" class="text-muted">Settings</a>
            </div>
        </div>
    </div>
    <div class="menu-items">
        <ul class="pc-menu m-0 p-0">
            <!-- menu item one -->

            @if (addon_status('paid_content') == 1)
                <li class="menu-item @if (Route::currentRouteName() == 'creator.payout' ||
                        Route::currentRouteName() == 'subscriber.list' ||
                        Route::currentRouteName() == 'user.subscription' ||
                        Route::currentRouteName() == 'subscription.payment') active @endif">
                    <a href="javascript: void(0);" class="menu-title text-black">
                        <span>Paid Content</span>
                        <i class="fa-solid fa-angle-up"></i>
                    </a>

                    <!-- menu dropdown item -->
                    <ul class="pc-dropdown-menu">

                        <!-- dropdown menu -->
                        <li class="dropdown-menu-item @if (Route::currentRouteName() == 'creator.payout' || Route::currentRouteName() == 'subscriber.list') active @endif">
                            <a href="javascript: void(0);"
                                @if (!$creator) data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="For creators only." class="d-flex justify-content-between creator-only" @else class="dropdown-menu-title" @endif>
                                <span>Creator Panel</span>
                                <i class="fa-solid fa-angle-up"></i>
                            </a>
                            @if ($creator)
                                <!-- dropdown submenu item -->
                                <ul class="pc-dropdown-submenu">
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'subscriber.list') active @endif">
                                        <a href="{{ route('subscriber.list') }}">Subscribes</a>
                                    </li>
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'creator.payout') active @endif">
                                        <a href="{{ route('creator.payout') }}">Payout report</a>
                                    </li>
                                </ul>
                            @endif
                        </li>


                        @if (!$creator)
                            <!-- dropdown menu -->
                            <li class="dropdown-menu-item @if (Route::currentRouteName() == 'user.subscription' || Route::currentRouteName() == 'subscription.payment') active @endif">
                                <a href="javascript: void(0);" class="dropdown-menu-title">
                                    <span>Customer Panel</span>
                                    <i class="fa-solid fa-angle-up"></i>
                                </a>

                                <!-- dropdown submenu item -->
                                <ul class="pc-dropdown-submenu">
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'user.subscription') active @endif">
                                        <a href="{{ route('user.subscription') }}">Subscriptions</a>
                                    </li>
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'subscription.payment') active @endif">
                                        <a href="{{ route('subscription.payment') }}">Payment History</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif


            @if (addon_status('fundraiser') == 1)
                <li class="menu-item @if (Route::currentRouteName() == 'fundraiser.myactivity' ||
                        Route::currentRouteName() == 'fundraiser.payment' ||
                        Route::currentRouteName() == 'fundraiser.donor' ||
                        Route::currentRouteName() == 'campaign.type' ||
                        Route::currentRouteName() == 'donor.history' ||
                        Route::currentRouteName() == 'campaign.history') active @endif">
                    <a href="javascript: void(0);" class="menu-title text-black">
                        <span>Fundraiser</span>
                        <i class="fa-solid fa-angle-up"></i>
                    </a>

                    <!-- menu dropdown item -->
                    <ul class="pc-dropdown-menu ">

                        @if ($fundraiser)
                            <!-- dropdown menu -->
                            <li class="dropdown-menu-item @if (Route::currentRouteName() == 'fundraiser.myactivity' ||
                                    Route::currentRouteName() == 'campaign.type' ||
                                    Route::currentRouteName() == 'fundraiser.payment' ||
                                    Route::currentRouteName() == 'campaign.history') active @endif">
                                <a href="javascript: void(0);" class="dropdown-menu-title">
                                    <span>Fundraise Panel</span>
                                    <i class="fa-solid fa-angle-up"></i>
                                </a>

                                <!-- dropdown submenu item -->
                                <ul class="pc-dropdown-submenu ">
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'fundraiser.myactivity' || Route::currentRouteName() == 'campaign.type') active @endif">
                                        <a href="{{ route('fundraiser.myactivity') }}">{{get_phrase('My Activities')}}</a>
                                    </li>
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'fundraiser.payment' || Route::currentRouteName() == 'campaign.history') active @endif">
                                        <a href="{{ route('fundraiser.payment') }}">{{get_phrase('Payment History')}}</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                        @if ($donate)
                            <!-- dropdown menu -->
                            <li class="dropdown-menu-item @if (Route::currentRouteName() == 'fundraiser.donor' || Route::currentRouteName() == 'donor.history') active @endif">
                                <a href="{{ route('fundraiser.donor') }}" class="dropdown-menu-title">
                                    <span>Donor Panel</span>
                                    <i class="fa-solid fa-angle-up"></i>
                                </a>

                                <!-- dropdown submenu item -->
                                <ul class="pc-dropdown-submenu ">
                                    <li class="dropdown-submenu-item @if (Route::currentRouteName() == 'donor.history') active @endif">
                                        <a href="{{ route('donor.history') }}">Payment History</a>
                                    </li>
                                </ul>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

        </ul>
    </div>
</div>
