# 🧪 WMS Multi Gudang — Comprehensive Testing Plan

> **Version:** 1.0  
> **Date:** 2026-06-11  
> **Audit Coverage:** 28 cards, 64 findings (all remediated)

---

## 1. Testing Pyramid Overview

```
        E2E (Playwright)
       +-----------+
       |   5-10    |  Critical user journeys
       +-----------+
       |   20+     |  Component (Vitest)
       |   15+     |  Feature/API (PHPUnit)
       +-----------+
       |   50+     |  Unit (PHPUnit)
       +-----------+
```

| Layer | Tool | Coverage Target | Priority |
|-------|------|----------------|----------|
| Unit | PHPUnit 10.5 | Services, Models, Policies | P0 |
| Feature/API | PHPUnit 10.5 | Controllers, Auth, Permissions | P1 |
| Component | Vitest | Vue components, Pinia stores | P2 |
| E2E | Playwright | Critical user journeys | P2 |
| Database | PHPUnit | Transaction integrity, constraints | P0 |
| Performance | k6/Custom | Reports, stock operations | P3 |

---

## 2. Backend — Unit Tests

### 2.1 InventoryService (existing: InventoryServiceTest.php)

| # | Test Case | What It Verifies | Audit Ref |
|---|-----------|------------------|-----------|
| 1 | receiveStock wraps in DB transaction | Rollback on exception -> no partial save | R5 |
| 2 | issueStock wraps in DB transaction | Rollback on exception -> no orphan transactions | R5/issueStock |
| 3 | adjustStock wraps in DB transaction | Rollback on exception | R5/adjustStock |
| 4 | transferStock uses row-level locking | Concurrent transfers don't double-allocate | R5/transferStock |
| 5 | issueStock insufficient stock throws | Exception thrown, no inventory mutation | R5 |
| 6 | issueStock race condition | Two concurrent calls -> only one succeeds | R5 |
| 7 | Variance recalculated server-side | Manipulated client qty rejected -> server recalc | R3 |
| 8 | Cache invalidation on stock mutation | Cache::forget called after stock change | R8/R15 |

### 2.2 DocumentSequenceService

| # | Test Case | What It Verifies | Audit Ref |
|---|-----------|------------------|-----------|
| 9 | generate('RET') returns next number | Method call resolves correctly | R3 |
| 10 | getNextNumber() increments per prefix | RINV-00001 -> RINV-00002 | R3 |
| 11 | Unique constraint on (prefix, date) | No duplicate sequence numbers | R7 |

### 2.3 StockOpnamePolicy

| # | Test Case | What It Verifies | Audit Ref |
|---|-----------|------------------|-----------|
| 12 | start() returns true for authorized roles | Warehouse Manager, Admin can start | R1 |
| 13 | submit() returns true for authorized roles | Warehouse Manager, Admin can submit | R1 |
| 14 | cancel() returns true for authorized roles | Admin can cancel pending opname | R14 |
| 15 | start() returns false for Staff role | Staff cannot initiate stock opname | R1 |

### 2.4 NotificationService

| # | Test Case | What It Verifies | Audit Ref |
|---|-----------|------------------|-----------|
| 16 | notifyUsers skips duplicate within 24h | Same type+title in last 24h -> no duplicate | R22 |
| 17 | notifyUsers creating notifications has timestamps | created_at/updated_at set correctly | R22 |

### 2.5 Product Model

| # | Test Case | What It Verifies | Audit Ref |
|---|-----------|------------------|-----------|
| 18 | SKU uniqueness enforced on create | Duplicate SKU -> validation error | R17 |
| 19 | SKU uniqueness enforced on update | Same SKU allowed on same product | R17 |
| 20 | Soft delete filters active products | deleted_at queries exclude soft-deleted | R9 |

---

## 3. Backend — Feature/API Tests

### 3.1 Auth (existing: AuthTest.php)

| # | Test Case | Audit Ref |
|---|-----------|-----------|
| 1 | Login returns token | - |
| 2 | Invalid credentials returns 401 | - |
| 3 | CSRF cookie endpoint works | R21 |
| 4 | Unauthenticated -> 401 | - |
| 5 | Staff cannot access /users | R10 |

### 3.2 Stock Opname API (B14)

| # | Test Case | Audit Ref |
|---|-----------|-----------|
| 6 | POST /stock-opnames - create | R1 |
| 7 | POST /stock-opnames/{uuid}/start | R1 |
| 8 | POST /stock-opnames/{uuid}/submit | R1 |
| 9 | POST /stock-opnames/{uuid}/cancel | R14 |
| 10 | PUT /stock-opnames/{uuid} - upsert items | R2 |
| 11 | Staff cannot call start/submit (403) | R1 |

### 3.3 Inbound / Outbound / Transfer

