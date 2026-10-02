import React, {useEffect, useRef, useState} from 'react';
import "@blocknote/core/fonts/inter.css";
import {BlockNoteView} from "@blocknote/mantine";
import "@blocknote/mantine/style.css";
import {BlockNoteSchema, defaultBlockSpecs, filterSuggestionItems, insertOrUpdateBlock} from "@blocknote/core";
import {
  createReactBlockSpec, getDefaultReactSlashMenuItems, SuggestionMenuController, useCreateBlockNote
} from "@blocknote/react";
import {createRoot} from 'react-dom/client';
import {HiSparkles} from "react-icons/hi";
import {Menu} from "@mantine/core";

const ctx = {
  history: [],
};

const text2blocks = (text) => {
  return text.split("\n")
  .map(block => {
    block = block.trim();
    if (block.startsWith("- ")) {
      return {type: "bulletListItem", content: block.substring(2).trim()};
    }
    return {type: "paragraph", content: block};
  })
  .filter(block => block.content.length > 0);
};

const markdown2blocks = (props, text) => {
  props.editor.tryParseMarkdownToBlocks(text).then(blocks => props.editor.insertBlocks(blocks, props.block, 'after'));
};

// This component render a list of questions. The user answers the questions. Then, a paragraph is generated using the
// provided paragraph template and answers.
const QaBlock = createReactBlockSpec({
  type: "qa_block", propSchema: {
    questions: {
      default: [],
    }, answers: {
      default: [],
    }, template: {
      default: "",
    }, prompt: {
      default: "",
    },
  }, content: "inline",
}, {
  render: (props) => {

    // Show/hide loader
    const [loading, setLoading] = useState(false);

    // When an answer is updated, update the underlying data structure
    const handleChange = (event, question) => {
      const answers = [...props.block.props.answers];
      const answer = answers.find(answer => answer.question === question);
      if (answer) {
        answer.answer = event.target.value;
      } else {
        answers.push({question: question, answer: event.target.value});
      }
      props.editor.updateBlock(props.block, {type: "qa_block", props: {answers: answers}});
    };

    // Submit the questions and answers to the LLM
    const handleClick = (event) => {
      setLoading(true);
      axios.post(`/llm2`, {
        template: props.block.props.template,
        prompt: props.block.props.prompt,
        q_and_a: props.block.props.answers.map(answer => {
          return {question: answer.question, answer: answer.answer};
        })
      })
      .then(function (response) {
        if (response.data) {
          markdown2blocks(props, response.data);
        } else {
          console.log(response.data);
        }
      })
      .catch(error => console.log(error))
      .finally(() => setLoading(false));
    };
    // Design system classes (ui: prefix, see resources/themes/cywise/assets/css/ui.css)
    return (<div className="ui:my-2 ui:flex ui:w-full ui:flex-col ui:gap-3 ui:rounded-xl ui:border ui:border-solid ui:border-line ui:bg-slate-50 ui:p-4">
      {props.block.props.questions.map(question => {
        return (<label key={question} className="ui:m-0 ui:flex ui:flex-col ui:gap-1.5">
          <span className="ui:text-sm ui:font-medium ui:text-ink">{question}</span>
          <input type={"text"}
                 className="ui:h-9 ui:w-full ui:rounded-lg ui:border ui:border-solid ui:border-line ui:bg-white ui:px-3 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:focus:border-brand-500 ui:focus:outline-none ui:focus:ring-2 ui:focus:ring-brand-100"
                 onChange={(event) => handleChange(event, question)}
                 placeholder={"Saisissez votre réponse ici..."}
                 disabled={loading}
                 required>
          </input>
        </label>);
      })}
      <div className="ui:flex ui:items-center ui:justify-end">
        {!loading && <button type={"button"}
                             className="ui:inline-flex ui:h-9 ui:items-center ui:gap-2 ui:rounded-lg ui:border-0 ui:bg-brand-500 ui:px-4 ui:text-sm ui:font-medium ui:text-white ui:cursor-pointer ui:hover:bg-brand-600"
                             onClick={handleClick}>
          <HiSparkles size={16}/> Générer
        </button>}
        {loading && <span className="loader-25"></span>}
      </div>
    </div>);
  }
});

