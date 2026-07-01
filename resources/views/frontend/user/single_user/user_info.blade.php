@php
use App\Models\Follower;
if(isset($page_identifire)) {
    $identifires = $page_identifire; 
}else{
    $identifires = 'user'; 
}

@endphp
@if ($user_data->id!=auth()->user()->id)
<div class="widget page-widget ac_control_we">
    <div class="user_ac">
        @php
            $user_id = $user_data->id;
            $friend = \App\Models\Friendships::where(function($query) use ($user_id){
                $query->where('requester', auth()->user()->id);
                $query->where('accepter', $user_id);
            })
            ->orWhere(function($query) use ($user_id) {
                $query->where('accepter', auth()->user()->id);
                $query->where('requester', $user_id);
            })
            ->count();

            $friendAccepted = \App\Models\Friendships::where(function($query) use ($user_id){
                $query->where('requester', auth()->user()->id);
                $query->where('accepter', $user_id);
            })
            ->orWhere(function($query) use ($user_id) {
                $query->where('accepter', auth()->user()->id);
                $query->where('requester', $user_id);
            })
        @endphp
        

            @if ($friend>0)
                @if ($friendAccepted->count()>0 && $friendAccepted->value('is_accepted') == 1)
                    <a href="#" class="btn common_btn ac_btn" id="btnGroupDrop1" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-user-group"></i> {{ get_phrase('Friend') }} </a>
                    <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                        <li><a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_data->id); ?>')" class="dropdown-item custom_unfrind_button"> <i class="fa-solid fa-user-xmark"></i> {{ get_phrase('Unfriend') }}</a></li>

                        @if($user_data->id != auth()->user()->id)
                        @php
                            $follow = \App\Models\Follower::where('user_id', auth()->user()->id)
                                ->where('follow_id', $user_data->id)
                                ->count();
                        @endphp
                    
                        <li>
                            @if($follow > 0)
                                <a href="javascript:void(0)" class="dropdown-item custom_unfrind_button" 
                                    onclick="ajaxAction('{{ route('user.unfollow', ['id' => $user_data->id]) }}')"><i class="fa-solid fa-square-xmark"></i> {{ get_phrase('Unfollow') }}</a>
                            @else
                                <a href="javascript:void(0)" class="dropdown-item custom_unfrind_button"
                                    onclick="ajaxAction('{{ route('user.follow', ['id' => $user_data->id]) }}')"><i class="fa-regular fa-calendar-plus"></i> {{ get_phrase('Follow') }}</a>
                            @endif
                        </li>
                    @endif
                    


                    </ul>
                    <a href="{{ route('chat',$user_data->id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                @else
                    <form class="ajaxForm" action="{{route('profile.accept_friend_request')}}" method="post">
                        <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_data->id); ?>')" class="btn common_btn px-3" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Cancle Friend Request') }}"><i class="fa-solid fa-xmark"></i> {{ get_phrase('Cancel') }}</a>
	            		@CSRF
	            		<input type="hidden" name="user_id" value="{{$user_data->id}}">

	                	
                            @if($friendAccepted->value('requester') == auth()->user()->id)
                                <button type="button" class="btn common_btn_2 px-4 no-processing no-uploading" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Friend request') }}">
                                    {{get_phrase('Requested')}}
                                </button>
                            @else
                                <button type="submit" id="friendRequestConfirmBtn{{$user_data->id}}" class="btn common_btn px-4 no-processing no-uploading" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Accept Friend Request') }}">
                                    {{get_phrase('Accept')}}
                                </button>
                            @endif

	                	<button type="button" id="friendRequestAcceptedBtn{{$user_data->id}}" class="btn common_btn d-hidden ac_btn  px-4" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Accepted') }}">{{get_phrase('Accepted')}}</button>
                    </form>
                @endif
            @else   
                <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.friend',$user_data->id); ?>')" class="btn common_btn ac_btn"><i class="fa-solid fa-plus"></i> {{ get_phrase('Add Friend') }} </a>
            @endif
        
    </div>
</div>
@endif

@php 
$media_files = DB::table('media_files')->where('user_id', $user_data->id)
                    ->whereNull('story_id')
                    ->whereNull('page_id')
                    ->whereNull('chat_id')
                    ->whereNull('product_id')
                    ->whereNull('group_id')
                    ->take(9)->orderBy('id', 'desc')->get(); 
