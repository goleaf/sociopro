

<div class="profile-wrap">
    @include('frontend.pages.timeline-header')
    <div class="profile-content mt-3">
        <div class="row gx-3">
            <div class="col-lg-12 col-sm-12">
                {{-- @include('frontend.pages.inner-nav') --}}
                
                <!-- Profile Nav End -->
                <div class="friends-tab pg_tab_main ct-tab radius-8 bg-white p-3">
                    
                    
                    <div class="photo-list">
                        <h4 class="h6 mb-3">{{get_phrase('Your videos')}}</h4>
                        <div class="flex-wrap" id="allVideos">
                            @include('frontend.profile.video_single')
                        </div>
                    </div>

                </div> <!-- Friends Tab End -->
                
            </div> <!-- COL END -->
            {{-- <div class="col-lg-5 col-sm-12">
                @include('frontend.pages.bio')
            </div> --}}
        </div>
    </div> <!-- Profile content End -->
</div>


