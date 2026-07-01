
{{-- <div class="row d-flex flex-wrap"> --}}
    @foreach($all_photos as $photo)
    {{-- <div class="col-lg-3 col-md-4 col-sm-6"> --}}
        <div class="single-item-countable single-photo cursor-pointer n_video"  >
            <img  src="{{get_post_image($photo->file_name)}}" onclick="$(location).prop('href', '{{ route('single.post', $photo->post_id) }}')" alt="">
            
            <a class=" del_v_icon" href="javascript:void(0)" onclick="confirmAction('<?php echo route('delete.mediafile', $photo->id); ?>', true)"><i class="fas fa-trash"></i></a>
        </div>
    {{-- </div> --}}
@endforeach
{{-- </div> --}}



