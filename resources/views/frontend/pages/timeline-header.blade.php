<div class="profile-cover np_profile_cover bg-white">
    <div class="profile-header" style="background-image: url('{{ get_page_cover_photo($page->coverphoto, 'coverphoto') }}')">
        <div class="cover-btn-group">
            @if ($page->user_id == auth()->id())
                <button
                    type="button"
                    onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.pages.edit-modal', 'page_id' => $page->id]) }}', '{{ get_phrase('Edit Page') }}');"
                    class="edit-cover btn"
                    data-bs-toggle="modal"
                    data-bs-target="#edit-profile"
                >
                    <i class="fa fa-pen" aria-hidden="true"></i>{{ get_phrase('Edit Profile') }}
                </button>
                <button
                    type="button"
                    onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.pages.edit-cover-photo', 'page_id' => $page->id]) }}', '{{ get_phrase('Update your cover photo') }}');"
                    class="edit-cover btn n_edit"
                >
                    <i class="fa fa-camera" aria-hidden="true"></i>{{ get_phrase('Edit Cover Photo') }}
                </button>
            @endif
        </div>
    </div>
    <div class="n_profile_tab np_page_tab">
        <div class="n_main_tab">
            <div class="profile-avatar d-flex align-items-center">
                <div class="avatar avatar-xl">
                    <img
                        src="{{ get_page_logo($page->logo, 'logo') }}"
                        class="rounded-circle"
                        alt="{{ $page->title }} {{ get_phrase('logo') }}"
                    >
                </div>
                <div class="avatar-details">
                    <h3 class="mb-1 n_font">{{ $page->title }}</h3>
                    <span class="mute d-block text-white">{{ $page->getCategory?->name }}</span>
                </div>
            </div>
            <div class="n_tab_right d-flex">
                <div class="inline-btn">
                    @if ($page->liked_by_current_user)
                        <button
                            type="button"
                            onclick="ajaxAction('{{ route('page.dislike', $page->id) }}')"
                            class="btn common_btn_3"
                        >
                            <img class="mb-1 me-1" src="{{ asset('assets/frontend/images/like-i.png') }}" alt="">
                            <span class="d-sm-inline-block d-xl-inline-block">{{ get_phrase('Liked') }}</span>
                        </button>
                    @else
                        <button
                            type="button"
                            onclick="ajaxAction('{{ route('page.like', $page->id) }}')"
                            class="btn common_btn"
                        >
                            <i class="me-1 fa-regular fa-thumbs-up" aria-hidden="true"></i>
                            <span class="d-sm-inline-block d-xl-inline-block">{{ get_phrase('Like') }}</span>
                        </button>
                    @endif
                    <a class="btn common_btn" href="{{ route('pages') }}">
                        <img src="{{ asset('assets/frontend/images/page.svg') }}" class="w-20 height-20-css" alt="">
                        <span class="d-sm-inline-block d-xl-inline-block">{{ get_phrase('Pages') }}</span>
                    </a>
                </div>
            </div>
        </div>
        @include('frontend.pages.inner-nav')
    </div>
</div>
