<div class="modal-body p-3">
    <div class="group-suggestion e_suggetion mt-3">
        <div class="sugest-wrap">
            @forelse (($eventGuestRows ?? collect()) as $guestRow)
                <div class="single-suggest d-flex justify-content-between align-items-center">
                    <div class="suggest-avatar d-flex justify-content-between align-items-center">
                        <img class="user-round h-39" width="40" src="{{ get_user_image($guestRow['user']->photo, 'optimized') }}" alt="">
                        <h3 class="h6 ms-2"><a href="#"></a>
                            {{ $guestRow['user']->name }}
                        </h3>
                    </div>
                    <span class="btn common_btn_2 py-2 px-4">{{ get_phrase($guestRow['status']) }}</span>
                </div>
            @empty
            @endforelse
        </div>
    </div>
</div>
@include('frontend.initialize')
