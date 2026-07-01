@foreach ($users as $user )
    @if (! $viewData->isGroupInviteSent($user, $group_id))
        <div class="single-suggest nSuggest d-flex justify-content-between align-items-center" onclick="inviteGroupPeople('{{$user->id}}', '{{$user->name}}')">
            <div class="suggest-avatar d-flex justify-content-between align-items-center">
                <img src="{{ get_user_image($user->photo,'optimized') }}" class="img-fluid rounded-circle user_image_show_on_modal" width="45" alt="Avatar">
                <h3 class="h6 ms-2">{{ $user->name }}</h3>
            </div>
            <button class="btn common_btn" type="button"><i class="fa fa-plus"></i></button>
        </div> 
    @endif
@endforeach

                                                   
