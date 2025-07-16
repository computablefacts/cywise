<style>

  .errors {
    border: 1px solid #f9bfbf;
    background-color: #f9bfbf;
    color: var(--color-red);
    margin-top: var(--spacing-large);
    margin-bottom: var(--spacing-large);
    padding: var(--spacing-medium);
    font-weight: bold;
  }

  .errors.hidden {
    display: none;
  }

</style>
<div class="errors hidden">
  <!-- FILLED DYNAMICALLY -->
</div>
<script>

  const showErrors = (errors) => {
    const errorsDiv = document.querySelector('.errors');
    errorsDiv.innerText = errors;
    errorsDiv.classList.remove('hidden');
  };

  const hideErrors = () => {
    const errorsDiv = document.querySelector('.errors');
    errorsDiv.classList.add('hidden');
  };

</script>