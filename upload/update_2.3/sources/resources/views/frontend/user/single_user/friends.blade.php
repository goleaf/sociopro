<!-- Profile Nav End -->
<div class="friends-tab ct-tab bg-white radius-8 p-3">
    <div class="search_left">
        <h3>{{get_phrase('Friends')}}</h3>
     </div>
   
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel"
            aria-labelledby="home-tab">
            <div id="" class="friends-list mt-3">

                @include('frontend.user.single_user.friends_single_data')

            </div>
        </div>
        <!-- Tab Pane End -->
        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="friends-request my-3 g-2">
                <div id="" class="row">

                    @include('frontend.user.single_user.friend_requests_single_data')
                    
                </div>
            </div>
        </div>
        <!-- Tab Pane End -->
    </div>
    <!-- Tab Content End -->
</div>
<!-- Friends Tab End -->