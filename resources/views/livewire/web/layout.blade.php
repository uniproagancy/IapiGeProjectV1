<!DOCTYPE html>
<html lang="en" data-bs-theme="light" data-pwa="true">
<head>
    @section('seo')
        <title>Iapi.Ge - იაფი მაღაზია</title>
        <meta name="description" content="Iapi.Ge">
        <meta name="keywords"
              content="Iapi.ge, იაფი,ჯი, იაფი, მაღაზია, ტექნიკა, ტელეფონები, სმარტფონები, კომპიუტერული ტექნიკა, მაცივრები, გათბობის სისტემები, Phones, Tech, PC, Refrigerators, Air cond,">
    @show
    @yield('og_tags')
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{--    <link rel="icon" type="image/png" href="assets/app-icons/icon-32x32.png" sizes="32x32">--}}
    {{--    <link rel="apple-touch-icon" href="assets/app-icons/icon-180x180.png">--}}

    <link rel="preload" href="{{ asset('web-assets/fonts/inter-variable-latin.woff2') }}" as="font" type="font/woff2"
          crossorigin>
    <link rel="preload" href="{{ asset('web-assets/icons/cartzilla-icons.woff2') }}" as="font" type="font/woff2"
          crossorigin>
    <link rel="stylesheet" href="{{ asset('web-assets/icons/cartzilla-icons.min.css') }}">
    <link rel="shortcut icon" type="image/x-icon"
              href="{{ asset('web-assets/img/logo.png') }}">

    <link rel="stylesheet" href="{{ asset('web-assets/vendor/swiper/swiper-bundle.min.css') }}">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="{{ asset('web-assets/css/theme.min.css') }}" id="theme-styles">
    @yield('page_css')
    @livewireStyles

    <link rel="stylesheet" href="{{ asset('web-assets/css/style.css') }}">


    
</head>
<body>
@yield('fb_pixel')
@include('livewire.web.partials.header.header')
@include('livewire.web.search.search-offcanvas')
{{ $slot }}

<div id="site-loader">
    <div class="loader-content">
        <div class="spinner"></div>
    </div>
</div>

<livewire:web.cart.shopping-cart-offcanvas/>
@include('livewire.web.partials.category-offcanvas')

<style>
    #site-loader {
        position: fixed;
        inset: 0;
        background: rgba(255, 255, 255, 0.95);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.4s ease, visibility 0.4s ease;
    }

    #site-loader.hidden {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    .loader-content {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .spinner {
        width: 48px;
        height: 48px;
        border: 3px solid #e9ecef;
        border-top-color: #ff6900; /* შენი brand ფერი */
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
</style>
<style>
    .countInput {
        background-color: #fff;
        border: var(--cz-border-width) solid #cad0d9;
        border-radius: var(--cz-border-radius);
        display: inline-flex;
        overflow: hidden;
        transform: translateZ(0);
    }

    .countInput .form-control {
        -moz-appearance: textfield;
        -webkit-appearance: textfield;
        appearance: textfield;
        background-color: transparent;
        border: 0;
        border-radius: 0;
        font-weight: 500;
        padding: 0 .25rem;
        text-align: center;
        width: 2.5rem;
    }

    .countInput .form-control::-webkit-inner-spin-button,
    .countInput .form-control::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .countInput .btn {
        border: 0;
        border-radius: 0;
    }

    .countInput .btn:not(.btn-primary) {
        --cz-btn-hover-color: var(--cz-component-hover-color);
        --cz-btn-hover-bg: var(--cz-secondary-bg);
        --cz-btn-active-bg: var(--cz-secondary-bg);
    }

    .countInput .btn-group-sm > .btn + .form-control,
    .countInput .btn-sm + .form-control {
        width: 2rem;
    }

    .countInput .btn-group-lg > .btn + .form-control,
    .countInput .btn-lg + .form-control {
        width: 3rem;
    }

    .countInput.disabled {
        background-color: var(--cz-tertiary-bg);
        border-color: var(--cz-border-color);
        border-style: dashed;
    }

    .countInput-collapsible.collapsed .form-control,
    .countInput-collapsible.collapsed [data-decrement] {
        display: none;
    }

    /* Dark theme support */
    [data-bs-theme=dark] .countInput:not([data-bs-theme=light]) {
        background-color: transparent;
        border-color: #4e5562;
    }
</style>
<script>
    window.addEventListener('load', () => {
        document.getElementById('site-loader')?.classList.add('hidden');
    });

    document.addEventListener('livewire:navigating', () => {
        document.getElementById('site-loader')?.classList.remove('hidden');
    });

    document.addEventListener('livewire:navigated', () => {
        document.getElementById('site-loader')?.classList.add('hidden');
    });
</script>

@include('livewire.web.partials.footer.footer')
@include('livewire.web.partials.cookies')
@include('livewire.web.partials.bottom-menu')

@guest
    <livewire:web.auth.login-modal/>
    <livewire:web.auth.register-modal/>
    <livewire:web.auth.forgot-modal />
@endguest

@livewireScripts
<script src="{{ asset('web-assets/vendor/swiper/swiper-bundle.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

@yield('page_scripts')
<script src="{{ asset('web-assets/js/theme.min.js') }}"></script>
<!--Start of Tawk.to Script-->
<script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/686bd1bca86aec190ca6b61d/1iviimin6';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
    })();
</script>
<!--End of Tawk.to Script-->
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('ui:error', (data) => {
            Toastify({
                text: data.message,
                duration: 3000
            }).showToast();
        });

        Livewire.on('ui:success', (data) => {
            Toastify({
                text: data.message,
                style: {
                    background: "linear-gradient(to right, #00b09b, #96c93d)",
                },
                duration: 3000
            }).showToast();
        });
    });
</script>
<script>
    function pixelAddToCart(productId, price, name, eventId) {
        fbq('track', 'AddToCart', {
            content_ids: [productId],
            content_name: name,
            content_type: 'product',
            value: price,
            currency: 'GEL',
            event_id: eventId
        });

        // event_id ვაგზავნით Livewire-სთვის
        Livewire.dispatch('storePixelEventId', { eventId });
    }
</script>
<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-icon');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }

    document.addEventListener('livewire:init', () => {
        Livewire.on('close-modal', (modalId) => {
            const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
            if (modal) {
                modal.hide();
                if (modalId[0] === 'loginModal') {
                    location.reload();
                }
            }
        });
    });
</script>
<style>
    .modal-dialog-scrollable .modal-body {
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }

    .btn-link {
        text-decoration: none;
        padding: 0;
    }

    .btn-link:hover {
        text-decoration: none;
    }

    .form-control:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.15);
    }

    .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .position-relative .btn-link {
        z-index: 10;
    }
</style>
</body>
</html>
