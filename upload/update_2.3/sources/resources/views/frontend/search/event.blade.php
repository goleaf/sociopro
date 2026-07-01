
    <div class="page-wrap">
        <div class="card search-card border-none bg-white radius-8  p-20">
            <h3 class="sub-title mb-3">{{ get_phrase('Search Results') }}</h3>
            @include('frontend.search.header')
        </div> <!-- Search Card End -->
        
        
       
        <div class="event-card  card border-none bg-white radius-8  p-20 mt-4">
            <h3 class="sub-title mb-3">{{ get_phrase('Events') }}</h3>
            <div class="row">
                @include('frontend.events.event-single',['events'=>$events])
            </div>
        </div> <!-- Event Cards End -->
        
        
    </div>



    @include('frontend.main_content.scripts')
    @include('frontend.initialize')
    @include('frontend.common_scripts')
