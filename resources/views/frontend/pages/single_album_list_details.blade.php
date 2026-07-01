@php
    $album_data = \App\Models\Albums::where('id', $album_id)->first();
    $images = \App\Models\Album_image::where('album_id', $album_id)->get();
@endphp

<!-- Profile Nav End -->
<div class="friends-tab ct-tab bg-white radius-8 p-3">
    <div class="row">
        <div class="al_head d-flex justify-content-between">
            <div class="search_left"> 
                <h3>{{ $album_data->title }}</h3>
                <p>{{ count($images) }} {{ get_phrase('Items') }}</p>
            </div>
            <a href="{{ url()->previous() }}" class="btn back_btns common_btn">
                <i class="fa-solid fa-left-long me-2"></i>{{ get_phrase('Back') }}
            </a>
        </div>
        @foreach($images as $image)
            @php
                $image_name = \App\Models\Media_files::where('album_image_id', $image->id)->first();
                $image_post =  \App\Models\Posts::where('post_id', $image_name->post_id)->first();
            @endphp
            <div class="col-lg-3 col-md-4 col-6 mb-2">
                <div class="card sugg-card p-0 box_shadow border-none al_details  suggest_p radius-8">
                    <div>
                        <img class="thumbnail-110-106 w-100" onclick="$(location).prop('href', '{{ route('single.post', ['id' => $image_post->post_id]) }}')" src="{{ asset('storage/album/images/'.$image->image) }}" alt="">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!-- Tab Content End -->
</div>
<!-- Friends Tab End -->
