
<!DOCTYPE html>
<html class="loading dark-layout" lang="ka" data-layout="dark-layout" data-textdirection="ltr">
<head>
    <title>Dashboard ecommerce - Vuexy - Bootstrap HTML admin template</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=0,minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="apple-touch-icon" href="{{ asset('dashboard-assets/images/ico/apple-icon-120.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('dashboard-assets/images/ico/favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet') }}">

    <link rel="apple-touch-icon" href="../../../app-assets/images/ico/apple-icon-120.png">
    <link rel="shortcut icon" type="image/x-icon" href="../../../app-assets/images/ico/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;1,400;1,500;1,600" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/vendors/css/vendors.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/bootstrap.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/bootstrap-extended.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/colors.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/components.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/themes/dark-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/themes/bordered-layout.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/themes/semi-dark-layout.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/core/menu/menu-types/horizontal-menu.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/pages/page-misc.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('dashboard-assets/css/style.css') }}">
</head>
<body class="horizontal-layout horizontal-menu blank-page navbar-floating footer-static  " data-open="hover" data-menu="horizontal-menu" data-col="blank-page">
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <div class="misc-wrapper">
                <a class="brand-logo" href="{{ route('dashboard.main') }}">
                    <h2 class="brand-text text-primary ms-1">ECOMMERCE</h2>
                </a>
                <div class="misc-inner p-2 p-sm-3">
                    <div class="w-100 text-center">
                        <h2 class="mb-1 font-neue">უპს 😖, დაფიქსირდა შეცდომა 🕵🏻‍♀️</h2>
                        <p class="mb-2">    {{ $message ?? '' }}</p>
                        <button class="btn btn-primary mb-2 btn-sm-block" onclick="history.back()">უკან დაბრუნება</button>
                        <img class="img-fluid" src="{{ asset('dashboard-assets/images/pages/error-dark.svg') }}" alt="Error page" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('dashboard-assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('dashboard-assets/vendors/js/ui/jquery.sticky.js') }}"></script>
<script src="{{ asset('dashboard-assets/js/core/app-menu.js') }}"></script>
<script src="{{ asset('dashboard-assets/js/core/app.js') }}"></script>
<script>
    $(window).on('load', function() {
        if (feather) {
            feather.replace({
                width: 14,
                height: 14
            });
        }
    })
</script>
</body>
</html>