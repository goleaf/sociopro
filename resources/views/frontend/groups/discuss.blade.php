 <div class="profile-cover group-cover ng_profile  bg-white mb-3">
        @include('frontend.groups.cover-photo')
        @include('frontend.groups.iner-nav')
    </div>
    <div class="group-content profile-content">
        <div class="row gx-3">
            <div class="col-lg-12 col-sm-12">
                @if ($viewData->userJoinedGroup($group, auth()->user()) || $group->user_id==auth()->user()->id)
                    {{-- @include('frontend.groups.iner-nav') --}}
                    @include('frontend.main_content.create_post',['group_id'=>$group->id])
                    {{-- @include('frontend.main_content.comments',['comments'=>$comments,'post_id'=>$group->id,'type'=>"group"]) --}}
                    
                    @if($viewData->rootComments($group, 'group')->count() < $viewData->postCommentCount($group, 'group')) 
                        <a class="btn p-3 pt-0" onclick="loadMoreComments(this, {{$group->id}}, 0, {{$viewData->postCommentCount($group, 'group')}},'group')">{{get_phrase('View Comment')}}</a>
                    @endif
                    
                        @include('frontend.main_content.posts',['type'=>"group"])
                    
                    
                @else
                <div class="card">
                    <div class="card-body">
                        {{ get_phrase('join Group First') }}
                    </div>
                </div>
                @endif
            </div> <!-- COL END -->
            <!--  Group Content Inner Col End -->
            {{-- @include('frontend.groups.bio') --}}
        </div>
    </div><!-- Group content End -->
    @include('frontend.groups.invite')
@include('frontend.main_content.scripts')
