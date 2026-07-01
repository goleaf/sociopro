<style>
    .eForm-control:focus-visible {
        outline: none !important;
        box-shadow: 0 !important
    }
</style>

<form method="POST" enctype="multipart/form-data" class="d-block event-form" action="{{ route('addon.install') }}">
    @csrf
    @if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1')
        {{-- running on local server --}}
    @else
        <div class="fpb-7">
            <label for="purchase_code" class="eForm-label">Purchase code</label>
            <input type="text" class="eForm-control" name="purchase_code" placeholder="Enter purchase code"
                id="purchase_code" required>
        </div>
    @endif
    <div class="fpb-7">
        <label for="formFileSm" class="eForm-label">Select file</label>
        <input class="form-control eForm-control-file" id="formFileSm" type="file" name="file" accept=".zip"
            required>
    </div>
    <button type="submit" class="btn-form float-end mt-2">
        <i class="fa-solid fa-cloud-arrow-down me-2"></i>
        {{ get_phrase('Install') }}
    </button>
</form>
