# Chatbot Guardrails Design

## Goal

Prevent unsafe or invalid n8n answers from reaching customers while preserving n8n for product discovery and general advice.

## Design

- Keep deterministic PC builds in PHP. Build requests must collect purpose and budget, select eight in-stock categories, validate CPU/mainboard socket and RAM type, enforce the 5% budget ceiling, and use database-backed product links.
- Validate n8n text before returning it. Reject placeholder links, unsupported price-filter recommendations, and build-like responses that bypass the deterministic builder.
- When validation fails, route to the existing database-backed category/build fallback instead of trying to repair untrusted text.
- Add a command-line webhook runner for repeatable live checks. It records latency, response text, and deterministic assertions without modifying production data.

## Testing

- Unit/regression tests cover build routing, memory, budget handling, placeholder links, and price-filter violations.
- The webhook runner covers representative P0 cases and can be expanded independently from production code.

