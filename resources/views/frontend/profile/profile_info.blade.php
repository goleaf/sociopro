@php
    
    $media_files = \App\Models\Media_files::where('user_id', Auth()->user()->id)
        ->whereNull('story_id')
        ->whereNull('product_id')
        ->whereNull('page_id')
        ->whereNull('group_id')
        ->whereNull('chat_id')
        ->take(9)
        ->orderBy('id', 'desc')
        ->get();
    
@endphp

<aside class="sidebar">
   
    <div class="widget" id="my-profile-info">

        <h4 class="widget-title">{{ get_phrase('Intro') }}</h4>

        <div class="my-about mb-8  mt-8">
            @php echo script_checker($user_info->about) @endphp
        </div>
        @if (isset($type) && $type == 'my_account')
            <button onclick="toggleBio(this, '.edit-bio-form')"
                class="edit-bio-btn btn common_btn w-100 mb-14">{{ get_phrase('Edit Bio') }}</button>
        @endif

        <form class="ajaxForm d-hidden edit-bio-form" action="{{ route('profile.about', ['action_type' => 'update']) }}"
            method="post">
            @CSRF
            <div class="mb-3">
                <textarea name="about" class="form-control">{{ $user_info->about }}</textarea>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn common_btn w-100 mb-8">{{ get_phrase('Save Bio') }}</button>
            </div>
        </form>

        @include('frontend.profile.my_info')
    </div>
    <div class="widget">
        <div class="n_pro_con d-flex">
            <h4 class="widget-title">{{ get_phrase('Photo') }}/{{ get_phrase('Video') }}</h4>
            <a href="{{ route('profile.photos') }}" >{{ get_phrase('See All') }}</a>
        </div>
        <div id="sidebarPhotoAndVideos" class="row row-cols-3 row-cols-md-5 row-cols-lg-2 row-cols-xl-3 g-1 mt-3">
            @include('frontend.profile.sidebar_photos_and_videos')
        </div>
    
    </div>
    <!--  Widget End -->
    <div class="widget friend-widget">
        @php
            $friends = DB::table('friendships')
                ->where(function ($query) {
                    $query->where('accepter', Auth()->user()->id)->orWhere('requester', Auth()->user()->id);
                })
                ->where('is_accepted', 1)
                ->orderBy('friendships.importance', 'desc');
        @endphp
        <div class="n_pro_con d-flex align-items-start">
            <div class="widget-header">
                <h4 class="widget-title">{{ get_phrase('Friends') }}</h4>
                <span class='f-text'>{{ $friends->get()->count() }} {{ get_phrase('Friends') }}</span>
            </div>
            <a href="{{ route('profile.friends') }}" >{{ get_phrase('See All') }}</a
            href="{{ route('profile.friends') }}">

        </div>

        <div class="f_image_g row-cols-3 g-1 mt-8">
            @foreach ($friends->take(6)->get() as $friend)
                @if ($friend->requester == Auth()->user()->id)
                    @php
                        $friends_user_data = DB::table('users')
                            ->where('id', $friend->accepter)
                            ->first();
                    @endphp
                @else
                    @php
                        $friends_user_data = DB::table('users')
                            ->where('id', $friend->requester)
                            ->first();
                    @endphp
                @endif
                @if (isset($friends_user_data->id))
                    <div class="image_div">
                        <a href="{{ route('user.profile.view', $friends_user_data->id) }}" class="friend d-block n_friend">
                            <img width="100%" src="{{ get_user_image($friends_user_data->photo, 'optimized') }}"
                                alt="">
                            <h6 class="small">{{ $friends_user_data->name }}</h6>
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
        
    </div>
    <!--  Widget End -->

    <!--  Widget End -->
</aside>
<!--  Sidebar End -->
