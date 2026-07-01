<!-- Profile Nav End -->
<div class="friends-tab ct-tab bg-white radius-8 p-3">
    <div class="n_friend_search">
        <div class="search_left">
           <h3>{{get_phrase('Friends')}}</h3>
           {{-- <form action="#" class="f_search">
               <input type="text" class="form-control" placeholder="Search">
               <i class="fa-solid fa-magnifying-glass"></i>
           </form> --}}
        </div>
      
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="home-tab" data-bs-toggle="tab"
                data-bs-target="#home" type="button" role="tab" aria-controls="home"
                aria-selected="true">{{get_phrase('My Friends')}}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-tab" data-bs-toggle="tab"
                data-bs-target="#profile" type="button" role="tab"
                aria-controls="profile" aria-selected="false">{{get_phrase('Friend Requests')}}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="add_friend-tab" data-bs-toggle="tab"
                data-bs-target="#add_friend" type="button" role="tab"
                aria-controls="add_friend" aria-selected="false">{{get_phrase('Find Friends')}}</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="block_friend-tab" data-bs-toggle="tab"
                data-bs-target="#block_friend" type="button" role="tab"
                aria-controls="block_friend" aria-selected="false">{{get_phrase('Block List')}}</button>
        </li>
    </ul>
 </div>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel"
            aria-labelledby="home-tab">
            <div id="my-friends-list" class="friends-list mt-3">
                   
                  <div class="row">
                     @include('frontend.profile.friends_single_data')
                  </div>
               
            </div>
        </div>
        <!-- Tab Pane End -->
        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
            <div class="friends-request my-3 g-2">
                <div id="my-friend-request-list" class="row">

                    @include('frontend.profile.friend_requests_single_data')
                    
                </div>
            </div>
        </div>
        <!-- Tab Pane End -->
        <!-- Tab Pane End -->
        <div class="tab-pane fade" id="add_friend" role="tabpanel" aria-labelledby="add_friend-tab">
            <div class="friends-request my-3 g-2">
                <div id="my-friend-request-list" class="row">

                    @include('frontend.profile.add_friend_data')
                    
                </div>
            </div>
        </div>
        <!-- Tab Pane End -->
        <!-- Tab Pane End -->
        <div class="tab-pane fade" id="block_friend" role="tabpanel" aria-labelledby="block_friend-tab">
            <div class="friends-request my-3 g-2">
                <div id="my-friend-request-list" class="row">

                    @include('frontend.profile.block_friend')
                    
                </div>
            </div>
        </div>
        <!-- Tab Pane End -->
    </div>
    <!-- Tab Content End -->
</div>
<!-- Friends Tab End -->