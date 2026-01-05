<div class="row mt-2">
    <div class="col">
    <textarea id="prompt"
              class="form-control mt-2"
              rows="4"
              placeholder="{{ __('Please provide a detailed prompt to generate a draft SQL query.') }}"></textarea>
    </div>
</div>
<div class="row mt-2">
    <div class="col text-center">
        <button id="prompt-to-query" class="btn btn-primary">{{ __('Generate!') }}</button>
    </div>
</div>
<div class="row mt-2">
    <div class="col">
        <div id="editor" style="height:300px;width:100%;"></div>
    </div>
</div>
<div class="row mt-2">
    <div class="col text-center">
        <button id="execute-sql-query" class="btn btn-primary">{{ __('Execute!') }}</button>
    </div>
</div>
<div class="row mt-2">
    <div class="col">
        <table id="query-result" class="table">
            <thead>
            <!-- FILLED DYNAMICALLY -->
            </thead>
            <tbody>
            <!-- FILLED DYNAMICALLY -->
            </tbody>
        </table>
    </div>
</div>
<div class="row mt-2">
    <div class="col">
        <x-tables-list/>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.6.0/ace.js"></script>
<script>

    const editor = ace.edit("editor");
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/sql");

    const elPromptToQuery = document.getElementById('prompt-to-query');
    elPromptToQuery.addEventListener('click', (event) => {

        event.preventDefault();
        event.stopPropagation();

        const prompt = document.getElementById('prompt').value;

        promptToQueryApiCall(prompt, response => editor.setValue(response.query));
    });

    const elExecuteSqlQuery = document.getElementById('execute-sql-query');
    elExecuteSqlQuery.addEventListener('click', (event) => {

        event.preventDefault();
        event.stopPropagation();

        const elQueryResult = document.getElementById('query-result');
        const elTableHead = elQueryResult.querySelector('thead');
        const elTableBody = elQueryResult.querySelector('tbody');

        elTableHead.innerHTML = "<tr><th></th></tr>";
        elTableBody.innerHTML = "<tr><td>{{ __('Loading...') }}</td></tr>";

        const sql = editor.getValue();

        executeSqlQueryApiCall(sql, response => {
            if (response.data.length >= 0) {
                elTableHead.innerHTML = `<tr>${response.data[0].map(column => `<th>${column}</th>`).join('')}</tr>`;
                if (response.data.length === 1) {
                    elTableBody.innerHTML = `<tr><td colspan='${response.data[0].length}'>{{ __('No results found.') }}</td></tr>`;
                } else {
                    elTableBody.innerHTML = response.data.slice(1)
                        .map(row => `<tr>${row.map(column => `<td>${column}</td>`).join('')}</tr>`)
                        .join('');
                }
            }
        });
    });

</script>