@php 
$mutualFriendsData = [];
foreach($friendships as $friendship) {
    if($friendship->requester == $user_info->id) {
        $friends_user_data = DB::table('users')->where('id', $friendship->accepter)->first();
    } else {
        $friends_user_data = DB::table('users')->where('id', $friendship->requester)->first();
    }

    // Decode the JSON object as associative arrays and get the keys
    $number_of_friend_friends = array_keys(json_decode($friends_user_data->friends, true));
    $number_of_my_friends = array_keys(json_decode($user_info->friends, true));

    // Find the intersection of the two arrays
    $mutual_friends = array_intersect($number_of_friend_friends, $number_of_my_friends);
    foreach($mutual_friends as $set){
        array_push($mutualFriendsData, $set);
    }
}


// Block User Each Other
$blockedByUser = DB::table('block_users')->where('user_id', auth()->user()->id)->pluck('block_user')->toArray();
$blockedByOthers = DB::table('block_users')->where('block_user', auth()->user()->id)->pluck('user_id')->toArray();

//Save_post

$user = Auth()->user();
    $savedPostsJson = $user->save_post;
    $save_posts = collect(json_decode($savedPostsJson, true)); 

@endphp


@foreach ($posts as $loopIndex => $post)
    @if(!empty($post->location))
        @php
            $total_comment_main_comments = DB::table('comments')
                ->where('comments.is_type', 'post')
                ->where('comments.id_of_type', $post->post_id)
                ->where('comments.parent_id', 0)
                ->get()
                ->count();
            $total_comment_sub_comments = DB::table('comments')
                ->where('comments.is_type', 'post')
                ->where('comments.id_of_type', $post->post_id)
                ->where('comments.parent_id', '>', 0)
                ->get()
                ->count();
            $total_comments = $total_comment_main_comments + $total_comment_sub_comments;

            $comments = DB::table('comments')
                ->join('users', 'comments.user_id', '=', 'users.id')
                ->where('comments.is_type', 'post')
                ->where('comments.id_of_type', $post->post_id)
                ->where('comments.parent_id', 0)
                ->select('comments.*', 'users.name', 'users.photo')
                ->orderBy('comment_id', 'DESC')
                ->take(1)
                ->get();

            $tagged_user_ids = json_decode($post->tagged_user_ids);



        // New Feature Code

                $user_id = $post->user_id;
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
        @php
        $user_reacts = json_decode($post->user_reacts, true); 
        
        //  User Block
        if (in_array($post->user_id, $blockedByUser)) {
                continue;
            }
            if (in_array($post->user_id, $blockedByOthers)) {
                continue;
            }
        @endphp
        @if($post->post_type != 'fundraiser')
        <div class="single-item-countable single-entry" id="postIdentification{{ $post->post_id }}">
            <div class="entry-inner">
                @if ($post->publisher == 'memory')
                    @php
                        $explode_data = explode('/', $post->description);
                        $shared_id = end($explode_data);
                    

                        $post_date_by_memory = DB::table('posts')
                            ->where('post_id', $shared_id)
                            ->value('created_at');
                        $time_passed = round((time() - $post_date_by_memory) / 31536000);
                    @endphp
                    <div class="entry-header memories-header">
                        <small class="d-block w-100 text-center text-muted mb-3">
                            {{ ucfirst(get_phrase('on ____ ____ ____ ago', [date('M d Y,', $post_date_by_memory), $time_passed, $time_passed > 1 ? 'years' : 'year'])) }}
                        </small>
                    </div>
                @endif

                @isset($has_memories)
                    @php
                    
                    
                    $time_passed = round((time() - $post->created_at) / 31536000); @endphp
                    <div class="entry-header memories-header">
                        <small class="meta-time text-muted text-center w-100 d-block">
                            {{ get_phrase('On this day') }}
                            {{ date('M d, Y', $post->created_at) }}
                        </small>
                        <h3 class="mb-3 pb-3  text-center text-muted">
                            {{ get_phrase('You have a memory ____ ____ ago', [$time_passed, $time_passed > 1 ? 'years' : 'year']) }}
                        </h3>
                    </div>
                @endisset

                <div class="entry-header d-flex justify-content-between">
                    <div class="ava-info d-flex align-items-center">
                    
                        @if (isset($type) && $type == 'page')
                            <div class="flex-shrink-0">
                                <img src="{{ get_page_logo($post->logo, 'logo') }}"
                                    class="rounded-circle user_image_show_on_modal" alt="...">
                            </div>
                        @elseif (isset($type) && $type == 'group')
                            <div class="eUserFeature">
                                <div class="flex-shrink-0">
                                    <img src="{{ get_user_image($post->photo, 'optimized') }}"
                                        class="rounded-circle user_image_show_on_modal" alt="...">
                                </div>

                                {{-- New Hover Profile  --}} 
                                @if(Auth()->user()->id != $post->user_id)
                                <div class="hoverFeature">
                                    <div class="h_top">
                                        <div class="h_eImage">
                                            <img src="{{ get_user_image($post->user_id, 'optimized') }}">
                                        </div> 
                                        <div class="h_right_text">
                                            <h4><a href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->getUser->name }}</a> </h4>
                                            <ul>
                                                    <li class="d-flex">
                                                        <i class="fas fa-user-friends"></i>
                                                        @php
                                                        $number_of_my_friends = json_decode($user_info->friends, true);
                                                        $my_data = DB::table('users')->where('id', $post->getUser->id)->first();
                                                        $my_fr = json_decode($my_data->friends, true);
                                                        $mutual_fr = count(array_intersect($number_of_my_friends, $my_fr));
                                                        @endphp
                                                        <p>{{$mutual_fr}} {{get_phrase('Mutual friend')}}</p>
                                                    </li>
                                                    <li class="d-flex">
                                                        <i class="fa-solid fa-house-user"></i>
                                                        <p>{{get_phrase('Lives in')}} <strong>{{ $post->getUser->address }}</strong> </p>
                                                    </li>
                                            </ul>
                                        </div>
                                    </div>
                                    @if ($friend>0)
                                            @if ($friendAccepted->count()>0 && $friendAccepted->value('is_accepted') == 1)
                                                <ul class="d-flex eHfooter">
                                                    <li>
                                                        <a href="#" class="btn common_btn ac_btn" id="btnGroupDrop1" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-user-group"></i> {{ get_phrase('Friend') }} </a>
                                                        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                            <li><a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="dropdown-item custom_unfrind_button"> <i class="fa-solid fa-xmark"></i>{{ get_phrase('Unfriend') }}</a></li>
                                                        </ul>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                    </li>
                                                    <li class="eDrops">
                                                        <div class="dropdown">
                                                            <button class="btn  dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)"
                                                                onclick="showCustomModal('<?php echo route('block_user', $post->post_id); ?>', 'Block')">{{get_phrase('Block')}}</a></li>
                                                            </ul>
                                                        </div>
                                                    </li>
                                                </ul>
                                            @else
                                                <ul  class="d-flex eHfooter">
                                                    <li>
                                                        <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="btn common_btn ac_btn" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Cancle Friend Request') }}"><i class="fa-solid fa-xmark"></i> {{ get_phrase('Cancel') }}</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                    </li>
                                                </ul>
                                            @endif
                                    @else   
                                        <ul class="d-flex eHfooter">
                                            <li> <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.friend',$post->user_id); ?>')" class="btn common_btn ac_btn"><i class="fa-solid fa-plus"></i> {{ get_phrase('Add Friend') }} </a></li>
                                            <li>  <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a></li>
                                        </ul>
                                    @endif
                                </div>
                            @endif
                            {{-- End Hover Profile --}}

                            </div>

                        @elseif (isset($type) && $type == 'video')
                            <div class="entry-header d-flex justify-content-between">
                                <div class="ava-info d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <img src="{{ get_user_image($post->photo, 'optimized') }}"
                                            class="rounded rounded-circle user_image_show_on_modal" alt="...">

                                    </div>
                                    <div class="ava-desc ms-2">
                                        <h3 class="mb-0">{{ $post->name }}</h3>
                                        <small class="meta-time text-muted">{{ date('M d ', strtotime($post->created_at)) }}
                                            at {{ date('H:i A', strtotime($post->created_at)) }}</small>
                                        @if ($post->privacy == 'public')
                                            <span class="meta-privacy text-muted"><i
                                                    class="fa-solid fa-earth-americas"></i></span>
                                        @endif
                                    </div>
                                </div>
                                <div class="post-controls dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <li><a class="dropdown-item" href="#"><img
                                                    src="{{ asset('assets/frontend/images/save.png') }}" alt="">
                                                {{ get_phrase('Save Video') }}</a></li>
                                        <li><a class="dropdown-item" href="#"><img
                                                    src="{{ asset('assets/frontend/images/link.png') }}"
                                                    alt="">{{ get_phrase('Copy Link') }}</a></li>
                                        <li><a class="dropdown-item" href="#"><img
                                                    src="{{ asset('assets/frontend/images/report.png') }}"
                                                    alt="">{{ get_phrase('Report') }} </a></li>
                                    </ul>
                                </div>
                            </div>
                        @elseif (isset($type) && $type == 'user_post')
                        
                            <div class="eUserFeature">
                                <div class="flex-shrink-0">
                                    <img src="{{ get_user_image($post->user_id, 'optimized') }}"
                                        class="rounded-circle user_image_show_on_modal" alt="...">
                                </div>
                                {{-- New Hover Profile  --}} 
                                @if(Auth()->user()->id != $post->user_id)
                                    <div class="hoverFeature">
                                        <div class="h_top">
                                            <div class="h_eImage">
                                                <img src="{{ get_user_image($post->user_id, 'optimized') }}">
                                            </div> 
                                            <div class="h_right_text">
                                                <h4><a href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->getUser->name }}</a> </h4>
                                                <ul>
                                                        <li class="d-flex">
                                                            <i class="fas fa-user-friends"></i>
                                                            @php
                                                            $number_of_my_friends = json_decode($user_info->friends, true);
                                                            $my_data = DB::table('users')->where('id', $post->getUser->id)->first();
                                                            $my_fr = json_decode($my_data->friends, true);
                                                            $mutual_fr = count(array_intersect($number_of_my_friends, $my_fr));
                                                            @endphp
                                                            <p>{{$mutual_fr}} {{get_phrase('Mutual friend')}}</p>
                                                        </li>
                                                        <li class="d-flex">
                                                            <i class="fa-solid fa-house-user"></i>
                                                            <p>{{get_phrase('Lives in')}} <strong>{{ $post->getUser->address }}</strong> </p>
                                                        </li>
                                                </ul>
                                            </div>
                                        </div>
                                        @if ($friend>0)
                                                @if ($friendAccepted->count()>0 && $friendAccepted->value('is_accepted') == 1)
                                                    <ul class="d-flex eHfooter">
                                                        <li>
                                                            <a href="#" class="btn common_btn ac_btn" id="btnGroupDrop1" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-user-group"></i> {{ get_phrase('Friend') }} </a>
                                                            <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                                <li><a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="dropdown-item custom_unfrind_button"> <i class="fa-solid fa-xmark"></i>{{ get_phrase('Unfriend') }}</a></li>
                                                            </ul>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                        </li>
                                                        <li class="eDrops">
                                                            <div class="dropdown">
                                                                <button class="btn  dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                                    onclick="showCustomModal('<?php echo route('block_user', $post->post_id); ?>', 'Block')">{{get_phrase('Block')}}</a></li>
                                                                </ul>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                @else
                                                    <ul  class="d-flex eHfooter">
                                                        <li>
                                                            <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="btn common_btn ac_btn" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Cancle Friend Request') }}"><i class="fa-solid fa-xmark"></i> {{ get_phrase('Cancel') }}</a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                        </li>
                                                    </ul>
                                                @endif
                                        @else   
                                            <ul class="d-flex eHfooter">
                                                <li> <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.friend',$post->user_id); ?>')" class="btn common_btn ac_btn"><i class="fa-solid fa-plus"></i> {{ get_phrase('Add Friend') }} </a></li>
                                                <li>  <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a></li>
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                                {{-- End Hover Profile --}}
                            </div>
                        @elseif (isset($type) && $type == 'paid_content')
                        <div class="eUserFeature">
                                <div class="flex-shrink-0">
                                    <img src="{{ get_user_image($post->user_id, 'optimized') }}"
                                        class="rounded-circle user_image_show_on_modal" alt="...">
                                </div>
                                {{-- New Hover Profile  --}} 
                                @if(Auth()->user()->id != $post->user_id)
                                    <div class="hoverFeature">
                                        <div class="h_top">
                                            <div class="h_eImage">
                                                <img src="{{ get_user_image($post->user_id, 'optimized') }}">
                                            </div> 
                                            <div class="h_right_text">
                                                <h4><a href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->getUser->name }}</a> </h4>
                                                <ul>
                                                        <li class="d-flex">
                                                            <i class="fas fa-user-friends"></i>
                                                            @php
                                                            $number_of_my_friends = json_decode($user_info->friends, true);
                                                            $my_data = DB::table('users')->where('id', $post->getUser->id)->first();
                                                            $my_fr = json_decode($my_data->friends, true);
                                                            $mutual_fr = count(array_intersect($number_of_my_friends, $my_fr));
                                                            @endphp
                                                            <p>{{$mutual_fr}} {{get_phrase('Mutual friend')}}</p>
                                                        </li>
                                                        <li class="d-flex">
                                                            <i class="fa-solid fa-house-user"></i>
                                                            <p>{{get_phrase('Lives in')}} <strong>{{ $post->getUser->address }}</strong> </p>
                                                        </li>
                                                </ul>
                                            </div>
                                        </div>
                                        @if ($friend>0)
                                                @if ($friendAccepted->count()>0 && $friendAccepted->value('is_accepted') == 1)
                                                    <ul class="d-flex eHfooter">
                                                        <li>
                                                            <a href="#" class="btn common_btn ac_btn" id="btnGroupDrop1" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-user-group"></i> {{ get_phrase('Friend') }} </a>
                                                            <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                                <li><a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="dropdown-item custom_unfrind_button"> <i class="fa-solid fa-xmark"></i>{{ get_phrase('Unfriend') }}</a></li>
                                                            </ul>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                        </li>
                                                        <li class="eDrops">
                                                            <div class="dropdown">
                                                                <button class="btn  dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                </button>
                                                                <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)"
                                                                    onclick="showCustomModal('<?php echo route('block_user', $post->post_id); ?>', 'Block')">{{get_phrase('Block')}}</a></li>
                                                                </ul>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                @else
                                                    <ul  class="d-flex eHfooter">
                                                        <li>
                                                            <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="btn common_btn ac_btn" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Cancle Friend Request') }}"><i class="fa-solid fa-xmark"></i> {{ get_phrase('Cancel') }}</a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                        </li>
                                                    </ul>
                                                @endif
                                        @else   
                                            <ul class="d-flex eHfooter">
                                                <li> <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.friend',$post->user_id); ?>')" class="btn common_btn ac_btn"><i class="fa-solid fa-plus"></i> {{ get_phrase('Add Friend') }} </a></li>
                                                <li>  <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a></li>
                                            </ul>
                                        @endif
                                    </div>
                                @endif
                                {{-- End Hover Profile --}}
                            </div>
                        @elseif ($has_memories)
                            <div class="flex-shrink-0">
                                <img src="{{ get_user_image($post->user_id, 'optimized') }}"
                                    class="rounded-circle user_image_show_on_modal" alt="...">
                            </div>
                        @else
                            <div class="flex-shrink-0">
                                <img src="{{ get_user_image($post->id, 'optimized') }}"
                                    class="rounded-circle user_image_show_on_modal" alt="...">
                            </div>
                        @endif
                        <div class="ava-desc ms-2">
                            <h3 class="mb-0">
                                @if (isset($type) && $type == 'page')
                                    <a class="text-black ms-0"
                                        href="{{ route('single.page', $post->id) }}">{{ $post->title }}</a>
                                @elseif (isset($type) && $type == 'group')
                                    <a class="text-black ms-0"
                                        href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->name }}</a>
                                @else
                            
                                @php
                                
                                $currentDate = \Carbon\Carbon::now();
                                $badge = \App\Models\Badge::where('user_id', $post->user_id)
                                    ->whereDate('start_date', '<=', $currentDate)
                                    ->whereDate('end_date', '>=', $currentDate)
                                    ->first();
                                @endphp
                                <div class="eUserFeature">
                                    <a class="text-black ms-0 badge"  href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->getUser->name }}
                                        @if($badge?->status == '1' && $badge->start_date <= now() && $badge->end_date >= now())
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11.1825 1.16051C11.5808 0.595046 12.4192 0.595047 12.8175 1.16051L13.8489 2.62463C14.1272 3.01962 14.648 3.15918 15.0865 2.95624L16.7118 2.20397C17.3395 1.91343 18.0655 2.33261 18.1277 3.02149L18.2889 4.80515C18.3324 5.28634 18.7137 5.66763 19.1948 5.71111L20.9785 5.87226C21.6674 5.9345 22.0866 6.66054 21.796 7.28825L21.0438 8.91352C20.8408 9.35198 20.9804 9.87284 21.3754 10.1511L22.8395 11.1825C23.405 11.5808 23.405 12.4192 22.8395 12.8175L21.3754 13.8489C20.9804 14.1272 20.8408 14.648 21.0438 15.0865L21.796 16.7118C22.0866 17.3395 21.6674 18.0655 20.9785 18.1277L19.1948 18.2889C18.7137 18.3324 18.3324 18.7137 18.2889 19.1948L18.1277 20.9785C18.0655 21.6674 17.3395 22.0866 16.7117 21.796L15.0865 21.0438C14.648 20.8408 14.1272 20.9804 13.8489 21.3754L12.8175 22.8395C12.4192 23.405 11.5808 23.405 11.1825 22.8395L10.1511 21.3754C9.87284 20.9804 9.35198 20.8408 8.91352 21.0438L7.28825 21.796C6.66054 22.0866 5.9345 21.6674 5.87226 20.9785L5.71111 19.1948C5.66763 18.7137 5.28634 18.3324 4.80515 18.2889L3.02149 18.1277C2.33261 18.0655 1.91343 17.3395 2.20397 16.7117L2.95624 15.0865C3.15918 14.648 3.01962 14.1272 2.62463 13.8489L1.16051 12.8175C0.595046 12.4192 0.595047 11.5808 1.16051 11.1825L2.62463 10.1511C3.01962 9.87284 3.15918 9.35198 2.95624 8.91352L2.20397 7.28825C1.91343 6.66054 2.33261 5.9345 3.02149 5.87226L4.80515 5.71111C5.28634 5.66763 5.66763 5.28634 5.71111 4.80515L5.87226 3.02149C5.9345 2.33261 6.66054 1.91343 7.28825 2.20397L8.91352 2.95624C9.35198 3.15918 9.87284 3.01962 10.1511 2.62463L11.1825 1.16051Z" fill="#329CE8"/>
                                            <path d="M7.5 11.83L10.6629 14.9929L17 8.66705" stroke="white" stroke-width="1.67647" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        @else
                                        
                                        @endif
                                        @if ($post->user_id != auth()->user()->id)
                                            @php
                                                $follow = \App\Models\Follower::where('user_id', auth()->user()->id)
                                                    ->where('follow_id', $post->user_id)
                                                    ->count();
                                            @endphp
                                            @if ($follow > 0)
                                                <a href="javascript:void(0)"
                                                    onclick="ajaxAction('<?php echo route('user.unfollow', $post->user_id); ?>')">{{ get_phrase('Unfollow') }}</a>
                                            @else
                                                <a href="javascript:void(0)"
                                                    onclick="ajaxAction('<?php echo route('user.follow', $post->user_id); ?>')">{{ get_phrase('Follow') }}</a>
                                            @endif
                                        @endif
                                    </a>
                                    {{-- New Hover Profile  --}} 
                                @if(Auth()->user()->id != $post->user_id)
                                <div class="hoverFeature el_list">
                                    <div class="h_top">
                                        <div class="h_eImage">
                                            <img src="{{ get_user_image($post->user_id, 'optimized') }}">
                                        </div> 
                                        <div class="h_right_text">
                                            <h4><a href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->getUser->name }}</a> </h4>
                                            <ul class="el_shift">
                                                    <li class="d-flex">
                                                        <i class="fas fa-user-friends"></i>
                                                        @php
                                                        $number_of_my_friends = json_decode($user_info->friends, true);
                                                        $my_data = DB::table('users')->where('id', $post->getUser->id)->first();
                                                        $my_fr = json_decode($my_data->friends, true);
                                                        $mutual_fr = count(array_intersect($number_of_my_friends, $my_fr));
                                                        @endphp
                                                        <p>{{$mutual_fr}} {{get_phrase('Mutual friend')}}</p>
                                                    </li>
                                                    <li class="d-flex">
                                                        <i class="fa-solid fa-house-user"></i>
                                                        <p>{{get_phrase('Lives in')}} <strong>{{ $post->getUser->address }}</strong> </p>
                                                    </li>
                                            </ul>
                                        </div>
                                    </div>
                                    @if ($friend>0)
                                            @if ($friendAccepted->count()>0 && $friendAccepted->value('is_accepted') == 1)
                                                <ul class="d-flex eHfooter">
                                                    <li>
                                                        <a href="#" class="btn common_btn ac_btn" id="btnGroupDrop1" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-user-group"></i> {{ get_phrase('Friend') }} </a>
                                                        <ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                            <li><a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="dropdown-item custom_unfrind_button"> <i class="fa-solid fa-xmark"></i>{{ get_phrase('Unfriend') }}</a></li>
                                                        </ul>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                    </li>
                                                    <li class="eDrops">
                                                        <div class="dropdown">
                                                            <button class="btn  dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)"
                                                                onclick="showCustomModal('<?php echo route('block_user', $post->post_id); ?>', 'Block')">{{get_phrase('Block')}}</a></li>
                                                            </ul>
                                                        </div>
                                                    </li>
                                                </ul>
                                            
                                            @else
                                                <ul  class="d-flex eHfooter">
                                                    <li>
                                                        <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user_id); ?>')" class="btn common_btn ac_btn" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ get_phrase('Cancle Friend Request') }}"><i class="fa-solid fa-xmark"></i> {{ get_phrase('Cancel') }}</a>
                                                    </li>
                                                    <li>
                                                        <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a>
                                                    </li>
                                                </ul>
                                            @endif
                                    @else   
                                        <ul class="d-flex eHfooter">
                                            <li> <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.friend',$post->user_id); ?>')" class="btn common_btn ac_btn"><i class="fa-solid fa-plus"></i> {{ get_phrase('Add Friend') }} </a></li>
                                            <li>  <a href="{{ route('chat',$post->user_id) }}" class="btn common_btn ac_btn"><i class="fa-solid fa-message"></i> {{ get_phrase('Message') }}</a></li>
                                        </ul>
                                    @endif
                                </div>
                            @endif
                        </div>
                            {{-- End Hover Profile --}}
                                @endif
                                <!-- Check tagged users -->

                                @if ($post->post_type == 'cover_photo')
                                    <small class="text-muted">{{ get_phrase('has changed cover photo') }}</small>
                                @endif

                                @if ($post->post_type == 'share')
                                    @if ($post->publisher == 'memory')
                                        <small class="text-muted">{{ get_phrase('shared a memory') }}</small>
                                    @else
                                        <small class="text-muted">{{ get_phrase('shared post') }}</small>
                                    @endif
                                @endif

                                @if ($post->post_type == 'live_streaming')
                                    @php
                                        $live_description = json_decode($post->description, true);
                                    @endphp
                                    @if (is_array($live_description) && $live_description['live_video_ended'] == 'yes')
                                        <small
                                            class="text-muted">{{ get_phrase('was on live ____', [date_formatter($post->created_at, 3)]) }}</small>
                                    @else
                                        <small class="text-muted">{{ get_phrase('is live now') }}</small>
                                    @endif
                                @endif

                                @if (count($tagged_user_ids) > 0 || $post->activity_id > 0)
                                    <small class="text-muted">-</small>

                                    <!-- Feeling and activity -->
                                    @if ($post->activity_id > 0)
                                        @php
                                            $feeling_and_activities = DB::table('feeling_and_activities')
                                                ->where('feeling_and_activity_id', $post->activity_id)
                                                ->first();
                                        @endphp
                                        @if ($feeling_and_activities->type == 'activity')
                                            {{ $feeling_and_activities->title }}
                                        @else
                                            <spam class="text-muted">{{ get_phrase('feeling') }}</spam>
                                            <b> {{ $feeling_and_activities->title }} </b>
                                        @endif
                                    @endif

                                    @if (count($tagged_user_ids) > 0)
                                        <small class="text-muted">{{ get_phrase('with') }}</small>
                                        @foreach ($tagged_user_ids as $key => $tagged_user_id)
                                            <small class="text-muted">@php
                                                if ($key > 0) {
                                                    echo ',';
                                                }
                                            @endphp</small>
                                            <a class="text-black"
                                                href="{{ route('profile') }}">{{ DB::table('users')->where('id', $tagged_user_id)->value('name') }}</a>
                                        @endforeach
                                    @endif
                                @endif

                                @if (!empty($post->location))
                                    <small class="text-muted">{{ get_phrase('in') }}</small> <a
                                        href="https://www.google.com/maps/place/{{ $post->location }}"
                                        target="_blanck">{{ $post->location }}</a>
                                @endif
                            </h3>
                            <small class="meta-time text-muted">{{ date_formatter($post->created_at, 2) }}</small>

                            @if ($post->privacy == 'public')
                                <span class="meta-privacy text-muted" title="{{ ucfirst(get_phrase($post->privacy)) }}"><i
                                        class="fa-solid fa-earth-americas"></i></span>
                            @elseif($post->privacy == 'private')
                                <span class="meta-privacy text-muted" title="{{ ucfirst(get_phrase($post->privacy)) }}"><i
                                        class="fa-solid fa-user"></i></span>
                            @else
                                <span class="meta-privacy text-muted" title="{{ ucfirst(get_phrase($post->privacy)) }}"><i
                                        class="fa-solid fa-users"></i></span>
                            @endif
                        </div>
                    </div>
                    <div class="post-controls dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        </a>
                        <ul class="dropdown-menu post_dots" aria-labelledby="navbarDropdown">
                            <input type="hidden" id="copy_post_{{ $post->post_id }}"
                                value="{{ route('single.post', $post->post_id) }}">
                                
                                <li>
                                    @if($save_posts->contains($post->post_id))
                                        <a class="dropdown-item" href="{{ route('unsave_post', ['id' => $post->post_id]) }}">
                                            <i class="fa-solid fa-bookmark"></i> {{ get_phrase('Unsave Post') }}
                                        </a>
                                    @else
                                        <a class="dropdown-item" href="{{ route('save_post', ['id' => $post->post_id]) }}">
                                            <i class="fa-regular fa-bookmark"></i> {{ get_phrase('Save Post') }}
                                        </a>
                                    @endif
                                </li>
                                <li><a class="dropdown-item" href="javascript:void(0)" value="copy"
                                        onclick="copyToClipboard('copy_post_{{ $post->post_id }}')"><img
                                            src="{{ asset('storage/images/link.png') }}"
                                            alt="">{{ get_phrase('Copy Link') }}</a></li>
                            @if ($post->user_id == auth()->user()->id)
                                @if ($post->post_type != 'live_streaming' && $post->location == '')
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                            onclick="showCustomModal('<?php echo route('edit_post_form', $post->post_id); ?>', '{{ get_phrase('Edit post') }}', 'lg')">
                                            <i class="fa-solid fa-pencil"></i> {{ get_phrase('Edit') }}</a>
                                    </li>
                                @endif
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)"
                                        onclick="confirmAction('<?php echo route('post.delete', ['post_id' => $post->post_id]); ?>', true)"> <i
                                            class="fa-solid fa-trash-can"></i> {{ get_phrase('Delete') }}</a>
                                </li>
                            @endif
                            <li><a class="dropdown-item" href="javascript:void(0)"
                                    onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.main_content.create_report', 'post_id' => $post->post_id]) }}', '{{ get_phrase('Report Post') }}');"
                                    data-bs-toggle="modal" data-bs-target="#createEvent"><img
                                        src="{{ asset('storage/images/report.png') }}"
                                        alt="">{{ get_phrase('Report') }}
                                </a></li>
                        </ul>
                    </div>
                </div>

                <!-- START POST VIEW -->
            
                <div class="entry-content pt-2">
                    <!-- post description -->

                    @if (
                        $post->post_type == 'general' ||
                            $post->post_type == 'profile_picture' ||
                            $post->post_type == 'cover_photo' ||
                            $post->post_type == 'paid_content')
                        @if (isset($subscription))
                            @if ($subscription == 0)
                                @if ($post->privacy == 'friends')
                                    @php $visibility = 'locked'; @endphp
                                @else
                                    @php $visibility = 'unlocked'; @endphp
                                @endif
                            @else
                                @php $visibility = 'unlocked'; @endphp
                            @endif

                            @if ($visibility == 'locked')
                                @php
                                    $media = DB::table('media_files')
                                        ->where('post_id', $post->post_id)
                                        ->get();

                                    $cover_pic = DB::table('paid_content_creators')
                                        ->where('user_id', $post->user_id)
                                        ->value('cover_photo');
                                @endphp
                                @if ($media->count() == 0)
                                    @include('frontend.paid_content.enable_lock_section')
                                @else
                                    @php echo script_checker($post->description); @endphp
                                    @include('frontend.paid_content.enable_lock_section')
                                @endif
                            @else
                                @php echo script_checker($post->description); @endphp
                                @include('frontend.main_content.media_type_post_view')
                            @endif
                        @else
                        
                            @php 
                            $description = $post->description;
                            $pattern = '/\bhttps?:\/\/\S+\b/';
                            $preg = preg_match($pattern, $description, $matches); 
                            @endphp
                            @if ($preg && !str_contains($matches[0] , request()->getHttpHost())) 
                                {!! $post->description !!}
                                @include('frontend.main_content.url_content', ['url' => $matches[0]])
                            @else
                                {!! $post->description !!}
                                @include('frontend.main_content.media_type_post_view')
                            @endif
                        
                        @endif

                    

                        @if (!empty($post->location))
                            @include('frontend.main_content.location_type_post_view')
                        @endif
                
                    @elseif($post->post_type == 'share' || $post->post_type == 'memory') 
                        <div class="py-1">
                            <div class="text-quote">
                                @if (\Illuminate\Support\Str::contains($post->description, 'http', 'https'))
                                    @php
                                        $explode_data = explode('/', $post->description);
                                        $shared_id = end($explode_data);
                                    

                                    @endphp
                                    
                                    <iframe src="{{ $post->description }}?shared=yes" onload="resizeIframe(this)"
                                        scrolling="no" class="w-100" frameborder="0"></iframe>
                                    <a class="ellipsis-line-1 ellipsis-line-2"
                                        href="{{ $post->description }}">{{ $post->description }}</a>
                                @endif
                            </div>
                        </div>
                    @elseif($post->post_type == 'fundraisers')
                        @php 
                            if(isset($post->publisher_id)){
                                $fundShare = \App\Models\Fundraiser::where('id', $post->publisher_id)->first();
                                $user_info = \App\Models\Users::where('id', $fundShare->user_id)->first();
                            }
                        @endphp  
                        <div class="py-1">
                            <div class="text-quote image">
                                @if(isset($fundShare->cover_photo))
                                <a class="ellipsis-line-1 ellipsis-line-2" href="{{ route('fundraiser.profile', $fundShare->id) }}">
                                    <img src="{{ asset('assets/frontend/images/campaign/' . $fundShare->cover_photo) }}"
                                                class="card-img-top" alt="blog">
                                            <div class="fundraiser-text">
                                                <img src="{{ get_user_image($user_info->id, 'optimized') }}"
                                                class="rounded-circle user_image_show_on_modal" alt="...">
                                                <div class="fund-information">
                                                    <p>{{$user_info->name}}'s {{get_phrase('Campaign link')}}</p>
                                                    <span>{{$fundShare->title}}</span>
                                                </div>
                                            </div>
                                            </a>
                                        @else
                                        <img src="{{ asset('storage/blog/coverphoto/default/default.jpg') }}" class="card-img-top" alt="blog">
                                @endif
                            </div>
                        </div>
                    @elseif($post->post_type == 'live_streaming')
                        @include('frontend.main_content.live_streaming_type_post_view')
                    @endif
                </div>
            
            </div>
            <!-- Comment Start -->
            <div class="user-comments s_comment d-hidden bg-white" id="user-comments-{{ $post->post_id }}">

                <ul class="comment-wrap p-3 pt-0 pb-0 list-unstyled" id="comments{{ $post->post_id }}">
                    @include('frontend.main_content.comments', [
                        'comments' => $comments,
                        'post_id' => $post->post_id,
                        'type' => 'post',
                    ])
                </ul>

                @if ($comments->count() < $total_comments)
                    <a class="btn view_btn_text p-3 pt-0"
                        onclick="loadMoreComments(this, {{ $post->post_id }}, 0, {{ $total_comments }},'post')">{{ get_phrase('View more') }}</a>
                @endif
                <div class="comment-form d-flex pb-3">
                    <img src="{{ get_user_image(Auth()->user()->photo, 'optimized') }}" alt=""
                        class="rounded-circle img-fluid h-39" width="40px">
                    <form action="javascript:void(0)" class="w-100 ms-2" method="post">
                        <input class="form-control py-3"
                            onkeypress="postComment(this, 0, {{ $post->post_id }}, 0,'post');" rows="1"
                            placeholder="Write Comments">
                    </form>
                </div>
            </div>
        </div><!--  Single Entry End -->
        @endif
        @if (isset($search) && !empty($search))
            @if ($loopIndex == 2)
            @break
        @endif
        @endif
    @endif
@endforeach

@include('frontend.initialize')
