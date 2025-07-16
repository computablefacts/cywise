@once
<style>

  .tw-action {
    cursor: pointer;
    border-width: 2px;
    border-color: rgb(226, 232, 240);
    border-style: solid;
    border-radius: 8px;
    padding: 1rem;
  }

  .tw-action:hover {
    --tw-shadow: 0 10px 15px -3px rgb(0 0 0 / .1), 0 4px 6px -4px rgb(0 0 0 / .1);
    box-shadow: var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow);
  }

  .tw-action .tw-header {
    flex-direction: column;
    display: flex;
    padding: 0.5rem
  }

  .tw-action .tw-body {
    padding-bottom: 0.75rem;
    padding-left: 0.5rem;
    padding-right: 0.5rem;
    padding-top: 0.5rem;
    font-size: 14px;
    color: rgb(75, 85, 99)
  }

  .tw-action .tw-header h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .tw-action .tw-header p {
    color: rgb(100, 116, 139);
    font-size: 14px;
  }

  .tw-action .tw-body p {
    display: -webkit-box;
    overflow: hidden
  }

</style>
@endonce
<div class="tw-action" onclick="onActionClick('{{ e($text) }}')">
  <div class="tw-header">
    <h3>{{ $title }}</h3>
    <p>{{ $subtitle }}</p>
  </div>
  <div class="tw-body">
    <p>{{ $text }}</p>
  </div>
</div>
@once
<script>

  const onActionClick = (text) => {

    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    text = textarea.value;

    const elInputField = document.querySelector('.tw-chat-footer-input');
    elInputField.value = text;
    elInputField.focus();

    console.log(text);
  };

</script>
@endonce