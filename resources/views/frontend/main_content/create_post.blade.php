<div class="newsfeed-form single-entry">
    <div class="entry-inner current-entry">

        <div class="create-entry mb-0">
            @if (isset($page_id) && !empty($page_id))
                @php
                    $page = \App\Models\Page::find($page_id);
                @endphp
                <a href="{{ route('single.page', $page_id) }}" class="author-thumb d-flex align-items-center">
                    <img src="{{ get_page_logo($page->logo, 'logo') }}" width="40px" height="40px" class="rounded-circle"
                        alt="">
                </a>
            @else
                <a href="{{ route('profile') }}" class="author-thumb d-flex align-items-center">
                    <img src="{{ get_user_image($user_info->photo, 'optimized') }}" width="40px" height="40px"
                        class="rounded-circle" alt="">
                </a>
            @endif
            <button class="btn-trans" data-bs-toggle="modal" data-bs-target="#createPost" onclick="$('#createPost').modal('show');">
                {{ get_phrase("What's on your mind ____", [auth()->user()->name]) }}?
            </button>

            @if (isset($page_id) && !empty($page_id))
                @include('frontend.main_content.create_post_modal', ['page_id' => $page_id])
            @elseif (isset($group_id) && !empty($group_id))
                @include('frontend.main_content.create_post_modal', ['group_id' => $group_id])
            @elseif (isset($paid_content_id) && !empty($paid_content_id))
                @include('frontend.main_content.create_post_modal', [
                    'paid_content_id' => $paid_content_id,
                ])
            @else
                @include('frontend.main_content.create_post_modal')
            @endif
       
        </div>
        @if (Route::currentRouteName() == 'timeline' ||Route::currentRouteName() == 'profile' || Route::currentRouteName() == 'single.group')
            <div class="post-options justify-content-center">
                <button class="btn" data-bs-toggle="modal" data-bs-target="#createPost" onclick="$('#createPost').modal('show');"><img
                        src="{{ asset('storage/images/image.svg') }}"
                        alt="photo"></button>
                <button class="btn" data-bs-toggle="modal" data-bs-target="#createPost" onclick="$('#createPost').modal('show');"><img
                        src="{{ asset('storage/images/location.png') }}" alt="photo"
                        alt="photo"></button>
                <button class="btn" data-bs-toggle="modal" data-bs-target="#createPost" onclick="$('#createPost').modal('show');"><img
                        src="{{ asset('storage/images/camera.svg') }}"
                        alt="photo"></button>
                <button class="btn" data-bs-toggle="modal" data-bs-target="#createPost" onclick="$('#createPost').modal('show');"><img
                        src="{{ asset('storage/images/plus-circle-fill.svg') }}"
                        alt="photo"></button>
            </div>
       @endif

    </div>
</div>
