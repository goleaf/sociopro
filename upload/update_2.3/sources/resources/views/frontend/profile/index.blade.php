
<div class="profile-wrap">
    <div class="profile-cover  bg-white">
        <div class="profile-header" style="background-image: url('{{ get_cover_photo($user_info->cover_photo) }}');">
           <div class="cover-btn-group">
                <button
                onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.profile.edit_profile']) }}', '{{ get_phrase('Edit your profile') }}');"
                class="edit-cover btn " data-bs-toggle="modal" data-bs-target="#edit-profile"><i
                    class="fa fa-pencil"></i>{{ get_phrase('Edit Profile') }}</button>
                <button  onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.profile.edit_cover_photo']) }}', '{{ get_phrase('Update your cover photo') }}');"
                    class="edit-cover btn n_edit"><i class="fa fa-camera"></i>{{ get_phrase('Edit Cover Photo') }}</button>
            </div>
        </div>
            <div class="n_profile_tab">
                 <div class="n_main_tab">
                    <div class="profile-avatar d-flex align-items-center">
                        <div class="avatar avatar-xl"><img class="rounded-circle"
                                src="{{ get_user_image($user_info->photo, 'optimized') }}" alt=""></div>
                        <div class="avatar-details">
                            @php
                                $user_name = \App\Models\Users::where('id', auth()->user()->id)->first()->name;
                            @endphp
                            <h3 class="n_font">{{ $user_name }}</h3>
                            
                        </div>
                    </div>
                    <div class="n_tab_follow ">
                        @php
                        $friends = DB::table('friendships')
                            ->where(function ($query) {
                                $query->where('accepter', Auth()->user()->id)->orWhere('requester', Auth()->user()->id);
                            })
                            ->where('is_accepted', 1)
                            ->orderBy('friendships.importance', 'desc');
                    @endphp
                        {{-- <p><span>{{ $friends->get()->count() }}</span>{{get_phrase('Friends')}}</p> --}}
                        {{-- <p><span>50 </span>{{get_phrase('followers')}}</p> --}}
                    </div>
                 </div>
                <nav class="profile-nav">
                    <ul class="nav align-items-center justify-content-start">
                        <li class="nav-item @if (Route::currentRouteName() == 'profile') active @endif"><a
                                href="{{ route('profile') }}" class="nav-link">{{ get_phrase('Timeline') }}</a></li>
                        <li class="nav-item @if (Route::currentRouteName() == 'profile.friends') active @endif"><a
                                href="{{ route('profile.friends') }}" class="nav-link">{{ get_phrase('Friends') }}</a>
                        </li>
                        <li class="nav-item @if (Route::currentRouteName() == 'profile.photos' || Route::currentRouteName() == 'album.details.list') active @endif"><a
                                href="{{ route('profile.photos') }}" class="nav-link">{{ get_phrase('Photo') }}</a>
                        </li>
                        <li class="nav-item @if (Route::currentRouteName() == 'profile.videos') active @endif"><a
                                href="{{ route('profile.videos') }}" class="nav-link">{{ get_phrase('Video') }}</a>
                        </li>
                    </ul>
                </nav>
            </div>

        
    </div>
    <div class="profile-content mt-3">
        <div class="row gx-3">
            <div class="col-lg-12 col-sm-12">
                
                @if (Route::currentRouteName() == 'profile.friends')
                    @include('frontend.profile.friends')
                @elseif(Route::currentRouteName() == 'profile.photos')
                    @include('frontend.profile.photos')
                    
                @elseif(Route::currentRouteName() == 'album.details.list')
                    @include('frontend.profile.single_album_list_details')

                @elseif(Route::currentRouteName() == 'profile.videos')
                    @include('frontend.profile.videos')

                @else
                    @include('frontend.main_content.create_post')

                    <div id="profile-timeline-posts">
                        @include('frontend.main_content.posts', ['type' => 'user_post'])
                    </div>

                    @include('frontend.main_content.scripts')
                @endif
            </div>
            <!-- COL END -->
            {{-- <div class="col-lg-5 col-sm-12">
                @include('frontend.profile.profile_info', ['type' => 'my_account'])
            </div> --}}
        </div>
    </div>
    <!-- Profile content End -->
</div>

@include('frontend.profile.scripts')
