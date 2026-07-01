<div class="row" id="postMediaSection{{ $post->post_id }}">
    <div class="col-12">
        <div class="photoGallery visibility-hidden @if ($viewData->postMediaFileCount($post) == 1) initialized mt-12 @endif">
            <!-- break after loaded 5 images -->
            @foreach ($viewData->postMediaFiles($post)->take(5) as $key => $media_file)

                @if ($media_file->file_type == 'video')
                    @if (File::exists('public/storage/post/videos/' . $media_file->file_name) || (get_settings('amazon_s3', 'object')->active ?? 0) == 1)
                        @if ($viewData->postMediaFileCount($post) > 1)
                            <a class="position-relative"
                                onclick="showCustomModal('{{ route('preview_post', ['post_id' => $post->post_id, 'file_name' => $media_file->file_name]) }}', '{{ get_phrase('Preview') }}', 'xxl')"
                                href="javascript:void(0)">
                        @endif

                        <video muted controlsList="nodownload"
                            class="plyr-js w-100 rounded video-thumb @if ($viewData->postMediaFileCount($post) > 1) initialized @endif"
                            onplay="pauseOtherVideos(this)">
                            <source src="{{ get_post_video($media_file->file_name) }}" type="">
                        </video>

                        @if ($viewData->moreUnloadedImages($post) > 0 && $key == 4)
                            <div class="more_image_overlap"><span><i class="fa-solid fa-plus"></i>
                                    {{ $viewData->moreUnloadedImages($post) }}</span></div>
                        @endif

                        @if ($viewData->postMediaFileCount($post) > 1)
                            </a>
                        @endif
                    @endif
                @else
                    <div class="picture text-center">
                        <a onclick="showCustomModal('{{ route('preview_post', ['post_id' => $post->post_id, 'file_name' => $media_file->file_name]) }}', '{{ get_phrase('Preview') }}', 'xxl')"
                            href="javascript:void(0)">

                            @if ($viewData->moreUnloadedImages($post) > 0 && $key == 4)
                                <div class="more_image_overlap"><span><i class="fa-solid fa-plus"></i>
                                        {{ $viewData->moreUnloadedImages($post) }}</span></div>
                            @endif
                            @if(!isset($post_albums) )            
                            <img src="{{ get_post_image($media_file->file_name) }}"
                                class="w-100 h-100 @if ($viewData->postMediaFileCount($post) == 1) single-image-ration @endif @if ($viewData->moreUnloadedImages($post) > 0 && $key == 4) opacity-7 @endif"
                                alt="">
                             @endif   
                        </a>
                    </div>
                @endif

            @endforeach
        </div>
    </div>
</div>
