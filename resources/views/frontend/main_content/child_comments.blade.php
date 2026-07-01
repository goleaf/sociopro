@foreach($child_comments as $child_comment)
@php $user_comment_reacts = json_decode($child_comment->user_reacts, true); @endphp
<!-- Comment item START -->
<li class="comment-item n_comment_item c_details mb-0" id="comment_{{ $child_comment->comment_id }}">
    <div class="d-flex justify-content-between">
        <div class="d-flex">
            <!-- Avatar -->
            <div class="">
                <a href="#!" class="h-39"><img class="rounded-circle h-39" src="{{get_user_image($child_comment->photo, 'optimized')}}" alt="Profile photo"></a>
            </div>
            <div class="avatar-info ms-2">
                {{-- <h4 class="ava-nave">{{$child_comment->name}}</h4>
                <div class="activity-time small-text text-muted">{{date_formatter($child_comment->updated_at, 2)}}
                </div> --}}
                {{--  --}}
                <div class="comment-details n_comment_details">
                    <div class="comment-content bg-secondary">
                        <h4 class="ava-nave">{{$child_comment->name}}</h4>
                        <p>{{$child_comment->description}}</p>
                        <a href="javascript:void(0)" id="comment_reacts<?php echo $child_comment->comment_id; ?>">
                            @include('frontend.main_content.comment_reacts', ['comment_react' => true])
                        </a>
                    </div>
            
                    <ul class="nav">
                        <li class="nav-item">
                           <p class="f-13">
                            {{date_formatter($child_comment->updated_at, 2)}}
                            </p>
                        </li>
                        <li class="nav-item post-react">
                            <a class="nav-link" href="javascript:void(0)" onclick="myCommentReact('like', 'toggle', {{$child_comment->comment_id}})" id="my_comment_reacts<?php echo $child_comment->comment_id; ?>">
                                    @include('frontend.main_content.comment_reacts', ['my_react' => true])
                            </a>
            
                            <ul class="react-list">
                                <li><a href="javascript:void(0)" onclick="myCommentReact('like', 'update', {{$child_comment->comment_id}})"><img src="{{asset('storage/images/like.svg')}}" class="" alt="Like" style="margin-right: 1px;"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myCommentReact('love', 'update', {{$child_comment->comment_id}})"><img src="{{asset('storage/images/love.svg')}}" alt="Love" style="width: 30px; margin-top: 2px;"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myCommentReact('haha', 'update', {{$child_comment->comment_id}})"><img src="{{asset('storage/images/haha.svg')}}" alt="Haha"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myCommentReact('sad', 'update', {{$child_comment->comment_id}})"><img src="{{asset('storage/images/sad.svg')}}" class="mx-1" alt="Sad"></a>
                                </li>
                                <li><a href="javascript:void(0)" onclick="myCommentReact('angry', 'update', {{$child_comment->comment_id}})"><img src="{{asset('storage/images/angry.svg')}}" alt="Angry"></a>
                                </li>
                            </ul>
                        </li>
            
                    </ul>
                </div>
                {{--  --}}
            </div>
        </div>
        @if(Auth()->user()->id == $child_comment->user_id)
        <div class="post-controls dropdown dotted">
            <a class="dropdown-toggle" href="#" id="navbarDropdown"
                role="button" data-bs-toggle="dropdown" aria-expanded="false">
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li><a href="javascript:void(0)" onclick="confirmAction('<?php echo route('comment.delete', ['comment_id' => $child_comment->comment_id]); ?>', true)" class="dropdown-item"><i class="fa fa-trash me-1"></i> {{get_phrase('Delete Comment')}}</a></li>
            </ul>
        </div>
        @endif
    </div>
    
</li>
<!-- Comment item END -->
@endforeach

