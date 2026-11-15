# Agent Workflow

## Communication

Apply ASD-STE100 Simplified Technical English writing principles to **all languages** and all human-facing text, including comments, reviews, replies, and commits:

- Use the active voice.
- Use 20 words or fewer per sentence.
- Express one idea per sentence.
- Prefer simple present, past, and future tenses.
- Use simple, consistent terms. Avoid idioms, slang, and unnecessary jargon.
- Keep paragraphs to six sentences or fewer.
- Use as few words as possible without losing necessary information.
- Avoid superlatives, praise, and unnecessary agreement.
- State facts directly.

For languages other than English, apply equivalent clarity rules.

## Rules

GitHub Issues are the sole source of truth.

## Roles

### Define

The [Define skill](.junie/skills/define.md) creates requirements. It creates GitHub Issues with descriptions and acceptance criteria. It sets the issue status to `todo` after user validation.

### Decompose

The [Decompose skill](.junie/skills/decompose.md) breaks complex tasks into sub-tasks. It creates sub-item issues in `ready` status. It adds technical requirements and test descriptions to each sub-item.

### Develop

The [Develop skill](.junie/skills/develop.md) implements features and fixes bugs. It creates feature branches. It implements changes for each sub-item and ensures unit tests pass.

### Review and Merge

The [Review and Merge skill](.junie/skills/review_merge.md) verifies completed tasks. It summarizes the work for user approval. It merges branches and closes issues after approval.

## Project Management Workflow

1. **Define**: Create the main issue and specify requirements.
2. **Decompose**: Break the main issue into technical sub-tasks.
3. **Develop**: Implement changes in a feature branch.
4. **Review and Merge**: Verify the work and merge it into the main branch.
