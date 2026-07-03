<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Job List') }}</h4>
                    </div>
                    <div class="export-btn-area">
                        <a href="{{ route('admin.job.create') }}" class="export_btn">{{ get_phrase('Create') }}</a>
                    </div>
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
                                <th class="text-center">{{ get_phrase('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($jobs as $job)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $job->title }}</td>
                                    <td>{{ $job->company }}</td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-primary" href="{{ route('admin.job.edit', $job->id) }}">{{ get_phrase('Edit') }}</a>
                                        <a class="btn btn-sm btn-danger" onclick="return confirm('{{ get_phrase('Are You Sure Want To Delete?') }}')" href="{{ route('admin.delete.job', $job->id) }}">{{ get_phrase('Delete') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">{{ get_phrase('No data found') }}</td>
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
