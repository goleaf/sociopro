<div class="profile-wrap">
    <div class="profile-cover bg-white radius-8">
        <div class="profile-header" style="background-image: url('{{get_cover_photo($user_data->cover_photo)}}');">
        </div>
       <div class="n_profile_tab">
             <div class="n_main_tab">
                <div class="profile-avatar d-flex align-items-center">
                    <div class="avatar avatar-xl"><img class="rounded-circle" src="{{get_user_image($user_data->photo, 'optimized')}}" alt=""></div>
                    <div class="avatar-details">
                        <h3 class="n_font">{{$user_data->name}} </h3>
                    </div>
                </div>
             </div>

            <nav class="profile-nav">
                <ul class="nav align-items-center justify-content-start">
                    <li class="nav-item @if(Route::currentRouteName() == 'user.profile.view') active @endif"><a href="{{route('user.profile.view',$user_data->id)}}" class="nav-link">{{get_phrase('Timeline')}}</a></li>
                    <li class="nav-item @if(Route::currentRouteName() == 'user.friends') active @endif"><a href="{{route('user.friends',$user_data->id)}}" class="nav-link">{{get_phrase('Friends')}}</a></li>
                    <li class="nav-item @if(Route::currentRouteName() == 'user.photos' && isset($page_identifire)) active @endif">
                        <a href="{{ route('user.photos', [$user_data->id , 'identifire' => $viewData->profileIdentifier($page_identifire ?? null)]) }}" class="nav-link">{{ get_phrase('Photo') }}</a>
                    </li>
                    
                    <li class="nav-item @if(Route::currentRouteName() == 'user.videos') active @endif"><a href="{{route('user.videos',$user_data->id)}}" class="nav-link">{{get_phrase('Video')}}</a></li>
                </ul>
            </nav>          
       </div>
    </div>

    @if($viewData->canViewProfile($user_data, auth()->user()))
        <div class="row gx-3 mt-3">
            <div class="col-lg-12 col-sm-12">
                @if(Route::currentRouteName() == 'user.friends')
                    @include('frontend.user.single_user.friends')
                @elseif(Route::currentRouteName() == 'user.photos'  && isset($page_identifire))
                    @include('frontend.user.single_user.photos')
                @elseif(Route::currentRouteName() == 'user.videos')
                    @include('frontend.user.single_user.videos')
                @else
                    @if ($user_data->id == auth()->user()->id)
                        @include('frontend.main_content.create_post')
                    @endif
                    <div id="user-timeline-posts">
                        @include('frontend.main_content.posts',['type'=>'user_post'])
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="widget page-widget ac_control_we mt-3">
            <div class="user_ac">  
                <a href="javascript:void(0)" class="btn common_btn ac_btn">
                    <i class="fa-solid fa-shield"></i> {{$user_data->name}}
                    {{ get_phrase('locked '.$viewData->profilePronouns($user_data)['gender'].' profile ') }}<br>
                    <span>{{ get_phrase('Only '.$viewData->profilePronouns($user_data)['gender'].' friends can see what '.$viewData->profilePronouns($user_data)['pronoun'].' shares on '.$viewData->profilePronouns($user_data)['gender'].' profile.') }}</span>
                </a>
            </div>
        </div>
        <h3 class="lock_no_post_h3">{{get_phrase('No posts available')}}</h3>
    @endif
</div>

@include('frontend.main_content.scripts')
