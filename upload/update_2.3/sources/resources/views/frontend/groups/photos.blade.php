<div class="profile-cover group-cover ng_profile  bg-white mb-3">
    @include('frontend.groups.cover-photo')
    @include('frontend.groups.iner-nav')
</div>
<div class="group-content profile-content">
    <div class="row gx-3">
        <div class="col-lg-12 col-sm-12">
            {{-- @include('frontend.groups.iner-nav') --}}
            <!-- People Nav End -->
            <div class="people-wrap media_wrap bg-white">
                
                <div class="group-content">
                    <div class="card group-card e_media">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gp_grap">
                                <h3 class="h6 fw-7 m-0">{{ get_phrase('Photos') }}</h3>
                                <div class="gap-2">
                                    <!-- Button trigger modal -->
                                    <a onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.profile.album_create_form','group_id'=>$group->id])}}', '{{get_phrase('Create Album')}}');" data-bs-toggle="modal" data-bs-target="#albumModal"
                                        class="media_text"><i class="fa-solid fa-plus"></i> {{ get_phrase('Create Album') }}
                                    </a>
                                    <a onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.groups.album_image','group_id'=>$group->id])}}', '{{get_phrase('Add Photo To Album')}}');" data-bs-toggle="modal" data-bs-target="#albumCreateModal"
                                        class="media_text"> {{ get_phrase('Add Photo/Album') }}
                                    </a>
                                </div>
                            </div>
                            <ul class="nav ct-tab g_tab" id="myTab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="g-photo-tab" data-bs-toggle="tab"
                                        data-bs-target="#g-photo" type="button" role="tab"
                                        aria-controls="g-photo" aria-selected="true">{{ get_phrase('Photos of you') }}</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="g-video-tab" data-bs-toggle="tab"
                                        data-bs-target="#g-video" type="button" role="tab"
                                        aria-controls="g-video" aria-selected="false">{{ get_phrase('Your videos') }}</button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="g-album-tab" data-bs-toggle="tab"
                                        data-bs-target="#g-album" type="button" role="tab"
                                        aria-controls="g-album" aria-selected="false">{{ get_phrase('Albums') }}</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active sp_control photoGallery" id="g-photo" role="tabpanel"
                                    aria-labelledby="g-photo-tab">
                                            @include('frontend.profile.photo_single')
                                </div>
                                <div class="tab-pane fade g-video" id="g-video" role="tabpanel"
                                    aria-labelledby="g-video-tab">
                                    @include('frontend.profile.video_single')
                                </div><!--  Group Video End -->
                                <div class="tab-pane fade" id="g-album" role="tabpanel"
                                    aria-labelledby="g-album-tab">
                                    <div class="row row_gap" id="group-album-row">
                                        <div class="grid_control">
                                            <div class="col-create-album">
                                                <div class="card album-create-card new_album min-auto">
                                                    <a onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.profile.album_create_form','group_id'=>$group->id])}}', '{{get_phrase('Create Album')}}');" class="create-album">
                                                        <i class="fa-solid fa-plus"></i>
                                                    </a>
                                                    <h4 class="h6">{{get_phrase('Create Album')}}</h4>
                                                </div>
                                            </div> <!-- Card End -->
                                            @include('frontend.profile.album_single')
                                        </div>
                                    </div>
                                </div><!--  Group Album End -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!--  Single Entry End -->
        </div> <!-- COL END -->
        <!--  Group Content Inner Col End -->
        {{-- @include('frontend.groups.bio') --}}
    </div>
</div><!-- Group content End -->
@include('frontend.groups.invite')