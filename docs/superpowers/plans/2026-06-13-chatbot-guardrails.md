# Chatbot Guardrails Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop invalid n8n responses from reaching website users and make live webhook testing repeatable.

**Architecture:** `ChatController` remains the enforcement boundary. Deterministic build requests stay local; other n8n responses pass through a small validator and fall back to database-backed replies when invalid. A standalone PHP CLI runner exercises the remote webhook.

**Tech Stack:** PHP 8.1, existing MVC/database helpers, cURL/stream HTTP.

---

### Task 1: Regression coverage

**Files:**
- Modify: `tests/chatbot_regression.php`

- [ ] Add failing cases for placeholder product links, price limits, and build-like n8n output.
- [ ] Correct the memory test to accept a safe no-build result when no valid configuration fits the budget.
- [ ] Run `C:\xampp\php\php.exe tests\chatbot_regression.php` and confirm the new guardrail cases fail for the expected reason.

### Task 2: Response guardrails

**Files:**
- Modify: `app/controllers/ChatController.php`

- [ ] Add a focused n8n response validation method.
- [ ] Route rejected answers to deterministic category/build fallbacks.
- [ ] Run the regression suite and confirm all cases pass.

### Task 3: Live webhook runner

**Files:**
- Create: `tests/chatbot_webhook.php`

- [ ] Add CLI argument/environment support for a webhook URL.
- [ ] Run representative P0 prompts with isolated session IDs.
- [ ] Print PASS/FAIL, latency, and response details; return nonzero when P0 checks fail.

### Task 4: Verification

**Files:**
- Verify: `app/controllers/ChatController.php`
- Verify: `tests/chatbot_regression.php`
- Verify: `tests/chatbot_webhook.php`

- [ ] Run PHP syntax checks.
- [ ] Run the complete local regression suite.
- [ ] Run the live webhook suite and record remaining workflow/RAG failures separately from website guardrail behavior.