| # | Test Case | Audit Ref |
|---|-----------|-----------|
| 12 | POST /inbounds/{uuid}/receive - DB transaction | R16 |
| 13 | POST /outbounds/{uuid}/pick - row locking | R5 |
| 14 | POST /transfers/{uuid}/execute - transaction | R5 |
| 15 | Concurrent transfer race condition | R5 |

### 3.4 RBAC (B11)

| # | Test Case | Audit Ref |
|---|-----------|-----------|
| 16 | Staff GET /settings - 403 | R10 |
| 17 | Staff GET /audit-logs - 403 | R10 |
| 18 | Staff GET /users - 403 | R10 |

---

## 4. Database Integrity Tests

| # | Test Case | Audit Ref |
|---|-----------|-----------|
| 1 | Rollback on issueStock crash | R5 |
| 2 | Rollback on transferStock crash | R5 |
| 3 | Rollback on adjustStock crash | R5 |
| 4 | Concurrent transfer - PESSIMISTIC_WRITE | R5 |
| 5 | StockOpname upsert - no data loss | R2 |
| 6 | Inbound receive - rollback on crash | R16 |
| 7 | Document sequence unique constraint | R7 |

---

## 5. Frontend Tests (Vitest)

### Setup
```bash
cd frontend
npm install -D vitest @vue/test-utils happy-dom
```

| # | Test Case | Audit Ref |
|---|-----------|-----------|
| 1 | Store refresh after create | R11 |
| 2 | Selected state cleared on navigate | R11 |
| 3 | Planogram notify.error on load fail | R24 |
| 4 | Planogram hasChanges guard | R24 |
| 5 | API CSRF 419 retry capped at 3 | R21 |
| 6 | API success toast on mutation | R25 |

---

## 6. E2E Tests (Playwright)

Playwright already installed. Create frontend/e2e/.

### Critical Journeys

| # | Scenario | Audit Ref |
|---|----------|-----------|
| 1 | Login -> Dashboard | R21 |
| 2 | Create warehouse -> see in list | R11 |
| 3 | Edit warehouse -> verify update | R11 |
| 4 | Delete warehouse -> confirmation | R11 |
| 5 | Create product with SKU | R17 |
| 6 | Duplicate SKU rejected | R17 |
| 7 | Inbound create -> receive -> stock updates | R16 |
| 8 | Outbound create -> pick -> ship | R5 |
| 9 | Stock opname full cycle | R1,R2,R14 |
| 10 | Transfer create -> execute | R5 |
| 11 | Planogram unsaved changes warning | R24 |
| 12 | Staff blocked from /settings | R10 |

---

## 7. Manual QA Checklist

- [ ] Stock Opname CRUD: all status transitions
- [ ] Inbound Receive: quantity reflected in inventory
- [ ] Outbound Pick: no double allocation
- [ ] Transfer Execute: atomic source+destination
- [ ] Concurrent stock issue -> only 1 succeeds, no negative stock
- [ ] Large report (>100K rows) -> no timeout
- [ ] CORS: only allowed origins work
- [ ] Auth: unauthenticated -> redirect login
- [ ] RBAC: Staff blocked from sensitive routes
- [ ] Docker: compose up succeeds
- [ ] MinIO: credentials from .env
- [ ] Scheduler: no overlapping runs

---

## 8. Execution Priority

| Phase | Priority | Tests | Effort |
|-------|----------|-------|--------|
| P0 | Must pass | Unit tests for R1-R6 (audit critical) | 8-16h |
| P0 | Must pass | Database integrity (transactions, locks) | 4-8h |
| P1 | High | Feature/API tests all CRUD | 16-24h |
| P1 | High | RBAC & permission tests | 4-8h |
| P2 | Should | E2E critical journeys (Playwright) | 16-24h |
| P2 | Should | Component tests (Vitest) | 8-12h |
| P3 | Nice | Performance/Load tests | 8-12h |

### Rapid Win - Day 1

```bash
# 1. Run existing tests (baseline)
cd backend && php artisan test --parallel

# 2. Write StockOpnamePolicyTest (4 cases)
# 3. Write DocumentSequenceServiceTest (2 cases)
# 4. Write InventoryService transaction tests (3 cases)
# 5. Re-run all tests - no regressions
```

---

## 9. CI Integration

GitHub Actions: PHP 8.3 + PostgreSQL 16 + Redis for backend tests. Node 20 for frontend vitest + Playwright E2E.

---

## Appendix: Audit Coverage Map

| Finding | Test Layer | Priority | Status |
|---------|-----------|----------|--------|
| R1-R6 | Unit + API | P0 | Write |
| R7-R11 | API + DB | P1 | Write |
| R12-R15 | E2E + Perf | P2 | Write |
| R16-R28 | Various | P0-P1 | Verified in code |
