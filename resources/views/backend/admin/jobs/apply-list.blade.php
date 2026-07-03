<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('All Apply List') }}</h4>
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
                                <th>{{ get_phrase('Email') }}</th>
                                <th>{{ get_phrase('Phone') }}</th>
                                <th class="text-center">{{ get_phrase('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($all_list as $application)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $application->email }}</td>
                                    <td>{{ $application->phone }}</td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-primary" href="{{ route('admin.job.pdf.download', $application->id) }}">{{ get_phrase('Download') }}</a>
                                        <a class="btn btn-sm btn-danger" onclick="return confirm('{{ get_phrase('Are You Sure Want To Delete?') }}')" href="{{ route('admin.job.apply.list-delete', $application->id) }}">{{ get_phrase('Delete') }}</a>
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
