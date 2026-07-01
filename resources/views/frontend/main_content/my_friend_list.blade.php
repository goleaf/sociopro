@foreach ($viewData->shareFriendRows(auth()->user()) as $row)
    <div class="d-flex justify-content-between align-items-center e_friend">
        <div class="user-information d-flex">
            <img src="{{ get_user_image($row['user']->photo, 'optimized') }}"
                class="rounded-circle user_image_show_on_modal" alt="">
            <h6 class="align-self-center mx-3">{{ $row['user']->name }}</h6>
        </div>
        <form class="ajaxForm" id="chatMessageFieldForm" action="{{ route('chat.save') }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="reciver_id" value="{{ $row['user']->id }}" id="">
            <input type="hidden" name="thumbsup" value="0" id="">
            @if (isset($post_id) && !empty($post_id))
                <input type="hidden" name="message" value="{{ route('single.post', $post_id) }}">
                <input type="hidden" name="shared_post_id" value="{{ $post_id }}">
            @endif
            @if (isset($product_id) && !empty($product_id))
                <input type="hidden" name="productUrl" value="{{ route('single.product', $product_id) }}">
                <input type="hidden" name="shared_product_id" value="{{ $product_id }}">
            @endif
            <div class="message-send-area">
                <button type="submit" class="btn common_btn send">{{ get_phrase('Send') }}</button>
            </div>
        </form>
    </div>
@endforeach
