@include('auth.layout.header')

<!-- Main Start -->
<main class="main my-4 p-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="login-img">
                    <img class="img-fluid" src="{{ asset('assets/frontend/images/login.png') }}" alt="">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="login-txt ms-5">
                    <h3>{{ get_phrase('Contact Us') }}</h3>

                    <form action="{{ route('contact.send') }}" method="POST">
                        @csrf

                        <div class="form-group form-name">
                            <label for="contact-name">{{ get_phrase('Full Name') }}</label>
                            <input
                                id="contact-name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="{{ get_phrase('Your full name') }}"
                                autocomplete="name"
                                required
                                @error('name')
                                    aria-invalid="true"
                                    aria-describedby="contact-name-error"
                                @else
                                    aria-invalid="false"
                                @enderror
                            >
                        </div>
                        @error('name')
                            <p class="text-danger" id="contact-name-error" role="alert">{{ $message }}</p>
                        @enderror

                        <div class="form-group form-email">
                            <label for="contact-email">{{ get_phrase('Email') }}</label>
                            <input
                                id="contact-email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="{{ get_phrase('Example@domain.com') }}"
                                autocomplete="email"
                                required
                                @error('email')
                                    aria-invalid="true"
                                    aria-describedby="contact-email-error"
                                @else
                                    aria-invalid="false"
                                @enderror
                            >
                        </div>
                        @error('email')
                            <p class="text-danger" id="contact-email-error" role="alert">{{ $message }}</p>
                        @enderror

                        <div class="form-group form-email">
                            <label for="contact-subject">{{ get_phrase('Subject') }}</label>
                            <input
                                id="contact-subject"
                                type="text"
                                name="subject"
                                value="{{ old('subject') }}"
                                placeholder="{{ get_phrase('Subject') }}"
                                required
                                @error('subject')
                                    aria-invalid="true"
                                    aria-describedby="contact-subject-error"
                                @else
                                    aria-invalid="false"
                                @enderror
                            >
                        </div>
                        @error('subject')
                            <p class="text-danger" id="contact-subject-error" role="alert">{{ $message }}</p>
                        @enderror

                        <div class="form-group">
                            <label for="contact-details">{{ get_phrase('Details') }}</label>
                            <textarea
                                id="contact-details"
                                name="details"
                                placeholder="{{ get_phrase('Write In Details') }}"
                                class="bg-secondary border2px-c4c4c4"
                                cols="30"
                                rows="10"
                                required
                                @error('details')
                                    aria-invalid="true"
                                    aria-describedby="contact-details-error"
                                @else
                                    aria-invalid="false"
                                @enderror
                            >{{ old('details') }}</textarea>
                        </div>
                        @error('details')
                            <p class="text-danger" id="contact-details-error" role="alert">{{ $message }}</p>
                        @enderror

                        <input class="btn btn-primary my-3" type="submit" name="submit" id="submit" value="{{ get_phrase('Contact') }}">
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- Main End -->

@include('auth.layout.footer')
