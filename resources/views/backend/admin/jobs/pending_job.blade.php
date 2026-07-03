<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Pending Job') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="eSection-wrap-2">
                <div class="table-responsive">
                    <table class="table eTable">
                        <thead>
                            <tr>
                                <th>{{ get_phrase('Sl No') }}</th>
                                <th>{{ get_phrase('Title') }}</th>
                                <th>{{ get_phrase('Company') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pending_job as $job)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $job->title }}</td>
                                    <td>{{ $job->company }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">{{ get_phrase('No data found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('backend.footer')
</div>
