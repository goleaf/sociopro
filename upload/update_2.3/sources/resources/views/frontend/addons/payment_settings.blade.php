<div class="tab-pane fade show active" id="payment_settings" role="tabpanel" aria-labelledby="payment_settings-tab">


    <div class="payment-settings">
        <div class="container p-0">
            <div class="row">
                <div class="col-xl-12 order-xl-1 order-2">
                    <form action="{{ route('save.payment.settings') }}" method="post" class=" create-article payform">
                        @csrf

                        <div class="payment-type bg-white radius-8 p-3">

                            <div
                                class="border-bottom border-dark pb-3 text-dark opacity-75 d-flex justify-content-between">
                                <h5 class="m-0">{{get_phrase('Razorpay')}}</h5>
                            </div>

                            <div class="input-wrap pb-4 pb-sm-3 mt-sm-3">
                                <div class="row">
                                    <div class="col-12 ">
                                      <div class="form-group">
                                         <label class="form-label d-block m-0">{{get_phrase('Key id')}}</label>
                                         <input type="text" class="form-control eForm-control"
                                            aria-label="razorpay key id" name="raz_key_id"
                                            value="{{ $payment_settings->raz_key_id }}" />
                                      </div>
                                      <div class="form-group">
                                        <label class="form-label d-block m-0">{{get_phrase('Secret
                                            key')}}</label>
                                         <input type="text" class="form-control eForm-control"
                                            aria-label="razorpay secret key" name="raz_secret_key"
                                            value="{{ $payment_settings->raz_secret_key }}" />
                                      </div>
                                      <div class="form-group">
                                        <label class="form-label d-block m-0">{{get_phrase('Theme
                                            color')}}</label>
                                         <input type="text" class="form-control eForm-control"
                                         aria-label="theme color" name="theme_color"
                                         value="{{ $payment_settings->theme_color }}" />
                                         <span class="text-danger color-note">{{get_phrase('*Please enter HEX color
                                            code.')}}</span>
                                      </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div class="payment-type bg-white radius-8 p-3 mt-12">

                            <div  class="border-bottom border-dark pb-3 text-dark opacity-75 d-flex justify-content-between">
                                <h5 class="m-0">{{get_phrase('Stripe')}}</h5>
                                <div class="form-check form-switch d-flex align-items-center align-items-center gap-3 p-0 m-0">
                                    <p class="m-0">{{get_phrase('Live mode')}}</p>
                                    <input class="form-check-input d-block p-0 m-0 live" type="checkbox"
                                        name="stripe_live" @if ($payment_settings->stripe_live) checked @endif>
                                </div>
                            </div>

                            <div class="input-wrap pb-4 pb-sm-3 mt-sm-3">
                                <div class="row">
                                    <div class="col-12">
                                       <div class="form-group">
                                           <label class="form-label d-block m-0">{{get_phrase('Public key')}}</label>
                                           <input type="text" class="form-control eForm-control"
                                              aria-label="stripe public key" name="stripe_public_key"
                                               value="{{ $payment_settings->stripe_public_key }}" />
                                       </div>
                                       <div class="form-group">
                                          <label class="form-label d-block m-0">{{get_phrase('Secret key')}}</label>
                                          <input type="text" class="form-control eForm-control"
                                          aria-label="stripe secret key" name="stripe_secret_key"
                                          value="{{ $payment_settings->stripe_secret_key }}" />
                                       </div>
                                       <div class="form-group">
                                          <label class="form-label d-block m-0">{{get_phrase('Public live key')}}</label>
                                          <input type="text" class="form-control eForm-control"
                                            aria-label="stripe public live key" name="stripe_public_live_key"
                                            value="{{ $payment_settings->stripe_public_live_key }}" />
                                       </div>
                                       <div class="form-group">
                                          <label class="form-label d-block m-0">{{get_phrase('Secret live key')}}</label>
                                          <input type="text" class="form-control eForm-control"
                                          aria-label="stripe secret live key" name="stripe_secret_live_key"
                                          value="{{ $payment_settings->stripe_secret_live_key }}" />
                                       </div>
                                    </div>
                                </div>
                            </div>
                        </div>




                        <div class="payment-type bg-white radius-8 p-3 mt-12">

                            <div  class="border-bottom border-dark pb-3 text-dark opacity-75 d-flex justify-content-between">
                                <h5 class="m-0">{{get_phrase('Paypal')}}</h5>
                                <div  class="form-check form-switch d-flex align-items-center align-items-center gap-3 p-0 m-0">
                                    <p class="m-0">{{get_phrase('Live mode')}}</p>
                                    <input class="form-check-input d-block p-0 m-0 live" type="checkbox"
                                        id="paypal_live" name="paypal_live"
                                        @if ($payment_settings->paypal_live) checked @endif>
                                </div>
                            </div>

                            <div class="input-wrap pb-4 pb-sm-3 mt-sm-3">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label d-block m-0">{{get_phrase('Sandbox
                                                client id')}}</label>
                                            <input type="text" class="form-control eForm-control"
                                                aria-label="paypal client id" name="paypal_client_id"
                                                value="{{ $payment_settings->paypal_client_id }}" />
                                         </div>
                                         <div class="form-group">
                                            <label class="form-label d-block m-0">{{get_phrase('Sandbox secret key')}}</label>
                                            <input type="text" class="form-control eForm-control"
                                            aria-label="paypal sandbox secret key" name="paypal_secret_key"
                                            value="{{ $payment_settings->paypal_secret_key }}" />
                                         </div>
                                         <div class="form-group">
                                            <label class="form-label d-block m-0">{{get_phrase('Production client id')}}</label>
                                            <input type="text" class="form-control eForm-control"
                                            aria-label="production client id" name="paypal_production_client_id"
                                            value="{{ $payment_settings->paypal_production_client_id }}" />
                                         </div>
                                         <div class="form-group">
                                            <label class="form-label d-block m-0">Production secret key</label>
                                            <input type="text" class="form-control eForm-control"
                                            aria-label="production secret key" name="paypal_production_secret_key"
                                            value="{{ $payment_settings->paypal_production_secret_key }}" />
                                         </div>

                                    </div>
                                </div>
                            </div>
                        </div>




                        <div class="payment-type bg-white radius-8 p-3 mt-12">
                            <div  class="border-bottom border-dark pb-3 text-dark opacity-75 d-flex justify-content-between">
                                <h5 class="m-0">{{get_phrase('Flutterwave')}}</h5>
                                <div class="form-check form-switch d-flex align-items-center align-items-center gap-3 p-0 m-0">
                                    <p class="m-0">{{get_phrase('Live mode')}}</p>
                                    <input class="form-check-input d-block p-0 m-0 live" type="checkbox"
                                        id="flutterwave_live" name="flutterwave_live"
                                        @if ($payment_settings->flutterwave_live) checked @endif>
                                </div>
                            </div>

                            <div class="input-wrap pb-4 pb-sm-3 mt-sm-3">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="form-label d-block m-0">{{get_phrase('Public key')}}</label>
                                            <input type="text" class="form-control eForm-control"
                                            aria-label="flutterwave public key" name="flutterwave_public_key"
                                            value="{{ $payment_settings->flutterwave_public_key }}" />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label d-block m-0">{{get_phrase('Secret key')}}</label>
                                            <input type="text" class="form-control eForm-control"
                                            aria-label="flutterwave  secret key" name="flutterwave_secret_key"
                                            value="{{ $payment_settings->flutterwave_secret_key }}" />
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label d-block m-0">{{get_phrase('Encryption key')}}</label>
                                            <input type="text" class="form-control eForm-control"
                                            aria-label="flutterwave encryption key" name="flutterwave_encryption_key"
                                            value="{{ $payment_settings->flutterwave_encryption_key }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn common_btn py-2 mt-3 px-3">{{get_phrase('Save settings')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
