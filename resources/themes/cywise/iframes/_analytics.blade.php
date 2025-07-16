@if(setting('site.google_analytics_tracking_id', ''))
<script async
        src="https://www.googletagmanager.com/gtag/js?id={{ setting('site.google_analytics_tracking_id') }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];

  function gtag() {
    dataLayer.push(arguments);
  }

  gtag('js', new Date());
  gtag('config', '{{ setting("site.google_analytics_tracking_id") }}');
</script>
@endif