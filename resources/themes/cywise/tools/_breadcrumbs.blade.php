<style>

  .circle-container {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: white;
    color: #FFA500;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 10px;
    font-weight: bold;
    font-size: 25px;
    position: relative;
    border: 3px solid #FFA500;
  }

  .circle.selected {
    background-color: #FFA500;
    color: white;
  }

  .line-container {
    width: 10%;
  }

  .line {
    width: 100%;
    height: 3px;
    background-color: #FFA500;
  }

</style>
<div class="circle-container">
  <div class="circle @if($step >= 1) selected @endif" style="margin-left:0">1</div>
  <div class="line-container">
    <div class="line"></div>
  </div>
  <div class="circle @if($step >= 2) selected @endif">2</div>
  <div class="line-container">
    <div class="line"></div>
  </div>
  <div class="circle @if($step >= 3) selected @endif">3</div>
  <div class="line-container">
    <div class="line"></div>
  </div>
  <div class="circle @if($step >= 4) selected @endif">4</div>
  <div class="line-container">
    <div class="line"></div>
  </div>
  <div class="circle @if($step >= 5) selected @endif" style="margin-right:0">5</div>
</div>