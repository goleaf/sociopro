<div class="profile-wrap">
    @include('frontend.pages.timeline-header')
    <div class="profile-content mt-3">
        <div class="row gx-3 np_timeline">
            <div class="col-lg-12 col-sm-12">
                {{-- @include('frontend.pages.inner-nav') --}}
                @if ($page->user_id==auth()->user()->id)
                    @include('frontend.main_content.create_post',['page_id'=>$page->id])
                @endif

                @include('frontend.main_content.comments',['comments'=>$comments,'post_id'=>$page->id,'type'=>"page"])
                
                @include('frontend.main_content.posts',['type'=>"page"])
            </div>
            {{-- <div class="col-lg-5 col-sm-12">
                @include('frontend.pages.bio')
            </div> --}}
        </div>
    </div> 
</div>

@include('frontend.main_content.scripts')
