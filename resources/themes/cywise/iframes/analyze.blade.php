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
            <b>{{ __('Explore (bêta)') }}</b>
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
            The file must contain only columns with <strong>numerical</strong>, <strong>categorical</strong> and
            <strong>timestamp</strong> data. The file must contain one special column named <strong>output</strong>
            with up to 5 categories. The <strong>output</strong> column is the target variable to optimize.
          </div>
        </div>
        <div class="row mt-3">
          <div class="col">
            <span id="errors" class="d-none text-red-600"></span>
          </div>
        </div>
        <div id="output-categories-section" class="row mt-3 d-none">
          <div class="col">
            <label class="block mb-2 font-medium">
              <b>
                {{ __('Category to optimize under selected constraints') }} :
              </b>
            </label>
            <div id="output-categories" class="d-flex flex-column gap-1"></div>
            <div class="mt-2">
              <button id="output-categories-button" type="button" class="btn btn-sm btn-primary">
                {{ __('Optimize!') }}
              </button>
            </div>
          </div>
        </div>
        <div class="row mt-3">
          <div class="col">
            <div id="results"></div>
          </div>
        </div>
      </div>
    </div>
    <div id="output-card" class="card mt-3 d-none">
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
      if (elOutputCategoriesSection) {
        elOutputCategoriesSection.classList.add('d-none');
      }
      if (elOutputCategories) {
        elOutputCategories.innerHTML = '';
      }
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
        if (elOutputCategoriesSection) {
          elOutputCategoriesSection.classList.add('d-none');
        }
        if (elOutputCategories) {
          elOutputCategories.innerHTML = '';
        }
      }
    };
    reader.readAsText(file);
  });

  /** Display errors */
  const elErrors = document.getElementById('errors');
  const setError = (msg) => {
    elErrors.textContent = msg || '';
    elErrors.classList.toggle('d-none', !msg);
  }

  /** Display charts */
  const elCharts = document.getElementById('charts');
  const elOutputCard = document.getElementById('output-card');
  const elOutputChart = document.getElementById('output-chart');
  const elOutputCategoriesSection = document.getElementById('output-categories-section');
  const elOutputCategories = document.getElementById('output-categories');
  const elOutputCategoriesButton = document.getElementById('output-categories-button');

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
    // Detect datetime columns (Date instances)
    if (notNull.every(v => v instanceof Date && !isNaN(v))) {
      // console.log('findColumnType', values, 'datetime');
      return 'datetime';
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
      elOutputCard.classList.add('d-none');
      if (elOutputChart) {
        elOutputChart.innerHTML = '';
      }
    }

    // Reset output categories
    if (elOutputCategoriesSection) {
      elOutputCategoriesSection.classList.add('d-none');
    }
    if (elOutputCategories) {
      elOutputCategories.innerHTML = '';
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

    // Render output categories radio group (sorted ASC, select first)
    const sortAsc = (a, b) => {
      const an = typeof a === 'number';
      const bn = typeof b === 'number';
      if (an && bn) {
        return a - b;
      }
      return String(a).localeCompare(String(b), undefined, {numeric: true, sensitivity: 'base'});
    }

    const categoriesSorted = [...outputValues].sort(sortAsc);

    if (elOutputCategories && elOutputCategoriesSection) {
      elOutputCategories.innerHTML = '';
      categoriesSorted.forEach((cat, idx) => {
        const id = 'out-cat-' + String(cat).replace(/[^a-zA-Z0-9_\-]/g, '_');
        const wrapper = document.createElement('div');
        wrapper.className = 'form-check';
        const input = document.createElement('input');
        input.type = 'radio';
        input.className = 'form-check-input';
        input.name = 'output-category';
        input.value = String(cat);
        input.id = id;
        if (idx === 0) {
          input.checked = true;
        }
        const label = document.createElement('label');
        label.className = 'form-check-label';
        label.setAttribute('for', id);
        label.textContent = String(cat);
        wrapper.appendChild(input);
        wrapper.appendChild(label);
        elOutputCategories.appendChild(wrapper);
      });
      elOutputCategoriesSection.classList.remove('d-none');
    }
    if (elOutputCategoriesButton) {
      elOutputCategoriesButton.onclick = () => {

        const selected = (document.querySelector('input[name="output-category"]:checked') || {}).value;
        if (!selected) {
          return;
        }

        console.log('Optimize for category:', selected);

        const filtered = filteredData();
        if (!filtered.length) {
          return;
        }

        console.log('filtered:', filtered);

        const labels = Object.keys(filtered[0]).filter(col => col !== 'output' && !excluded.includes(col));

        console.log('Labels:', labels);

        // Fonction pour discrétiser un attribut numérique
        const discretize = (data, column, bins = 3) => {

          const values = data.map(d => d[column]).sort((a, b) => a - b);
          const min = values[0];
          const max = values[values.length - 1];
          const step = (max - min) / bins;
          const thresholds = Array.from({length: bins - 1}, (_, i) => min + step * (i + 1));

          // Stocker les seuils pour reconversion
          const binThresholds = {
            thresholds: thresholds, min: min, max: max,
          };

          // Discrétiser les données
          const discretized = data.map(d => {
            for (let i = 0; i < thresholds.length; i++) {
              if (d[column] <= thresholds[i]) {
                return {bin: `${column}_bin_${i}`, value: d[column]};
              }
            }
            return {bin: `${column}_bin_${bins - 1}`, value: d[column]};
          });

          return {discretized, binThresholds};
        }

        const bins = {};
        const thresholds = {};

        // Ne discrétiser que les colonnes numériques; laisser passer les catégorielles/date telles quelles
        labels.forEach(col => {
          const colType = findColumnType(filtered.map(d => d[col]));
          if (colType === 'numeric') {
            const {discretized, binThresholds} = discretize(filtered, col);
            bins[col] = discretized;
            thresholds[col] = binThresholds; // utile pour reconversion intervalle
          } else {
            // Catégorielle ou datetime: pas de discrétisation, on conserve la valeur brute comme "bin"
            bins[col] = filtered.map(d => ({bin: d[col], value: d[col]}));
            thresholds[col] = null; // pas de seuils disponibles
          }
        });

        console.log('Bins:', bins);
        console.log('Thresholds:', thresholds);

        const features = filtered.map((d, idx) => {
          const feature = {};
          Object.keys(d).filter(col => labels.includes(col)).forEach(col => {
            feature[col] = bins[col][idx];
          });
          return feature;
        });
        const target = filtered.map(d => d['output']);

        console.log('Features:', features);
        console.log('Target:', target);

        // Fonction pour calculer l'entropie
        const entropy = (targets) => {
          const counts = {};
          targets.forEach(t => counts[t] = (counts[t] || 0) + 1);
          return Object.values(counts).reduce((sum, count) => {
            const p = count / targets.length;
            return sum - p * Math.log2(p);
          }, 0);
        }

        // Fonction pour trouver la meilleure division
        const findBestSplit = (features, target) => {

          let bestGain = -Infinity;
          let bestFeature = null;
          let bestBin = null;

          // Parcourir chaque feature discrétisée (catégorielle)
          const featureNames = Object.keys(features[0] || {});

          for (const feature of featureNames) {

            // Extraire l'ensemble des bins (chaînes), pas les objets de référence
            const binsSet = new Set(
              features.map(f => (f[feature] && f[feature].bin) ? f[feature].bin : undefined).filter(
                v => v !== undefined));

            for (const bin of binsSet) {

              const left = [];
              const right = [];

              features.forEach((f, i) => {
                if ((f[feature] && f[feature].bin) === bin) {
                  left.push(target[i]);
                } else {
                  right.push(target[i]);
                }
              });

              if (left.length === 0 || right.length === 0) {
                continue; // Split non informatif
              }

              const gain = entropy(target) - (left.length / target.length) * entropy(left) - (right.length
                / target.length) * entropy(right);

              if (gain > bestGain) {
                bestGain = gain;
                bestFeature = feature;
                bestBin = bin;
              }
            }
          }
          return {feature: bestFeature, bin: bestBin, gain: bestGain};
        }

        // Fonction pour construire l'arbre de décision
        const buildTree = (features, target) => {

          // Si tous les exemples ont la même classe, retourner cette classe
          if (new Set(target).size === 1) {
            return {decision: target[0]};
          }

          // jeux vides -> feuille neutre
          if (!features || !features.length || !target || !target.length) {
            return {decision: null};
          }

          // Sinon, trouver la meilleure division
          const {feature, bin, gain} = findBestSplit(features, target);

          // Aucun split utile trouvé -> feuille avec classe majoritaire
          if (!feature || !bin || !(gain > 0)) {
            const counts = {};
            target.forEach(t => counts[t] = (counts[t] || 0) + 1);
            return {decision: Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0]};
          }

          // Diviser les données
          const leftFeatures = [];
          const leftTarget = [];
          const rightFeatures = [];
          const rightTarget = [];

          features.forEach((f, i) => {
            if ((f[feature] && f[feature].bin) === bin) {
              leftFeatures.push(f);
              leftTarget.push(target[i]);
            } else {
              rightFeatures.push(f);
              rightTarget.push(target[i]);
            }
          });

          // Si une des branches est vide (sécurité), retourner la classe majoritaire
          if (leftTarget.length === 0 || rightTarget.length === 0) {
            const counts = {};
            target.forEach(t => counts[t] = (counts[t] || 0) + 1);
            return {decision: Object.entries(counts).sort((a, b) => b[1] - a[1])[0][0]};
          }

          // Construire les sous-arbres
          return {
            feature,
            value: {bin},
            trueBranch: buildTree(leftFeatures, leftTarget),
            falseBranch: buildTree(rightFeatures, rightTarget),
          };
        }

        // Fonction pour reconvertir les bins en intervalles
        const binToInterval = (bin, thresholds) => {

          // Reconstruire le nom de colonne depuis le bin si non présent dans thresholds
          const column = (bin && typeof bin === 'string') ? bin.split('_bin_')[0] : 'col';
          const bins = (thresholds && Array.isArray(thresholds.thresholds)) ? thresholds.thresholds : [];
          const binIndex = parseInt(String(bin).split("_bin_")[1]);

          if (!Number.isFinite(binIndex) || bins.length === 0) {
            return `${column} ∈ ${bin}`; // fallback
          }
          if (binIndex === 0) {
            return `${column} <= ${Number(bins[0]).toFixed(2)}`;
          } else if (binIndex < bins.length) {
            return `${Number(bins[binIndex - 1]).toFixed(2)} < ${column} <= ${Number(bins[binIndex]).toFixed(2)}`;
          } else {
            return `${column} > ${Number(bins[bins.length - 1]).toFixed(2)}`;
          }
        }

        // Fonction pour extraire les règles pour une classe donnée
        const extractRulesForClass = (tree, targetClass, path = [], rules = []) => {
          if (!tree) {
            return rules;
          }

          // Détecter une feuille par la présence de la propriété 'decision' (et non sa vérité)
          if (Object.prototype.hasOwnProperty.call(tree, 'decision')) {

            // Normaliser la comparaison pour éviter les mismatches de type (ex: nombre vs texte)
            if (String(tree.decision) === String(targetClass)) {
              const converted = (path.length ? path : ["(toujours vrai)"]).map(item => { // Reconvertir les bins en intervalles
                if (typeof item === 'string' && item.includes("_bin_")) {

                  // Supporte " = " et " != "
                  let op = ' = ';
                 
                  if (item.includes(' != ')) {
                    op = ' != ';
                  }

                  // Séparer sur le premier opérateur trouvé pour éviter des splits multiples
                  const idx = item.indexOf(op);
                  const feature = idx >= 0 ? item.slice(0, idx) : '';
                  const bin = idx >= 0 ? item.slice(idx + op.length) : '';
                  const interval = binToInterval(bin, thresholds[feature]);

                  return op.trim() === '!=' ? `NON(${interval})` : interval;
                }
                return item;
              });
              rules.push(converted);
            }
            return rules;
          }

          // Éviter d'ajouter des conditions vides dans le chemin si le bin est manquant
          const hasBin = !!(tree.value && tree.value.bin);
          const newPathTrue = hasBin ? [...path, `${tree.feature} = ${tree.value.bin}`] : [...path];
          const newPathFalse = hasBin ? [...path, `${tree.feature} != ${tree.value.bin}`] : [...path];

          if (tree.trueBranch) {
            extractRulesForClass(tree.trueBranch, targetClass, newPathTrue, rules);
          }
          if (tree.falseBranch) {
            extractRulesForClass(tree.falseBranch, targetClass, newPathFalse, rules);
          }
          return rules;
        }

        const decisionTree = buildTree(features, target);

        console.log('Decision Tree:', decisionTree);

        const rules = extractRulesForClass(decisionTree, selected);

        console.log('Rules:', rules);

        const resultsDiv = document.getElementById("results");

        if (rules.length === 0) {
          resultsDiv.innerHTML = `
            <div><b>No rule found!</b></div>
          `;
        } else {
          let showRules = false;

          resultsDiv.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-2">
              <div><b>${rules.length} rules found!</b></div>
              <a href="#" id="toggle-rules">(${showRules ? 'hide' : 'show'} rules)</a>
            </div>
            <pre
              id="rules"
              class="mb-0 ${showRules ? '' : 'd-none'}"
              style="max-height: 200px; overflow-y: auto">${rules.map(
            (rule, i) => `Règle ${i + 1} : ${rule.join(" ET ")}`).join("\n")}</pre>
          `;

          document.getElementById('toggle-rules').addEventListener('click', () => {

            showRules = !showRules;

            const elRules = document.getElementById('rules');
            const elBtn = document.getElementById('toggle-rules');

            if (showRules) {
              elRules.classList.remove('d-none');
            } else {
              elRules.classList.add('d-none');
            }
            elBtn.textContent = `(${showRules ? 'hide' : 'show'} rules)`;
          });
        }
      };
    }

    // Debounce to avoid recomputing too often while brushing/filtering
    const debounce = (fn, wait = 150) => {
      let t;
      return function (...args) {
        const ctx = this;
        clearTimeout(t);
        t = setTimeout(() => fn.apply(ctx, args), wait);
      };
    }

    // Choose formatting based on value type
    const formatNumber = (v) => {
      if (v == null || isNaN(v)) {
        if (v instanceof Date) { // For Date objects, isNaN(Date) returns false, so handle below
          return v.toLocaleString();
        }
        return '';
      }
      if (v instanceof Date) {
        return v.toLocaleString();
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
      if (Array.isArray(f) && f.length >= 2 && ([f[0], f[1]].every(x => typeof x === 'number') || [f[0], f[1]].every(
        x => x instanceof Date))) {
        return [f[0], f[1]];
      }
      // dc.js RangedFilter variants
      const candidates = [['from', 'to'], ['lo', 'hi'], ['lowerBound', 'upperBound'], ['begin', 'end'], ['x0', 'x1']];
      for (const [a, b] of candidates) {
        if (typeof f === 'object' && f != null && ((typeof f[a] === 'number' && typeof f[b] === 'number') || (f[a]
          instanceof Date && f[b] instanceof Date))) {
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
      elOutputCard.classList.remove('d-none');

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
        .x(d3.scaleLinear().domain(ext).nice())
        .renderHorizontalGridLines(true);
        ranges[colName] = ext;
        chart.on('postRender.updateRange', () => updateRangeDisplay(colName));
        chart.on('postRedraw.updateRange', () => updateRangeDisplay(colName));
        chart.on('filtered.updateRange', () => updateRangeDisplay(colName));
        chart.on('filtered.recompute', () => scheduleRecomputeExplainer());
      } else if (type === 'datetime') {
        const ext = d3.extent(values.filter(v => v instanceof Date && !isNaN(v)));
        dimension = ndx.dimension(d => d[colName]);
        chart = new dc.BarChart(`#${chartId}`);
        const day = d3.timeDay;
        chart.dimension(dimension)
        .group(dimension.group(d => day(d)))
        .margins({top: 10, left: 30, right: 30, bottom: 20})
        .elasticY(true)
        .centerBar(true)
        .gap(1)
        .x(d3.scaleTime().domain(ext))
        .round(day.round)
        .xUnits(d3.timeDays)
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
