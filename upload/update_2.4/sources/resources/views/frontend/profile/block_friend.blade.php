
@php 
   $blockfriend = \App\Models\BlockUser::where('user_id', auth()->user()->id)->get();
@endphp
    
    @foreach($blockfriend as $friend)
    @php 
        $userInfo = \App\Models\User::where('id', $friend->block_user)->first();
    @endphp
    <div class="col-lg-6">
        <div class="single-item-countable d-flex friend-item align-items-center justify-content-between mb-3">
            <div class="n_request_control">
                <div class="d-flex align-items-center w-100">
                    <!-- Avatar -->
                    <div class="avatar">
                        <a href=""><img class="avatar-img rounded-circle user_image_show_on_modal" src="{{get_user_image($userInfo->photo, 'optimized')}}" alt="" height="40" width="40"></a>
                    </div>
                    <div class="avatar-info ms-2">
                        <h6><a href="">{{$userInfo->name}}</a></h6>
                    </div>
                </div>
                <div class="post-controls dropdown dotted">
                    <a class="dropdown-toggle" href="#" id="navbarDropdown"
                        role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="{{route('unblock_user',$friend->id)}}">{{ get_phrase('Unblock') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endforeach
