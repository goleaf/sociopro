
<div class="main_content">
    <!-- Mani section header and breadcrumb -->
    <div class="mainSection-title">
      <div class="row">
        <div class="col-12">
          <div
            class="d-flex justify-content-between align-items-center flex-wrap gr-15"
          >
            <div class="d-flex flex-column">
              <h4>{{ get_phrase('Account Active Request') }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Start Admin area -->
    <div class="row">
      <div class="col-12">
        <div class="eSection-wrap-2">
          <!-- Filter area -->
          
          <div class="table-responsive">
            <table class="table eTable w-100" id="server_side_users_data">
              <thead>
                <tr>
                  <th>#</th>
                  <th>{{ get_phrase('Photo') }}</th>
                  <th>{{ get_phrase('Name') }}</th>
                  <th>{{ get_phrase('Email') }}</th>
                  <th class="text-center">{{ get_phrase('Actions') }}</th>
                </tr>
              </thead>
              @foreach ($request_users as $key => $request_user)
              <tbody>
                
                
                    <td>{{ $key + 1 }}</td>
                    <td>
                      <img src="{{ asset('storage/userimage/' . (!empty($request_user->user->photo) ? $request_user->user->photo : 'default.png')) }}" 
                           alt="" height="50" width="50" class="img-fluid rounded-circle img-thumbnail">
                  </td>
                  
                    <td>{{ $request_user->user->name }}</td>
                    <td>{{ $request_user->user->email }}</td>
                    <td>
                      <div class="adminTable-action me-auto">
                      <button
                        type="button"
                        class="eBtn eBtn-black dropdown-toggle table-action-btn-2"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                      >
                        {{ get_phrase('Actions') }}
                      </button>
                      <ul
                        class="dropdown-menu eDropdown-menu-2 eDropdown-table-action"
                      >
                      <li>
                        <a class="dropdown-item" 
                          onclick="return confirm('{{ get_phrase('Are You Sure Want To Approve?') }}')" 
                          href="{{ route('admin.users.acActiveReqApp', ['id' => $request_user->id, 'user_id' => $request_user->user->id]) }}">
                            {{ get_phrase('Approved') }}
                        </a>
                    </li>
                        <li>
                          <a class="dropdown-item" 
                          onclick="return confirm('{{ get_phrase('Are You Sure Want To Delete?') }}')" 
                          href="{{ route('admin.users.acActiveReDlt', ['id' => $request_user->id]) }}">
                            {{ get_phrase('Delete') }}
                        </a>
                        </li>
                      </ul>
                    </div>
                  </td>
              </tbody>
            @endforeach
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="admin-tInfo-pagi d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
      <p class="admin-tInfo">{{ get_phrase('Showing').' 1 - '.count($request_users).' '.get_phrase('from').' '.$request_users->total().' '.get_phrase('data') }}</p>
      <div class="admin-pagi">
        {!! $request_users->appends(request()->all())->links() !!}
      </div>
    </div>
  </div>