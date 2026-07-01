<div class="album_details_show">
    <a href="{{ url()->previous() }}" class="x_mark"><i class="fa-solid fa-xmark"></i></a>
    <div class="row">
        <div class="col-lg-7">
            <div class="post-image-wrap position-sticky top-30px">
                <div class="piv-gallerys">
                    {{-- @foreach($posts as $post) --}}
    
                         @php
                         $media_files = DB::table('media_files')->where('post_id', $posts->post_id)->get();
                         @endphp         
                        @foreach($media_files as $media_file)
                            <div class="piv-item video-player">
                                <img class="ms-auto me-auto img-fluid rounded" src="{{ get_post_image($media_file->file_name) }}" alt="">
                            </div>
                        @endforeach
                    {{-- @endforeach --}}
                </div>
            </div>
        </div>
        {{-- <div class="col-lg-6">
            <div class="single-entry" id="postPreviewSection">
                @include('frontend.main_content.posts',['type'=>'user_post'])
            </div>
        </div> --}}
    </div>
</div>

{{-- <script type="text/javascript">
	"Use strict";

	$('#postMediaSection{{$post->post_id}}').hide();
	var postView = $('#postIdentification{{$post->post_id}}').html();
	$('#postPreviewSection').html(postView);
	$('#postIdentification{{$post->post_id}}').html('');

	function restorePostView(){
		var postView = $('#postPreviewSection').html();
		console.log(postView)
		$('#postIdentification{{$post->post_id}}').html(postView);
		$('#postPreviewSection').html('');
		$('#postMediaSection{{$post->post_id}}').show();
	}

	$('.piv-gallery').owlCarousel({
		loop: false,
		margin: 10,
		dots: false,
		nav: true,
		items: 1,
	});
</script> --}}

@include('frontend.initialize')