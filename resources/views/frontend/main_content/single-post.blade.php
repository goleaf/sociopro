<div class="single-item-countable single-entry" id="">
    <div class="entry-inner en_left">
        <div
            class="entry-header d-flex justify-content-between @if (isset($_GET['shared'])) hidden-on-shared-view @endif">
            <div class="ava-info d-flex align-items-center">
                @if (isset($type) && $type == 'page')
                    <div class="flex-shrink-0">
                        <img src="{{ get_page_logo($post->logo, 'logo') }}" class="rounded-circle" alt="...">
                    </div>
                @elseif (isset($type) && $type == 'group')
                    <div class="flex-shrink-0">
                        <img src="{{ get_user_image('storage/userimage/' . $post->photo, 'optimized') }}"
                            class="rounded-circle" alt="...">
                    </div>
                @elseif (isset($type) && $type == 'video')
                    <div class="entry-header d-flex justify-content-between">
                        <div class="ava-info d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="{{ get_user_image($post->photo, 'optimized') }}"
                                    class="rounded rounded-circle" alt="...">

                            </div>
                            <div class="ava-desc ms-2">
                                <h3 class="mb-0">{{ $post->name }} <a href="#">{{ get_phrase('Follow') }}</a>
                                </h3>
                                <small class="meta-time text-muted">{{ date('M d ', strtotime($post->created_at)) }} at
                                    {{ date('H:i A', strtotime($post->created_at)) }}</small>
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
                                            src="{{ asset('assets/frontend/images/save.png') }}"
                                            alt="">{{ get_phrase('Save Video') }}</a></li>
                                <li><a class="dropdown-item" href="#"><img
                                            src="{{ asset('assets/frontend/images/link.png') }}"
                                            alt="">{{ get_phrase('Copy Link') }}</a></li>
                                <li><a class="dropdown-item" href="#"><img
                                            src="{{ asset('assets/frontend/images/report.png') }}"
                                            alt="">{{ get_phrase('Report') }} </a></li>
                            </ul>
                        </div>
                    </div>
                @elseif (isset($type) && $type == 'paid_content')
                    <div class="flex-shrink-0">
                        <img src="{{ get_user_image($post->user_id, 'optimized') }}"
                            class="rounded-circle user_image_show_on_modal" alt="...">
                    </div>
                @elseif (isset($type) && $type == 'user_post')
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
                            <a class="text-black" href="{{ route('single.page', $post->id) }}">{{ $post->title }}</a>
                        @elseif (isset($type) && $type == 'group')
                            <a class="text-black"
                                href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->name }}</a>
                        @else
                            <a class="text-black"
                                href="{{ route('user.profile.view', $post->user_id) }}">{{ $post->getUser->name }}</a>
                        @endif
                        <!-- Check tagged users -->

                        @if ($post->post_type == 'live_streaming')
                            <small class="text-muted">{{ get_phrase('is live now') }}</small>
                        @endif

                        @if (count($viewData->taggedUserIds($post)) > 0 || $post->activity_id > 0)
                            <small class="text-muted">-</small>

                            <!-- Feeling and activity -->
                            @if ($viewData->feelingActivity($post->activity_id))
                                @if ($viewData->feelingActivity($post->activity_id)->type == 'activity')
                                    {{ $viewData->feelingActivity($post->activity_id)->title }}
                                @else
                                    <spam class="text-muted">{{ get_phrase('feeling') }}</spam>
                                    <b> {{ $viewData->feelingActivity($post->activity_id)->title }} </b>
                                @endif
                            @endif

                            @if (count($viewData->taggedUserIds($post)) > 0)
                                <small class="text-muted">{{ get_phrase('with') }}</small>
                                @foreach ($viewData->taggedUserIds($post) as $key => $tagged_user_id)
                                    @if ($key > 0)
                                        <small class="text-muted">,</small>
                                    @endif
                                    <a class="text-black"
                                        href="{{ route('profile') }}">{{ $viewData->userName($tagged_user_id) }}</a>
                                @endforeach

                            @endif
                        @endif

                        @if (!empty($post->location))
                            <small class="text-muted">{{ get_phrase('in') }}</small> <a
                                href="https://www.google.com/maps/place/{{ $post->location }}"
                                target="_blanck">{{ $post->location }}</a>
                        @endif
                    </h3>
                    <span class="meta-time text-muted">{{ date_formatter($post->created_at, 2) }}</span>

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
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <input type="hidden" id="copy_post_{{ $post->post_id }}"
                        value="{{ route('single.post', $post->post_id) }}">
                    <li><a class="dropdown-item" href="javascript:void(0)" value="copy"
                            onclick="copyToClipboard('copy_post_{{ $post->post_id }}')"><img
                                src="{{ asset('storage/images/link.png') }}"
                                alt="">{{ get_phrase('Copy Link') }}</a></li>

                    <li><a class="dropdown-item" href="javascript:void(0)"
                            onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.main_content.create_report', 'post_id' => $post->post_id]) }}', '{{ get_phrase('Report Post') }}');"
                            data-bs-toggle="modal" data-bs-target="#createEvent"><img
                                src="{{ asset('storage/images/report.png') }}"
                                alt="">{{ get_phrase('Report') }}
                        </a></li>
                </ul>
            </div>
        </div>
        <div class="entry-content pt-2">
            @if ($post->post_type == 'general' || $post->post_type == 'profile_picture' || $post->post_type == 'cover_photo')
                @include('frontend.main_content.media_type_post_view')

                @if (!empty($post->location))
                    @include('frontend.main_content.location_type_post_view')
                @endif
            @elseif($post->post_type == 'live_streaming')
                <div class="row">
                    <div class="col-12 text-center">
                        <span class="live-icon">
                            <i class="fa fa-dot-circle"></i>
                            {{ get_phrase('LIVE') }}
                        </span>
                        <img class="live-image" src="{{ asset('storage/images/live.png') }}">
                    </div>
                    <div class="col-12 text-center ">
                        <a class="live-watch-now mt-20" href="{{ route('live', ['post_id' => $post->post_id]) }}"><i
                                class="fa fa-video"></i> {{ get_phrase('Watch now') }}</a>
                    </div>
                </div>
            @elseif($post->post_type == 'share')
                <div class="py-1">
                    <div class="text-quote">
                        @if (\Illuminate\Support\Str::contains($post->description, 'http', 'https'))
                            <iframe src="{{ $post->description }}?shared=yes" onload="resizeIframe(this)"
                                scrolling="no" class="w-100" frameborder="0"></iframe>
                            <a class="ellipsis-line-1 ellipsis-line-2"
                                href="{{ $post->description }}">{{ $post->description }}</a>
                        @endif
                    </div>
                </div>
           @elseif($post->post_type == 'album')


            @endif

        </div>
       <div class="e_comment">
            <div class="entry-footer @if (isset($_GET['shared'])) hidden-on-shared-view @endif">
                <div class="footer-share d-flex justify-content-around">
                    <span class="entry-react post-react">

                        <a href="javascript:void(0)" onclick="myReact('post', 'like', 'toggle', {{ $post->post_id }})"
                            id="my_post_reacts{{ $post->post_id }}">
                            @include('frontend.main_content.post_reacts', [
                                'my_react' => true,
                                'user_reacts' => $viewData->reacts($post),
                            ])
                        </a>

                        <ul class="react-list">
                            <li><a href="javascript:void(0)"
                                    onclick="myReact('post', 'like', 'update', {{ $post->post_id }})"><img
                                        src="{{ asset('storage/images/r-like.png') }}" alt="Like"></a>
                            </li>
                            <li><a href="javascript:void(0)"
                                    onclick="myReact('post', 'love', 'update', {{ $post->post_id }})"><img
                                        src="{{ asset('storage/images/r-love.png') }}" alt="Love"></a>
                            </li>
                            <li><a href="javascript:void(0)"
                                    onclick="myReact('post', 'sad', 'update', {{ $post->post_id }})"><img
                                        src="{{ asset('storage/images/r-cry1.png') }}" alt="Sad"></a>
                            </li>
                            <li><a href="javascript:void(0)"
                                    onclick="myReact('post', 'angry', 'update', {{ $post->post_id }})"><img
                                        src="{{ asset('storage/images/r-angry.png') }}" alt="Angry"></a>
                            </li>
                        </ul>
                    </span>
                    <span class="entry-react"><a href="javascript:void(0)"
                            onclick="$('#user-comments-{{ $post->post_id }}').toggle();"> <img width="19px" src="{{ asset('storage/images/comment2.svg') }}">{{ get_phrase('Comments') }}</a></span>
                    <span class="entry-react" data-bs-toggle="modal" data-bs-target="#exampleModal"><a
                            href="javascript:void(0)"
                            onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.main_content.share_post_modal', 'post_id' => $post->post_id]) }}', '{{ get_phrase('Share post') }}');"> <img width="19px" src="{{ asset('storage/images/share2.svg') }}">{{ get_phrase('Share') }}</a></span>
                    <!-- Post share modal -->
                </div>
                <div class="entry-meta py-4 d-flex border-bottom justify-content-between align-items-center ">
                    <a href="javascript:void(0)" id="post_reacts{{ $post->post_id }}">
                        @include('frontend.main_content.post_reacts', [
                            'post_react' => true,
                            'user_reacts' => $viewData->reacts($post),
                        ])
                    </a>
                    
                    <div class="post-comment">
                        <ul>
                            <li><a onclick="$('#user-comments-{{ $post->post_id }}').toggle();"
                                    href="javascript:void(0)"><span
                                        id="post_comment_count{{ $post->post_id }}">{{ $viewData->postCommentCount($post) }}</span>{{ get_phrase('Comments') }}</a>
                            </li>
                            <li><a href="javascript:void(0)"><span>0</span>{{ get_phrase('Share') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div> <!-- Entry Footer End -->
    </div>
    <!-- Comment Start -->
    <div class="user-comments s_comment d-hidden bg-white" id="user-comments-{{ $post->post_id }}">
        <div class="comment-form d-flex bg-secondary">
            <img src="{{ get_user_image(Auth()->user()->photo, 'optimized') }}" alt=""
                class="rounded-circle img-fluid h-39" width="40px">
            <form action="javascript:void(0)" class="w-100 ms-2" method="post">
                @csrf
                <input class="form-control py-3" onkeypress="postComment(this, 0, {{ $post->post_id }}, 0,'post');"
                    rows="1" placeholder="Write Comments">
            </form>
        </div>
        <ul class="comment-wrap p-3 pb-0 list-unstyled" id="comments{{ $post->post_id }}">
            @include('frontend.main_content.comments', [
                'comments' => $viewData->rootComments($post),
                'post_id' => $post->post_id,
                'type' => 'post',
            ])
        </ul>

        @if ($viewData->rootComments($post)->count() < $viewData->postCommentCount($post))
            <a class="btn p-3 pt-0"
                onclick="loadMoreComments(this, {{ $post->post_id }}, 0, {{ $viewData->postCommentCount($post) }},'post')">{{ get_phrase('View more') }}</a>
        @endif
    </div>
</div><!--  Single Entry End -->

@unless ($embeddedPostCard ?? false)
    @include('frontend.main_content.scripts')

    <script src="{{ asset('assets/frontend/gallery/jquery.justifiedGallery.min.js') }}"></script>
    @include('frontend.initialize')
@endunless




