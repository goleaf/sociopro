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

    $('.menu-title').click(function (e) {
        e.preventDefault();

        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');

        // dropdown menu slide up
        $('.menu-item').find('.pc-dropdown-menu').slideUp();

        // dropdown submenu slide up
        $('.menu-item').find('.pc-dropdown-submenu').slideUp();

        // remove rotate class
        $('.menu-title').children('i').removeClass('rotate');

        // remove active class from dropdown menu
        $('.dropdown-menu-item').removeClass('active');


        if ($(this).parent().hasClass('active')) {
            $(this).next().slideDown();
            $(this).children('i').addClass('rotate');
        } else {
            $(this).next().slideUp();
            $(this).parent().siblings().find('.pc-dropdown-submenu').slideUp();
            $('.menu-title').children('i').removeClass('rotate');
            $('.dropdown-menu-title').children('i').removeClass('rotate');
        }
    });
    $('.dropdown-menu-title').click(function (e) {
        e.preventDefault();

        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');

        $('.dropdown-menu-item').find('.pc-dropdown-submenu').slideUp();
        $('.dropdown-menu-title').children('i').removeClass('rotate');

        if ($(this).parent().hasClass('active')) {
            $(this).next().slideDown();
            $(this).children('i').addClass('rotate');
        } else {
            $(this).next().slideUp();
            $(this).parent().find('.pc-dropdown-menu').slideUp();
            $('.dropdown-menu-title').children('i').removeClass('rotate');
        }
    });
});