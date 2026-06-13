@push('styles')
<style>

  .timeline {
    width: 85%;
    max-width: 100%;
    margin-left: 80px;
    margin-right: auto;
    display: flex;
    flex-direction: column;
    padding: 32px 0 32px 32px;
    border-left: 2px solid var(--c-grey-200);
    font-size: 1rem;
    margin-bottom: 0;
  }

  .timeline-item {
    display: flex;
    gap: 24px;
  }

  .timeline-item + * {
    margin-top: 24px;
  }

  .timeline-item + .extra-space {
    margin-top: 48px;
  }

  .new-comment {
    width: 100%;
  }

  .new-comment input:not([type=checkbox]):not([type=radio]) {
    border: 1px solid var(--c-grey-200);
    border-radius: 6px;
    height: 48px;
    padding: 0 16px;
    width: 100%;
  }

  .new-comment input:not([type=checkbox]):not([type=radio])::-moz-placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:not([type=checkbox]):not([type=radio]):-ms-input-placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:not([type=checkbox]):not([type=radio])::placeholder {
    color: var(--c-grey-300);
  }

  .new-comment input:not([type=checkbox]):not([type=radio]):focus {
    border-color: var(--c-grey-300);
    outline: 0;
    box-shadow: 0 0 0 4px var(--c-grey-100);
  }

  .timeline-item-hour {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    margin-left: -65px;
    flex-shrink: 0;
    color: var(--c-grey-400);
  }

  .timeline-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-left: -52px;
    flex-shrink: 0;
    overflow: hidden;
    box-shadow: 0 0 0 6px #fff;
  }

  .timeline-item-icon svg {
    width: 20px;
    height: 20px;
  }

  .timeline-item-icon.faded-icon {
    background-color: var(--c-grey-100);
    color: var(--c-grey-400);
  }

  .timeline-item-icon.filled-icon {
    background-color: var(--c-blue);
    color: #fff;
  }

  .timeline-item-wrapper {
    width: 100%;
  }

  .timeline-item-description {
    display: flex;
    gap: 8px;
    color: var(--c-grey-400);
    align-items: center;
  }

  .timeline-item-description img {
    flex-shrink: 0;
  }

  .timeline-item-description b {
    color: var(--c-grey-500);
    font-weight: 500;
    text-decoration: none;
  }

  .avatar {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    aspect-ratio: 1/1;
    flex-shrink: 0;
    width: 40px;
    height: 40px;
  }

  .avatar.small {
    width: 28px;
    height: 28px;
  }

  .avatar img {
    -o-object-fit: cover;
    object-fit: cover;
  }

  .comment {
    margin-top: 12px;
    color: var(--c-grey-500);
    border: 1px solid var(--c-grey-200);
    box-shadow: 0 4px 4px 0 var(--c-grey-100);
    border-radius: 6px;
    padding: 16px;
    font-size: 0.8rem;
  }

  .button {
    border: 0;
    display: inline-flex;
    vertical-align: middle;
    margin-right: 4px;
    margin-top: 12px;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    height: 32px;
    padding: 0 8px;
    background-color: var(--c-grey-100);
    flex-shrink: 0;
    cursor: pointer;
    border-radius: 99em;
  }

  .button:hover {
    background-color: var(--c-grey-200);
  }

  .button.square {
    border-radius: 50%;
    color: var(--c-grey-400);
    f
    width: 32px;
    height: 32px;
    padding: 0;
  }

  .button.square svg {
    width: 24px;
    height: 24px;
  }

  .button.square:hover {
    background-color: var(--c-grey-200);
    color: var(--c-grey-500);
  }

  .show-replies {
    color: var(--c-grey-300);
    background-color: transparent;
    border: 0;
    padding: 0;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 1rem;
    cursor: pointer;
  }

  .show-replies svg {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
  }

  .show-replies:hover, .show-replies:focus {
    color: var(--c-grey-500);
  }

  .avatar-list {
    display: flex;
    align-items: center;
  }

  .avatar-list > * {
    position: relative;
    box-shadow: 0 0 0 2px #fff;
    margin-right: -8px;
  }

  /* TABLE */

  .timeline-item-wrapper table {
    border-collapse: collapse;
    caption-side: bottom;
    display: table;
    width: 100%;
    font-size: 0.8rem;
    margin-top: 0;
  }

  .timeline-item-wrapper table thead {
    border-top-width: 1px;
    display: table-header-group;
    font-weight: 500;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .timeline-item-wrapper table tr {
    border-bottom-width: 1px;
    display: table-row;
    border-color: rgb(226, 232, 240);
    border-style: solid;
  }

  .timeline-item-wrapper table tbody {
    display: table-row-group
  }

  .timeline-item-wrapper table thead tr th {
    padding: 0.5rem;
    vertical-align: middle;
    display: table-cell;
    height: 2rem;
  }

  .timeline-item-wrapper table tbody tr td {
    padding: 0.5rem;
    vertical-align: middle;
    display: table-cell;
  }

  /* SCROLL TO TOP */

  .scroll-to-top {
    position: fixed;
    top: calc(56px + 20px);
    right: 20px;
    background-color: var(--c-blue);
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    transition: all 0.3s ease;
  }

  .pre-light {
    color: #565656;
    padding: 0.5rem;
    background-color: #fff3cd;
  }

  .scroll-to-top:hover {
    background-color: var(--c-grey-500);
    transform: translateY(-1px);
  }

  .scroll-to-top.show {
    display: flex;
  }

</style>
@endpush