@endphp
<aside class="sidebar ">
    <div class="widget intro-widget">
        <h4>{{get_phrase('Intro')}}</h4>

        <div class="my-about">
            {{ nl2br($user_data->about) }}
        </div>

        <form class="ajaxForm d-hidden edit-bio-form" action="{{route('profile.about', ['action_type' => 'update'])}}" method="post">
            @CSRF
            <div class="mb-3">
                <textarea name="about" class="form-control">{{$user_info->about}}</textarea>
            </div>
            <div class="mb-3">
                    <button type="submit" class="btn common_btn w-100">{{get_phrase('Save Bio')}}</button>
            </div>
        </form>
    </div>
    <div class="widget" id="my-profile-info">
        <h4 class="widget-title mb-14">{{get_phrase('Info')}}</h4>
        <ul>
            <li>
                <i class="fa fa-briefcase"></i> <span>
                <strong>{{$user_data->job}}</strong></span>
            </li>
            <li>
                <i class="fa-solid fa-graduation-cap"></i>
                <span>{{get_phrase('Stadied at')}} <strong>{{$user_data->studied_at}}</strong></span>
            </li>
            <li>
                <i class="fa-solid fa-location-dot"></i>
                <span>{{get_phrase('From')}} <strong>{{$user_data->address}}</strong></span>
            </li>
            <li>
                <i class="fa-solid fa-user"></i>
                <span><strong class="text-capitalize">{{get_phrase($user_data->gender)}}</strong></span>
            </li>
            <li>
                <i class="fa-solid fa-clock"></i>
                <span>{{get_phrase('Joined')}} <strong>{{date_formatter($user_data->created_at, 1)}}</strong></span>
            </li>
        </ul>
    </div>
    <div class="widget">
        <h4 class="widget-title">{{get_phrase('Photo')}}/{{get_phrase('Video')}}</h4>
        <div id="sidebarPhotoAndVideos" class="row row-cols-3 g-1 mt-3">
            @foreach($media_files as $media_file)
                @if($media_file->file_type == 'video')
                        <div class="single-item-countable col">
                            <a href="{{ route('single.post',$media_file->post_id) }}">
                                <video muted controlsList="nodownload" class="img-thumbnail w-100 user_info_custom_height">
                                    <source src="{{get_post_video($media_file->file_name)}}" type="">
                                </video>
                            </a>
                        </div>
                @else
                    <div class="single-item-countable col"><a href="{{ route('single.post',$media_file->post_id) }}"><img class="img-thumbnail w-100 user_info_custom_height"  src="{{get_post_image($media_file->file_name, 'optimized')}}" alt=""></a></li></div>
                @endif
            @endforeach
        </div>
        <a href="{{ route('user.photos',[$user_data->id , 'identifire' => $identifires]) }}" class="btn common_btn mt-3 d-block mx-auto">{{ get_phrase('See More') }}</a>
    </div>
    <!--  Widget End -->
    <div class="widget friend-widget">
        @php
            $friends = DB::table('friendships')->where(function ($query) use ($user_data) {
            $query->where('accepter', $user_data->id)
                ->orWhere('requester', $user_data->id);
            })
            ->where('is_accepted', 1)
            ->orderBy('friendships.importance', 'desc');
        @endphp
        <div
            class="widget-header mb-3 d-flex justify-content-between align-items-center">
            <h4 class="widget-title">{{get_phrase('Friends')}}</h4>
            <span>{{$friends->get()->count()}} {{get_phrase('Friends')}}</span>
        </div>

        <div class="row row-cols-3 g-1 mt-3">
            @foreach($friends->take(6)->get() as $friend)
                @if($friend->requester == $user_data->id)
                    @php $friends_user_data = DB::table('users')->where('id', $friend->accepter)->first(); @endphp
                @else
                    @php $friends_user_data = DB::table('users')->where('id', $friend->requester)->first(); @endphp
                @endif

                <div class="col">
                    <a href="{{ route('user.profile.view',$friends_user_data->id) }}" class="friend d-block">
                        <img width="100%" class="h-90" src="{{get_user_image($friends_user_data->photo, 'optimized')}}" alt="">
                        <h6 class="small">{{$friends_user_data->name}}</h6>
                    </a>
                </div>
            @endforeach
        </div>
        <a href="{{ route('user.friends',$user_data->id) }}" class="btn common_btn mt-3 d-block mx-auto">{{ get_phrase('See More') }}</a>
    </div>
    <!--  Widget End -->
   
    <!--  Widget End -->
</aside>