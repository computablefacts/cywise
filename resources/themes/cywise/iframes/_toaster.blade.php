@once
<div id="toaster"></div>
<script>

  window.toaster = {
    el: new com.computablefacts.blueprintjs.MinimalToaster(document.getElementById('toaster')),
    toast: (msg, intent) => window.toaster.el.toast(msg, intent),
    toastSuccess: (msg) => window.toaster.toast(msg, 'success'),
    toastError: (msg) => window.toaster.toast(msg, 'danger'),
    toastAxiosError: (error) => {
      console.error('Error:', error);
      if (error.response && error.response.data && error.response.data.message) {
        window.toaster.toastError(error.response.data.message);
      } else if (error.response && error.response.data && error.response.data.error) {
        window.toaster.toastError(error.response.data.error);
      } else {
        window.toaster.toastError("{{ __('An error occurred. Try again in a moment or contact the support.') }}");
      }
    },
  };

</script>
@endonce