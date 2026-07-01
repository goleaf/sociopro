<!DOCTYPE html>
<html lang="">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?php echo e($systemName); ?></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?php echo e(get_system_logo_favicon($systemFavicon,'favicon')); ?>">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/bootstrap.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/fontawesome/all.min.css')); ?>">
    <!-- CSS Library -->
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/owl.carousel.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/nice-select.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/venobox.min.css')); ?>">

    <!-- Style css -->
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/style.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('assets/frontend/css/own.css')); ?>">

   
</head>

<body class="bg-white login">


<?php
    $homeUrl = Auth::check() && Route::has('timeline') ? route('timeline') : route('login');
?>

<!-- header -->
    <header class="header header-default py-3">
    <nav class="navigation">
        <div class="container">
            <div class="row">
                <div class="col-auto col-lg-6">
                    <div class="logo-branding mt-1">
                        <a class="navbar-brand d-xs-hidden" href="<?php echo e($homeUrl); ?>">
                            <img src="<?php echo e(get_system_logo_favicon($systemLightLogo,'light')); ?>" height="35px" class="max-width-250px d-xs-hidden" alt="logo" />
                        </a>

                        <a class="navbar-brand d-block" href="<?php echo e($homeUrl); ?>">
                            <img src="<?php echo e(get_system_logo_favicon($systemLightLogo,'favicon')); ?>" height="35px" class="max-width-250px d-hidden d-xs-show mt--5px" alt="logo" />
                        </a>
                    </div>
                </div>

                <div class="col-auto col-lg-6 ms-auto">
                    <div class="login-btns ms-5">
                        <a href="<?php echo e(route('login')); ?>" class="btn <?php if(Route::currentRouteName() == 'login'): ?> active <?php endif; ?>"><?php echo e(__('Login')); ?></a>
                        <?php if(get_settings('public_signup') == 1): ?>
                            <a href="<?php echo e(route('register')); ?>" class="btn <?php if(Route::currentRouteName() == 'register'): ?> active <?php endif; ?>"><?php echo e(__('Sign up')); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
<!-- Header End -->
<?php /**PATH /Users/andrejprus/Herd/sociopro/resources/views/auth/layout/header.blade.php ENDPATH**/ ?>