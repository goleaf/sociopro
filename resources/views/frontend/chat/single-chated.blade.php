@foreach ( $previousChatList as $previousChatList)
    @continue(! $viewData->chatThreadUser($previousChatList, auth()->user()))
    <div class="single-contact message_ava d-flex align-items-center justify-content-between @if($viewData->chatUnreadCount($previousChatList, $viewData->chatThreadUser($previousChatList, auth()->user()))>1) bg-my-black @endif">
            <div class="avatar d-flex align-items-center">
                <a href="{{ route('chat',$viewData->chatThreadUser($previousChatList, auth()->user())->id) }}" class="d-flex align-items-center">
                    <div class="avatar">
                        <img src="{{ get_user_image($viewData->chatThreadUser($previousChatList, auth()->user())->photo,'optimized') }}" class="img-fluid rounded-circle w-100" alt="">
                        @if ($viewData->chatThreadUser($previousChatList, auth()->user())->isOnline())
                            <span class="online-status active"></span>
                        @endif
                    </div>
                </a>
                <div class="avatar-info">
                    <a href="{{ route('chat',$viewData->chatThreadUser($previousChatList, auth()->user())->id) }}"><h3 class="h6 mb-0">{{ $viewData->chatThreadUser($previousChatList, auth()->user())->name }}</h3></a>
                    <span>
                        @if(!empty($viewData->chatLastMessage($previousChatList)?->thumbsup))
                                <i class="fa-solid fa-thumbs-up fs-6"></i>
                        @else
                            <a href="{{ route('chat',$viewData->chatThreadUser($previousChatList, auth()->user())->id) }}">{{ $viewData->chatLastMessage($previousChatList)?->message ? ellipsis($viewData->chatLastMessage($previousChatList)->message,30):"" }} @if ($viewData->chatUnreadCount($previousChatList, $viewData->chatThreadUser($previousChatList, auth()->user()))>1) <span class="badge bg-primary">{{ $viewData->chatUnreadCount($previousChatList, $viewData->chatThreadUser($previousChatList, auth()->user())) }}</span>@endif</a>
                        @endif
                    </span>
                </div>
            </div>
            <div class="m-user-action">
                <div class="post-controls dropdown dotted">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="{{ route('user.profile.view',$viewData->chatThreadUser($previousChatList, auth()->user())->id) }}"><i class="fa fa-user"></i> {{ get_phrase('View Profile') }} </a></li>
                    </ul>
                </div>
            </div>
    </div> 
@endforeach
