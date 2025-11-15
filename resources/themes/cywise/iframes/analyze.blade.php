@extends('theme::iframes.app')

@push('styles')
<style>

  .dc-chart g.dc-row rect {
    fill-opacity: 0.8;
    cursor: pointer;
  }

  .dc-chart g.dc-row text {
    fill: #fff;
    font-size: 12px;
    cursor: pointer;
  }

</style>
@endpush

@section('content')
<div class="d-grid gap-3 mb-3" style="grid-template-columns: 1fr 1fr;">
  <div class="card mt-3">
    <div class="card-body">
      <div class="row">
        <div class="col">
          <b>{{ __('Analyze (bêta)') }}</b>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col">
          <label class="block mb-2 font-medium">
            {{ __('Upload a CSV file') }}
          </label>
          <input id="csvFile" type="file" accept=".csv" class="block w-full">
        </div>
      </div>
      <div class="row mt-3">
        <div class="col">
          {!! __('The file must contain one special column named <strong>output</strong> with up to 5 categories.') !!}
        </div>
      </div>
      <div class="row mt-3">
        <div class="col">
          <span id="errors" class="hidden text-red-600"></span>
        </div>
      </div>
    </div>
  </div>
  <div id="output-card" class="card mt-3 hidden">
    <div class="card-body">
      <div class="row">
        <div class="col">
          <b>{{ __('Output') }}</b>
        </div>
      </div>
      <div class="row mt-3">
        <div class="col">
          <div id="output-chart"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="charts" class="d-grid gap-3 mt-3 mb-3" style="grid-template-columns: 1fr 1fr;">
  <!-- Charts will be inserted here -->
</div>
@endsection

@include('theme::iframes._crossfilter')

