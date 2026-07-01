<style>
    .ellipsis-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .ellipsis-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .url-content {
        width: 100%;
        height: 180px;
        background: #fff;
        border-radius: 8px !important;
    }

    .url-content img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        object-position: center;
    }

    .url-content .card {
        border-radius: 8px !important;
        overflow: hidden;

    }
</style>

@php
    $meta = get_url_contents($url);
    $serverName = parse_url(URL::to($url), PHP_URL_HOST);
@endphp

@if ($meta && count($meta) > 0)
    <a href="{{ $url }}" class="d-block mt-3 mb-2 url-content" target="_blank">
        <div class="card mb-3">
            <div class="row g-0 align-items-center">
                <div class="col-md-4">
                    <div class="url-content">
                        <img src="@if ($meta['image'] != '') {{ $meta['image'] }} @else {{ asset('storage/post/images/default.jpg') }} @endif"
                            class="img-fluid rounded-start" alt="">
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title ellipsis-2">{{ $meta['title'] }}</h5>
                        <p class="card-text text-muted ellipsis-3">{{ $meta['description'] }}</p>
                        <p class="card-text text-muted">
                            <small class="text-body-secondary">{{ strtoupper($serverName) }}</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </a>
@endif
