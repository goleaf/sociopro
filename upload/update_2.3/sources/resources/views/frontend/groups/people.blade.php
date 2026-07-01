<div class="profile-cover group-cover ng_profile  bg-white mb-3">
    @include('frontend.groups.cover-photo')
    @include('frontend.groups.iner-nav')
</div>
<div class="group-content profile-content">
    <div class="row gx-3">
        <div class="col-lg-12 col-sm-12">
            {{-- @include('frontend.groups.iner-nav') --}}
            <!-- People Nav End -->
            <div class="people-wrap p-3  bg-white">
                <div class="member-entry gr-search">
                    <h3 class="h6 mb-0 fw-7">{{ get_phrase('Members') }}<p class="ct_text">{{ $total_member }}</p> </h3>
                    <p class="mb-0">{{ get_phrase('New people and Pages who join this group will appear here') }}</p>
                    <form action="#" class="ag_form">
                        <input type="text" class="bg-secondary form-control rounded" name="search" value="" placeholder="Search Member">
                        <span class="i fa fa-search"></span>
                    </form>
                    @foreach ($recent_team_member as $recent_team_member)
                        <div class="entry-header d-flex justify-content-between py-3 border-bottom">
                            <div class="ava-info d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <img src="{{ get_user_image($recent_team_member->getUser->photo,'optimized') }}" class="circle user_image_show_on_modal" alt="...">
                                </div>
                                <div class="ava-desc ms-12">
                                    <h3 class="mb-0 h6">{{ $recent_team_member->getUser->name }}</h3>
                                    <span class="meta-time text-muted">{{ $recent_team_member->getUser->username }}</span>
                                </div>
                            </div>
                            @if($recent_team_member->user_id==auth()->user()->id)
                            <div class="post-controls dropdown dotted">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown"
                                    role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="ajaxAction('<?php echo route('group.rjoin',$group->id); ?>')">
                                            {{ get_phrase('Leave Group') }}</a></li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    @endforeach
                    <a href="{{ route('all.people.group.view',$group->id) }}" class="btn d-block mt-4 common_btn line-height-12 line_hight p-8">{{ get_phrase('See More') }}</a>
                </div> <!-- Member Entry End -->
                <div class="group-friend friens_g mt-3">
                    <h3 class="h6 fw-7 mb-4">{{ get_phrase('Friends') }} <p class="ct_text">{{ $friends_count }}</p></h3>
                    <div class="gr-wrap mt-3">

                        @foreach ($friends as $friend)
                        @if ($friend->getFriend->id==auth()->user()->id)
                            @continue
                        @endif
                            <div class="single-friend gap-3 d-flex align-items-start">
                                <div class="avatar"><a href="#"><img class="img-fluid "
                                    src="{{ get_user_image($friend->getFriend->id,'optimized') }}" alt=""></a></div>
                                <h3><a href="#">{{ $friend->getFriend->name }}</a> <span>{{ $friend->getFriend->username }}</span></h3>
                                <a href="{{ route('chat',$friend->getFriend->id) }}" class="btn common_btn p_message ms-auto"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 3H6C5.20435 3 4.44129 3.31607 3.87868 3.87869C3.31607 4.44131 3 5.20438 3 6.00004L3 15.0002C3 15.7958 3.31607 16.5589 3.87868 17.1215C4.44129 17.6841 5.20435 18.0002 6 18.0002H8.175L11.5132 20.8225C11.6487 20.9371 11.8203 21 11.9977 21C12.1752 21 12.3468 20.9371 12.4822 20.8225L15.825 18.0002H18C18.7956 18.0002 19.5587 17.6841 20.1213 17.1215C20.6839 16.5589 21 15.7958 21 15.0002V6.00004C21 5.20438 20.6839 4.44131 20.1213 3.87869C19.5587 3.31607 18.7956 3 18 3ZM8.25 6.75005H12C12.1989 6.75005 12.3897 6.82907 12.5303 6.96973C12.671 7.11038 12.75 7.30115 12.75 7.50006C12.75 7.69898 12.671 7.88975 12.5303 8.0304C12.3897 8.17105 12.1989 8.25007 12 8.25007H8.25C8.05109 8.25007 7.86032 8.17105 7.71967 8.0304C7.57902 7.88975 7.5 7.69898 7.5 7.50006C7.5 7.30115 7.57902 7.11038 7.71967 6.96973C7.86032 6.82907 8.05109 6.75005 8.25 6.75005ZM15.75 14.2502H8.25C8.05109 14.2502 7.86032 14.1711 7.71967 14.0305C7.57902 13.8898 7.5 13.6991 7.5 13.5001C7.5 13.3012 7.57902 13.1105 7.71967 12.9698C7.86032 12.8292 8.05109 12.7501 8.25 12.7501H15.75C15.9489 12.7501 16.1397 12.8292 16.2803 12.9698C16.421 13.1105 16.5 13.3012 16.5 13.5001C16.5 13.6991 16.421 13.8898 16.2803 14.0305C16.1397 14.1711 15.9489 14.2502 15.75 14.2502ZM15.75 11.2501H8.25C8.05109 11.2501 7.86032 11.1711 7.71967 11.0304C7.57902 10.8898 7.5 10.699 7.5 10.5001C7.5 10.3012 7.57902 10.1104 7.71967 9.96977C7.86032 9.82911 8.05109 9.75009 8.25 9.75009H15.75C15.9489 9.75009 16.1397 9.82911 16.2803 9.96977C16.421 10.1104 16.5 10.3012 16.5 10.5001C16.5 10.699 16.421 10.8898 16.2803 11.0304C16.1397 11.1711 15.9489 11.2501 15.75 11.2501Z" fill="black"/>
                                    </svg>
                                    {{get_phrase('Message')}}</a>
                            </div> 
                        @endforeach
                    </div>
                </div> <!-- Group Friend End -->
                <div class="group-friend friens_g mt-3">
                    <h3 class="h6 fw-7 mb-4">{{ get_phrase('Members With Things in Common') }}</h3>
                    <div class="gr-wrap mt-3">
                        @foreach ($users as $user)
                            <div class="single-friend gap-3 d-flex align-items-start">
                                <div class="avatar"><a href="#"><img class="img-fluid"
                                    src="{{ get_user_image($user->photo,'optimized') }}" alt=""></a></div>
                                <h3><a href="#">{{ $user->name }}</a> <span>{{ $user->username }}</span></h3>
                                @php
                                    $user_id = $user->id;
                                    $friend = \App\Models\Friendships::where(function($query) use ($user_id){
                                        $query->where('requester', auth()->user()->id);
                                        $query->where('accepter', $user_id);
                                    })
                                    ->orWhere(function($query) use ($user_id) {
                                        $query->where('accepter', auth()->user()->id);
                                        $query->where('requester', $user_id);
                                    })
                                    ->count();
                                @endphp
                                @if ($friend>0)
                                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.unfriend',$user->id); ?>')" class="btn common_btn ms-auto"><i class="fa-solid fa-xmark"></i> {{ get_phrase('Cancel') }}</a>
                                @else   
                                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('user.friend',$user->id); ?>')" class="btn common_btn ms-auto"><i class="fa-solid fa-plus"></i> {{ get_phrase('Add Friend') }} </a>
                                @endif
                            </div> <!-- Add Friend End -->
                        @endforeach
                    </div>
                </div> <!-- Group Friend End -->
            </div>
            <!--  Single Entry End -->
        </div> <!-- COL END -->
        <!--  Group Content Inner Col End -->
        {{-- @include('frontend.groups.bio') --}}
    </div>
</div><!-- Group content End -->
@include('frontend.groups.invite')