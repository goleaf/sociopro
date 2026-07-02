<div class="single-contact d-flex align-items-center justify-content-between">
    <div class="avatar d-flex align-items-center">
        <a href="{{ $chatUrl }}" class="d-flex align-items-center">
            <div class="avatar me-3">
                <img src="{{ $imageUrl }}" class="img-fluid rounded-circle h-39" alt="">
            </div>
        </a>
        <div class="avatar-info">
            <a href="{{ $chatUrl }}"><h3 class="h6 mb-0">{{ $userName }}</h3></a>
            <span>
                @if ($isThumbsup)
                    <i class="fa-solid fa-thumbs-up"></i>
                @else
                    {{ $lastMessage }}
                @endif
            </span>
        </div>
    </div>
    <div class="m-user-action">
        <div class="post-controls dropdown dotted">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown"
                role="button" data-bs-toggle="dropdown" aria-expanded="false">
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="#"><i class="fa fa-user"></i>{{ $viewProfileText }}</a></li>
            </ul>
        </div>
    </div>
</div>
