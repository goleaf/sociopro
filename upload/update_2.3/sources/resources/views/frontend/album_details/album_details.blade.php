

<div class="album_details_show">
    <a href="{{ url()->previous() }}" class="x_mark"><i class="fa-solid fa-xmark"></i></a>
    <div class="row">
        <div class="col-lg-7">
            <div class="post-image-wrap position-sticky top-30px">
                <div class="piv-gallerys">
                    
                    @php 
                     $post_image_id = DB::table('album_images')->where('id', $post_album->album_image_id)->first();
                     $post_album_id = DB::table('albums')->where('id', $post_image_id->album_id)->first();
                     $count_album_id = DB::table('album_images')->where('album_id', $post_image_id->album_id)->get();
                     $album_ids = DB::table('album_images')
                        ->where('album_id', $post_image_id->album_id)
                        ->get()
                        ->toArray();
                    $album_ids_array = array_column($album_ids, 'id');
                    $active_index = array_search($post_image_id->id, $album_ids_array);
                    if($active_index <= count($album_ids_array)){
                        $keys = array_keys($album_ids_array);
                        $last_index = end($keys);

                        if ($active_index == $last_index) {
                            $next_index = $last_index;
                        } else {
                            $next_index = $active_index + 1;
                        }

                        $next_id = $album_ids_array[$next_index];

                    }
                    if($active_index <= count($album_ids_array)){
                        if($active_index == 0){
                            $previous_index = 0;
                        }else{
                            $previous_index = $active_index - 1;
                        }
                        $previous_id = $album_ids_array[$previous_index];
                    }
                    $previous_post_id = DB::table('posts')->where('album_image_id', $previous_id)->first()->post_id;
                    $next_post_id = DB::table('posts')->where('album_image_id', $next_id)->first()->post_id;
                  
                    @endphp
                   
                    @foreach($posts as $post)
                        @if($previous_index != $active_index)
                         <a href="{{ route('album.details.page_show', ['id' => $previous_post_id]) }}" class="left common_arrow"><i class="fas fa-chevron-left"></i></a>
                        @endif
                        @if($next_index != $active_index)
                        <a href="{{ route('album.details.page_show', ['id' => $next_post_id]) }}" class="right common_arrow"><i class="fas fa-chevron-right"></i></a>
                        @endif

                         @php
                         $media_files = DB::table('media_files')->where('post_id', $post->post_id)->get();
                         @endphp         
                        @foreach($media_files as $media_file)
                            <div class="piv-item video-player">
                                <img class="ms-auto me-auto img-fluid rounded" src="{{ get_post_image($media_file->file_name) }}" alt="">
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="single-entry" id="postPreviewSection">
                @include('frontend.main_content.posts',['type'=>'user_post' , 'post_albums' =>'true'])
            </div>
        </div>
    </div>
</div>

@include('frontend.initialize')
