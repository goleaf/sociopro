@foreach ($viewData->suggestedFriendRows($add_friend, auth()->user(), $info ?? null) as $friend)
    <div class="col-lg-4 col-md-4 col-6">
        <div class="card sugg-card p-0 box_shadow border-none suggest_p radius-8">
            <a href="{{ route('user.profile.view', $friend->id) }}" class="thumbnail-110-106"
                style="background-image: url('{{ get_user_image($friend->photo, 'optimized') }}')"></a>
            <div class="p-8 d-flex flex-column">
                <h4><a href="{{ route('user.profile.view', $friend->id) }}">{{ $friend->name }}</a></h4>
                <a href="javascript:;" onclick="ajaxAction('{{ route('user.friend', $friend->id) }}')"
                    class="btn common_btn">{{ get_phrase('Add Friend') }}</a>
            </div>
        </div>
    </div>
@endforeach
