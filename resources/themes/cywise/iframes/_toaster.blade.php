@once
<div id="toaster"></div>
<script>

  const toaster = {
    el: new com.computablefacts.blueprintjs.MinimalToaster(document.getElementById('toaster')),
    toast: (msg, intent) => toaster.el.toast(msg, intent),
    toastSuccess: (msg) => toaster.toast(msg, 'success'),
    toastError: (msg) => toaster.toast(msg, 'danger'),
    toastAxiosError: (error) => {
      console.error('Error:', error);
      if (error.response && error.response.data && error.response.data.message) {
        toaster.toastError(error.response.data.message);
      } else {
        toaster.toastError("{{ __('An error occurred. Try again in a moment or contact the support.') }}");
      }
    },
  };
  
</script>
@endonce