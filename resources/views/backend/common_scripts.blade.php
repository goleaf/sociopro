<!-- NEW THME START FROM HERE -->

<!-- Google analytics -->
@if (! empty($googleAnalyticsId))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());

    gtag('config', '{{ $googleAnalyticsId }}');
</script>
@endif
<!-- Ended Google analytics -->

<!-- Meta pixel -->
@if (! empty($metaPixelId))
<script>
    ! function(f, b, e, v, n, t, s) {
        if (f.fbq) return;
        n = f.fbq = function() {
            n.callMethod ?
                n.callMethod.apply(n, arguments) : n.queue.push(arguments)
        };
        if (!f._fbq) f._fbq = n;
        n.push = n;
        n.loaded = !0;
        n.version = '2.0';
        n.queue = [];
        t = b.createElement(e);
        t.async = !0;
        t.src = v;
        s = b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
        'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $metaPixelId }}');
    fbq('track', 'PageView');
</script>
<noscript>
    <img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1" />
</noscript>
@endif
<!-- Ended Meta pixel -->



<script type="text/javascript">
    $(function() {
        $('.select2').select2();
    });
</script>
