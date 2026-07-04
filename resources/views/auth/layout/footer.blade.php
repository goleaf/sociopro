
    <!--Javascript
        ========================================================-->
        <script src="{{ asset('assets/frontend/js/jquery-3.6.0.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/owl.carousel.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/venobox.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/timepicker.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/jquery.datepicker.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/jquery.nice-select.min.js') }}"></script>
        <script src="{{ asset('assets/frontend/js/custom.js') }}"></script>

        <script src="{{ asset('assets/authentication/moment.min.js') }}"></script>
        <script src="{{ asset('assets/authentication/moment-timezone-with-data.js') }}"></script>

        <script>
            'use strict';

            document.addEventListener('DOMContentLoaded', function() {
                var termsCheckbox = document.getElementById('exampleCheck1');
                var submitButton = document.getElementById('submit');
                var syncTermsSubmitState = function() {
                    if (! termsCheckbox || ! submitButton) {
                        return;
                    }

                    var acceptedTerms = termsCheckbox.checked;

                    submitButton.classList.toggle('disabled', ! acceptedTerms);
                    submitButton.disabled = ! acceptedTerms;
                    submitButton.setAttribute('aria-disabled', acceptedTerms ? 'false' : 'true');
                };

                if (termsCheckbox) {
                    termsCheckbox.addEventListener('change', syncTermsSubmitState);
                    syncTermsSubmitState();
                }

                document.querySelectorAll('[data-password-toggle-target]').forEach(function(toggleButton) {
                    var passwordToggleInput = document.getElementById(toggleButton.getAttribute('data-password-toggle-target'));
                    var passwordToggleIcon = toggleButton.querySelector('i');

                    if (! passwordToggleInput) {
                        return;
                    }

                    toggleButton.addEventListener('click', function() {
                        var isHidden = passwordToggleInput.type === 'password';

                        passwordToggleInput.type = isHidden ? 'text' : 'password';
                        toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                        toggleButton.setAttribute('aria-label', isHidden ? toggleButton.getAttribute('data-hide-label') : toggleButton.getAttribute('data-show-label'));

                        if (passwordToggleIcon) {
                            passwordToggleIcon.classList.toggle('fa-eye', ! isHidden);
                            passwordToggleIcon.classList.toggle('fa-eye-slash', isHidden);
                        }
                    });
                });

                var timezoneInput = document.getElementById('timezone');

                if (timezoneInput && window.moment && window.moment.tz) {
                    timezoneInput.value = window.moment.tz.guess();
                }
            });
        </script>
    </body>
    
    </html>