@push('scripts')
<script>

  /* Load the CSV file and build charts */
  const elFileInput = document.getElementById('csvFile');
  elFileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) {
      return;
    }
    const reader = new FileReader();
    reader.onload = function (e) {
      try {
        data = d3.csvParse(e.target.result).map(row => {
          const obj = {};
          for (const k in row) {
            const v = row[k];
            // Detect ISO date time YYYY-MM-DD HH:MM:SS
            const dateMatch = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(v);
            if (dateMatch) {
              obj[k] = new Date(v.replace(' ', 'T') + 'Z');
            } else if (v !== '' && !isNaN(+v)) {
              obj[k] = +v;
            } else {
              obj[k] = v;
            }
          }
          return obj;
        });
        console.log(data);
        if (!data || !data.length) {
          setError('Empty TSV file.');
          return;
        }
        buildCharts();
      } catch (err) {
        setError(err.message || 'Failed to parse TSV');
      }
    };
    reader.readAsText(file);
  });

  /** Display errors */
  const elErrors = document.getElementById('errors');
  const setError = (msg) => {
    elErrors.textContent = msg || '';
    elErrors.classList.toggle('hidden', !msg);
  }

  /** Display charts */
  const elCharts = document.getElementById('charts');
  const elOutputCard = document.getElementById('output-card');
  const elOutputChart = document.getElementById('output-chart');

  let ndx = null;
  let dimensions = {}; // map col. name to crossfilter dimension
  let charts = {}; // map col. name to chart instance
  let data = []; // raw data from CSV file
  let output = []; // categories for the "output" column
  let outputDim = null; // crossfilter dimension for output
  let outputGroup = null; // crossfilter group for output
  let outputChart = null; // dc.js chart for output

  const findColumnType = (values) => {
    const notNull = values.filter(v => v !== null && v !== undefined && v !== '');
    if (notNull.length === 0) {
      return 'categorical';
    }
    if (notNull.every(v => typeof v === 'number' && !isNaN(v))) {
      return 'numeric';
    }
    return 'categorical';
  }

  const buildCharts = () => {

    elCharts.innerHTML = '';
    charts = {};
    dimensions = {};
    outputDim = null;
    outputGroup = null;
    outputChart = null;

    if (elOutputCard) {
      elOutputCard.classList.add('hidden');
      if (elOutputChart) {
        elOutputChart.innerHTML = '';
      }
    }

    const cf = window.crossfilter || window.crossfilter2;
    if (!cf) {
      setError('Crossfilter library failed to load. Please check your network connection and try again.');
      return;
    }

    ndx = cf(data);

    const columns = Object.keys(data[0] || {});
    if (!columns.includes('output')) {
      setError('The CSV file must contain an "output" column.');
      return;
    }

    const outputValues = Array.from(new Set(data.map(d => d.output))).filter(v => v !== '');
    if (outputValues.length > 5) {
      setError('The "output" column must have 5 or fewer categories. Found: ' + outputValues.length);
      return;
    }

    setError('');

    // Create a row chart for output categories
    outputDim = ndx.dimension(d => d['output']);
    outputGroup = outputDim.group();

    if (elOutputChart) {
      outputChart = new dc.RowChart('#output-chart');
      outputChart._rowCssClass = 'dc-row'; // fix class conflict with FastBootstrap
      outputChart
      .dimension(outputDim)
      .group(outputGroup)
      .margins({top: 0, left: 0, right: 0, bottom: 20})
      .label(d => d.key)
      .elasticX(true)
      .data(group => group.all().filter(d => d.value > 0));
      elOutputCard.classList.remove('hidden');
    }

    // Insert charts in the DOM for each column except the "output" column
    columns.filter(colName => colName !== 'output').forEach((colName, idx) => {

      const values = data.map(d => d[colName]);
      const type = findColumnType(values);
      const chartId = 'chart_' + colName.replace(/[^a-zA-Z0-9_]/g, '_');
      const elCard = document.createElement('div');
      elCard.className = 'card p-0';
      elCard.innerHTML = `
        <div class="card-body p-3">
          <div class="row">
            <div class="col">
              <b>${colName}</b>
            </div>
            <div class="col-auto">
              <a data-action="reset" data-col="${colName}">
                {{ __('reset') }}
              </a>&nbsp;
              <a data-action="exclude" data-col="${colName}">
                {{ __('exclude') }}
              </a>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col">
              <div id="${chartId}"></div>
            </div>
          </div>
        </div>`;

      elCharts.appendChild(elCard);

      // Create chart based on column type
      let dimension;
      let chart;

      if (type === 'numeric') {
        const ext = d3.extent(values.filter(v => typeof v === 'number'));
        dimension = ndx.dimension(d => d[colName]);
        chart = new dc.BarChart(`#${chartId}`);
        chart.dimension(dimension)
        .group(dimension.group())
        .margins({top: 10, left: 30, right: 30, bottom: 20})
        .elasticY(true)
        .centerBar(true)
        .gap(1)
        .x(d3.scaleLinear().domain(ext).nice())
        .renderHorizontalGridLines(true);
      } else {
        dimension = ndx.dimension(d => d[colName]);
        chart = new dc.RowChart(`#${chartId}`);
        chart._rowCssClass = 'dc-row'; // fix class conflict with FastBootstrap
        chart.dimension(dimension)
        .group(dimension.group())
        .margins({top: 10, left: 0, right: 0, bottom: 20})
        .label(d => d.key)
        .elasticX(true)
        .data(group => group.all().filter(d => d.value > 0));
      }

      dimensions[colName] = dimension;
      charts[colName] = chart;
    });

    dc.renderAll();

    // Deal with toolbar buttons inside cards
    elCharts.addEventListener('click', function (e) {

      const elLink = e.target.closest('a[data-action]');
      if (!elLink) {
        return;
      }

      const action = elLink.getAttribute('data-action');
      const colName = elLink.getAttribute('data-col');
      const dimension = dimensions[colName];
      const chart = charts[colName];

      console.log(action, dimension, chart);

      if (action === 'reset') {
        console.log('reset');
        chart.filterAll();
        dc.redrawAll();
      } else if (action === 'exclude') {

        // Remove column from dataset
        data = data.map(d => {
          const newObj = {...d};
          delete newObj[colName];
          return newObj;
        });

        // Remove dimension and chart
        delete dimensions[colName];
        charts[colName].svg().remove();
        delete charts[colName];

        chart.root().node().closest('.card').remove(); // remove the card containing the chart

        // Rebuild crossfilter with updated data
        ndx = cf(data);
        dc.renderAll();
      }
    });
  }

</script>
@endpush
