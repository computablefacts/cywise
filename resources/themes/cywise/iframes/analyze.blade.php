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
        <div class="row mt-3 d-none">
          <div class="col">
            <div class="d-flex gap-2">
              <button id="optimize-button" type="button" class="btn btn-sm btn-primary w-100">
                {{ __('Optimize!') }}
              </button>
              <button id="reset-optimize-button" type="button" class="btn btn-sm btn-secondary">
                {{ __('Reset') }}
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
      return;
    }
    const resultsDiv = document.getElementById('results');
    if (resultsDiv) {
      resultsDiv.innerHTML = '';
    }
    if (elOptimizeBtn) {
      const row = elOptimizeBtn.closest('.row');
      if (row) {
        row.classList.add('d-none');
      }
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

        if (!data || !data.length) {
          toaster.toastError('Empty TSV file.');
          return;
        }

        buildCharts();

      } catch (err) {
        toaster.toastError(err.message || 'Failed to parse TSV');
      }
    };
    reader.readAsText(file);
  });

  /** Display charts */
  const elCharts = document.getElementById('charts');
  const elOutputCard = document.getElementById('output-card');
  const elOutputChart = document.getElementById('output-chart');
  const elOptimizeBtn = document.getElementById('optimize-button');
  const elResetOptimizeBtn = document.getElementById('reset-optimize-button');

  let ndx = null;
  let dimensions = {}; // map col. name to crossfilter dimension
  let charts = {}; // map col. name to chart instance
  let data = []; // raw data from CSV file
  let outputChart = null; // Chart.js instance for output
  let ranges = {}; // map col. name to [min,max] for numeric charts
  let excluded = []; // excluded dimensions i.e. col. names
  let hiddenFeatureCards = new Set(); // track hidden feature cards

  const findColumnType = (values) => {
    const notNull = values.filter(v => v !== null && v !== undefined && v !== '');
    if (notNull.length === 0) {
      return 'categorical';
    }
    // Detect datetime columns (Date instances)
    if (notNull.every(v => v instanceof Date && !isNaN(v))) {
      return 'datetime';
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
    outputChart = null;

    if (elOutputCard) {
      elOutputCard.classList.add('d-none');
      if (elOutputChart) {
        elOutputChart.innerHTML = '';
      }
    }

    const cf = window.crossfilter || window.crossfilter2;
    if (!cf) {
      toaster.toastError('Crossfilter library failed to load. Please check your network connection and try again.');
      return;
    }

    ndx = cf(data);
    const columns = Object.keys(data[0] || {});

    if (!columns.includes('output')) {
      toaster.toastError('The CSV file must contain an "output" column.');
      return;
    }

    const outputValues = Array.from(new Set(data.map(d => d['output']))).filter(v => v !== '');

    if (outputValues.length > 5) {
      toaster.toastError('The "output" column must have 5 or fewer categories. Found: ' + outputValues.length);
      return;
    }
    if (elOptimizeBtn) {
      const row = elOptimizeBtn.closest('.row');
      if (row) {
        row.classList.remove('d-none');
      }
    }
    if (elOptimizeBtn) {
      elOptimizeBtn.onclick = () => {

        const filtered = filteredData();

        if (!filtered.length) {
          return;
        }

        const labels = Object.keys(filtered[0]).filter(col => col !== 'output' && !excluded.includes(col));

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

        const features = filtered.map((d, idx) => {
          const feature = {};
          Object.keys(d).filter(col => labels.includes(col)).forEach(col => {
            feature[col] = bins[col][idx];
          });
          return feature;
        });
        const target = filtered.map(d => d['output']);

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

        // Fonction pour extraire les chemins (règles brutes) menant à une classe donnée
        // Retourne un tableau de chemins, chaque chemin étant un tableau de chaînes de conditions
        // ex: ["colA = colA_bin_1", "colB != colB_bin_0", ...]
        const extractRulesForClass = (tree, targetClass, path = [], rules = []) => {
          if (!tree) {
            return rules;
          }

          // Feuille détectée si propriété 'decision' présente
          if (Object.prototype.hasOwnProperty.call(tree, 'decision')) {
            if (String(tree.decision) === String(targetClass)) {
              // Conserver la version brute du chemin (sans conversion pretty) pour permettre le filtrage
              rules.push(path.length ? [...path] : []);
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

        // Vérifie si un enregistrement de features satisfait un chemin (ensemble de conditions)
        const matchesPath = (featureRow, path) => {
          if (!path || path.length === 0) {
            return true;
          }
          for (const cond of path) {
            if (typeof cond !== 'string') {
              continue;
            }

            // Priorité à " != " (pour ne pas confondre le ! dans les noms)
            let op = ' != ';
            let idx = cond.indexOf(op);

            if (idx === -1) {
              op = ' = ';
              idx = cond.indexOf(op);
            }

            const feature = idx >= 0 ? cond.slice(0, idx) : '';
            const bin = idx >= 0 ? cond.slice(idx + op.length) : '';
            const val = featureRow && featureRow[feature] ? featureRow[feature].bin : undefined;

            if (op.trim() === '!=') {
              if (val === bin) {
                return false;
              }
            } else {
              if (val !== bin) {
                return false;
              }
            }
          }
          return true;
        }

        // Conversion "jolie" d'un chemin brut vers un texte lisible avec intervalles
        const prettifiesPath = (path) => {
          if (!path || path.length === 0) {
            return ["(toujours vrai)"];
          }
          return path.map(item => {
            if (typeof item === 'string' && item.includes('_bin_')) {

              // Déterminer l'opérateur présent
              let op = ' = ';

              if (item.includes(' != ')) {
                op = ' != ';
              }

              const idx = item.indexOf(op);
              const feature = idx >= 0 ? item.slice(0, idx) : '';
              const bin = idx >= 0 ? item.slice(idx + op.length) : '';
              const interval = binToInterval(bin, thresholds[feature]);

              return op.trim() === '!=' ? `NON(${interval})` : interval;
            }
            return item;
          });
        }

        // Construire les règles pour toutes les catégories de sortie
        const decisionTree = buildTree(features, target);

        // Extraire les features utilisées dans l'arbre
        const used = new Set();
        const collectUsed = (node) => {
          if (!node || typeof node !== 'object') {
            return;
          }
          if (node.feature) {
            used.add(node.feature);
          }
          collectUsed(node.trueBranch);
          collectUsed(node.falseBranch);
        };

        collectUsed(decisionTree);
        const unused = labels.filter(n => !used.has(n));

        // Masquer les graphiques correspondants
        hiddenFeatureCards.clear();

        unused.forEach(col => {
          const card = elCharts && elCharts.querySelector(`.feature-card[data-col="${CSS.escape(col)}"]`);
          if (card && !card.classList.contains('d-none')) {
            card.classList.add('d-none');
            hiddenFeatureCards.add(col);
          }
        });

        const categories = Array.from(new Set(target)).filter(v => v !== '');
        const resultsByCategory = categories.map((cat) => {
          const rules = extractRulesForClass(decisionTree, cat);

          // Pour chaque règle, calculer l'ensemble des index de lignes qui correspondent
          const ruleMatchSets = rules.map(path => {
            const idxs = new Set();
            for (let i = 0; i < features.length; i++) {
              // ATTENTION: on ne compte que les lignes dont la sortie (target)
              // correspond à la catégorie courante, afin d'éviter de sur-compter
              // des lignes d'autres catégories qui satisfont aussi le chemin.
              if (matchesPath(features[i], path) && String(target[i]) === String(cat)) {
                idxs.add(i);
              }
            }
            return idxs;
          });

          // Trier les règles par nombre de correspondances décroissant pour un affichage pertinent
          const sorted = rules
          .map((path, i) => ({path, set: ruleMatchSets[i], rawCount: ruleMatchSets[i].size}))
          .filter(item => (item.rawCount || 0) > 0)
          .sort((a, b) => b.rawCount - a.rawCount);

          // Dédupliquer: n = nombre de lignes couvertes UNIQUEMENT par cette règle
          // en excluant celles déjà couvertes par les règles précédentes (dans l'ordre trié)
          const covered = new Set();
          const sortedRules = sorted.map(item => {
            let uniqueCount = 0;
            for (const idx of item.set) {
              if (!covered.has(idx)) {
                uniqueCount++;
              }
            }
            for (const idx of item.set) {
              covered.add(idx);
            }
            return {path: item.path, n: uniqueCount};
          }).filter(item => item.n > 0);

          return {cat, sortedRules};
        });

        const elResults = document.getElementById("results");

        if (!resultsByCategory.length || resultsByCategory.every(rc => rc.sortedRules.length === 0)) {
          elResults.innerHTML = `${droppedHtml}<div><b>No rule found!</b></div>`;
        } else {

          const headerDropped = `
            <div class="mb-3">
              <div class="mb-2"><b>{{ __('Dropped features') }}</b></div>
              ${(unused && unused.length) ? unused.map(
              name => `<span class="lozenge new me-1">${name}</span>`).join('')
            : '<span class="text-muted">None.</span>'}
            </div>`;

          const htmlRules = resultsByCategory.map((rc, idx) => {
            const show = false;
            const rulesHtml = rc.sortedRules.map((item, i) => {
              const pretty = prettifiesPath(item.path);
              const n = item.n ?? 0;
              return `Règle ${i + 1} (n=${n}) : ${pretty.join(" ET ")}`;
            }).join("\n");
            const toggleId = `toggle-rules-${idx}`;
            const preId = `rules-${idx}`;

            return `
              <div class="mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <div><b>Category "${String(rc.cat)}" — ${rc.sortedRules.length} rule(s)</b></div>
                  <a href="#" id="${toggleId}">(${show ? 'hide' : 'show'} rules)</a>
                </div>
                <pre id="${preId}" class="mb-0 ${show ? ''
              : 'd-none'}" style="max-height: 200px; overflow-y: auto">${rulesHtml}</pre>
              </div>
            `;
          }).join('');

          elResults.innerHTML = headerDropped + htmlRules;
          resultsByCategory.forEach((rc, idx) => {
            const elToggle = document.getElementById(`toggle-rules-${idx}`);
            const elPre = document.getElementById(`rules-${idx}`);
            if (elToggle && elPre) {
              let show = false;
              elToggle.addEventListener('click', (e) => {
                e.preventDefault();
                show = !show;
                if (show) {
                  elPre.classList.remove('d-none');
                } else {
                  elPre.classList.add('d-none');
                }
                elToggle.textContent = `(${show ? 'hide' : 'show'} rules)`;
              });
            }
          });

          const sumNByCat = {};

          resultsByCategory.forEach(rc => {
            sumNByCat[rc.cat] = (rc.sortedRules || []).reduce((acc, it) => acc + (it.n || 0), 0);
          });

          updateChartExplainer(data, filtered, sumNByCat);
        }
      };
    }

    if (elResetOptimizeBtn) {
      elResetOptimizeBtn.onclick = () => {
        if (elCharts) {
          elCharts.querySelectorAll('.feature-card.d-none').forEach(card => card.classList.remove('d-none'));
        }
        hiddenFeatureCards.clear();
        scheduleRecomputeExplainer();
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
      if (ndx && typeof ndx.allFiltered === 'function') {
        return ndx.allFiltered();
      }
      if (ndx && typeof ndx.all === 'function') {
        return ndx.all();
      }
      return data || [];
    }

    // Recompute explainer based on current filters
    const scheduleRecomputeExplainer = debounce(() => {
      const elResults = document.getElementById('results');
      if (elResults) {
        elResults.innerHTML = '';
      }
      const filtered = filteredData();
      if (!filtered || !filtered.length) {
        updateChartExplainer(data);
      } else {
        updateChartExplainer(data, filtered);
      }
    }, 200);

    // Prepare output explainer chart (two bars per category: selection vs whole dataset)
    const updateChartExplainer = (all, filtered = null, selectionOverrideByCat = null) => {
      if (!elOutputChart) {
        return;
      }
      if (outputChart && typeof outputChart.destroy === 'function') {
        outputChart.destroy();
      }

      const canvas = document.createElement('canvas');
      elOutputChart.innerHTML = '';
      elOutputChart.appendChild(canvas);
      elOutputCard.classList.remove('d-none');

      const features = Array.from(new Set(data.map(d => d['output']))).filter(v => v !== '');
      const dataGlobal = features.map(cat => all.filter(d => d['output'] === cat).length);
      const dataSelectionDefault = features.map(cat => (filtered ?? all).filter(d => d['output'] === cat).length);
      const dataSelection = selectionOverrideByCat ? features.map(cat => Number(selectionOverrideByCat[cat] || 0))
        : dataSelectionDefault;

      let selectionColors = 'gray';

      if (selectionOverrideByCat) {
        selectionColors = dataSelection.map((v, i) => {
          const base = dataSelectionDefault[i] || 0;
          if (!(base > 0)) {
            return 'gray';
          }
          const diffPct = Math.abs(v - base) / base;
          if (diffPct >= 0.66) {
            return '#e53935';
          }
          if (diffPct >= 0.33) {
            return '#fdd835';
          }
          if (diffPct > 0) {
            return '#43a047';
          }
          return 'gray';
        });
      }
      const ctx = canvas.getContext('2d');

      outputChart = new Chart(ctx, {
        type: 'bar', data: {
          labels: features, datasets: [{
            label: 'Selection', data: dataSelection, backgroundColor: selectionColors,
          }, {
            label: 'Global', data: dataGlobal, backgroundColor: 'rgb(49, 130, 189)'
          }]
        }, options: {
          responsive: true, maintainAspectRatio: false, plugins: {
            legend: {display: false}, tooltip: {
              callbacks: {
                label: (context) => {
                  const value = Number(context.raw || 0);
                  const itemsLabel = `${value} item${value === 1 ? '' : 's'}`;
                  let pctStr = '';
                  // Compute percent from the same basis used for coloring bars
                  // Coloring logic compares selectionOverrideByCat (if provided) to dataSelectionDefault
                  const isSelection = context && context.dataset && context.dataset.label === 'Selection';
                  const idx = (context && typeof context.dataIndex === 'number') ? context.dataIndex : 0;
                  if (isSelection && selectionOverrideByCat) {
                    const base = Number((Array.isArray(dataSelectionDefault) && dataSelectionDefault[idx] != null)
                      ? dataSelectionDefault[idx] : 0);
                    if (base > 0) {
                      const pct = Math.abs(value - base) / base * 100; // same metric as color thresholds
                      pctStr = ` (diff=${pct.toFixed(1)}%)`;
                    }
                  }
                  return `${context.dataset.label}: ${itemsLabel}${pctStr}`;
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
      elCard.className = 'card p-0 feature-card';
      elCard.setAttribute('data-col', colName);
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

      if (action === 'reset') {
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
