@php
    if(isset($identifire) && $identifire == 'fundraiser'){
        $post_route = route('fundraiser.share.my.timeline');
    }else{
        $post_route = route('share.my.timeline');
    };
  
@endphp
<div class="social-share">
    <ul class="site-share text-center my-3">
        @foreach (Share::currentPage()->facebook()->twitter()->linkedin()->telegram()->getRawLinks(); as $key => $value )
            <li><a href="{{ $value }}" target="_blank" class="only_for_share_page"><i class="fa-brands fa-{{ $key }}"></i></a></li>
        @endforeach
    </ul>
</div>
<div class="footer-modal-share">
    <h4 class="h6 fw-6 mb-3"> {{ get_phrase('Share the post on') }}</h4>
    <div class="inner-share d-flex" id="myTab" role="tablist">
        <button class="btn btn-secondary btn-sm px-3" id="timelinePostBtn"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <mask id="mask0_881_1578" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24">
            <rect width="24" height="24" fill="#D9D9D9"/>
            </mask>
            <g mask="url(#mask0_881_1578)">
            <path d="M3 17.75C2.5141 17.75 2.10097 17.5798 1.7606 17.2394C1.4202 16.899 1.25 16.4859 1.25 16C1.25 15.5141 1.42019 15.1009 1.76057 14.7606C2.10096 14.4202 2.5141 14.25 3 14.25C3.11282 14.25 3.21474 14.2548 3.30578 14.2644C3.39681 14.274 3.48719 14.3051 3.57693 14.3577L8.35772 9.5769C8.30516 9.48717 8.27407 9.39678 8.26445 9.30575C8.25483 9.21472 8.25003 9.11279 8.25003 8.99998C8.25003 8.51408 8.42022 8.10094 8.7606 7.76058C9.10097 7.42019 9.5141 7.25 10 7.25C10.4859 7.25 10.899 7.42019 11.2394 7.76058C11.5798 8.10094 11.75 8.51408 11.75 8.99998C11.75 9.05896 11.7205 9.24486 11.6615 9.55768L14.4423 12.3385C14.5321 12.2987 14.6192 12.274 14.7038 12.2644C14.7885 12.2548 14.8872 12.25 15 12.25C15.1128 12.25 15.2131 12.2548 15.301 12.2644C15.3888 12.274 15.4744 12.3051 15.5577 12.3577L19.3577 8.55768C19.3052 8.47434 19.2741 8.38877 19.2645 8.30095C19.2548 8.21312 19.25 8.11279 19.25 7.99998C19.25 7.51408 19.4202 7.10094 19.7606 6.76058C20.101 6.42019 20.5141 6.25 21 6.25C21.4859 6.25 21.899 6.42019 22.2394 6.76058C22.5798 7.10094 22.75 7.51408 22.75 7.99998C22.75 8.48588 22.5798 8.89901 22.2394 9.23938C21.899 9.57976 21.4859 9.74995 21 9.74995C20.8872 9.74995 20.7869 9.74514 20.699 9.73553C20.6112 9.72591 20.5256 9.69482 20.4423 9.64225L16.6423 13.4423C16.6948 13.5256 16.7259 13.6112 16.7355 13.699C16.7452 13.7868 16.75 13.8872 16.75 14C16.75 14.4859 16.5798 14.899 16.2394 15.2394C15.899 15.5798 15.4859 15.75 15 15.75C14.5141 15.75 14.101 15.5798 13.7606 15.2394C13.4202 14.899 13.25 14.4859 13.25 14C13.25 13.8872 13.2548 13.7852 13.2645 13.6942C13.2741 13.6032 13.3052 13.5128 13.3577 13.4231L10.5769 10.6423C10.4872 10.6948 10.3968 10.7259 10.3058 10.7355C10.2147 10.7451 10.1128 10.75 10 10.75C9.94102 10.75 9.75512 10.7205 9.4423 10.6615L4.66153 15.4423C4.70126 15.532 4.72593 15.6192 4.73555 15.7038C4.74517 15.7884 4.74997 15.8872 4.74997 16C4.74997 16.4859 4.57978 16.899 4.2394 17.2394C3.89903 17.5798 3.4859 17.75 3 17.75Z" fill="#1C1B1F"/>
            </g>
            </svg> {{ get_phrase('My Timeline') }}</button>

        <button class="btn btn-secondary btn-sm px-3 mx-2" id="messageSendButton"><i class="fa-regular fa-message"></i> {{ get_phrase('Send in Message') }}</button>
        <button class="btn btn-secondary btn-sm px-3" id="groupPostButton"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <mask id="mask0_881_1584" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="24">
            <rect width="24" height="24" fill="#D9D9D9"/>
            </mask>
            <g mask="url(#mask0_881_1584)">
            <path d="M8.05765 16H19.4422C19.5191 16 19.5897 15.9679 19.6538 15.9038C19.7179 15.8397 19.7499 15.7692 19.7499 15.6923V5.99998H7.74993V15.6923C7.74993 15.7692 7.78198 15.8397 7.8461 15.9038C7.9102 15.9679 7.98072 16 8.05765 16ZM8.05765 17.5C7.55252 17.5 7.12496 17.325 6.77498 16.975C6.42498 16.625 6.24998 16.1974 6.24998 15.6923V4.3077C6.24998 3.80257 6.42498 3.375 6.77498 3.025C7.12496 2.675 7.55252 2.5 8.05765 2.5H19.4422C19.9473 2.5 20.3749 2.675 20.7249 3.025C21.0749 3.375 21.2499 3.80257 21.2499 4.3077V15.6923C21.2499 16.1974 21.0749 16.625 20.7249 16.975C20.3749 17.325 19.9473 17.5 19.4422 17.5H8.05765ZM4.55768 20.9999C4.05256 20.9999 3.625 20.8249 3.275 20.4749C2.925 20.1249 2.75 19.6973 2.75 19.1922V7.05768C2.75 6.84486 2.82179 6.66666 2.96537 6.52308C3.10897 6.37949 3.28718 6.3077 3.49998 6.3077C3.71279 6.3077 3.891 6.37949 4.0346 6.52308C4.17818 6.66666 4.24998 6.84486 4.24998 7.05768V19.1922C4.24998 19.2692 4.28203 19.3397 4.34613 19.4038C4.41024 19.4679 4.48076 19.5 4.55768 19.5H16.6922C16.905 19.5 17.0832 19.5717 17.2268 19.7153C17.3704 19.8589 17.4422 20.0371 17.4422 20.2499C17.4422 20.4627 17.3704 20.6409 17.2268 20.7845C17.0832 20.9281 16.905 20.9999 16.6922 20.9999H4.55768Z" fill="#1C1B1F"/>
            </g>
            </svg>{{ get_phrase('Share to a Group') }}
        </button>
    </div>
        <div class="time-line-area d-none" id="timeline-content-area">
            <input type="hidden" name="istimeline" value="1">
        </div>
        <div class="message-area mt-3 d-none" id="message-content-area">
            <h5 class="my-3">{{ get_phrase('Friends')}}</h5>
            @include('frontend.main_content.my_friend_list')
        </div>
        <div class="group-area mt-3 d-none" id="group-content-area">
            <h5 class="my-3">{{ get_phrase('Groups')}}</h5>
            @include('frontend.main_content.my_group_list')
        </div>
    <form class="ajaxForm" action="{{ $post_route }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($post_id)&&!empty($post_id))
                <input type="hidden" name="postUrl" value="{{ route('single.post',$post_id) }}">
                <input type="hidden" name="shared_post_id" value="{{$post_id }}">
                @if (isset($is_memory))
                    <input type="hidden" name="is_memory" value="{{ $post_id }}">
                @endif
            @endif
            @if(isset($product_id)&&!empty($product_id))
                <input type="hidden" name="productUrl" value="{{ route('single.product.iframe',$product_id) }}">
                <input type="hidden" name="shared_product_id" value="{{$product_id }}">
            @endif
        <button type="submit" class="btn common_btn mt-3 rounded w-100 btn-lg" id="ShareButton">{{ get_phrase('Share')}}</button>
    </form>
</div>


@include('frontend.main_content.scripts')
@include('frontend.initialize')
@include('frontend.common_scripts')

