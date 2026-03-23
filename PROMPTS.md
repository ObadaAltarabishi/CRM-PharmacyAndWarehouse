# PROMPTS.md

## New Chat Starter Prompt
```text
You are working inside my existing pharmacy/warehouse backend repo.
First, read AGENT.md, PROJECT_CONTEXT.md, DECISIONS.md, API_PROGRESS.md, and TESTING_NOTES.md.
Then summarize current context in 8-12 bullets, list risks, and propose the smallest safe next step.
Do not over-engineer and do not assume unverified features are complete.
```

## Codex Task Prompt
```text
Act as a careful backend coding partner.
Goal: [describe task].
Constraints:
- Keep changes minimal and safe.
- Preserve controller-centric structure unless I explicitly request refactor.
- Use dedicated FormRequest for new endpoints.
- Keep responses JSON-first and role-safe.
- Add/update tests for behavior changes.
Before coding, briefly state files you will change and risk points.
After coding, summarize changed files, behavior impact, tests run, and TODO/verify items.
```

## Code Review Prompt
```text
Review the current changes with focus on:
1) bugs/regressions,
2) stock/totals correctness,
3) role authorization safety,
4) transaction boundaries,
5) missing tests.
Return findings ordered by severity with exact file references.
If uncertain, mark as TODO/verify.
```

## Minimal-Safe-Change Prompt
```text
Implement only the smallest safe change for this request: [task].
Do not refactor unrelated code.
Do not introduce new tooling.
Keep naming/style consistent with nearby files.
If there are assumptions, state them explicitly at the end.
```

## Continue-From-Context Prompt
```text
Continue from repository context files:
- AGENT.md
- PROJECT_CONTEXT.md
- DECISIONS.md
- API_PROGRESS.md
- TESTING_NOTES.md
Use them as source of truth.
First output:
- what is already done,
- what is in progress,
- what you will do next now.
Then proceed with implementation.
```

## Compact Project Brief Template
```text
Project: Pharmacy + Warehouse Management Backend
Stack: PHP, Laravel 12, Sanctum, MySQL, Pest
API Style: JSON-first, role-based (`admin`, `pharmacy`, `warehouse`)
Core Rules:
- Sanctum abilities: admin/pharmacy/warehouse
- Product shared by barcode
- PharmacyProduct = pharmacy stock/sell data
- WarehouseProduct = warehouse stock/cost data
- OrderCart/SalesCart are temporary before final submission
- Sales discount >= 20% requires confirmation
- Stock-sensitive flows must use transactions + careful validation
Current Status: [paste from API_PROGRESS.md]
Task Needed: [describe]
Constraints: minimal safe change, controller-centric consistency, FormRequest for new endpoints
```

## Session Handoff Snippet Template
```text
Session date: YYYY-MM-DD
What I changed:
Files touched:
Behavior impact:
Tests run:
Open risks/TODO:
Next recommended step:
```