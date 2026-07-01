<style>
.new_alert h4 {
	font-size: 16px;
	margin-bottom: 9px;
}
.new_alert p {
	font-size: 14px;
	margin-bottom: 9px;
	color: #000;
}
.new_alert p a{
    color: #727cf5;
}
</style>


<div class="main_content">
    <!-- Mani section header and breadcrumb -->
    <div class="mainSection-title">
      <div class="row">
        <div class="col-12">
          <div
            class="d-flex justify-content-between align-items-center flex-wrap gr-15"
          >
            <div class="d-flex flex-column">
              <h4>{{ get_phrase('Update Zitsi Api keys') }}</h4>
              
            </div>
            <div class="export-btn-area">
              
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Start Admin area -->
    <div class="row">
      <div class="col-12 col-lg-6">
        <div class="eSection-wrap-2">
            <div class="eForm-layouts">
            <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.zitsi.live.settings.update') }}">
                
                @csrf
                <div class="fpb-7">
                    <label for="account_email" class="eForm-label">{{ get_phrase('Account email*') }}</label>
                    <input type="text" class="form-control eForm-control" value="{{ get_settings('zitsi_configuration', true)['account_email'] }}" id="account_email" name="account_email" required="">
                </div>
                <div class="fpb-7">
                    <label for="jitsi_app_id" class="eForm-label">{{ get_phrase('Jitsi app id*') }}</label>
                    <input type="text" class="form-control eForm-control" value="{{ get_settings('zitsi_configuration', true)['jitsi_app_id'] }}" id="jitsi_app_id" name="jitsi_app_id" required="">
                </div>
                <div class="fpb-7">
                    <label for="jitsi_jwt" class="eForm-label">{{ get_phrase('Jwt token*') }}</small></label>
                        <input type="text" class="form-control eForm-control" value="{{ get_settings('zitsi_configuration', true)['jitsi_jwt'] }}" id="jitsi_jwt" name="jitsi_jwt" required="">
                </div>
                <div class="fpb-7 pt-2">
                    <button type="submit" class="btn-form">{{ get_phrase('Save') }}</button>
                </div>
            </form>
            </div>
        </div>
      </div>
      <div class="col-lg-6">
            <div class="new_alert alert alert-info" role="alert">
                <h4 class="alert-heading"><?php echo get_phrase('How to configure Jitsi API?'); ?></h4>
                <p>1. Login to <a href="https://jaas.8x8.vc" target="_blank">Jitsi as a Service</a></p>
                <p>2. Go to the <a href="https://jaas.8x8.vc/#/apikeys" target="_blank">API Keys</a>. Copy your Your AppID and generate a JWT from here.</p>
            </div>
      </div>
    </div>
    <!-- Start Footer -->
    @include('backend.footer')
    <!-- End Footer -->
  </div>



