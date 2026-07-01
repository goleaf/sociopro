<div class="d-flex e_media align-items-center justify-content-between al_title mb-4">
    <h3 class="h6 fw-7 m-0" style="font-size: 11px">{{ $followersCount . get_phrase(' People follow you') }}</h3>
</div>

@foreach($followers as $followerProfile)
    <div class="col-lg-6">
        <div class="single-item-countable d-flex friend-item align-items-center justify-content-between mb-3">
            <div class="n_request_control">
                <div class="d-flex align-items-center w-100">
                    <div class="avatar">
                        <a href="{{ route('user.profile.view', $followerProfile['user']->id) }}">
                            <img class="avatar-img rounded-circle user_image_show_on_modal" src="{{ get_user_image($followerProfile['user']->photo, 'optimized') }}" alt="" height="40" width="40">
                        </a>
                    </div>
                    <div class="avatar-info ms-2">
                        <h6><a href="{{ route('user.profile.view', $followerProfile['user']->id) }}">{{ $followerProfile['user']->name }}</a></h6>
                        <div class="activity-time small-text text-muted">{{ $followerProfile['mutual_friends'] }} {{ get_phrase('Mutual Friends') }}</div>
                    </div>
                </div>
                <div class="post-controls dropdown dotted">
                    <a class="dropdown-toggle" href="#" id="navbarDropdown"
                        role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        @if ($followerProfile['is_following'])
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="ajaxAction('{{ route('user.unfollow', $followerProfile['user']->id) }}')">{{ get_phrase('Unfollow') }}</a></li>
                        @else
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="ajaxAction('{{ route('user.follow', $followerProfile['user']->id) }}')">{{ get_phrase('Follow') }}</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endforeach
