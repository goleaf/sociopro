{{-- <div class="col-lg-5">
    <aside class="sidebar group-sidebar plain-sidebar"> --}}
        <div class="widget intro-widget">
            @if ($viewData->userJoinedGroup($group, auth()->user()))
                @if ($group->user_id==auth()->user()->id)
                <a href="javascript:void(0)" class="btn common_btn me-2 my-1 w-100"><i  class="fa-solid fa-users me-2"></i> {{ get_phrase('Joined') }}</a>
                @else
                <a href="javascript:void(0)" onclick="ajaxAction('{{ route('group.rjoin',$group->id) }}')" class="btn common_btn_2 me-2 my-1 w-100"><i
                    class="fa-solid fa-users me-2"></i>{{ get_phrase('Joined') }}</a>
                @endif
            @else
            <a href="javascript:void(0)" onclick="ajaxAction('{{ route('group.join',$group->id) }}')" class="btn common_btn my-1 w-100"><i
                class="fa-solid fa-users me-2"></i>{{ get_phrase('Join') }}</a>
            @endif
            {{-- <a data-bs-toggle="modal" data-bs-target="#newGroup" href="#" class="btn btn-primary my-1"><i class="fa fa-circle-plus"></i> {{ get_phrase('Invite') }}</a> --}}

            
            <h3 class="widget-title mt-3">{{ get_phrase('About') }}</h3>
            <div class="intro_t">
                <p>{!! script_checker($group->about, false) !!}</p>
            </div>
        </div>

        <div class="widget gw-info">
            <h3 class="widget-title mb-8">{{ get_phrase('Info') }}</h3>
            <ul>
                <li><i class="fa-solid fa-earth-americas"></i> <span>{{ $group->privacy}} </span></li>

                <li><i class="fa-solid fa-location-dot"></i>
                    <span>{{ $group->location }}</span>
                </li>

                <li><i class="fa-solid fa-users"></i><span> {{ $group->group_type}}
                    </span></li>
            </ul>
        </div>
        <div class="widget">
            
            <h4 class="widget-title">{{ get_phrase('Recent Media') }}</h4>
          
            <div class="row row-cols-3 g-1 mt-3">
                @foreach($viewData->groupMediaFiles($group) as $media_file)
                    @if($media_file->file_type == 'video')
                        <div class="single-item-countable col">
                            <video muted controlsList="nodownload" class="img-thumbnail w-100 user_info_custom_height">
                                <source src="{{get_post_video($media_file->file_name)}}" type="">
                            </video>
                        </div>
                    @else
                        <div class="single-item-countable col">
                            <img class="img-thumbnail w-100 user_info_custom_height" src="{{get_post_image($media_file->file_name, 'optimized')}}">
                        </div>
                    @endif
                @endforeach
            </div>

            <a href="{{ route('single.group.photos',$group->id) }}" class="btn common_btn mt-3 d-block mx-auto">{{ get_phrase('See More') }}</a>
        </div><!--  Widget End -->
        <div class="widget friend-widget">
            <div
                class="widget-header mb-3 d-flex justify-content-between align-items-center">
                <h4 class="widget-title mb-0">{{ get_phrase('Recent Members') }}</h4>
            </div>
            <div class="row row-cols-3 g-1 mt-3">
            @foreach ($viewData->recentGroupMembers($group) as $key => $groupmember)
                <div class="col">
                    <a href="{{ route('user.profile.view',$groupmember->getUser->id) }}" class="friend image_hight d-block">
                        <img width="100%" class="rounded" src="{{ get_user_image($groupmember->getUser->photo,'optimized') }}" alt="">
                        <h6 class="small">{{ $groupmember->getUser->name }}</h6>
                    </a>
                </div>
            @endforeach
            </div>
            
            <a href="{{ route('all.people.group.view',$group->id) }}" class="btn common_btn mt-3 d-block mx-auto">{{ get_phrase('See More') }}</a>
        </div><!--  Widget End -->

    {{-- </aside>
</div> <!-- Group Sidebar End --> --}}


@include('frontend.groups.invite')
