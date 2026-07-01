             
<!-- Content Section Start -->
    <div class="single-event-wrap single_event">
        <div class="event-image event-cover">
            <img class="w-100" src="{{ viewImage('event',$event->banner,'coverphoto') }}" class="img-fluid" alt="Event">
            <div class="ev_s_control evn_card">
                <div class="card m_product ev_event_card radius-8 p-20">
                   <div class="ev_head">
                     <div class="ev_left">
                        <span class="text-primary">{{ date('l', strtotime($event->event_date)) }}, {{ date('d F Y', strtotime($event->event_date))  }}, at {{ $event->event_time }}</span>
                        <h2 class="h5 mb-0"> {{$event->title}}</h2>
                        <span>{{ $event->location }}</span>
                     </div>
                     <div class="ev_right">
                        @if (in_array(auth()->user()->id, json_decode($event->going_users_id)))
                          <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.going',$event->id); ?>')" class="w-100 mb-2 btn btn-primary @if (in_array(auth()->user()->id, json_decode($event->going_users_id))) displaynone @endif" id="goingId{{ $event->id }}"> {{get_phrase('Going')}}</a>
                        @endif
                        {{-- <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.notgoing',$event->id); ?>')" class="w-100 mb-2 btn btn-secondary @if (!in_array(auth()->user()->id, json_decode($event->going_users_id))) displaynone @endif" id="notGoingId{{ $event->id }}"> {{get_phrase('Cancel')}}</a> --}}

                          @if(in_array(auth()->user()->id, json_decode($event->interested_users_id)))
                           <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.interested',$event->id); ?>')" class="w-100 mb-2 no_btn btn btn-primary @if (in_array(auth()->user()->id, json_decode($event->interested_users_id))) displaynone @endif" id="interestedId{{ $event->id }}"><i class="fa-solid fa-star me-2"></i> {{get_phrase('Interested')}}</a>
                          @endif
                        {{-- <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.notinterested',$event->id); ?>')" class="w-100 mb-2 btn no_btn btn-secondary @if (!in_array(auth()->user()->id, json_decode($event->interested_users_id))) displaynone @endif" id="notInterestedId{{ $event->id }}"> {{get_phrase('Not Interested')}}</a> --}}
                     </div>
                   </div>

                    <div class="event-tab ev_tabs ct-tab ">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="about-tab" data-bs-toggle="tab"
                                    data-bs-target="#about" type="button" role="tab" aria-controls="about"
                                    aria-selected="true">{{ get_phrase('About') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="discussion-tab" data-bs-toggle="tab"
                                    data-bs-target="#discussion" type="button" role="tab"
                                    aria-controls="discussion" aria-selected="false">{{ get_phrase('Discussion') }}</button>
                            </li>
                        </ul>
                         <div class="ns_share">
                            @php  $postOfThisEvent = \App\Models\Posts::where('publisher','event')->where('publisher_id',$event->id)->first();@endphp
                            @if($postOfThisEvent != null)
                                <div class="post-controls dropdown dotted">
                                    <a class="nav-link dropdown-toggle ms-auto text-end m-0 p-0 w-25" href="#" id="navbarDropdown"
                                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                       
                                            <li>
                                                <a href="javascript:void(0)" onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.main_content.share_post_modal', 'post_id' => $postOfThisEvent->post_id] )}}', '{{get_phrase('Share Event')}}');" class="dropdown-item "> {{get_phrase('Share')}}</a>
                                            </li>
                                        {{-- @else --}}
                                            {{-- <li>
                                                <a href="#" class="dropdown-item "> {{get_phrase('Create post to share')}}</a>
                                            </li> --}}
                                        
                                    </ul>
                                </div>
                                @endif
                         </div>
                    </div>
                </div> <!-- Card End -->

            </div>
        </div>
        <div class="row mt-12">
            <div class="col-lg-12 col-sm-12">
                <div class="tab-content card m_product ev_event_card radius-8 p-3 " id="myTabContent">
                    <div class="tab-pane fade show active" id="about" role="tabpanel"
                        aria-labelledby="about-tab">
                        <h2 class="h6">{{ get_phrase('Details') }}</h2>
                        <p>
                            @php echo script_checker($event->description, false); @endphp
                        </p>
                    </div> <!-- Tab Pane End -->

                    

                    <div class="tab-pane fade" id="discussion" role="tabpanel"  aria-labelledby="discussion-tab">
                        {{--  include the post feature   --}}
                        @include('frontend.main_content.create_post', ['event_id' => $event->id])

                        <div class="discuss-wrap">
                            <h3 class="h6 my-3">{{get_phrase('Recent Activity')}}</h3>
                            @include('frontend.main_content.posts',['type'=>'user_post'])
                        </div>
                    </div><!-- Tab Pane End -->
                </div> <!-- Tab Content End -->
            </div>
            {{-- <div class="col-lg-5 col-sm-12">
                
            </div> --}}
        </div>
    </div>

<!-- Content Section End -->

@include('frontend.events.event_invite_modal')
@include('frontend.main_content.scripts')