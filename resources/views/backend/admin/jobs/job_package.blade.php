<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Job Price') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="eSection-wrap-2">
                <form method="POST" action="{{ route('admin.job.price.view.save') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="job_price" class="form-label eForm-label">{{ get_phrase('Job Price') }}</label>
                        <input type="text" class="form-control eForm-control" id="job_price" name="job_price" value="{{ old('job_price', $job_price) }}">
                    </div>
                    <div class="mb-3">
                        <label for="day" class="form-label eForm-label">{{ get_phrase('Day') }}</label>
                        <input type="text" class="form-control eForm-control" id="day" name="day" value="{{ old('day', $day) }}">
                    </div>
                    <button type="submit" class="btn btn-primary">{{ get_phrase('Submit') }}</button>
                </form>
            </div>
        </div>
    </div>
    @include('backend.footer')
</div>
