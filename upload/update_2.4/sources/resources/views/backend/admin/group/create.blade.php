
<div class="main_content">
    <!-- Mani section header and breadcrumb -->
    <div class="mainSection-title">
      <div class="row">
        <div class="col-12">
          <div
            class="d-flex justify-content-between align-items-center flex-wrap gr-15"
          >
            <div class="d-flex flex-column">
              <h4>{{ get_phrase('Add a new Group') }}</h4>
              
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
              <form method="POST" action="{{ route('admin.group.created') }}" enctype="multipart/form-data">
                  @csrf
                  <div class="mb-3">
                    <label for="title" class="form-label eForm-label">{{ get_phrase('Group title') }}</label>
                    <input type="text" class="form-control eForm-control" id="title" name="title" placeholder="Group title">
                  </div>

                  <div class="mb-3">
                    <label for="subtitle" class="form-label eForm-label">{{ get_phrase('Group Sub title') }}</label>
                    <input type="text" class="form-control eForm-control" id="subtitle" name="subtitle" placeholder="Group sub title">
                  </div>

                  <div class="mb-3">
                      <label for="description" class="form-label eForm-label">{{ get_phrase('Group details') }}</label>
                      <textarea id="description" name="about" class="content"></textarea>
                  </div>

                  <div class="mb-3">
                      <label for="coverphoto" class="form-label eForm-label">{{ get_phrase('Group Logo') }}</label>
                      <input id="coverphoto" class="form-control eForm-control-file" type="file" name="logo">
                  </div>
                  <div class="mb-3">
                    <label for="page_category" class="form-label eForm-label">{{ get_phrase('Privacy') }}</label>
                    <select name="privacy" class="form-select eForm-control select2" required>
                      <option value="public">{{get_phrase('Public')}}</option>
                      <option value="private">{{get_phrase('Private')}}</option>
                    </select>
                </div>
                  <div class="mb-3">
                    <label for="page_category" class="form-label eForm-label">{{ get_phrase('Status') }}</label>
                    <select name="status" class="form-select eForm-control select2" required>
                      <option value="1">{{get_phrase('Active')}}</option>
                      <option value="0">{{get_phrase('Deactive')}}</option>
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



