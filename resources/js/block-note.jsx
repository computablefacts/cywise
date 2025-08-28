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
    return (<div style={{width: "100%"}}>{props.block.props.questions.map(question => {
      return (<div key={question}>
        <div style={{
          backgroundColor: "var(--ds-background-discovery)", color: "var(--ds-text-discovery)", padding: "3px"
        }}>
          {question}
        </div>
        <input type={"text"}
               style={{width: "100%", border: "none", padding: "3px", outline: "unset"}}
               onChange={(event) => handleChange(event, question)}
               placeholder={"Saisissez votre réponse ici..."}
               disabled={loading}
               required>
        </input>
      </div>);
    })}
      <div className={"d-flex justify-content-center"}>
        <input type={"button"}
               value={"Générer!"}
               style={{backgroundColor: "#0d6efd", color: "white", border: "none", padding: "10px"}}
               disabled={loading}
               onClick={handleClick}>
        </input>
        {loading && <span className="tw-loader-25 align-self-center ml-3"></span>}
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
      <div style={{width: "100%", display: "flex", justifyContent: "center", alignItems: "center", flexGrow: "1"}}>
        <div style={{
          backgroundColor: "var(--ds-background-discovery)", color: "var(--ds-text-discovery)", padding: "3px"
        }}>
          @{props.block.props.assistant_name}&nbsp;
        </div>
        {props.block.props.collections.length > 0 && <Menu withinPortal={false} zIndex={999999}>
          <Menu.Target>
            <div style={{
              cursor: "pointer",
              backgroundColor: "var(--ds-background-information)",
              color: "var(--ds-text-information)",
              padding: "3px"
            }}>
              &nbsp;{props.block.props.collection}&nbsp;
            </div>
          </Menu.Target>
          <Menu.Dropdown>
            {props.block.props.collections.map(col => {
              return (<Menu.Item key={col} onClick={() => handleCollectionChange(col)}>{col}</Menu.Item>);
            })}
          </Menu.Dropdown>
        </Menu>}
        <input type={"text"}
               style={{flexGrow: "1", border: "none", padding: "3px", outline: "unset", minHeight: "30px"}}
               ref={inputRef}
               disabled={loading}
               onKeyDown={handleKeyDown}
               onChange={handlePromptChange}
               placeholder={"Saisissez vos instructions ici..."}
               value={props.block.props.prompt}
               autoFocus
               required>
        </input>
        {loading && <span className="tw-loader-25"></span>}
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
  return (<BlockNoteView
    editor={editor}
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
