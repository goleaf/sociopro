@foreach($all_albums as $album)
@php
if(isset($page_identifire)) {
    $identifires = $page_identifire; 
}
@endphp
    <div class="single-item-countable col-3 mt-2">
        <div class="card album-card new_album album-card">
            <div class="mb-2 position-relative">
                <a href="{{route('album.details.list', ['album_id' => $album->id ,'identifire' => $identifires])}}"><img src="{{get_album_thumbnail($album->id, 'optimized')}}" class="rounded img-fluid" alt=""></a>
            </div>
            <div class="card-details">
                <h6><a href="{{route('album.details.list', ['album_id' => $album->id , 'identifire' => $identifires,])}}">{{$album->title}}</a></h6>
                <span class="mute">{{DB::table('album_images')->where('album_id', $album->id)->get()->count()}} {{get_phrase('Items')}}</span>
            </div>
        </div>
    </div> <!-- Card End -->
@endforeach
