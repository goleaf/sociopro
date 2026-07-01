@foreach($add_friend as $friend)
    @php
        $friendsData = json_decode($friend->friends, true);
        $isFriend = in_array($info, $friendsData);
        $hasRequestSent = App\Models\Friendships::where('requester', auth()->user()->id)
                                                 ->where('accepter', $friend->id)
                                                 ->exists();
        $hasRequestReceived = App\Models\Friendships::where('requester', $friend->id)
                                                     ->where('accepter', auth()->user()->id)
                                                     ->exists();
    @endphp
    
    @if (!$isFriend && !$hasRequestSent && !$hasRequestReceived)
        <div class="col-lg-4 col-md-4 col-6">
            <div class="card sugg-card p-0 box_shadow border-none  suggest_p radius-8">
                <a href="{{ route('user.profile.view', $friend->id) }}" class="thumbnail-110-106" style="background-image: url('{{ get_user_image($friend->photo, 'optimized') }}')"></a>
                <div class="p-8 d-flex flex-column">
                    <h4><a href="{{ route('user.profile.view', $friend->id) }}">{{ $friend->name }}</a></h4>
                    <a href="javascript:;" onclick="ajaxAction('<?php echo route('user.friend',$friend->id); ?>')" class="btn common_btn">{{ get_phrase('Add Friend') }}</a>
                </div>
            </div>
        </div>
    @endif
@endforeach
