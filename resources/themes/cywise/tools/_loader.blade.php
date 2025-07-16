<style>

  .loader-container {
    display: flex;
    align-items: center;
    margin-bottom: var(--spacing-large);
  }

  .loader-picto {
    width: 48px;
    height: 48px;
    border: 5px solid var(--color-cywise);
    border-bottom-color: transparent;
    border-radius: 50%;
    display: inline-block;
    box-sizing: border-box;
    animation: rotation 1s linear infinite;
    margin-right: var(--spacing-large);
  }

  @keyframes rotation {
    0% {
      transform: rotate(0deg);
    }
    100% {
      transform: rotate(360deg);
    }
  }

  .checkmark {
    width: 28px;
    height: 48px;
    display: inline-block;
    box-sizing: border-box;
    transform: rotate(45deg);
    border-bottom: 7px solid #78b13f;
    border-right: 7px solid #78b13f;
    margin-right: 40px;
    margin-left: 10px;
  }

  .hidden {
    display: none !important;
  }

</style>
<div class="loader-container">
  <div class="loader-picto-container">
    <span class="loader-picto"></span>
  </div>
  <div class="loader-info">
    <h2>{{ $title }}</h2>
    <p>{{ $subtitle }}</p>
  </div>
</div>