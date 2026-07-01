

<div class="profile-wrap">
    @include('frontend.pages.timeline-header')
    <div class="profile-content mt-3">
        <div class="row gx-3">
            <div class="col-lg-12 col-sm-12">
                {{-- @include('frontend.pages.inner-nav') --}}

                
                <div class="friends-tab pg_tab_main ct-tab bg-white radius-8">
                    <div class="d-flex align-items-center justify-content-between gp_grap">
                        <h3 class="h6 fw-7 m-0">{{get_phrase('Photos')}}</h3>
                        <div class="gap_m e_media d-flex align-items-center justify-content-end gap-2">
                                <a onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.profile.album_create_form','page_id'=>$page->id])}}', '{{get_phrase('Create Album')}}');" data-bs-toggle="modal" data-bs-target="#albumModal"
                                    class="btn media_text">
                                    <i class="fa fa-circle-plus"></i> {{ get_phrase('Create Album') }}
                                </a>
                                <a onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.groups.album_image','page_id'=>$page->id])}}', '{{get_phrase('Add Photo To Album')}}');" data-bs-toggle="modal" data-bs-target="#albumCreateModal"
                                    class="btn media_text"> {{ get_phrase('Add Photo/Album') }}
                                </a>
                        </div>
                   </div>
                    <div class="d-flex mt-12 justify-content-between align-items-center">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="profile-photo-tab"
                                    data-bs-toggle="tab" data-bs-target="#profile-photo"
                                    type="button" role="tab" aria-controls="profile-photo"
                                    aria-selected="true">{{ get_phrase('Your Photos') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="profile-album-tab" data-bs-toggle="tab"
                                    data-bs-target="#profile-album" type="button" role="tab"
                                    aria-controls="profile-album"
                                    aria-selected="false">{{ get_phrase('Album') }}</button>
                            </li>
                        </ul>
                    </div>
                     
                    <div class="tab-content pg_tabs_con" id="myTabContent">
                        <div class="tab-pane fade show active" id="profile-photo" role="tabpanel"
                            aria-labelledby="profile-photo-tab">
                            
                            <div class="photo-list mt-12 photoGallery">
                                @include('frontend.profile.photo_single')
                            </div>
                        </div> <!-- Tab Pane End -->
                        
                        <div class="tab-pane fade" id="profile-album" role="tabpanel" aria-labelledby="profile-tab">
                            
                            <div class="friends-request my-3 g-2">
                                <div class="row  row_gap" id="page-album-row">
                                    <div class="grid_control">
                                        <div class="col-create-album  ">
                                            <div class="card album-create-card new_album min-auto">
                                                <a onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.profile.album_create_form','page_id'=>$page->id])}}', '{{get_phrase('Create Album')}}');" class="create-album"><i class="fa-solid fa-plus"></i>
                                                </a>
                                                <h4 class="h6">{{get_phrase('Create Album')}}</h4>
                                            </div>
                                        </div> <!-- Card End -->
                                        @include('frontend.profile.album_single')
                                    </div>
                                </div>
                            </div>
                        </div><!-- Tab Pane End -->
                    </div> <!-- Tab Content End -->
                </div> <!-- Friends Tab End -->
            </div> <!-- COL END -->
            {{-- <div class="col-lg-5 col-sm-12">
                @include('frontend.pages.bio')
            </div> --}}
        </div>
    </div> <!-- Profile content End -->
</div>


