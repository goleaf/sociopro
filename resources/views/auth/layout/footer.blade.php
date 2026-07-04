
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

            $( document ).ready(function() {
                $('#exampleCheck1').change(function() {
                    if(this.checked) {
                        $('#submit').removeClass("disabled");
                    }else{
                        $('#submit').addClass("disabled");
                    }
                });

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

                var timezone = moment.tz.guess();
                $('#timezone').val(timezone);
            });
        </script>
    </body>
    
    </html>
