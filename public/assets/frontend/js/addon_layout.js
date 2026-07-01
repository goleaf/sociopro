$(document).ready(function () {
    $('.expand-btn').click(function (e) {
        e.preventDefault();
        $(this).toggleClass('active');

        if (!$(this).hasClass('active')) {
            $(this).addClass('btn-slide-left');
            $(this).removeClass('btn-slide-right');
        } else {
            $(this).addClass('btn-slide-right');
            $(this).removeClass('btn-slide-left');
        }

        if ($('.menu-panel').hasClass('slide-left')) {
            $('.menu-panel').removeClass('slide-left');
            $('.menu-panel').addClass('slide-right');
        } else {
            $('.menu-panel').addClass('slide-left');
            $('.menu-panel').removeClass('slide-right');
        }
    });

    $('.toggle-menu').click(function (e) {
        e.preventDefault();
        var menu = $('.menu-panel');
        menu.toggleClass('active');
    });

    $('.menu-title').click(function (e) {
        e.preventDefault();

        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');

        if ($(this).parent().hasClass('active')) {
        } else {
            $('.dropdown-menu-item').removeClass('active');
        }
    });
    $('.dropdown-menu-title').click(function (e) {
        e.preventDefault();

        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');
    });

    $('.dropdown-submenu-item a').click(function (e) {
        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');

    });

    $('.creator-menu-item').click(function (e) {
        $('.creator-menu-item').removeClass('active');
        $(this).addClass('active');
    });

    $('.post-type').click(function (e) {
        $(this).toggleClass('show-menu');
    });
});