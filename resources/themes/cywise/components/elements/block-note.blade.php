{{--
  BlockNote editor (React, resources/js/block-note.jsx) with a live table of contents.

    ┌──────────┬──────────────────────────┐
    │ contents │ document (#block-note)   │
    │ (sticky) │                          │
    └──────────┴──────────────────────────┘
--}}
<div class="ui:grid ui:items-start ui:gap-6 ui:lg:grid-cols-[16rem_1fr]">
    <nav class="ui:hidden ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:p-4 ui:shadow-xs ui:lg:sticky ui:lg:top-20 ui:lg:block ui:lg:max-h-[calc(100dvh-6rem)] ui:lg:overflow-y-auto"
         aria-label="{{ __('Table of contents') }}">
        <div class="ui:mb-2 ui:text-xs ui:font-semibold ui:uppercase ui:tracking-wider ui:text-slate-400">{{ __('Table of contents') }}</div>
        <div id="block-note-headings" class="ui:flex ui:flex-col ui:gap-0.5"></div>
    </nav>
    <div class="ui:min-w-0 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-surface ui:py-6 ui:shadow-xs">
        <div id="block-note"></div>
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
    // Indent by heading level: h1 flush, h2 / h3 nested
    const headingIndent = {1: '', 2: 'ui:pl-3', 3: 'ui:pl-6'};
    // Headings come from the document content: never inject them as HTML
    const escapeHtml = (text) => {
        const el = document.createElement('span');
        el.textContent = text;
        return el.innerHTML;
    };
    const updateSummary = () => {
        const summary = headings();
        const elSummary = document.getElementById('block-note-headings');

        if (summary.length === 0) {
            elSummary.innerHTML = `<span class="ui:text-sm ui:text-slate-400">${@js(__('Load a template to start.'))}</span>`;
            return;
        }
        elSummary.innerHTML = summary.map(heading => `
            <a id="bn-${heading.id}" href="#"
               class="${headingIndent[heading.level] ?? 'ui:pl-6'} ${heading.level === 1 ? 'ui:font-medium ui:text-ink!' : 'ui:text-slate-600!'} ui:block ui:truncate ui:rounded-md ui:py-1 ui:pr-2 ui:text-sm ui:no-underline! ui:hover:bg-slate-100 ui:hover:text-ink!">${escapeHtml(heading.text ?? '')}</a>
        `).join('');
    };
    setInterval(updateSummary, 3000);
    document.addEventListener('click', e => {
        if (e.target.tagName === 'A' && e.target.id.startsWith('bn-')) {
            const elBlock = document.querySelector(`[data-id='${e.target.id.substring(3)}']`);
            if (elBlock) {
                elBlock.scrollIntoView({behavior: 'smooth', block: 'start'});
            }
            e.preventDefault();
            e.stopPropagation();
        }
    });
</script>
