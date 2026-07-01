@foreach($all_photos as $photo)
   <a href="{{ route('single.post',$photo->post_id) }}">
    <div class="user_profil single-item-countable single-photo">
        <img src="{{get_post_image($photo->file_name)}}" alt="">
    </div>
   </a>
@endforeach
