@foreach ($viewData->friendRequestRows($friend_requests, $user_info) as $row)
    <div class="single-item-countable col-6" id="friendRequest{{ $row['user']->id }}">
        <div class="card">
            <div class="mb-2">
                <img src="{{ get_user_image($row['user']->photo, 'optimized') }}"
                    class="rounded-circle user_image_show_on_modal img-fluid" alt="">
            </div>
            <div class="card-details">
                <h6><a href="{{ route('user.profile.view', $row['user']->id) }}">{{ $row['user']->name }}</a></h6>
                <span class="mute">{{ $row['mutual_count'] }} {{ get_phrase('Mutual Friends') }}</span>
                <div class="card-control">
                    <form class="ajaxForm" action="{{ route('profile.accept_friend_request') }}" method="post">
                        @CSRF
                        <input type="hidden" name="user_id" value="{{ $row['user']->id }}">
                        <button type="submit" id="friendRequestConfirmBtn{{ $row['user']->id }}"
                            class="btn btn-primary w-100">{{ get_phrase('Confirm') }}</button>
                        <button type="button" id="friendRequestAcceptedBtn{{ $row['user']->id }}"
                            class="btn btn-secondary w-100 d-hidden">{{ get_phrase('Accepted') }}</button>
                    </form>
                    <a href="javascript:void(0)"
                        onclick="confirmAction('{{ route('profile.delete_friend_request', ['user_id' => $row['user']->id]) }}', true)"
                        class="btn btn-secondary d-block">{{ get_phrase('Delete') }}</a>
                </div>
            </div>
        </div>
    </div>
@endforeach
