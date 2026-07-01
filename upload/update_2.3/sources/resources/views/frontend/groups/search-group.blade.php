<div class="row gx-3">
    <div class="col-lg-12">
        <div class="group-inner bg-white border-none radius-8 p-3">
            <div class="gr-search gt_search">
                <h3 class="h6"><span><i class="fa-solid fa-users"></i></span>{{ get_phrase('Group') }}</h3>
                <form action="{{ route('search.group') }}" method="GET">
                    <input type="text" class="bg-secondary rounded" name="search" value="@if(isset($_GET['search'])) {{ $_GET['search'] }} @endif" placeholder="Search Group">
                    <span class="i fa fa-search"></span>
                </form>
            </div>
            <div class="page-suggest mt-4">
                <h3 class="h6">{{ get_phrase('Groups')}}</h3>
                <div class="ps-wrap mt-3 justify-content-between">
                    <div class="row gx-2">
                        @foreach ($searchgroup as $group)
                            {{-- <div class="col-md-4 col-lg-4 col-sm-6"> --}}
                                <div class="card eg_card p-2">
                                    <div class="card-head"> <img  src="{{ get_group_logo($group->logo,'logo') }}" ></div>
                                    <div class="card-body">
                                        <div class="motion_text">
                                            <a href="{{ route('single.group',$group->id) }}"><h4>{{ $group->title }}</h4></a>
                                            @php $joined = \App\Models\Group_member::where('group_id',$group->id)->where('is_accepted','1')->count(); @endphp
                                            <ul class="figure_p d-flex ">
                                                <li>{{ $group->privacy }}</li>
                                                <li>{{ $joined }} {{ get_phrase('Member') }}{{ $joined>1?"s":"" }}</li>
                                            </ul>
                                            
                                            <span class="small text-muted">{{ $joined }} {{ get_phrase('Member') }}{{ $joined>1?"s":"" }}</span>
                                        </div>
                                       
                                        {{-- @php $joined = \App\Models\Group_member::where('group_id',$group->id)->where('is_accepted','1')->count(); @endphp
                                        <span class="small text-muted">{{ $joined }} Member @if($joined>1) s @endif</span> --}}
                                         <div class="join_groups">
                                                @php $join = \App\Models\Group_member::where('group_id',$group->id)->where('user_id',auth()->user()->id)->count(); @endphp
                                                @if ($join>0)
                                                    @if ($group->user_id==auth()->user()->id)
                                                        <a href="javascript:void(0)" class="btn btn-secondary">{{ get_phrase('Admin') }}</a>
                                                    @else
                                                        <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('group.rjoin',$group->id); ?>')" class="j_btn btn btn-secondary">
                                                            <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M5.99967 1.83334C4.25301 1.83334 2.83301 3.25334 2.83301 5.00001C2.83301 6.71334 4.17301 8.10001 5.91967 8.16001C5.97301 8.15334 6.02634 8.15334 6.06634 8.16001C6.07967 8.16001 6.08634 8.16001 6.09967 8.16001C6.10634 8.16001 6.10634 8.16001 6.11301 8.16001C7.81967 8.10001 9.15967 6.71334 9.16634 5.00001C9.16634 3.25334 7.74634 1.83334 5.99967 1.83334Z" fill="#0D091D"/>
                                                                <path d="M9.38664 9.93333C7.52664 8.69333 4.49331 8.69333 2.61997 9.93333C1.77331 10.5 1.30664 11.2667 1.30664 12.0867C1.30664 12.9067 1.77331 13.6667 2.61331 14.2267C3.54664 14.8533 4.77331 15.1667 5.99997 15.1667C7.22664 15.1667 8.45331 14.8533 9.38664 14.2267C10.2266 13.66 10.6933 12.9 10.6933 12.0733C10.6866 11.2533 10.2266 10.4933 9.38664 9.93333Z" fill="#0D091D"/>
                                                                <path d="M13.3262 5.39332C13.4329 6.68665 12.5129 7.81998 11.2396 7.97332C11.2329 7.97332 11.2329 7.97332 11.2263 7.97332H11.2062C11.1662 7.97332 11.1262 7.97332 11.0929 7.98665C10.4462 8.01998 9.85292 7.81332 9.40625 7.43332C10.0929 6.81998 10.4862 5.89998 10.4062 4.89998C10.3596 4.35998 10.1729 3.86665 9.89292 3.44665C10.1463 3.31999 10.4396 3.23999 10.7396 3.21332C12.0463 3.09999 13.2129 4.07332 13.3262 5.39332Z" fill="#0D091D"/>
                                                                <path d="M14.6605 11.56C14.6071 12.2067 14.1938 12.7667 13.5005 13.1467C12.8338 13.5133 11.9938 13.6867 11.1605 13.6667C11.6405 13.2333 11.9205 12.6933 11.9738 12.12C12.0405 11.2933 11.6471 10.5 10.8605 9.86667C10.4138 9.51333 9.89382 9.23333 9.32715 9.02667C10.8005 8.6 12.6538 8.88667 13.7938 9.80667C14.4071 10.3 14.7205 10.92 14.6605 11.56Z" fill="#0D091D"/>
                                                                </svg>
                                                                
                                                      {{ get_phrase('Joined') }}</a>
                                                    @endif
                                                @else
                                                    <a href="javascript:void(0)" onclick="ajaxAction('<?php echo route('group.join',$group->id); ?>')" class="btn btn-primary">
                                                        <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M5.99967 1.83334C4.25301 1.83334 2.83301 3.25334 2.83301 5.00001C2.83301 6.71334 4.17301 8.10001 5.91967 8.16001C5.97301 8.15334 6.02634 8.15334 6.06634 8.16001C6.07967 8.16001 6.08634 8.16001 6.09967 8.16001C6.10634 8.16001 6.10634 8.16001 6.11301 8.16001C7.81967 8.10001 9.15967 6.71334 9.16634 5.00001C9.16634 3.25334 7.74634 1.83334 5.99967 1.83334Z" fill="white"/>
                                                            <path d="M9.38664 9.93333C7.52664 8.69333 4.49331 8.69333 2.61997 9.93333C1.77331 10.5 1.30664 11.2667 1.30664 12.0867C1.30664 12.9067 1.77331 13.6667 2.61331 14.2267C3.54664 14.8533 4.77331 15.1667 5.99997 15.1667C7.22664 15.1667 8.45331 14.8533 9.38664 14.2267C10.2266 13.66 10.6933 12.9 10.6933 12.0733C10.6866 11.2533 10.2266 10.4933 9.38664 9.93333Z" fill="white"/>
                                                            <path d="M13.3262 5.39332C13.4329 6.68665 12.5129 7.81998 11.2396 7.97332C11.2329 7.97332 11.2329 7.97332 11.2263 7.97332H11.2062C11.1662 7.97332 11.1262 7.97332 11.0929 7.98665C10.4462 8.01998 9.85292 7.81332 9.40625 7.43332C10.0929 6.81998 10.4862 5.89998 10.4062 4.89998C10.3596 4.35998 10.1729 3.86665 9.89292 3.44665C10.1463 3.31999 10.4396 3.23999 10.7396 3.21332C12.0463 3.09999 13.2129 4.07332 13.3262 5.39332Z" fill="white"/>
                                                            <path d="M14.6605 11.56C14.6071 12.2067 14.1938 12.7667 13.5005 13.1467C12.8338 13.5133 11.9938 13.6867 11.1605 13.6667C11.6405 13.2333 11.9205 12.6933 11.9738 12.12C12.0405 11.2933 11.6471 10.5 10.8605 9.86667C10.4138 9.51333 9.89382 9.23333 9.32715 9.02667C10.8005 8.6 12.6538 8.88667 13.7938 9.80667C14.4071 10.3 14.7205 10.92 14.6605 11.56Z" fill="white"/>
                                                      </svg>
                                                     {{ get_phrase('Join') }}</a>
                                                @endif
                                            </div>
                                    </div>
                                </div>
                            {{-- </div> --}}
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div><!--  Group Content Inner Col End -->
        {{-- @include('frontend.groups.right-sidebar') --}}
</div>