{{-- ✅ Cookie Consent Modal --}}
<div id="cookieConsent"
     class="cookie-consent position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3 p-md-4 shadow-lg"
     style="z-index: 9999; display: none;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <h5 class="mb-2 font-neue">{{ __('trans.cookie_title') }}</h5>
                <p class="mb-0 small">
                    {{ __('trans.cookie_description') }}
                    <a href="" class="text-white text-decoration-underline">
                        {{ __('trans.learn_more') }}
                    </a>
                </p>
            </div>
            <div class="col-lg-4 d-flex gap-2 justify-content-lg-end">
                <button type="button" class="btn btn-outline-light font-neue" id="cookieDecline">
                    {{ __('trans.decline') }}
                </button>
                <button type="button" class="btn btn-primary font-neue" id="cookieAccept">
                    {{ __('trans.accept_cookies') }}
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .cookie-consent {
        animation: slideUp 0.5s ease-out;
        border-top: 3px solid var(--bs-primary);
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .cookie-consent.hide {
        animation: slideDown 0.5s ease-out;
    }

    @keyframes slideDown {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(100%);
            opacity: 0;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const cookieConsent = document.getElementById('cookieConsent');
        const acceptBtn = document.getElementById('cookieAccept');
        const declineBtn = document.getElementById('cookieDecline');

        // ✅ Check if user already responded
        if (!localStorage.getItem('cookieConsent')) {
            setTimeout(() => {
                cookieConsent.style.display = 'block';
            }, 1000); // Show after 1 second
        }

        // ✅ Accept cookies
        acceptBtn.addEventListener('click', function () {
            localStorage.setItem('cookieConsent', 'accepted');
            hideCookieConsent();

            // ✅ Enable analytics/tracking here
            // gtag('consent', 'update', { 'analytics_storage': 'granted' });
        });

        // ✅ Decline cookies
        declineBtn.addEventListener('click', function () {
            localStorage.setItem('cookieConsent', 'declined');
            hideCookieConsent();
        });

        function hideCookieConsent() {
            cookieConsent.classList.add('hide');
            setTimeout(() => {
                cookieConsent.style.display = 'none';
            }, 500);
        }
    });
</script>