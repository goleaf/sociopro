<div class="row ">
    <div class="col-12 text-center">
        {{-- <span class="live-icon">
            <i class="fa fa-dot-circle"></i>
            {{ get_phrase('LIVE') }}
        </span> --}}
        <img class="live-image" src="{{ asset('storage/images/live.png') }}">
    </div>
    <div class="col-12 text-center mt-3">
        <a class="live-watch-now" href="{{ route('go.live', $post->post_id) }}"><i class="fa fa-video"></i>
            {{ get_phrase('Join now') }}</a>

    </div>
</div>

