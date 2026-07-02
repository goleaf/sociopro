@foreach($comments as $comment)
    <!-- Comment item START -->
    <li class="comment-item n_comment_item mb-0" id="comment_{{ $comment->comment_id }}">
        <div class="d-flex justify-content-between mb-8">
            <div class="d-flex">
                <!-- Avatar -->
                @if (isset($type)&&$type=="page")
                    <div class="">
                        <a href="#" class="h-39"><img class="rounded-circle height-40px h-39" src="{{get_page_logo($comment->photo, 'logo')}}" alt="Profile photo"></a>
                    </div>
                @else
                    <div class="">
                        <a href="#" class="h-39"><img class="rounded-circle height-40px h-39" src="{{get_user_image($comment->photo, 'optimized')}}" alt="Profile photo"></a>
                    </div>
                @endif
                <div class="avatar-info ms-2">
                    {{-- <h4 class="ava-nave">{{$comment->name}}</h4>
                    <div class="activity-time small-text text-muted">{{date_formatter($comment->updated_at, 2)}}</div> --}}
                    {{--  --}}
                    <div class="comment-details n_comment_details" >
                        <div class="comment-content bg-secondary" >
                            <h4 class="ava-nave">{{$comment->name}}</h4>
                            <p>{{$comment->description}}</p>
                            <a href="javascript:void(0)" id="comment_reacts{{ $comment->comment_id }}">
                                @include('frontend.main_content.comment_reacts', [
                                    'comment_react' => true,
                                    'user_comment_reacts' => $viewData->reacts($comment),
                                ])
                            </a>
                        </div>
            
                        <ul class="nav">
                            <li class="nav-item">
                              
                                <p class="f-13">{{date_formatter($comment->updated_at, 2)}}</p>

                            </li>
                            <li class="nav-item post-react">
                                <a class="nav-link" href="javascript:void(0)" onclick="myCommentReact('like', 'toggle', {{$comment->comment_id}})" id="my_comment_reacts{{ $comment->comment_id }}">
                                    @include('frontend.main_content.comment_reacts', [
                                        'my_react' => true,
                                        'user_comment_reacts' => $viewData->reacts($comment),
                                    ])
                                </a>
            
                                <ul class="react-list">
                                    <li><a href="javascript:void(0)" onclick="myCommentReact('like', 'update', {{$comment->comment_id}})"><img src="{{asset('storage/images/like.svg')}}" class="" alt="Like" style="margin-right: 1px;"></a>
                                    </li>
                                    <li><a href="javascript:void(0)" onclick="myCommentReact('love', 'update', {{$comment->comment_id}})"><img src="{{asset('storage/images/love.svg')}}" alt="Love" style="width: 30px; margin-top: 2px;"></a>
                                    </li>
                                    <li><a href="javascript:void(0)" onclick="myCommentReact('haha', 'update', {{$comment->comment_id}})"><img src="{{asset('storage/images/haha.svg')}}" alt="Angry"></a>
                                    </li>
                                    <li><a href="javascript:void(0)" onclick="myCommentReact('sad', 'update', {{$comment->comment_id}})"><img src="{{asset('storage/images/sad.svg')}}" class="mx-1" alt="Sad"></a>
                                    </li>
                                    <li><a href="javascript:void(0)" onclick="myCommentReact('angry', 'update', {{$comment->comment_id}})"><img src="{{asset('storage/images/angry.svg')}}" alt="Angry"></a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" onclick="$('.parent_comment_reply_fields:not(#reply_field{{$comment->comment_id}})').hide(); $('#reply_field{{$comment->comment_id}}').toggle(0);" href="javascript:void(0)">{{get_phrase('Reply')}}</a></li>
                        </ul>
                        {{-- <div class="comment-form  bg-secondary d-hidden parent_comment_reply_fields" id="reply_field{{$comment->comment_id}}">
                            <form action="javascript:void(0)" class="w-100 ms-2" method="post">
                                <input class="form-control" onkeypress="postComment(this, {{$comment->comment_id}}, {{$post_id}}, 0,'{{$type}}');" placeholder="Write your reply">
                            </form>
                        </div> --}}
                    </div>
                    {{--  --}}
                </div>
            </div>

            @if(Auth()->user()->id == $comment->user_id)
            <div class="post-controls dropdown dotted">
                <a class="dropdown-toggle" href="#" id="navbarDropdown" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                    <li>
                        <a href="javascript:void(0)" onclick="confirmAction('{{ route('comment.delete', ['comment_id' => $comment->comment_id]) }}', true)" class="dropdown-item"><i class="fa fa-trash me-1"></i> {{get_phrase('Delete Comment')}}</a>
                    </li>

                </ul>
            </div>
            @endif

        </div>

        <!-- Comment item nested START -->
        <ul class="comment-item-nested list-unstyled" id="child_comments{{$comment->comment_id}}">
            @include('frontend.main_content.child_comments', [
                'child_comments' => $viewData->childComments($comment),
            ]) 
        </ul>

        <div class="pl-45px comment-form  d-hidden parent_comment_reply_fields" id="reply_field{{$comment->comment_id}}">
            <form action="javascript:void(0)" class="w-100 ms-2" method="post">
                @csrf
                <input class="form-control" onkeypress="postComment(this, {{$comment->comment_id}}, {{$post_id}}, 0,'{{$type}}');" placeholder="Write your reply">
            </form>
        </div>

        @if($viewData->childComments($comment)->count() < $viewData->childCommentCount($comment, $type))
            <a class="btn view_btn_text p-3 pt-0" onclick="loadMoreComments(this, {{$post_id}}, {{$comment->comment_id}}, {{$viewData->childCommentCount($comment, $type)}},'{{ $type }}')">{{get_phrase('View more')}}</a>
        @endif
    </li>
        
@endforeach
