@foreach ($vidoes as $video)
    @continue(! $viewData->videoPost($video))

    <div class="single-entry single-item-countable" id="video-{{ $video->id }}"> 
        <div class="entry-inner">
            <div class="entry-header d-flex justify-content-between">
                <div class="ava-info d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <img src="{{ get_user_image($video->getUser->photo,'optimized') }}" class="rounded rounded-circle user_image_show_on_modal" alt="...">
                    
                    </div>
                    <div class="ava-desc ms-2">
                        <h3 class="mb-0">{{ $video->getUser->name }} 
                            @if ($viewData->isFollowing($video->getUser->id, auth()->user()))
                                <a href="javascript:void(0)" onclick="ajaxAction('{{ route('user.unfollow',$video->getUser->id) }}')">{{ get_phrase('Unfollow') }}</a> 
                            @else
                                <a href="javascript:void(0)" onclick="ajaxAction('{{ route('user.follow',$video->getUser->id) }}')">{{ get_phrase('Follow') }}</a> 
                            @endif
                            
                        </h3>
                        <span class="meta-time text-muted">{{ $video->created_at->timezone(Auth::user()->timezone)->format("M d") }} at {{ date('H:i A', strtotime($video->created_at)); }}</span>
                        @if ($video->privacy=='public')
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
                        <li>
                            @if ($viewData->isVideoSaved($video, auth()->user()))
                            <a href="javascript:void(0)" onclick="ajaxAction('{{ route('unsave.video.later',$video->id) }}')" class="dropdown-item btn btn-primary btn-sm"> <img src="{{ asset('assets/frontend/images/save.png') }}" alt=""> {{get_phrase('Unsave Video')}}</a>
                            @else
                            <a href="javascript:void(0)" onclick="ajaxAction('{{ route('save.video.later',$video->id) }}')" class="dropdown-item btn btn-primary btn-sm"> <img src="{{ asset('assets/frontend/images/save.png') }}" alt=""> {{get_phrase('Save Video')}}</a>
                            @endif
                        </li>
                        @if ($video->user_id==auth()->user()->id)
                            <li>
                                <a href="javascript:void(0)" onclick="confirmAction('{{ route('video.delete', ['video_id' => $video->id]) }}', true)" class="dropdown-item btn btn-primary btn-sm"><i class="fa fa-trash me-1"></i> {{get_phrase('Delete Video')}}</a>
                            </li>
                        @endif
                        
                    </ul>
                </div>
            </div>
            <div class="entry-content pt-2">
                <video class="plyr-js w-100" onplay="pauseOtherVideos(this)" controls src="{{asset('storage/videos/'.$video->file)}}">
            </div>
           
            <div class="e_comment">
                <div class="entry-footer">
                    <div class="footer-share d-flex justify-content-around">
                        <span class="entry-react post-react">

                            <a href="javascript:void(0)" onclick="myReact('post', 'like', 'toggle', {{ $viewData->videoPost($video)->post_id }})" id="my_post_reacts{{ $viewData->videoPost($video)->post_id }}">
                                @include('frontend.main_content.post_reacts', ['my_react' => true,'user_info'=>$video->getUser, 'user_reacts' => $viewData->reacts($viewData->videoPost($video))])
                            </a>

                            <ul class="react-list">
                                <li><a href="javascript:void(0)" onclick="myReact('post', 'like', 'update', {{ $viewData->videoPost($video)->post_id }})"><img src="{{asset('storage/images/like.svg')}}" alt="Like" style="margin-right: 1px;"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myReact('post', 'love', 'update', {{ $viewData->videoPost($video)->post_id }})"><img src="{{asset('storage/images/love.svg')}}" alt="Love" style="width: 30px; margin-top: 2px;"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myReact('post', 'haha', 'update', {{ $viewData->videoPost($video)->post_id }})"><img src="{{asset('storage/images/haha.svg')}}" alt="Haha"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myReact('post', 'sad', 'update', {{ $viewData->videoPost($video)->post_id }})"><img src="{{asset('storage/images/sad.svg')}}" class="mx-1" alt="Sad"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myReact('post', 'angry', 'update', {{ $viewData->videoPost($video)->post_id }})"><img src="{{asset('storage/images/angry.svg')}}" alt="Angry"></a>
                                </li>
                            </ul>
                        </span>
                        <span class="entry-react"><a href="javascript:void(0)" onclick="$('#user-comments-{{ $viewData->videoPost($video)->post_id }}').toggle();"><img width="19px" src="{{ asset('storage/images/comment2.svg') }}">{{get_phrase('Comments')}}</a></span>
                        <span class="entry-react" data-bs-toggle="modal" data-bs-target="#exampleModal"><a
                                href="javascript:void(0)" onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.main_content.share_post_modal', 'post_id' => $viewData->videoPost($video)->post_id] )}}', '{{get_phrase('Share post')}}');"><img width="19px" src="{{ asset('storage/images/share2.svg') }}">{{get_phrase('Share')}}</a></span>
                        <!-- Post share modal -->
                    </div>
                    <div class="entry-meta py-4 d-flex border-bottom justify-content-between align-items-center" >
                        <a href="javascript:void(0)" id="post_reacts{{ $viewData->videoPost($video)->post_id }}">
                            @include('frontend.main_content.post_reacts', ['post_react' => true,'user_info'=>$video->getUser, 'user_reacts' => $viewData->reacts($viewData->videoPost($video))])
                        </a>
        
                        <div class="post-comment">
                            <ul>
                                <li><a href="javascript:void(0)"> <span id="post_comment_count{{ $viewData->videoPost($video)->post_id }}">{{ $viewData->videoCommentCount($video) }}</span>  {{get_phrase('Comments')}}</a></li>
                                <li><a href="javascript:void(0)"><span> {{ $viewData->postShareCount($viewData->videoPost($video)) }} </span>{{get_phrase('Share')}}</a></li>
                            </ul>
                        </div>
                    </div>
                </div> <!-- Entry Footer End -->
           </div>
        </div>
        <!-- Comment Start -->
        <div class="user-comments s_comment  d-hidden bg-white" id="user-comments-{{ $viewData->videoPost($video)->post_id }}">
            <div class="comment-form d-flex mb-1">
                <img src="{{get_user_image(Auth()->user()->photo, 'optimized')}}" alt="" class="h-39 rounded-circle img-fluid" width="40px">
                <form action="javascript:void(0)" class="w-100 ms-2" method="post">
                    @csrf
                    <input class="form-control py-3" onkeypress="postComment(this, 0, {{ $viewData->videoPost($video)->post_id }}, 0,'post');" rows="1" placeholder="Write Comments">
                </form>
            </div>
            <ul class="comment-wrap p-3 pb-0 list-unstyled" id="comments{{ $viewData->videoPost($video)->post_id }}">
                @include('frontend.main_content.comments',['comments'=>$viewData->videoRootComments($video),'post_id'=>$viewData->videoPost($video)->post_id,'type'=>"post"])
            </ul>

            @if($viewData->videoRootComments($video)->count() < $viewData->videoCommentCount($video))
                <a class="btn p-3 pt-0" onclick="loadMoreComments(this, {{ $viewData->videoPost($video)->post_id }}, 0, {{ $viewData->videoCommentCount($video) }},'post')">{{get_phrase('View more')}}</a>
            @endif
        </div>
    </div>
    @endforeach


    @include('frontend.initialize')
