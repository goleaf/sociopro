
            <div class="widget ng_widget">
                <div class="w_btn">
                    <button class="btn common_btn d-block w-100" onclick="showCustomModal('{{route('load_modal_content', ['view_path' => 'frontend.groups.create'])}}', '{{get_phrase(' Create New Group')}}');" data-bs-toggle="modal"
                        data-bs-target="#newGroup"><i class="fa fa-plus-circle"></i>{{get_phrase(' Create New Group')}}</button>
                 </div>
                <div class="gr-search">
                    <h3 class="h6">{{ get_phrase('Groups')}}</h3>
                    <form action="{{ route('search.group') }}" class="ag_form">
                        <input type="text" class="bg-secondary form-control rounded" name="search" value="@if(isset($_GET['search'])) {{ $_GET['search'] }} @endif" placeholder="Search Group">
                        <span class="i fa fa-search"></span>
                    </form>
                </div>
           
            <div class="group-widget">
                <h3 class="widget-title">{{ get_phrase('Group you Manage') }}</h3>
                    @foreach ($managegroups as $managegroup )
                        <div class="d-flex align-items-center mt-3">
                            <div class="widget-img">
                                <img src="{{ get_group_logo($managegroup->logo,'logo') }}" alt="" class="img-fluid img-radisu">
                            </div>
                            <div class="widget-info">
                                <h6><a href="{{ route('single.group',$managegroup->id) }}">{{ $managegroup->title }}</a></h6>
                            </div>
                        </div>
                    @endforeach
                    @if (count($managegroups)>8)
                        <a href="{{ route('group.user.created') }}" class="btn btn-primary mt-3 d-block w-100">{{ get_phrase('See All') }}</a>
                    @endif
            </div> <!-- Widget End -->
            <div class=" group-widget join_wid">
                <h3 class="widget-title">{{ get_phrase('Group you Joined') }}</h3>
                    @foreach ($joinedgroups as $joinedgroup )
                        <div class="d-flex align-items-center mt-3">
                            <div class="widget-img">
                                <img src="{{ get_group_logo($joinedgroup->getGroup->logo,'logo') }}" alt="" class="img-fluid img-radisu">
                            </div>
                            <div class="widget-info">
                                <h6><a href="{{ route('single.group',$joinedgroup->group_id) }}"> {{ $joinedgroup->getGroup->title }} </a></h6>
                            </div>
                        </div>
                    @endforeach
                    @if (count($joinedgroups)>8)
                        <a href="{{ route('group.user.joined') }}" class="btn common_btn mt-3 d-block w-100">{{ get_phrase('See All') }}</a>
                    @endif
            </div> <!-- Widget End -->
            </div> <!-- Widget End -->
           
 <!-- Group Sidebar End -->