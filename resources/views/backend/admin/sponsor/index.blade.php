<div class="main_content">
    <!-- Mani section header and breadcrumb -->
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('All Sponsors') }}</h4>

                    </div>

                    <div class="export-btn-area">
                        <a href="{{ route('admin.create.sponsor') }}" class="export_btn" data-bs-toggle="tooltip"
                            data-bs-placement="top" data-bs-custom-class="custom-tooltip"
                            data-bs-title="Create Ads">{{ get_phrase('Create') }}</a>
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
                    <table class="table eTable " id="">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ get_phrase('Image') }}</th>
                                <th scope="col">{{ get_phrase('Title') }}</th>
                                <th scope="col">{{ get_phrase('Start Date') }}</th>
                                <th scope="col">{{ get_phrase('Status') }}</th>
                                <th scope="col" class="text-center">{{ get_phrase('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sponsors as $key => $sponsor)
                                <tr>
                                    <td>{{ ++$key }}</td>
                                    <td>
                                        <a href="{{ $sponsor->ext_url }}" target="_blank"><img class="image-fluid"
                                                width="80px"
                                                src="{{ get_image('storage/sponsor/thumbnail/' . $sponsor->image) }}"></a>
                                    </td>
                                    <td>{{ $sponsor->name }}</td>
                                    <td>{{ date_formatter($sponsor->start_date) }}</td>
                                    <td>

                                        @if ($sponsor->status != 1 && $sponsor->start_date == $sponsor->end_date)
                                            {{-- this is admin ad --}}
                                            <span class="badge bg-success">{{ get_phrase('Active') }}</span>
                                        @else
                                            {{-- this is user ad --}}
                                            @if ($sponsor->status != 1)
                                                <span
                                                    class="badge bg-secondary text-capitalize">{{ get_phrase('Disabled') }}</span>
                                            @elseif(strtotime($sponsor->start_date) == strtotime($sponsor->end_date))
                                                <span
                                                    class="badge bg-primary">{{ get_phrase('Not yet published') }}</span>
                                            @elseif(strtotime($sponsor->end_date) < time())
                                                <span class="badge bg-danger">{{ get_phrase('Expired') }}</span>
                                            @else
                                                <span class="badge bg-success">{{ get_phrase('Active') }}</span>
                                            @endif
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        <div class="adminTable-action me-auto">
                                            <button type="button"
                                                class="eBtn eBtn-black dropdown-toggle table-action-btn-2"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ get_phrase('Actions') }}
                                            </button>
                                            <ul
                                                class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">

                                                @if ($sponsor->status != 1 && $sponsor->start_date == $sponsor->end_date)
                                                    {{-- this is admin ad, no activation needs here --}}
                                                @else
                                                    {{-- this is user ad, enable activation status --}}
                                                    @if ($sponsor->status == 1)
                                                        @if (strtotime($sponsor->end_date) > time())
                                                            {{-- <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('admin.ad.status', ['type' => 'deactive', 'id' => $sponsor->id]) }}">
                                                                    {{ get_phrase('Deactive') }}
                                                                </a>
                                                            </li> --}}
                                                        @endif
                                                    @else
                                                        {{-- <li>
                                                            <a class="dropdown-item"
                                                                href="{{ route('admin.ad.status', ['type' => 'active', 'id' => $sponsor->id]) }}">
                                                                {{ get_phrase('Active') }}
                                                            </a>
                        
                                                        </li> --}}
                                                    @endif
                                                @endif


                                                <li>
                                                    <a class="dropdown-item"
                                                        href="{{ route('admin.edit.sponsor', $sponsor->id) }}">
                                                        {{ get_phrase('Edit') }}
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item"
                                                        onclick="return confirm('{{ get_phrase('Are You Sure Want To Delete?') }}')"
                                                        href="{{ route('admin.delete.sponsor', $sponsor->id) }}">
                                                        {{ get_phrase('Delete') }}
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end">
                        {!! $sponsors->links() !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Admin area -->


    <!-- Start Footer -->
    @include('backend.footer')
    <!-- End Footer -->
</div>
