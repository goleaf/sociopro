
<div class="main_content">
  <!-- Mani section header and breadcrumb -->
  <div class="mainSection-title">
    <div class="row">
      <div class="col-12">
        <div
          class="d-flex justify-content-between align-items-center flex-wrap gr-15"
        >
          <div class="d-flex flex-column">
            <h4>{{ get_phrase('Update a Group') }}</h4>
          </div>

          <div class="export-btn-area">
            <a href="{{ url()->previous() }}" class="export_btn"><i class="fas fa-chevron-left me-2"></i> {{ get_phrase('Back') }}</a>
          </div>
           
        </div>
      </div>
    </div>
  </div>

  <!-- Start Admin area -->
  <div class="row">
    <div class="col-md-7">
      <div class="eSection-wrap-2">
          <div class="eForm-layouts">
            @if ($errors->any())
                  <div class="alert alert-danger">
                      <ul>
                          @foreach ($errors->all() as $error)
                              <li>{{ $error }}</li>
                          @endforeach
                      </ul>
                  </div>
              @endif
            <form method="POST" action="{{ route('admin.group.updated',$group_details->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label for="title" class="form-label eForm-label">{{ get_phrase('Group title') }}</label>
                  <input type="text" class="form-control eForm-control" id="title" name="title" value="{{$group_details->title}}">
                </div>

                <div class="mb-3">
                  <label for="subtitle" class="form-label eForm-label">{{ get_phrase('Group Sub title') }}</label>
                  <input type="text" class="form-control eForm-control" id="subtitle" name="subtitle" placeholder="Group sub title" value="{{$group_details->subtitle}}">
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label eForm-label">{{ get_phrase('Group details') }}</label>
                    <textarea id="description" name="about" class="content">{{$group_details->about}}</textarea>
                </div>

                <div class="mb-3">
                    <label for="coverphoto" class="form-label eForm-label">{{ get_phrase('Group Logo') }}</label>
                    <input id="coverphoto" class="form-control eForm-control-file" type="file" name="logo">
                    <input value="{{$group_details->logo}}" type="file" name="old_logo" hidden>
                </div>
                <div class="mb-3">
                  <label for="page_category" class="form-label eForm-label">{{ get_phrase('Privacy') }}</label>
                  <select name="privacy" class="form-select eForm-control select2" required>
                    <option value="public" @if($group_details->privacy == 'public') selected @endif>{{get_phrase('Public')}}</option>
                    <option value="private" @if($group_details->privacy == 'private') selected @endif>{{get_phrase('Private')}}</option>
                  </select>
              </div>
                <div class="mb-3">
                  <label for="page_category" class="form-label eForm-label">{{ get_phrase('Status') }}</label>
                  <select name="status" class="form-select eForm-control select2" required>
                    <option value="1" @if($group_details->status == '1') selected @endif>{{get_phrase('Active')}}</option>
                    <option value="0" @if($group_details->status == '0') selected @endif>{{get_phrase('Deactive')}}</option>
                  </select>
              </div>
                <button type="submit" class="btn btn-primary">{{ get_phrase('Submit') }}</button>
            </form>
          </div>

      </div>
    </div>
  </div>
  <!-- Start Footer -->
  @include('backend.footer')
  <!-- End Footer -->
</div>



