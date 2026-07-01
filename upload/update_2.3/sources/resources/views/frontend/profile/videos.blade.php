<!-- Profile Nav End -->
<div class="friends-tab v_video_tabs ct-tab radius-8 bg-white p-3">
    <h3>{{get_phrase('Video')}}</h3>
    <div class="photo-list video_list mt-3">
        <h4 class="h6 mb-3">{{get_phrase('Your videos')}}</h4>
        <div class="flex-wrap" id="allVideos">
            @include('frontend.profile.video_single')
        </div>
    </div>

</div> <!-- Friends Tab End -->