@foreach($all_albums as $album) 
@php
if(isset($page_identifire)) {
    $identifires = $page_identifire; 
}
@endphp
    <div class="single-item-countable grid_single" id="photoAlbum{{$album->id}}">
        <div class="card new_album album-card" >
            <div class="mb-2 position-relative">
                <a href="{{route('album.details.list', ['identifire' => $identifires,'album_id' => $album->id])}}" class="mb-0" ><img src="{{get_album_thumbnail($album->id, 'optimized')}}" class="rounded img-fluid " alt=""></a>
                <div class="post-controls dropdown dotted">
                    <a class="nav-link dropdown-toggle" href="#"
                        id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    </a>
                    <ul class="dropdown-menu"
                        aria-labelledby="navbarDropdown">
                        <li>
                        <a href="{{route('album.details.list', ['identifire' => $identifires,'album_id' => $album->id, ])}}" class="dropdown-item"> {{ get_phrase('View Album') }}
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="dropdown-item" onclick="confirmAction('{{route('profile.album', ['action_type' => 'delete', 'album_id' => $album->id])}}', true);"  > {{ get_phrase('Delete Album') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-details">
                <h6><a href="{{route('album.details.list', ['identifire' => $identifires,'album_id' => $album->id])}}"  class="mb-0">{{$album->title}}</a></h6>
                <span class="mute">{{DB::table('album_images')->where('album_id', $album->id)->get()->count()}} {{get_phrase('Items')}}</span>
            </div>
        </div>
    </div> <!-- Card End -->
@endforeach
