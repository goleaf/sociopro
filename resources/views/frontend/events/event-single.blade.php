@foreach ($events as $key => $event)
    @php
        $postOfThisEvent = \App\Models\Posts::where('publisher', 'event')
            ->where('publisher_id', $event->id)
            ->first();
        if (!empty($postOfThisEvent->post_id)) {
            $postId = $postOfThisEvent->post_id;
        } else {
            $postId = 0;
        }
 
    @endphp
    <div class="col-lg-6 col-xl-4 col-md-4 col-sm-6 single-item-countable" id="event-{{ $event->id }}">

        <div class="card m_product m_event event-card">
            <a href="{{ route('single.event', $event->id) }}">
                <div class="event-image thumbnail-210-200"
                    style="background-image: url('{{ viewImage('event', $event->banner, 'thumbnail') }}')">
                    <div class="event-date n_date">
                        @php $date = explode("-",$event->event_date); @endphp
                        <p class="eve_t_text">
                            {{ date('M', strtotime($event->event_date)) }}
                        </p>
                        <span>{{ $date['2'] }}</span>
                    </div>
                </div>
            </a>
            <div class="event-text og_event_text">
                <small class="event-meta">{{ date('D', strtotime($event->event_date)) }}, at
                    {{ $event->event_time }}</small>
                <small class="mute e_location">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <mask id="mask0_16_74" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0"
                            width="20" height="20">
                            <rect width="20" height="20" fill="#D9D9D9" />
                        </mask>
                        <g mask="url(#mask0_16_74)">
                            <path
                                d="M10.0026 16.1248C11.697 14.5693 12.954 13.1561 13.7734 11.8853C14.5929 10.6144 15.0026 9.48595 15.0026 8.49984C15.0026 6.98595 14.52 5.74637 13.5547 4.78109C12.5894 3.81581 11.4054 3.33317 10.0026 3.33317C8.59983 3.33317 7.4158 3.81581 6.45052 4.78109C5.48524 5.74637 5.0026 6.98595 5.0026 8.49984C5.0026 9.48595 5.41233 10.6144 6.23177 11.8853C7.05121 13.1561 8.30816 14.5693 10.0026 16.1248ZM10.0026 17.7707C9.80816 17.7707 9.61371 17.736 9.41927 17.6665C9.22483 17.5971 9.05121 17.4929 8.89844 17.354C7.99566 16.5207 7.19705 15.7082 6.5026 14.9165C5.80816 14.1248 5.2283 13.3575 4.76302 12.6144C4.29774 11.8714 3.94358 11.1561 3.70052 10.4686C3.45747 9.78109 3.33594 9.12484 3.33594 8.49984C3.33594 6.4165 4.00608 4.75678 5.34635 3.52067C6.68663 2.28456 8.23871 1.6665 10.0026 1.6665C11.7665 1.6665 13.3186 2.28456 14.6589 3.52067C15.9991 4.75678 16.6693 6.4165 16.6693 8.49984C16.6693 9.12484 16.5477 9.78109 16.3047 10.4686C16.0616 11.1561 15.7075 11.8714 15.2422 12.6144C14.7769 13.3575 14.197 14.1248 13.5026 14.9165C12.8082 15.7082 12.0095 16.5207 11.1068 17.354C10.954 17.4929 10.7804 17.5971 10.5859 17.6665C10.3915 17.736 10.197 17.7707 10.0026 17.7707ZM10.0026 9.99984C10.4609 9.99984 10.8533 9.83664 11.1797 9.51025C11.5061 9.18387 11.6693 8.7915 11.6693 8.33317C11.6693 7.87484 11.5061 7.48248 11.1797 7.15609C10.8533 6.8297 10.4609 6.6665 10.0026 6.6665C9.54427 6.6665 9.15191 6.8297 8.82552 7.15609C8.49913 7.48248 8.33594 7.87484 8.33594 8.33317C8.33594 8.7915 8.49913 9.18387 8.82552 9.51025C9.15191 9.83664 9.54427 9.99984 10.0026 9.99984Z"
                                fill="#767676" />
                        </g>
                    </svg>
                    {{ $event->location }}</small>
                <h3 class="elips_con"><a class="ellipsis-line-2"
                        href="{{ route('single.event', $event->id) }}">{{ ellipsis($event->title, 100) }}</a></h3>
                <div class="organiser elips_con d-flex align-items-center">
                    <a href="{{ route('user.profile.view', $event['user_id']) }}"><img src="{{ get_user_image($event->getUser->photo, 'optimized') }}"
                            width="28" class="user-round" alt=""></a>
                    <div class="ognr-info ms-2">
                        <h6 class="m-0"><a href="{{ route('user.profile.view', $event['user_id']) }}">{{ $event->getUser->name }}</a></h6>
                    </div>
                </div>
                <div class="og_btn d-flex justify-content-between e_event mt-2">
                    <div class=" interest_text @if (!in_array(auth()->user()->id, json_decode($event->interested_users_id)) && !in_array(auth()->user()->id, json_decode($event->going_users_id))) displaynone @endif" id="dropdown_interest{{ $event->id }}">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown_event_label" >
                                <i class="fa-solid fa-star"></i>
                                    @if (in_array(auth()->user()->id, json_decode($event->going_users_id)))
                                   {{get_phrase('Going')}}
                                    @elseif(in_array(auth()->user()->id, json_decode($event->interested_users_id)))
                                    {{get_phrase('Interested')}} 
                                    @endif
                            </button>

                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown" aria-expanded="false" data-bs-reference="parent">
                                <span class="visually-hidden"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li class="btn-interested @if (in_array(auth()->user()->id, json_decode($event->interested_users_id))) displaynone @endif">
                                    <a class="dropdown-item " href="#"
                                        onclick="ajaxAction('<?php echo route('event.interested', $event->id); ?>')">
                                        <i class="fa-solid fa-star me-1"></i>
                                        {{get_phrase('Interested')}}
                                    </a>
                                </li>
                                <li class="btn-going @if (in_array(auth()->user()->id, json_decode($event->going_users_id))) displaynone @endif">
                                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.going', $event->id); ?>')"
                                        class="dropdown-item"> <i class="fa-solid fa-circle-check me-1"></i>
                                        {{ get_phrase('Going') }}</a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.cancel', $event->id); ?>')"
                                        class="dropdown-item"><i class="fa-solid fa-circle-xmark"></i>
                                        {{ get_phrase('Cancel') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>


                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('event.interested', $event->id); ?>')"
                        class="btn ni_btn btn-primary @if (in_array(auth()->user()->id, json_decode($event->interested_users_id)) || in_array(auth()->user()->id, json_decode($event->going_users_id))) displaynone @endif"
                        id="btn_interest{{ $event->id }}">
                        <i class="fa-solid fa-star me-2"></i>{{ get_phrase('Interest') }}
                    </a>


                    <div class="post-controls ev_event_con dropdown">
                        <div class="dropdown ">
                            <button class="btn cl_btn  btn-secondary" type="button" id="dropdownMenuButton1"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis"></i>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                @if ($event->user_id == auth()->user()->id)
                                    <li>
                                        <button
                                            onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.events.edit_event', 'event_id' => $event->id]) }}', '{{ get_phrase('Edit Event') }}');"
                                            class="dropdown-item  ed_ve btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#createEvent"><i class="fa fa-edit me-1"></i>
                                            {{ get_phrase('Edit Event') }}</button>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0)"
                                            onclick="confirmAction('<?php echo route('event.delete', ['event_id' => $event->id]); ?>', true)"
                                            class="dropdown-item  ed_ve btn-primary btn-sm"><i
                                                class="fa fa-trash me-1"></i> {{ get_phrase('Delete Event') }}</a>
                                    </li>
                                @endif
                                @if ($postId != 0)
                                    <li>
                                        <a href="javascript:void(0)"
                                            onclick="showCustomModal('{{ route('load_modal_content', ['view_path' => 'frontend.main_content.share_post_modal', 'post_id' => $postId]) }}', '{{ get_phrase('Share Event') }}');"
                                            class="dropdown-item btn ed_ve btn-primary btn-sm"><i class="fa fa-share me-1"></i>
                                            {{ get_phrase('Share Event') }}</a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>





            </div>
        </div>
    </div>
    @if (isset($search) && !empty($search))
        @if ($key == 2)
        @break
    @endif
@endif
@endforeach
