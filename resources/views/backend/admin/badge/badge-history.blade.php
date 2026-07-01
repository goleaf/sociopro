
<style>
    .btn-danger {
        background: #f1416c !important;
        color: #fff;
    }
    .acbtn {
        height: 27px;
        color: #fff !important;
        padding: 0 7px;
    }
    .adminTable-action {
	margin-left: 0 !important;
}
</style>

<div class="main_content">
    <!-- Mani section header and breadcrumb -->
    <div class="mainSection-title">
      <div class="row">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center flex-wrap gr-15" >
            <div class="d-flex flex-column">
              <h4>{{ get_phrase('Badge') }}</h4>
              
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Start Admin area -->
    <div class="row">
      <div class="col-12">
        <div class="eSection-wrap-2">
            <div class="eMain">
                <div class="row">
                    <div class="col-md-8 pb-3">
                        <div class="eForm-layouts">
                            <p class="column-title">{{get_phrase('FRONTEND BADGE PRICING SETTINGS')}}</p>
                            <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{route('admin.badge.price.save')}}">
                                @csrf
                                <div class="fpb-7">
                                    <label for="badge_price" class="eForm-label">{{get_phrase('Badge Price')}}</label>
                                    <input type="text" class="form-control eForm-control" value="{{$badge_price}}" id="badge_price" name="badge_price" required="">
                                </div>
                
                                <div class="fpb-7 pt-2">
                                    <button type="submit" class="btn-form">{{get_phrase('Submit')}}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
          
        </div>
      </div>
    </div>
    <!-- End Admin area -->
    <!-- Start Admin area -->
    <div class="row">
      <div class="col-12">
        <div class="eSection-wrap-2">
          <!-- Filter area -->
          <div class="table-responsive">
            <table class="table eTable " id="">
              <thead>
                <tr>
                  <th scope="col">{{ get_phrase('Sl No') }}</th>
                  <th scope="col">{{ get_phrase('Name') }}</th>
                  <th scope="col">{{ get_phrase('Start Date') }}</th>
                  <th scope="col">{{ get_phrase('End Date') }}</th>
                  <th scope="col">{{ get_phrase('Status') }}</th>
                  <th scope="col">{{ get_phrase('Action') }}</th>
                </tr>
              </thead>
              <tbody>
               @foreach($badges as $key => $badge)
               
               @php 
                $user_info = App\Models\User::where('id', $badge->user_id)->first();
               @endphp

                    <tr>
                        <th scope="row">
                             <p class="row-number">{{++$key}}</p>
                        </th>
                        <td>
                            <div class="dAdmin_info_name min-w-100px">
                                <a href="" class="text-dark" target="_blank">{{$user_info->name}} @if($user_info->user_role == 'admin'){{get_phrase('(Admin)')}}@endif</a>
                            </div>
                        </td>
                        <td>
                            <div class="dAdmin_info_name min-w-100px">
                                <p>{{ date('d M Y', strtotime($badge->start_date)) }}</p>
                            </div>
                        </td>
                        <td>
                            <div class="dAdmin_info_name min-w-100px">
                                <p>{{ date('d M Y', strtotime($badge->end_date)) }}</p>
                            </div>
                        </td>
                        <td>
                            <div class="dAdmin_info_name min-w-100px">
                                @php 
                                   $currentDate = \Carbon\Carbon::now();
                                   $isActive = $currentDate >= $badge->start_date && $currentDate <= $badge->end_date; 
                                @endphp
                                @if($isActive)
                                   <p class="btn btn-primary acbtn">{{get_phrase('Active')}}</p>
                                @else
                                  <p class="btn btn-danger acbtn">{{get_phrase('Expires')}}</p>
                                @endif
                            </div>
                        </td>
                        
                        <td class="text-center">
                          <div class="adminTable-action">
                            <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false">
                              {{get_phrase('Actions')}}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                              <li><a class="dropdown-item" target="_blank" href="{{ route('user.profile.view', $badge->user_id) }}">{{get_phrase('View on frontend')}}</a></li>
                              <li><a class="dropdown-item" onclick="return confirm('{{get_phrase('Are You Sure Want To Delete?')}}')" href="{{route('admin.badge.delete',$badge->id)}}">{{get_phrase('Delete')}}</a></li>
                            </ul>
                          </div>
                        </td>
                    </tr>
               @endforeach
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
    <!-- End Admin area -->

   
    <!-- Start Footer -->
    @include('backend.footer')
    <!-- End Footer -->
  </div>



