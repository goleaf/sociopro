
    <div class="page-wrap">
        <div class="card search-card border-none bg-white radius-8  p-20">
            <h3 class="sub-title mb-3">{{ get_phrase('Search Results') }}</h3>
            @include('frontend.search.header')
        </div> <!-- Search Card End -->
        
        
        <div class="card people-card border-none bg-white radius-8  p-20 mt-4">
            <h3 class="sub-title mb-3">{{get_phrase('Posts')}}</h3>
            @include('frontend.main_content.posts',['posts'=>$posts,'type'=>'user_post'])
        </div>
    </div>



    @include('frontend.main_content.scripts')
    @include('frontend.initialize')
    @include('frontend.common_scripts')
