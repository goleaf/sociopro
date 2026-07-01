@foreach ($viewData->friendshipRows($friendships, $user_data, auth()->user()) as $row)
    <div class="single-item-countable d-flex friend-item align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center w-100">
            <div class="avatar">
                <a href="{{ route('user.profile.view', $row['user']->id) }}">
                    <img class="avatar-img rounded-circle user_image_show_on_modal"
                        src="{{ get_user_image($row['user']->photo, 'optimized') }}" alt="">
                </a>
            </div>
            <div class="avatar-info ms-2">
                <h6 class="mb-1">
                    <a href="{{ route('user.profile.view', $row['user']->id) }}">{{ $row['user']->name }}</a>
                </h6>
                <div class="activity-time small-text text-muted">
                    {{ $row['mutual_count'] }} {{ get_phrase('Mutual Friends') }}
                </div>
            </div>
        </div>
    </div>
@endforeach
