@php
    $comments = DB::table('comments')->join('users', 'comments.user_id', '=', 'users.id')->where('comments.is_type', 'blog')->where('comments.id_of_type', $blog->id)->where('comments.parent_id', 0)->select('comments.*', 'users.name', 'users.photo')->orderBy('comment_id', 'DESC')->take(1)->get();                                                                
    $total_comments = DB::table('comments')->where('comments.is_type', 'blog')->where('comments.id_of_type', $blog->id)->where('comments.parent_id', 0)->get()->count();
@endphp

<div class="single-wrap">
    <div class="sblog_feature bg-white radius-8">
        <div class="blog-feature " style="background-image: url('{{ get_blog_image($blog->thumbnail,'coverphoto') }}')">
            <div class="blog-head">
                <div class="d-flex align-items-center">
                    <img src="{{ get_user_image($blog->user_id,'optimized') }}" class="user-round user_image_show_on_modal" alt="">
                    <div class="ava-info ms-2">
                        <h6 class="mb-0"><a href="{{ route('user.profile.view',$blog->getUser->id) }}">{{ $blog->getUser->name }}</a></h6>
                        <small>{{ $blog->created_at->diffForHumans()  }}</small>
                    </div>
                </div>
               
            </div>
        </div><!--  Blog Cover End -->
        <div class="sm_bottom">
             <div>
                <a href="#"> {{ $blog->created_at->format("d-M-Y") }} </a>
               <h1>{{ $blog->title }}</h1>
             </div>
            <div class="bhead-meta">
                <span>{{ $total_comments }} {{ get_phrase('Comments') }}</span>
                <span>{{ count(json_decode($blog->view)) }} {{ get_phrase('Views') }}</span>
            </div>
        </div>
    </div>
    <div class="row mt-12 ">
        <div class="col-lg-12">
            <div class="card border-none p-3 radius-8 nblog_details blog-details">
                @php echo script_checker($blog->description, false); @endphp
                <div class="blog-footer">
                    <div class="post-share justify-content-between align-items-center border-bottom pb-3">
                        <div class="post-meta ">
                            <h4 class="h3">{{get_phrase('tags:')}}</h4>
                            @php
                                $tags = json_decode($blog->tag, true);
                            @endphp
                            
                            @if(is_array($tags))
                                @foreach ($tags as $tag )
                                    <a href="#"><span class="badge common_btn_3 mt-1">#{{ $tag }}</span></a>
                                @endforeach
                            @endif
                        </div>
                        <div class="p-share d-flex align-items-center mt-20">
                            <h3 class="h6">{{ get_phrase('Share') }}: </h3>
                            <div class="social-share ms-2">
                                <ul>
                                    @foreach ($socailshare as $key => $value )
                                        <li><a href="{{ $value }}" target="_blank"><i class="fa-brands fa-{{ $key }}"></i></a></li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Comment Start -->
                        <div class="user-comments  bg-white" id="user-comments-{{$blog->id}}">
                            <div class="comment-form nBlog_user d-flex p-3 bg-secondary">
                                <img src="{{get_user_image(Auth()->user()->photo, 'optimized')}}" alt="" class="rounded-circle h-39 img-fluid " width="40px">
                                <form action="javascript:void(0)" class="w-100 ms-2" method="post">
                                    <input class="form-control py-3" onkeypress="postComment(this, 0, {{$blog->id}}, 0,'blog');" rows="1" placeholder="Write Comments">
                                </form>
                            </div>
                            <ul class="comment-wrap pt-3 pb-0 list-unstyled" id="comments{{$blog->id}}">
                                @include('frontend.main_content.comments',['comments'=>$comments,'post_id'=>$blog->id,'type'=>"blog"])
                            </ul>
                            @if($comments->count() < $total_comments) 
                                <a class="btn p-3 pt-0" onclick="loadMoreComments(this, {{$blog->id}}, 0, {{$total_comments}},'blog')">{{get_phrase('View Comment')}}</a>
                            @endif
                        </div>
                    
                </div><!--  Blog Details Footer End -->
            </div>
        </div>
        {{-- <div class="col-lg-5">
           
        </div> --}}
    </div>
</div><!-- Single Page Wrap End -->
@include('frontend.main_content.scripts')
@include('frontend.initialize')


