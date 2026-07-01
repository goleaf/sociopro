<div class="main_content">
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4>{{ get_phrase('About this application') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-xl-8">
            <div class="eSection-wrap">
                <div class="row">
                    <div class="col-12 p-4">

                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Software version') }}
                            <span class="float-end">{{ $softwareVersion }}</span>
                        </p>
                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('PHP version') }}
                            <span class="float-end">{{ phpversion() }}</span>
                        </p>
                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Curl enable') }}
                            <span class="float-end">
                                @if ($curlEnabled)
                                    <span class="badge bg-success">{{ get_phrase('Enabled') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ get_phrase('disabled') }}</span>
                                @endif
                            </span>
                        </p>

                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Purchase code') }}
                            <span class="float-end">{{ $purchaseCode }}</span>
                        </p>

                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Product license') }}
                            @if ($application_details['product_license'] == 'valid')
                            <span
                                class="float-end badge bg-success text-capitalize">{{ get_phrase($application_details['product_license']) }}</span>
                            @else
                            <span
                                class="float-end badge bg-danger mt-1 text-capitalize">{{ get_phrase($application_details['product_license']) }}</span>
                            <button class="btn btn-primary float-end me-2 py-0 text-13px" data-bs-toggle="modal"
                                data-bs-target="#purchasecodeModal">{{ get_phrase('Enter valid purchase code') }}</button>
                            @endif
                        </p>
                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Customer support status') }}
                            <span class="float-end">
                                @if (strtolower($application_details['purchase_code_status']) == 'expired')
                                <span
                                    class="badge bg-danger float-end mt-1 text-capitalize">{{ get_phrase($application_details['purchase_code_status']) }}</span>
                                @elseif (strtolower($application_details['purchase_code_status']) == 'valid')
                                <span
                                    class="badge bg-success text-capitalize">{{ get_phrase($application_details['purchase_code_status']) }}</span>
                                @else
                                <span
                                    class="badge bg-danger text-capitalize">{{ get_phrase($application_details['purchase_code_status']) }}</span>
                                @endif
                            </span>
                        </p>
                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Support expiry date') }}

                            @if ($application_details['support_expiry_date'] != "invalid")
                            <span class="float-end">{{ $application_details['support_expiry_date'] }}</span>
                            @else
                            <span class="float-end"><span
                                    class="badge bg-danger">{{ ucfirst($application_details['support_expiry_date']) }}</span></span>
                            @endif
                        </p>
                        <p class="border-bottom mb-2 pb-2 text-13px">
                            <i class="bi bi-arrow-right-square me-3"></i> {{ get_phrase('Customer name') }}
                            @if ($application_details['customer_name'] != "invalid")
                            <span class="float-end">{{ $application_details['customer_name'] }}</span>
                            @else
                            <span class="float-end"><span
                                    class="badge bg-danger">{{ ucfirst($application_details['customer_name']) }}</span></span>
                            @endif
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @include('backend.footer')
</div>

<!-- Modal -->
<div class="modal fade" id="purchasecodeModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="exampleModalLabel">{{ get_phrase('Enter your purchase code') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('backend.admin.setting.save_purchase_code')
            </div>
        </div>
    </div>
</div>
