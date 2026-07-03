<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Edit Job Category') }}</h4>
                    </div>
                    <div class="export-btn-area">
                        <a href="{{ route('admin.view.job.category') }}" class="export_btn">{{ get_phrase('View') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="eSection-wrap-2">
                <form method="POST" action="{{ route('admin.update.job.category', $jobcategories->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="jobcategory" class="form-label eForm-label">{{ get_phrase('Job Category') }}</label>
                        <input type="text" class="form-control eForm-control" id="jobcategory" name="jobcategory" value="{{ old('jobcategory', $jobcategories->name) }}">
                    </div>
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary">{{ get_phrase('Submit') }}</button>
                </form>
            </div>
        </div>
    </div>
    @include('backend.footer')
</div>
