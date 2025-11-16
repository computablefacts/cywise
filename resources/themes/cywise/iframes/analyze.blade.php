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
            The file must contain only <strong>numerical</strong> et <strong>categorical</strong> columns. The file must
            contain one special column named <strong>output</strong> with up to 5 categories. The
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
  let output = []; // categories for the "output" column
  let outputDim = null; // crossfilter dimension for output
  let outputGroup = null; // crossfilter group for output
  let outputChart = null; // Chart.js instance for output
  let ranges = {}; // map col. name to [min,max] for numeric charts
  let excluded = []; // excluded dimensions

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

    const getBoundsFromFilter = (f) => {
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
    };

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
        bounds = getBoundsFromFilter(filters[0]);
      }
      if (!bounds) {
        bounds = ext;
      }
      const [min, max] = bounds;
      el.textContent = formatNumber(min) + ' – ' + formatNumber(max);
      el.style.display = '';
    };

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

    // Recompute explainer based on current filters and update output chart
    const recomputeExplainer = () => {
      const filtered = filteredData();
      if (!filtered || !filtered.length) {
        // console.log('explainer: no data after filters');
        selectionExplainer = null;
        updateChartExplainer();
        return;
      }
      if (!Object.keys(filtered[0] || {}).includes('output')) {
        return; // Safety: if output was removed somehow, skip
      }
      try {
        console.log('filtered data', filtered);
        selectionExplainer = findExplanation(filtered);
        console.log('selection explainer', selectionExplainer);
        updateChartExplainer();
      } catch (err) {
        console.warn('Failed to recompute explainer:', err);
      }
    }

    const scheduleRecomputeExplainer = debounce(recomputeExplainer, 200);

    // Prepare output explainer chart (two bars per category: selection vs whole dataset)
    // Compute global explainer once (based on full dataset, not crossfilter selection)
    let globalExplainer = null;
    let selectionExplainer = null;

    try {
      globalExplainer = findExplanation(data);
    } catch (e) {
      console.warn('Failed to compute global explainer:', e);
    }

    const updateChartExplainer = () => {
      if (!elOutputChart) {
        return;
      }
      if (!globalExplainer) {
        return;
      }

      // Build data rows by category
      const categories = globalExplainer.categories || [];
      const scoresGlobal = (globalExplainer && globalExplainer.scoresByCategory) || {};
      const scoresSelection = (selectionExplainer && selectionExplainer.scoresByCategory) || {};
      const rows = categories.map(cat => ({
        key: cat,
        selection: +((scoresSelection[cat] != null) ? scoresSelection[cat] : 0),
        global: +((scoresGlobal[cat] != null) ? scoresGlobal[cat] : 0)
      }));

      // Destroy previous chart instance if any
      if (outputChart && typeof outputChart.destroy === 'function') {
        outputChart.destroy();
      }

      elOutputChart.innerHTML = '';
      const canvas = document.createElement('canvas');
      canvas.id = 'output-chart-canvas';
      elOutputChart.appendChild(canvas);
      elOutputCard.classList.remove('hidden');

      const labels = rows.map(r => r.key);
      const dataGlobal = rows.map(r => Math.max(0, Math.min(1, r.global)));
      const dataSelection = rows.map(r => Math.max(0, Math.min(1, r.selection)));
      const ctx = canvas.getContext('2d');

      outputChart = new Chart(ctx, {
        type: 'bar', data: {
          labels, datasets: [{
            label: 'Selection', data: dataSelection, backgroundColor: dataSelection.map((_, i) => {
              const p = selectionExplainer?.pvalues?.[labels[i]] || 0;
              if (p < 0.01) { // très significatif
                return 'darkgreen';
              }
              if (p < 0.05) { // significatif
                return 'green';
              }
              if (p < 0.10) { // tendance
                return 'orange';
              }
              return 'gray'; // non significatif
            })
          }, {
            label: 'Global', data: dataGlobal, backgroundColor: 'rgb(49, 130, 189)'
          }]
        }, options: {
          responsive: true, maintainAspectRatio: false, plugins: {
            legend: {display: false}, tooltip: {
              callbacks: {
                label: (context) => {
                  const v = context.parsed.y ?? context.parsed;
                  const percent = formatNumber(Math.max(0, Math.min(1, v)) * 100) + '%';
                  const count = context.dataset.label === 'Selection' ? filteredData().length : data.length;
                  const cat = labels[context.dataIndex];
                  const pvalue = context.dataset.label === 'Selection' && selectionExplainer?.pvalues?.[cat]
                    ? `, p-value=${formatNumber(selectionExplainer.pvalues[cat])}` : '';
                  return `${context.dataset.label}: ${percent} (${count} items${pvalue})`;
                }
              }
            }
          }, scales: {
            x: {
              stacked: false, ticks: {autoSkip: false}
            }, y: {
              beginAtZero: true, max: 1, ticks: {
                callback: (value) => Math.round(value * 100) + '%',
              }
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
              <div class="text-muted small" id="range_${chartId}" style="display:none"></div>
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
        // Update range text on render, redraw and filter changes
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
        .margins({top: 10, left: 0, right: 0, bottom: 20})
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

        // Destroy dimension and chart
        delete dimensions[colName];
        charts[colName].svg().remove();
        delete charts[colName];

        chart.root().node().closest('.card').remove(); // remove the card containing the chart

        // Update the global explainer based on the new dataset
        try {
          globalExplainer = findExplanation(data);
        } catch (e) {
          console.warn('Failed to compute global explainer:', e);
        }

        dc.redrawAll();
        scheduleRecomputeExplainer();
      }
    });
  }

  const findExplanation = (data) => {

    // Mélange in-place (Fisher–Yates)
    function shuffleInPlace(arr) {
      for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        const tmp = arr[i];
        arr[i] = arr[j];
        arr[j] = tmp;
      }
      return arr;
    }

    // Comptage p-value (proportion de scores permutés >= score observé)
    function computePValue(observed, permScores) {
      let countGE = 0;
      for (let i = 0; i < permScores.length; i++) {
        if (permScores[i] >= observed) {
          countGE++;
        }
      }
      return (countGE + 1) / (permScores.length + 1);
    }

    /**
     * Heuristique pour déterminer le nombre de bins à utiliser pour discrétiser des variables continues.
     *
     * - Trop peu de bins: les valeurs sont trop regroupées, la relation avec la sortie est écrasée
     *   -> sous‑estimation de l’information mutuelle.
     * - Trop de bins: beaucoup de bins avec peu d’observations
     *   -> estimation très bruitée, parfois sur‑estimation locale ou MI instable.
     */
    const findOptimalNumberOfBins = (n) => {
      if (n < 1000) {
        return 8;
      }
      if (1000 <= n && n < 10000) {
        return 16;
      }
      return 32;
    }

    const features = Object.keys(data[0] || {}).filter(colName => colName !== 'output' && !excluded.includes(colName));
    const y = data.map(d => d['output']);
    const X = data.map(d => features.map(colName => d[colName]));
    const options = {bins: findOptimalNumberOfBins(y.length)};
    const explainer = featureExplainer(y, X, features, options);
    const yCopy = y.slice();
    const scoresByCategoryPermutated = {};
    explainer.categories.forEach(cat => scoresByCategoryPermutated[cat] = []);

    for (let b = 0; b < 100 /* arbitrary value */; b++) {
      shuffleInPlace(yCopy);
      const permExplainer = featureExplainer(yCopy, X, features, options);
      const permScores = permExplainer.scoresByCategory || {};
      explainer.categories.forEach(
        cat => scoresByCategoryPermutated[cat].push(+(permScores[cat] != null ? permScores[cat] : 0)));
    }

    const pValuesByCategory = {};

    explainer.categories.forEach(cat => {
      const observedScored = +(explainer.scoresByCategory[cat] != null ? explainer.scoresByCategory[cat] : 0);
      const permutatedScores = scoresByCategoryPermutated[cat];
      pValuesByCategory[cat] = computePValue(observedScored, permutatedScores);
    });

    explainer.pvalues = pValuesByCategory;
    return explainer;
  }

  /**
   * Calcule, pour chaque catégorie de y, à quel point chaque feature l'explique.
   *
   * @param {Array<any>} y - labels de sortie (catégoriels)
   * @param {Array<Array<any>>} featureMatrix - matrice n x d (n samples, d features)
   * @param {Array<string>} [featureNames] - noms des features
   * @param {Object} [options]
   * @param {number} [options.numericThreshold=0.8] - ratio min de valeurs numériques pour considérer une feature comme numérique
   * @param {number} [options.bins=8] - nb de bins pour discrétiser les features numériques
   *
   * @returns {Object} { categories, featureNames, scores }
   */
  const featureExplainer = (y, featureMatrix, featureNames, options = {}) => {

    function unique(array) {
      return Array.from(new Set(array));
    }

    // Calcule distribution de probas (Map valeur -> p)
    function probabilityDistribution(values) {

      const counts = new Map();
      const n = values.length;

      for (let i = 0; i < n; i++) {
        const v = values[i];
        counts.set(v, (counts.get(v) || 0) + 1);
      }

      const dist = new Map();
      counts.forEach((count, v) => dist.set(v, count / n));

      return dist;
    }

    // Entropie discrète en bits
    function entropyFromDistribution(dist) {
      let h = 0;
      dist.forEach(p => {
        if (p > 0) {
          h -= p * Math.log2(p);
        }
      });
      return h;
    }

    // Entropie empirique H(X)
    function entropy(values) {
      return entropyFromDistribution(probabilityDistribution(values));
    }

    // Joint distribution de deux vecteurs de même taille
    function jointDistribution(x, y) {
      if (x.length !== y.length) {
        const msg = "jointDistribution: x et y doivent avoir la même taille";
        console.error(msg);
        throw new Error(msg);
      }

      const n = x.length;
      const counts = new Map(); // key = xVal + "||" + yVal

      for (let i = 0; i < n; i++) {
        const key = x[i] + "||" + y[i];
        counts.set(key, (counts.get(key) || 0) + 1);
      }

      const dist = new Map();
      counts.forEach((count, key) => dist.set(key, count / n));

      return dist;
    }

    // Info mutuelle I(X;Y) en bits, empirique
    function mutualInformation(x, y) {
      if (x.length !== y.length) {
        const msg = "mutualInformation: x et y doivent avoir la même taille";
        console.error(msg);
        throw new Error(msg);
      }

      const n = x.length;
      const px = probabilityDistribution(x);
      const py = probabilityDistribution(y);
      const pxy = jointDistribution(x, y);
      let mi = 0;

      pxy.forEach((pXY, key) => {

        const [vx, vy] = key.split("||");
        const pX = px.get(vx);
        const pY = py.get(vy);

        if (pX > 0 && pY > 0 && pXY > 0) {
          mi += pXY * Math.log2(pXY / (pX * pY));
        }
      });
      return mi;
    }

    // Discrétisation d'une feature numérique en nbBins classes
    function binNumericFeature(values, nbBins) {

      const n = values.length;
      const numeric = [];

      for (let i = 0; i < n; i++) {
        const v = values[i];
        const num = (v === null || v === undefined || v === "" || isNaN(Number(v))) ? NaN : Number(v);
        numeric.push(num);
      }

      const filtered = numeric.filter(v => !isNaN(v));

      if (filtered.length === 0) { // si tout est NaN, on retourne une feature constante
        return values.map(_ => "NA");
      }

      const min = Math.min.apply(null, filtered);
      const max = Math.max.apply(null, filtered);

      if (min === max) { // feature constante
        return values.map(_ => "const");
      }

      const binWidth = (max - min) / nbBins;
      const bins = new Array(n);

      for (let i = 0; i < n; i++) {
        const v = numeric[i];
        if (isNaN(v)) {
          bins[i] = "NA";
        } else {
          let binIndex = Math.floor((v - min) / binWidth);
          if (binIndex >= nbBins) {
            binIndex = nbBins - 1;
          } // pour max
          bins[i] = "b" + binIndex;
        }
      }
      return bins;
    }

    // Transforme y en vecteur binaire (string "1"/"0") pour une catégorie donnée
    function binaryOutcomeForCategory(y, category) {

      const n = y.length;
      const out = new Array(n);

      for (let i = 0; i < n; i++) {
        out[i] = (y[i] === category) ? "1" : "0";
      }
      return out;
    }

    // Détermine si une feature est numérique (heuristique simple)
    function isMostlyNumeric(values, thresholdRatio) {

      const n = values.length;
      let numericCount = 0;

      for (let i = 0; i < n; i++) {
        const v = values[i];
        if (v !== null && v !== undefined && v !== "" && !isNaN(Number(v))) {
          numericCount++;
        }
      }
      return (numericCount / n) >= thresholdRatio;
    }

    // Construit une "feature composée" pour un groupe d'indices de colonnes
    function buildGroupOfFeatures(discretizedColumns) {

      const n = discretizedColumns[0].length;
      const group = new Array(n);

      for (let i = 0; i < n; i++) {
        const parts = [];
        for (let j = 0; j < discretizedColumns.length; j++) {
          parts.push(discretizedColumns[j][i]);
        }
        group[i] = parts.join("##");
      }
      return group;
    }

    options = options || {};
    const numericThreshold = options.numericThreshold || 0.8;
    const nbBins = options.bins || 8;
    const n = y.length;

    // console.log("featureExplainer.y", y);
    // console.log("featureExplainer.featureMatrix", featureMatrix);
    // console.log("featureExplainer.featureNames", featureNames);
    // console.log("featureExplainer.options", options);

    if (!Array.isArray(featureMatrix) || featureMatrix.length !== n) {
      const msg = "featureMatrix doit être un tableau de longueur n (n = y.length)";
      console.error(msg);
      throw new Error(msg);
    }

    const d = featureMatrix[0].length;

    for (let i = 0; i < n; i++) {
      if (!Array.isArray(featureMatrix[i]) || featureMatrix[i].length !== d) {
        const msg = "Toutes les lignes de featureMatrix doivent avoir la même longueur";
        console.error(msg);
        throw new Error(msg);
      }
    }

    const categories = unique(y);
    const featNames = featureNames && featureNames.length === d ? featureNames.slice() : Array.from({length: d},
      (_, j) => "f" + j);

    // console.log("featureExplainer.categories", categories);
    // console.log("featureExplainer.featNames", featNames);

    // Prépare les colonnes de features
    const columns = [];

    for (let j = 0; j < d; j++) {

      const col = new Array(n);

      for (let i = 0; i < n; i++) {
        col[i] = featureMatrix[i][j];
      }

      // Discrétisation si numérique
      if (isMostlyNumeric(col, numericThreshold)) {
        columns.push(binNumericFeature(col, nbBins));
      } else { // on convertit tout en string pour uniformiser
        columns.push(col.map(v => String(v)));
      }
    }

    // console.log("featureExplainer.columns", columns);

    const scoresByFeature = {};

    for (let j = 0; j < d; j++) {
      scoresByFeature[featNames[j]] = {};
    }

    // console.log("featureExplainer.scores", scoresByFeature);

    const groupOfFeatures = buildGroupOfFeatures(columns);
    const scoresByCategory = {};

    // console.log("featureExplainer.groupOfFeatures", groupOfFeatures);

    // Pour chaque catégorie, calculer Y_k binaire et MI avec chaque feature
    for (let cIdx = 0; cIdx < categories.length; cIdx++) {

      const cat = categories[cIdx];
      const yBin = binaryOutcomeForCategory(y, cat);
      const hY = entropy(yBin);

      // Si H(Y_k) = 0, la catégorie est soit toujours présente soit jamais présente
      // -> pas d'incertitude, donc impossible de mesurer une "explication"
      if (hY === 0) {
        for (let j = 0; j < d; j++) {
          scoresByFeature[featNames[j]][cat] = 0;
          // console.log("featureExplainer.scoresByFeature[" + featNames[j] + "][" + cat + "]", 0);
        }
        scoresByCategory[cat] = 0;
        // console.log("featureExplainer.scoresByCategory[" + cat + "]", 0);
        continue;
      }

      const mii = mutualInformation(groupOfFeatures, yBin);
      scoresByCategory[cat] = mii / hY;
      // console.log("featureExplainer.scoresByCategory[" + cat + "]", scoresByCategory[cat]);

      for (let j = 0; j < d; j++) {
        const xCol = columns[j];
        const mi = mutualInformation(xCol, yBin);
        const normalized = mi / hY; // dans [0,1] si tout se passe bien
        scoresByFeature[featNames[j]][cat] = normalized;
        // console.log("featureExplainer.scoresByFeature[" + featNames[j] + "][" + cat + "]", normalized);
      }
    }
    return {
      categories, features: featNames, scoresByFeature, scoresByCategory
    };
  }

</script>
@endpush
