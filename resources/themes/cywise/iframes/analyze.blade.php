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

  /* Ensure only the charts area is scrollable on this page */

  #parameters {
    height: 100vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  #charts {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
  }

</style>
@endpush

@section('content')
<div id="parameters">
  <div class="d-grid gap-3 mb-3" style="grid-template-columns: 1fr 1fr;">
    <div class="card mt-3">
      <div class="card-body">
        <div class="row">
          <div class="col">
            <b>{{ __('Optimize (bêta)') }}</b>
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
            The file must contain only <strong>numerical</strong> and <strong>categorical</strong> columns. The file
            must contain one special column named <strong>output</strong> with up to 5 categories. The
            <strong>output</strong> column is the target variable to optimize.
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
  <div id="charts" class="d-grid gap-3 mb-3" style="grid-template-columns: 1fr 1fr;">
    <!-- Charts will be inserted here -->
  </div>
</div>
@endsection

@include('theme::iframes._crossfilter')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
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

        // console.log(data);

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
  let outputChart = null; // Chart.js instance for output
  let ranges = {}; // map col. name to [min,max] for numeric charts
  let excluded = []; // excluded dimensions i.e. col. names

  const findColumnType = (values) => {
    const notNull = values.filter(v => v !== null && v !== undefined && v !== '');
    if (notNull.length === 0) {
      return 'categorical';
    }
    if (notNull.every(v => typeof v === 'number' && !isNaN(v))) {
      // console.log('findColumnType', values, 'number');
      return 'numeric';
    }
    // console.log('findColumnType', values, 'categorical');
    return 'categorical';
  }

  const buildCharts = () => {

    elCharts.innerHTML = '';
    charts = {};
    dimensions = {};
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

    const outputValues = Array.from(new Set(data.map(d => d['output']))).filter(v => v !== '');

    if (outputValues.length > 5) {
      setError('The "output" column must have 5 or fewer categories. Found: ' + outputValues.length);
      return;
    }

    setError('');

    // Debounce to avoid recomputing too often while brushing/filtering
    const debounce = (fn, wait = 150) => {
      let t;
      return function (...args) {
        const ctx = this;
        clearTimeout(t);
        t = setTimeout(() => fn.apply(ctx, args), wait);
      };
    }

    // Choose formatting based on magnitude
    const formatNumber = (v) => {
      if (v == null || isNaN(v)) {
        return '';
      }
      const abs = Math.abs(v);
      const opts = abs >= 1000 ? {maximumFractionDigits: 0} : {maximumFractionDigits: 4};
      return Number(v).toLocaleString(undefined, opts);
    };

    const crossfilterRange = (f) => {
      if (!f) {
        return null;
      }
      // If it's an array-like [min, max]
      if (Array.isArray(f) && f.length >= 2 && [f[0], f[1]].every(x => typeof x === 'number')) {
        return [f[0], f[1]];
      }
      // dc.js RangedFilter variants
      const candidates = [['from', 'to'], ['lo', 'hi'], ['lowerBound', 'upperBound'], ['begin', 'end'], ['x0', 'x1']];
      for (const [a, b] of candidates) {
        if (typeof f === 'object' && f != null && typeof f[a] === 'number' && typeof f[b] === 'number') {
          return [f[a], f[b]];
        }
      }
      // Some versions expose .filter as function returning array
      if (typeof f.filter === 'function') {
        try {
          const arr = f.filter();
          if (Array.isArray(arr) && arr.length >= 2) {
            return [arr[0], arr[1]];
          }
        } catch (e) {
        }
      }
      return null;
    }

    const updateRangeDisplay = (colName) => {
      const chart = charts[colName];
      const ext = ranges[colName];
      const el = document.getElementById('range_chart_' + colName.replace(/[^a-zA-Z0-9_]/g, '_'));
      if (!chart || !ext || !el) {
        return;
      }
      const filters = (typeof chart.filters === 'function') ? chart.filters() : [];
      let bounds = null;
      if (filters && filters.length) {
        bounds = crossfilterRange(filters[0]);
      }
      if (!bounds) {
        bounds = ext;
      }
      const [min, max] = bounds;
      el.textContent = 'Range: ' + formatNumber(min) + ' – ' + formatNumber(max);
      el.style.display = '';
    }

    // Get filtered rows from Crossfilter (falls back to all)
    const filteredData = () => {
      try {
        if (ndx && typeof ndx.allFiltered === 'function') {
          return ndx.allFiltered();
        }
      } catch (e) {
      }
      try {
        if (ndx && typeof ndx.all === 'function') {
          return ndx.all();
        }
      } catch (e) {
      }
      return data || [];
    }

    // Recompute explainer based on current filters
    const recomputeExplainer = () => {
      const filtered = filteredData();
      if (!filtered || !filtered.length) {
        updateChartExplainer(data);
      } else {
        updateChartExplainer(data, filtered);
      }
    }

    const scheduleRecomputeExplainer = debounce(recomputeExplainer, 200);

    // Prepare output explainer chart (two bars per category: selection vs whole dataset)
    const updateChartExplainer = (all, filtered = null) => {
      if (!elOutputChart) {
        return;
      }

      // Destroy previous chart instance if any
      if (outputChart && typeof outputChart.destroy === 'function') {
        outputChart.destroy();
      }

      const canvas = document.createElement('canvas');
      elOutputChart.innerHTML = '';
      elOutputChart.appendChild(canvas);
      elOutputCard.classList.remove('hidden');

      const features = Array.from(new Set(data.map(d => d['output']))).filter(v => v !== '');
      const dataGlobal = features.map(cat => all.filter(d => d['output'] === cat).length);
      const dataSelection = features.map(cat => (filtered ?? all).filter(d => d['output'] === cat).length);
      const ctx = canvas.getContext('2d');

      outputChart = new Chart(ctx, {
        type: 'bar', data: {
          labels: features, datasets: [{
            label: 'Selection', data: dataSelection, backgroundColor: 'gray',
          }, {
            label: 'Global', data: dataGlobal, backgroundColor: 'rgb(49, 130, 189)'
          }]
        }, options: {
          responsive: true, maintainAspectRatio: false, plugins: {
            legend: {display: false}, tooltip: {
              callbacks: {
                label: (context) => {
                  return `${context.dataset.label}: ${context.raw || 0} items`;
                }
              }
            }
          }, scales: {
            x: {
              stacked: false, ticks: {autoSkip: false}
            }, y: {
              beginAtZero: true, max: Math.max(...dataGlobal, ...dataSelection),
            }
          }
        }
      });
    }

    // Insert charts in the DOM for each column except the "output" column
    columns.filter(colName => colName !== 'output').forEach((colName) => {

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
              <div class="text-muted small">
                <span id="range_${chartId}" style="display:none"></span>
              </div>
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
        ranges[colName] = ext;
        chart.on('postRender.updateRange', () => updateRangeDisplay(colName));
        chart.on('postRedraw.updateRange', () => updateRangeDisplay(colName));
        chart.on('filtered.updateRange', () => updateRangeDisplay(colName));
        chart.on('filtered.recompute', () => scheduleRecomputeExplainer());
      } else {
        dimension = ndx.dimension(d => d[colName]);
        chart = new dc.RowChart(`#${chartId}`);
        chart._rowCssClass = 'dc-row'; // fix class conflict with FastBootstrap
        chart.dimension(dimension)
        .group(dimension.group())
        .margins({top: 10, left: 10, right: 10, bottom: 20})
        .label(d => d.key)
        .elasticX(true)
        .data(group => group.all().filter(d => d.value > 0));
        chart.on('filtered.recompute', () => scheduleRecomputeExplainer());
      }

      dimensions[colName] = dimension;
      charts[colName] = chart;
    });

    dc.renderAll();
    Object.keys(ranges).forEach(col => updateRangeDisplay(col));
    scheduleRecomputeExplainer();

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

      // console.log(action, dimension, chart);

      if (action === 'reset') {
        // console.log('reset');
        chart.filterAll();
        dc.redrawAll();
        scheduleRecomputeExplainer();
      } else if (action === 'exclude') {

        excluded.push(colName);

        // Remove all filters and destroy dimension/chart
        dimension.filterAll();
        delete dimensions[colName];
        charts[colName].svg().remove();
        delete charts[colName];

        // Remove the card containing the chart
        chart.root().node().closest('.card').remove();

        // Update the other charts
        dc.redrawAll();
        scheduleRecomputeExplainer();
      }
    });
  }

</script>
@endpush
