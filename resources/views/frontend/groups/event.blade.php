<div class="profile-cover group-cover ng_profile bg-white mb-3">
    @include('frontend.groups.cover-photo')
    @include('frontend.groups.iner-nav')
</div>

<div class="group-content profile-content">
    <div class="row gx-3">
        <div class="col-lg-12 col-sm-12">
            {{-- @include('frontend.groups.iner-nav') --}}
            <!-- People Nav End -->
         
            <!-- Event Content Start -->
            <div class="event-content eVent">
                <div class="card create-event text-center">
                    <div class="upcoming-event">
                        <div class="entery_ev">
                            <h5 class="h6">{{get_phrase('Upcoming Events')}}</h5>
                            <a href="javascript:void(0)" onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.events.create_event','group_id'=>$group->id])}}', '{{get_phrase('Create Event')}}');"  data-bs-toggle="modal" data-bs-target="#createEvent" class="btn btn-sm common_btn d-block">{{get_phrase('Create Event')}}</a>
                        </div>
                        @php
                            $query = DB::table('events')->where('group_id',$group->id)->where('privacy','public')
                            ->whereDate('event_date', '>', now());
                            $number_of_upcoming_events = $query->get()->count();
                        @endphp
                        @if($number_of_upcoming_events > 0)
                            <i class="fa-solid fa-calendar text-primary"></i>
                            <p class="p-0 m-0 near_text">{{get_phrase('Nearest event')}}, <b>{{date_formatter($query->first()->event_date.' '.$query->first()->event_time, 3)}}</b></p>
                        @else
                            <i class="fa-solid fa-calendar-xmark"></i>
                        @endif
                        
                        <p class="mute mute_text">
                            @if($number_of_upcoming_events > 0)
                                {{ get_phrase('Total ____ Upcoming events', [$number_of_upcoming_events]) }}
                            @else
                                {{ get_phrase('No upcoming events') }}
                            @endif
                        </p>
                    </div>
                  
                </div>
               @if(count($group_events)>0) 
                <div class="row">
                    <div class="col-md-12">
                        <div class="card mt-3">
                            <div class="card-body pb-0"><h3 class="h6 fw-7">{{ get_phrase('Post Events') }}</h3></div>
                            @foreach ($group_events as $event )
                                <div class="single-event card my-2 border-0 px-3" id="event-{{ $event->id }}">
                                    <div class="rows eVent_box g-0">
                                        {{-- <div class="col-md-4 col-lg-12 col-xl-5">  --}}
                                            {{-- style="background-image: url('{{ viewImage('event',$event->banner,'thumbnail') }}'); background-size: 100%; background-position: center; background-repeat: no-repeat; "> --}}
                                            <div class="view_image">
                                                <a href="{{{ route('single.event', $event['id']) }}}}"><img src="{{ viewImage('event',$event->banner,'thumbnail') }}" alt="..."></a>
                                            </div>
                                        {{-- </div> --}}
                                        {{-- <div class="col-md-8 col-lg-12 col-xl-7"> --}}
                                            <div class="card-body group-event-dotted">
                                                
                                                <h5 class="card-title dotted d-flex">
                                                    <a class="event_line" href="{{ route('single.event', $event['id']) }}"> <span>{{ ellipsis($event->title, 80) }}</span></a>
                                                    <div class="dropdown p-0 m-0">
                                                        <a class="nav-link dropdown-toggle" href="#" id="eventDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false"></a>
                                                        <ul class="dropdown-menu" aria-labelledby="eventDropdown">
                                                            <li>
                                                                <button onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.events.edit_event', 'event_id' => $event->id] )}}', '{{get_phrase('Edit Event')}}');" class="dropdown-item btn btn-primary btn-sm" data-bs-toggle="modal"
                                                                    data-bs-target="#createEvent"><i class="fa fa-edit me-1"></i> {{get_phrase('Edit Event')}}</button>
                                                            </li>
                                                            <li>
                                                                <a href="javascript:void(0)" onclick="confirmAction('<?php echo route('event.delete', ['event_id' => $event->id]); ?>', true)" class="dropdown-item btn btn-primary btn-sm"><i class="fa fa-trash me-1"></i> {{get_phrase('Delete Event')}}</a>
                                                            </li>
                                                        </ul>
                                                        
                                                    </div>
                                                </h5>
                                                <div class="card-text p-0 m-0">
                                                    <div class="d-flex gap-2 center_text align-items-center line-height16px">
                                                        <div class="">
                                                            <a href="{{route('user.profile.view', $event->getUser->id)}}"><img src="{{get_user_image($event->getUser->photo, 'optimized')}}" class="rounded-circle group_event_user_img_w w-28" alt=""> </a>
                                                        </div>
                                                        <span><small>{{get_phrase('Created by')}}</small>, <a href="{{route('user.profile.view', $event->getUser->id)}}" class="bold_text">{{ $event->getUser->name }}</a></span>
                                                    </div>
                                                </div>
                                                <small class="line-height16px font-12">{{ date('l', strtotime($event->event_date)) }}, {{ date('d F Y', strtotime($event->event_date))  }}, at {{ $event->event_time }}</small>
                                            </div>
                                        {{-- </div> --}}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            </div>
            <!-- Event Content End -->
        </div> <!-- COL END -->
        <!--  Group Content Inner Col End -->
        {{-- @include('frontend.groups.bio') --}}
    </div>
</div><!-- Group content End -->

@include('frontend.groups.invite')