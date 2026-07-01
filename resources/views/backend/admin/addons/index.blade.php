<div class="row g-0">
    <div class="col-12 px-3">
        <div class="eSection-wrap-2 d-flex justify-content-between mt-3">

            <div class="d-flex align-items-center">
                <div class="title">
                    <i class="fa-solid fa-puzzle-piece text-black"></i>
                    <span class="text-black">Addon manager</span>
                </div>
            </div>

            {{-- addon installation area --}}
            <a href="javascript:void(0)"
                onclick="ajaxModal('{{ route('load_modal_content', ['view_path' => 'backend.admin.addons.install_form']) }}', '{{ get_phrase('Install addon') }}', 'modal-md');"
                data-bs-toggle="modal" class="btn btn-primary btn-sm py-2" id="addon-install-btn">
                <i class="fa fa-plus-circle m-0 me-1"></i>
                <div class="d-none d-md-inline-block">{{ get_phrase('Install addon') }}</div>
            </a>
        </div>

        <div class="eSection-wrap-2 mt-3">
            <div class="table-responsive">
                <table class="table eTable " id="">
                    <thead>
                        <tr>
                            <th scope="col">{{ get_phrase('Sl No') }}</th>
                            <th scope="col">{{ get_phrase('Name') }}</th>
                            <th scope="col">{{ get_phrase('Version') }}</th>
                            <th scope="col">{{ get_phrase('Status') }}</th>
                            <th scope="col" class="text-center">{{ get_phrase('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($addons as $key => $addon)
                            <tr>
                                <th scope="row">
                                    <p class="row-number">{{ ++$key }}</p>
                                </th>
                                <td>
                                    <div class="dAdmin_info_name min-w-100px">
                                        <span>{{ $addon->title }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="dAdmin_info_name min-w-100px">
                                        <span>{{ $addon->version }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="dAdmin_info_name min-w-100px">
                                        @if ($addon->status == 1)
                                            <span class="badge rounded-pill bg-success">Active</span>
                                        @else
                                            <span class="badge rounded-pill bg-danger">Deactivated</span>
                                        @endif
                                    </div>
                                </td>


                                <td class="text-center">
                                    <div class="d-flex justify-content-center">
                                        <div class="adminTable-action m-0 d-flex justify-content-center">
                                            <button type="button"
                                                class="eBtn eBtn-black dropdown-toggle table-action-btn-2"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                {{ get_phrase('Actions') }}
                                            </button>
                                            <ul
                                                class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                                                @if ($addon->status == 1)
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('addon.status', ['status' => 'deactivate', 'id' => $addon->id]) }}">{{ get_phrase('Deactivate') }}</a>
                                                    </li>
                                                @else
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('addon.status', ['status' => 'activate', 'id' => $addon->id]) }}">{{ get_phrase('Activate') }}</a>
                                                    </li>
                                                @endif
                                                <li><a class="dropdown-item"
                                                        onclick="return confirm('{{ get_phrase('Are You Sure Want To Delete?') }}')"
                                                        href="{{ route('addon.delete', $addon->id) }}">{{ get_phrase('Delete') }}</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>


<script>
    $(document).ready(function() {
        $('#addon_manager').click(function(e) {
            e.preventDefault();
            $('.addon_manager_dropdown').toggleClass('d-none');
        });
    });
</script>
@include('frontend.common_scripts')
