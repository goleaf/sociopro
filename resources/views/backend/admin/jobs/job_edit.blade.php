<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Edit Job') }}</h4>
                    </div>
                    <div class="export-btn-area">
                        <a href="{{ route('admin.job') }}" class="export_btn">{{ get_phrase('View') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="eSection-wrap-2">
                <form method="POST" action="{{ route('admin.job.updated', $job_details->id) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="old_image" value="{{ $job_details->thumbnail }}">
                    @include('backend.admin.jobs.partials.form', [
                        'job' => $job_details,
                        'submitLabel' => get_phrase('Submit'),
                    ])
                </form>
            </div>
        </div>
    </div>
    @include('backend.footer')
</div>
