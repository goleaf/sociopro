<div class="profile-cover ">
    <div class="profile-header" style="background-image: url('{{get_group_cover_photo($group->banner,"coverphoto")}}')">
        <div class="cover-btn-group ">
            @if ($group->user_id==auth()->user()->id)
            <button onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.groups.edit-modal', 'group_id' => $group->id])}}', '{{get_phrase('Edit Group')}}');" class="btn  edit-cover " data-bs-toggle="modal"
                data-bs-target="#edit-profile"><i class="fa fa-pen"></i>{{get_phrase('Edit Group')}}</button>
            @endif
            @if ($group->user_id==auth()->user()->id)
                <button onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.groups.edit-cover-photo','group_id'=>$group->id])}}', '{{get_phrase('Update your cover photo')}}');" class="edit-cover btn n_edit"><i class="fa fa-camera"></i>{{get_phrase('Edit Cover Photo')}}</button>
            @endif
        </div>
      
    </div>
    <div class="profile-avatar d-flex align-items-center ml-14">
        {{-- <div class="avatar avatar-xl"><img src="{{ get_group_logo($group->logo, 'logo') }}" class="user-round" alt=""></div> --}}
        <div class="avatar-details n_details">
            <div class="n_ava_details">

                <div class="hero_discus">
                    <h3 class="mb-1">{{ $group->title }}</h3>
                    <div class="img_hero">
                        @php $joined = \App\Models\Group_member::where('group_id',$group->id)->where('is_accepted','1')->count(); @endphp
                       
                        {{-- <div class="single_image">
                            @foreach ($recent_team_member as $recent_team_member)
                            <img src="{{ get_user_image($recent_team_member->getUser->photo,'optimized') }}" alt="">
                            @endforeach
                             <img src="{{asset('assets/frontend/images/album.jpg')}}" alt=""> 
                        </div> --}}
                       
                        
                        <p>{{ $joined }} {{ get_phrase('Member') }}{{ $joined>1?"s":"" }}</p>
                    </div>
                </div>

                <span class="mute d-block text-white">{{ $group->subtitle }}</span>
                <span class="mute d-block text-white">{{ $group->privacy }}</span>
               
            </div>
            <a href="#" data-bs-toggle="modal" data-bs-target="#newGroup" class="btn common_btn invite_btn"><i class="fa-solid fa-plus"></i>{{get_phrase('Invite')}}</a>
        </div>
    </div>
</div>





