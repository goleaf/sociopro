<aside class="sidebar page_side">
    <section class="widget intro-widget" aria-labelledby="page-intro-heading">
        <h4 id="page-intro-heading">{{ get_phrase('Intro') }}</h4>

        <div class="my-about mb-3">
            {{ $pageIntro ?? '' }}
        </div>
    </section>

    <section class="widget" aria-labelledby="page-info-heading">
        <h4 id="page-info-heading" class="widget-title mb-4">{{ get_phrase('Info') }}</h4>
        <ul>
            <li>
                <i class="fa fa-thumbs-up" aria-hidden="true"></i>
                <span>
                    {{ $page->liked_by_users_count ?? 0 }}
                    {{ get_phrase(($page->liked_by_users_count ?? 0) === 1 ? 'Person likes this' : 'People like this') }}
                </span>
            </li>
            <li>
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                <span>{{ $page->posts_count ?? 0 }} {{ get_phrase('Posts') }}</span>
            </li>
            <li>
                <i class="fa-solid fa-briefcase" aria-hidden="true"></i>
                <span>{{ $page->job }}</span>
            </li>
            <li>
                <i class="fa-solid fa-location" aria-hidden="true"></i>
                <span>{{ $page->location }}</span>
            </li>
            <li>
                <i class="fa-solid fa-tags" aria-hidden="true"></i>
                <span>{{ $page->lifestyle }}</span>
            </li>
        </ul>
        @if ($page->user_id == auth()->id())
            <button
                type="button"
                class="btn common_btn w-100 mt-8"
                onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.pages.edit-page-info', 'page_id' => $page->id]) }}', '{{ get_phrase('Update Page Info') }}');"
            >
                {{ get_phrase('Edit Info') }}
            </button>
        @endif
    </section>

    <section class="widget" aria-labelledby="suggested-pages-heading">
        <div class="d-flex pagetab-head align-items-center">
            <span><i class="fa-solid fa-flag" aria-hidden="true"></i></span>
            <h3 id="suggested-pages-heading" class="widget-title ms-1">{{ get_phrase('Page you may like') }}</h3>
        </div>

        @forelse ($suggestedpages as $suggestedpage)
            <article class="card n_like_image border-0 mt-3">
                <img
                    class="cov_img"
                    src="{{ get_page_cover_photo($suggestedpage->coverphoto, 'coverphoto') }}"
                    alt=""
                >
                <div class="d-flex align-items-center my-2">
                    <a href="{{ route('single.page', $suggestedpage->id) }}">
                        <img
                            class="circle me-2"
                            src="{{ get_page_logo($suggestedpage->logo, 'logo') }}"
                            width="60"
                            alt="{{ $suggestedpage->title }} {{ get_phrase('logo') }}"
                        >
                    </a>
                    <div class="ava-info">
                        <h3 class="h6 mb-0">
                            <a href="{{ route('single.page', $suggestedpage->id) }}">{{ $suggestedpage->title }}</a>
                        </h3>
                        <span class="mute small">
                            {{ $suggestedpage->liked_by_users_count ?? 0 }} {{ get_phrase('likes') }}
                        </span>
                    </div>
                </div>
                @if ($suggestedpage->liked_by_current_user)
                    <button
                        type="button"
                        onclick="ajaxAction('{{ route('page.dislike', $suggestedpage->id) }}')"
                        class="btn btn-primary"
                    >
                        <img class="me-1" src="{{ asset('assets/frontend/images/like-i.png') }}" alt="">
                        {{ get_phrase('Liked') }}
                    </button>
                @else
                    <button
                        type="button"
                        onclick="ajaxAction('{{ route('page.like', $suggestedpage->id) }}')"
                        class="btn btn-primary"
                    >
                        <img class="me-1" src="{{ asset('assets/frontend/images/like-i.png') }}" alt="">
                        {{ get_phrase('Like') }}
                    </button>
                @endif
            </article>
        @empty
        @endforelse
    </section>

    <section class="widget" aria-labelledby="page-media-heading">
        <div class="n_pro_con d-flex align-items-start">
            <h4 id="page-media-heading" class="widget-title">{{ get_phrase('Photo/Video') }}</h4>
            <a href="{{ route('single.page.photos', $page->id) }}">{{ get_phrase('See All') }}</a>
        </div>

        <div class="row row-cols-3 row-cols-md-5 row-cols-lg-2 row-cols-xl-3 g-1 mt-8">
            @foreach ($all_photos as $media_file)
                @if ($media_file->file_type == 'video')
                    <div class="single-item-countable col">
                        <a href="{{ route('single.post', $media_file->post_id) }}">
                            <video muted controlsList="nodownload" class="img-thumbnail w-100 user_info_custom_height">
                                <source src="{{ get_post_video($media_file->file_name) }}" type="">
                            </video>
                        </a>
                    </div>
                @else
                    <div class="single-item-countable col">
                        <a href="{{ route('single.post', $media_file->post_id) }}">
                            <img
                                class="img-thumbnail w-100 user_info_custom_height"
                                src="{{ get_post_image($media_file->file_name, 'optimized') }}"
                                alt=""
                            >
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
</aside>
