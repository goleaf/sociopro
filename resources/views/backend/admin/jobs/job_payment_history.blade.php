<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Job Payment History') }}</h4>
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
                                <th>{{ get_phrase('Amount') }}</th>
                                <th>{{ get_phrase('Currency') }}</th>
                                <th class="text-center">{{ get_phrase('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($job_history as $history)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $history->amount }}</td>
                                    <td>{{ $history->currency }}</td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-danger" onclick="return confirm('{{ get_phrase('Are You Sure Want To Delete?') }}')" href="{{ route('admin.delete.job.payment.history', $history->id) }}">{{ get_phrase('Delete') }}</a>
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
