<style>
    .bn-summary {
        background-color: var(--bs-light);
        border: var(--bs-card-border-width) solid var(--bs-card-border-color);
        border-radius: var(--bs-card-border-radius);
    }

    .bn-h1 {
        padding-left: 0;
        text-overflow: ellipsis;
    }

    .bn-h2 {
        padding-left: 0;
        text-overflow: ellipsis;
    }

    .bn-h3 {
        padding-left: 0.5rem;
        text-overflow: ellipsis;
    }

    .bn-h4 {
        padding-left: 1rem;
        text-overflow: ellipsis;
    }
</style>
<div class="container-fluid">
    <div class="row p-0">
        <div class="col col-3 bn-summary" id="block-note-headings">
            <div class="pt-2 pb-2 bn-h1"><b>Table des matières</b></div>
        </div>
        <div class="col">
            <div id="block-note"></div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', () => {
        window.BlockNote.render("block-note", {});
        window.BlockNote.observers = new com.computablefacts.observers.Subject();
        window.BlockNote.observers.register('template-change', template => {
            if (template) {
                window.BlockNote.ctx.editor.replaceBlocks(window.BlockNote.ctx.blocks /* old */, template.template /* new */);
            } else {
                window.BlockNote.ctx.editor.removeBlocks(window.BlockNote.ctx.blocks /* current */);
            }
            updateSummary();
        });
    });
    const headings = () => {
        if (window.BlockNote && window.BlockNote.ctx && window.BlockNote.ctx.blocks) {
            return window.BlockNote.ctx.blocks
                .filter(block => block.type === 'heading')
                .map(block => {
                    return {
                        id: block.id,
                        level: block.props.level,
                        text: block.content.length === 0 ? null : block.content[0].text
                    };
                });
        }
        return [];
    };
    const updateSummary = () => {
        const summary = headings();
        const elSummary = document.getElementById('block-note-headings');
        elSummary.innerHTML = `
      <div class="pt-2 ${summary.length === 0 ? 'pb-2' : 'pb-1'} bn-h1">
        <b>Table des matières</b>
      </div>
      ${summary.map(heading => {
            if (heading.level === 1) {
                return `<div class="bp4-text-overflow-ellipsis pt-1 bn-h2"><a id="bn-${heading.id}" href="#">${heading.text}</a></div>`;
            }
            if (heading.level === 2) {
                return `<div class="bp4-text-overflow-ellipsis pt-1 bn-h3"><a id="bn-${heading.id}" href="#">${heading.text}</a></div>`;
            }
            return `<div class="bp4-text-overflow-ellipsis pt-1 bn-h4"><a id="bn-${heading.id}" href="#">${heading.text}</a></div>`;
        }).join('')}
    `;
    };
    setInterval(updateSummary, 3000);
    document.addEventListener('click', e => {
        if (e.target.tagName === 'A' && e.target.id.startsWith('bn-')) {
            const elBlock = document.querySelector(`[data-id='${e.target.id.substring(3)}']`);
            if (elBlock) {
                elBlock.scrollIntoView();
            }
            e.preventDefault();
            e.stopPropagation();
        }
    });
</script>