// This component render a single question. An answer to this question will be provided using the selected collection.
const AiBlock = createReactBlockSpec({
  type: "ai_block", propSchema: {
    assistant_name: {
      default: "CyberBuddy",
    }, collections: {
      default: [],
    }, collection: {
      default: "",
    }, prompt: {
      default: "",
    },
  }, content: "inline",
}, {
  render: (props) => {

    // Show/hide loader
    const [loading, setLoading] = useState(false);

    // Focus the input element
    const inputRef = useRef(props.contentRef);
    useEffect(() => {
      const timer = setTimeout(() => inputRef.current?.focus(), 10);
      return () => clearTimeout(timer);
    }, [props.block.props.collection]);

    // When the collection is updated, update the underlying data structure
    const handleCollectionChange = (col) => {
      props.editor.updateBlock(props.block, {type: "ai_block", props: {collection: col}});
      const timer = setTimeout(() => inputRef.current?.focus(), 10);
      return () => clearTimeout(timer);
    };

    // When the prompt is updated, update the underlying data structure
    const handlePromptChange = (event) => {
      props.editor.updateBlock(props.block, {type: "ai_block", props: {prompt: event.target.value}});
    };

    // Submit the prompt to the LLM
    const handleKeyDown = (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        const propz = props.block.props;
        if (propz.prompt && propz.prompt.trim()) {
          setLoading(true);
          axios.post(`/llm1`, {collection: propz.collection, prompt: propz.prompt})
          .then(function (response) {
            if (response.data) {
              markdown2blocks(props, response.data);
              // insertOrUpdateBlock(props.editor, {type: "paragraph", content: response.data});
            } else {
              console.log(response.data);
            }
          })
          .catch(error => console.log(error))
          .finally(() => setLoading(false));
        }
      }
    };
    return (
      <div className="ui:my-1 ui:flex ui:w-full ui:flex-1 ui:items-center ui:gap-2 ui:rounded-lg ui:border ui:border-solid ui:border-brand-200 ui:bg-brand-50 ui:px-2 ui:py-1">
        <span className="ui:inline-flex ui:items-center ui:gap-1 ui:rounded-md ui:bg-brand-500 ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ui:text-white">
          <HiSparkles size={12}/> {props.block.props.assistant_name}
        </span>
        {props.block.props.collections.length > 0 && <Menu withinPortal={false} zIndex={999999}>
          <Menu.Target>
            <span className="ui:cursor-pointer ui:rounded-md ui:bg-white ui:px-2 ui:py-0.5 ui:text-xs ui:font-medium ui:text-slate-700 ui:ring-1 ui:ring-inset ui:ring-slate-200 ui:hover:bg-slate-100">
              {props.block.props.collection}
            </span>
          </Menu.Target>
          <Menu.Dropdown>
            {props.block.props.collections.map(col => {
              return (<Menu.Item key={col} onClick={() => handleCollectionChange(col)}>{col}</Menu.Item>);
            })}
          </Menu.Dropdown>
        </Menu>}
        <input type={"text"}
               className="ui:min-h-8 ui:flex-1 ui:border-0 ui:bg-transparent ui:px-1 ui:text-sm ui:text-ink ui:placeholder:text-slate-400 ui:outline-none"
               ref={inputRef}
               disabled={loading}
               onKeyDown={handleKeyDown}
               onChange={handlePromptChange}
               placeholder={"Saisissez vos instructions ici, puis Entrée..."}
               value={props.block.props.prompt}
               autoFocus
               required>
        </input>
        {loading && <span className="loader-25"></span>}
      </div>)
  }
});

const getCustomSlashMenuItems = (editor, isSlash) => {

  const items = isSlash ? getDefaultReactSlashMenuItems(editor).filter(
    (item) => item.group !== 'Media' && item.group !== 'Others') : [];

  if (!isSlash) {

    items.push({
      group: 'Cywise',
      key: 'ai_command',
      icon: <HiSparkles size={18}/>,
      title: 'CyberBuddy',
      subtext: 'Use AI to generate paragraph',
      onItemClick: () => {
        listCollectionsApiCall(response => {
          const collections = response.collections.map(collection => collection.name);
          insertOrUpdateBlock(editor, {
            type: "ai_block", props: {
              assistant_name: 'CyberBuddy', collection: collections[0], collections: collections,
            }
          });
        });
      },
    });
  }
  return items;
};

function BlockNoteElement() {
  const editor = useCreateBlockNote(ctx.settings);
  ctx.editor = editor;
  ctx.blocks = editor.document;
  // Light theme forced: the app has no dark mode yet (BlockNote follows the OS setting otherwise)
  return (<BlockNoteView
    editor={editor}
    theme="light"
    slashMenu={false}
    onChange={() => {
      ctx.blocks = editor.document;
    }}
  >
    <SuggestionMenuController
      triggerCharacter={"/"}
      getItems={async (query) => filterSuggestionItems(getCustomSlashMenuItems(editor, true), query)}
    />
    <SuggestionMenuController
      triggerCharacter={"@"}
      getItems={async (query) => filterSuggestionItems(getCustomSlashMenuItems(editor, false), query)}
    />
  </BlockNoteView>);
}

function renderBlockNote(id, settings) {
  const el = document.getElementById(id);
  if (el) {
    ctx.settings = settings;
    ctx.settings.schema = BlockNoteSchema.create({
      blockSpecs: {
        ...defaultBlockSpecs, ai_block: AiBlock, qa_block: QaBlock,
      },
    });
    const root = createRoot(el);
    root.render(<BlockNoteElement/>);
  }
}

const BlockNote = {
  render: renderBlockNote, observers: null, ctx: ctx,
};

export {
  BlockNote as default
};
