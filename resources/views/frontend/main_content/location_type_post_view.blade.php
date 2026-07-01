<div class="text-center">
    <img width="200px" src="{{asset('storage/images/map-pin.jpeg')}}"><br>
    <a class="location-post me-auto ms-auto" href="https://www.google.com/maps/place/{{$post->location}}" target="_blanck">
        <img src="{{asset('storage/images/location.png')}}">
        <span>{{$post->location}}</span>
        <hr>
        <small>{{ $viewData->locationVisitCount($post->location) }} {{ get_phrase('visits') }}</small>
    </a>
</div> 
