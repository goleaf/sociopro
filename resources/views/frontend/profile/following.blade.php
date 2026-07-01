<?php
use App\Models\Follower;

    $following_ids = Follower::where('user_id', auth()->user()->id)->orderBy('created_at', 'desc')->get();
    $count = Follower::where('user_id', auth()->user()->id)->count();

    // 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
    
?>


<div class="d-flex e_media  align-items-center justify-content-between al_title mb-4">
    <h3 class="h6 fw-7 m-0" style="font-size: 11px">{{ $count . get_phrase(' People you follow') }}</h3>
</div>


@foreach($following_ids as $following_id)
    @php 
        $following_user_data = DB::table('users')->where('id', $following_id->follow_id)->first();
        $number_of_friend_friends = json_decode($following_user_data->friends);
        $number_of_my_friends = json_decode($user_info->friends);

        if(!is_array($number_of_friend_friends)) $number_of_friend_friends = array();
        if(!is_array($number_of_my_friends)) $number_of_my_friends = array();

        if($following_user_data->id==auth()->user()->id){
            continue;
        }

        $number_of_mutual_friends = count(array_intersect($number_of_friend_friends, $number_of_my_friends)); 
    @endphp

<div class="col-lg-6">
    <div class="single-item-countable d-flex friend-item align-items-center justify-content-between mb-3">
        <div class="n_request_control">
             <div class="d-flex align-items-center w-100">
                 <!-- Avatar -->
                 <div class="avatar">
                     <a href="{{ route('user.profile.view',$following_user_data->id) }}"><img class="avatar-img rounded-circle user_image_show_on_modal" src="{{get_user_image($following_user_data->photo, 'optimized')}}" alt="" height="40" width="40"></a>
                 </div>
                 <div class="avatar-info ms-2">
                     <h6><a href="{{ route('user.profile.view',$following_user_data->id) }}">{{$following_user_data->name}}</a></h6>
                     <div class="activity-time small-text text-muted"> {{$number_of_mutual_friends}} {{get_phrase('Mutual Friends')}}</div>
                 </div>
             </div>
             <div class="post-controls dropdown dotted">
                 <a class="dropdown-toggle" href="#" id="navbarDropdown"
                     role="button" data-bs-toggle="dropdown"
                     aria-expanded="false">
                 </a>
                 <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                     @php
                         $friend_id = $following_user_data->id;
                         $follow = \App\Models\Follower::where('user_id',auth()->user()->id)->where('follow_id',$friend_id)->count();
                     @endphp
                     @if ($follow>0)
                             <li><a class="dropdown-item" href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfollow',$friend_id); ?>')">{{ get_phrase('Unfollow') }}</a></li>
                         @else
                             <li><a class="dropdown-item" href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.follow',$friend_id); ?>')">{{ get_phrase('Follow') }}</a> </li>
                     @endif
                    
                 </ul>
             </div>
        </div>
   </div>
</div>
@endforeach