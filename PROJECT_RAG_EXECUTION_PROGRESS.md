# سجل تنفيذ مشروع RAG

> **المرجع المعماري:** `PROJECT_RAG_MASTER_PLAN.md`  
> **الغرض:** حفظ الحالة التنفيذية الفعلية ونقطة الاستلام بين المحادثات  
> **آخر تحديث:** 2026-08-31  
> **الحالة العامة:** قيد التنفيذ — H2 merged; H3 is next

---

# CURRENT HANDOFF — نقطة الاستلام

> **هذا هو أول قسم يُقرأ في أي Chat جديد.**

```text
Project Mode: Start From Scratch
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Active Development

Verified Main Commit: 354c11e9195369d261000ff1c6d4f76e561e3940
Last Merged Feature PR on main: #82 — refactor(H2): align processing DTO contracts

Current Working Branch: main

Latest Completed Architectural Initiative:
ARC-1 — Remove Compare/Winner lifecycle

Latest Completed Task:
H2 — Processing DTOs and Contract Alignment

Current Phase:
H — Processing Orchestration

Architectural Result:
- One trusted Processing Profile per ProcessingRun: cloud | hybrid_local.
- No Compare upload workflow.
- No Winner/Loser selection lifecycle.
- No temporary comparison artifacts or promotion flow.
- Direct persistent Qdrant indexing is the target path.
- active_processing_run_id is the document pointer to the current indexed run.

Latest Verification:
FastAPI focused tests: 4 passed
Laravel DTO focused tests: 2 passed (8 assertions)
FastAPI full regression: 131 passed
Laravel full regression: 52 passed (210 assertions)
Laravel Pint: PASS

Next Planned Task:
H3 — FastAPI single-profile Process Document API / application orchestration

Open Blockers: none
```

---

# 1. مصادر الحقيقة

- `PROJECT_RAG_MASTER_PLAN.md` = المرجع الأعلى للمعمارية والنطاق.
- هذا الملف = **Single Source of Truth للحالة التنفيذية ونقطة الاستلام**.
- Git وPull Requests = التاريخ التفصيلي للتنفيذ السابق.
- أي تصميم سابق لا يظهر في خريطة المهام النشطة هنا لا يوجّه تنفيذاً جديداً.
- عند التعارض، يجب تحديث هذا الملف والـMaster Plan معاً قبل بدء Task جديدة.

---

# 2. قواعد التنفيذ

1. لا تعتبر أي Task `DONE` قبل التنفيذ والتحقق المناسب.
2. لا نفترض نجاح Command أو Test قبل رؤية الناتج.
3. Clean Code وSeparation of Concerns وLow Coupling إلزامية.
4. لا Scope expansion بلا قرار معماري واضح.
5. لا نحذف أو نغير Domain/Schema قبل Reference Audit مناسب.
6. أثناء التطوير الحالي، ومع قاعدة بيانات محلية فارغة وقبل وجود بيانات يجب الحفاظ عليها، يمكن Consolidate للـbaseline migrations بعد تحقق صريح.
7. بعد وجود بيانات يجب الحفاظ عليها أو بعد اعتماد Release baseline، تكون تغييرات الـSchema عبر Forward Migrations.
8. لا Push/PR/Merge تلقائياً؛ المستخدم ينفذ Git operations ما لم يطلب خلاف ذلك صراحةً.
9. المهام غير المطلوبة في Target Architecture تزال من خريطة التنفيذ النشطة بالكامل؛ تاريخها محفوظ في Git.
10. عند حذف مهمة من الخطة النشطة يعاد ترقيم المهام اللاحقة داخل المرحلة، وإذا أزيلت مرحلة كاملة يعاد ترقيم المراحل اللاحقة.

---

# 3. حالات المهام النشطة

| الحالة | المعنى |
|---|---|
| `TODO` | لم تبدأ |
| `IN PROGRESS` | قيد التنفيذ |
| `VERIFY` | التنفيذ موجود ويحتاج تحقق |
| `BLOCKED` | متوقفة بسبب عائق |
| `DONE` | منجزة ومتحقق منها |

---

# 4. آخر تغيير معماري مكتمل — ARC-1

## ARC-1 — Remove Compare/Winner lifecycle

**الحالة:** `DONE` ومندمج في `main` عبر PR #80 — `refactor: remove compare winner lifecycle` (merge commit `eb461885f62ef2fc315bc023c51a06a31aebf2c7`).

تم تنفيذ:

- تدقيق كامل لمراجع Compare/Winner/temporary artifacts.
- حذف Comparison domain من Laravel.
- إزالة compare-only statuses.
- اعتماد `active_processing_run_id`.
- تنظيف baseline migrations لأن قاعدة التطوير كانت فارغة.
- حذف `DocumentProcessingComparison` والـcomparison schema من الـbaseline.
- حذف FastAPI `app/artifacts/` بالكامل.
- حذف إعدادات `TEMP_ARTIFACT_ROOT` و`TEMP_ARTIFACT_TTL_HOURS`.
- حذف `temporary_artifact_ref` من processing response.
- حذف `compare_available` من capabilities contract.
- حذف F7 القديم الذي كان يعيد استخدام parsed result بين Cloud وLocal من أجل Compare:
  - `app/parsing/shared.py`
  - `tests/test_shared_parse_result.py`
- الإبقاء على normalization نفسها واختباراتها المستقلة.
- إضافة Direct Indexing foundation:
  - `QdrantDocumentIndexer`
  - `IndexingContext`
  - `IndexingResult`
  - profile-to-collection resolver
  - دعم Cloud `models.Document` وHybrid Local `models.SparseVector` عند Qdrant boundary
  - persisted-count verification بعد upsert
- عدم بناء Processing endpoint/orchestrator أو Laravel job ضمن ARC-1؛ هذه ضمن المرحلة H.

## Verification

```text
Laravel
43 passed
181 assertions

FastAPI
129 passed

Schema
php artisan migrate:fresh succeeded

Final active-code audit
No active Compare/Winner/temporary-artifact lifecycle references.
```

ملاحظة:

```python
assert "comparison_report" not in payload
```

يبقى عمداً كـregression guard يثبت أن الحقل القديم لا يعود إلى serialized processing report.

---

# 5. المراحل المنجزة

## A — Foundation

| المهمة | الحالة |
|---|---|
| A1 Laravel application | DONE |
| A2 Authentication | DONE |
| A3 MySQL | DONE |
| A4 Redis | DONE |
| A5 Queue | DONE |

## B — Documents Foundation

| المهمة | الحالة |
|---|---|
| B1 documents migration | DONE |
| B2 Document model | DONE |
| B3 FileType / DocumentStatus | DONE |
| B4 DocumentPolicy | DONE |
| B5 Documents pages | DONE |
| B6 Upload validation | DONE |
| B7 Private storage / download authorization | DONE |
| B8 SHA-256 / duplicate policy | DONE |
| B9 document_processing_runs migration | DONE |
| B10 ProcessingRun model / enums / relations | DONE |
| B11 active_processing_run_id baseline + active-run relation/invariants | DONE |

## C — Security Pipeline

| المهمة | الحالة |
|---|---|
| C1 On-demand ClamAV worker / signatures / lock contract | DONE |
| C2 DocumentSecurityService | DONE |
| C3 Temporary upload / quarantine flow | DONE |
| C4 Clean path | DONE |
| C5 Infected / fail-closed path | DONE |
| C6 Configurable security routing | DONE |
| C7 Aggregate security status transitions | DONE |
| C8 Security tests | DONE |

> كلمة `Temporary` في C3 تخص quarantine الأمني قبل اعتماد الملف في Private Storage، وليست temporary comparison artifact.

## D — FastAPI Foundation

| المهمة | الحالة |
|---|---|
| D1 FastAPI project | DONE |
| D2 Typed config | DONE |
| D3 Logging / correlation IDs | DONE |
| D4 Internal API security | DONE |
| D5 Health | DONE |
| D6 Versioned DTOs | DONE |
| D7 Structured exceptions | DONE |
| D8 Capabilities contract | DONE |
| D9 Startup validation | DONE |
| D10 Dependency split | DONE |
| D11 Local runtime / device probe / telemetry | DONE |

## E — Qdrant

| المهمة | الحالة |
|---|---|
| E1 Qdrant + persistent volume | DONE |
| E2 Cloud collection | DONE |
| E3 Hybrid Local collection | DONE |
| E4 Dense / sparse configs | DONE |
| E5 Payload indexes | DONE |
| E6 Point builder + deterministic run metadata | DONE |
| E7 Idempotent upsert / count / delete | DONE |
| E8 Cross-user leakage tests | DONE |
| E9 Direct document indexer + persisted-count verification | DONE |
| E10 Profile-to-collection resolver + Cloud/Local sparse boundary support | DONE |

## F — Parsing and Normalization

| المهمة | الحالة |
|---|---|
| F1 Loader interface | DONE |
| F2 LlamaParse provider | DONE |
| F3 PDF loader | DONE |
| F4 DOCX loader | DONE |
| F5 TXT loader | DONE |
| F6 Normalized document/page/section contract + normalization | DONE |
| F7 Loader and normalization tests | DONE |

المعمارية الحالية لا تحتوي helper خاصاً بإعادة استخدام نفس parse result بين Cloud وHybrid Local.  
كل ProcessingRun تنفذ Profile واحدة، بينما LlamaParse والـnormalization يبقيان عقوداً مشتركة بين الـProfiles.

## G — Profile Processing

| المهمة | الحالة |
|---|---|
| G1 ProcessingProfile registry | DONE |
| G2 Cloud chunking | DONE |
| G3 Cloud Jina embeddings | DONE |
| G4 Cloud sparse representation | DONE |
| G5 Hybrid Local chunking | DONE |
| G6 Local BGE-M3 embeddings | DONE |
| G7 Local BM25 | DONE |
| G8 Batching / retries / rate limits | DONE |
| G9 Processing metrics / report builder | DONE |
| G10 Profile parity / isolation tests | DONE |
| G11 Single-active-model coordinator + release-after-stage | DONE |

---

# 6. خريطة التنفيذ المتبقية — الترقيم المعتمد الجديد

## H — Processing Orchestration

| المهمة | الحالة |
|---|---|
| H1 AiServiceClient | DONE |
| H2 Processing DTOs and contract alignment | DONE |
| H3 FastAPI single-profile Process Document API / application orchestration | TODO |
| H4 ProcessDocumentJob + queue dispatch | TODO |
| H5 Processing metrics / report persistence | TODO |
| H6 Active-run transaction after successful indexing | TODO |
| H7 Safe reprocessing replacement | TODO |
| H8 Aggregate status projector | TODO |
| H9 Queue retries / timeouts / idempotency | TODO |
| H10 Serialized `ai-local` queue + global heavy-resource lock | TODO |

**معيار انتهاء المرحلة:**

```text
one file
→ one trusted profile
→ one ProcessingRun
→ FastAPI processing
→ persistent Qdrant indexing through E9/E10
→ exact count verification
→ Laravel persists report
→ active_processing_run_id switches only after success
→ document ready
```

## I — Blade Documents Experience

| المهمة | الحالة |
|---|---|
| I1 Responsive app shell / sidebar | TODO |
| I2 Workspace dashboard | TODO |
| I3 Documents list / cards / filters | TODO |
| I4 One-file upload + capability-aware Cloud/Hybrid Local choice | TODO |
| I5 Document details / processing timeline | TODO |
| I6 Accessibility / responsive / error states | TODO |

الواجهة تعرض فقط Processing Profiles المتاحة فعلياً من Capabilities.

## J — Conversations Database

| المهمة | الحالة |
|---|---|
| J1 Conversations migration / model | TODO |
| J2 conversation_document pivot | TODO |
| J3 Messages + snapshots / metrics | TODO |
| J4 message_sources + processing run / profile provenance | TODO |
| J5 Conversation policies | TODO |
| J6 Create / list conversations | TODO |
| J7 Multi-document selection | TODO |
| J8 Ready / indexed / runtime-capable document filtering | TODO |

كل Document جاهزة تشير إلى `active_processing_run_id`.

## K — Retrieval and Reranking

| المهمة | الحالة |
|---|---|
| K1 Trusted document_targets | TODO |
| K2 Cloud query embedding / retrieval | TODO |
| K3 Hybrid Local query embedding / retrieval | TODO |
| K4 user / document / run filters | TODO |
| K5 Per-profile Dense + BM25 + RRF | TODO |
| K6 Cloud Jina reranker | TODO |
| K7 Local BGE reranker | TODO |
| K8 Cross-profile rank fusion | TODO |
| K9 Metadata / source preservation | TODO |
| K10 Retrieval quality / security tests | TODO |

Cross-profile هنا يعني دمج نتائج وثائق مفهرسة مسبقاً بProfiles مختلفة داخل المحادثة، وليس Upload comparison workflow.

## L — Generation

| المهمة | الحالة |
|---|---|
| L1 ContextService + آخر تبادلين مكتملين | TODO |
| L2 Prompt / insufficient-context behavior | TODO |
| L3 LLMProvider registry by trusted profile / capability | TODO |
| L4 Hugging Face Qwen3.5-9B | TODO |
| L5 Ollama qwen3.5:4b | TODO |
| L6 No-fallback / provider validation | TODO |
| L7 Answer / sources / timings contract | TODO |
| L8 Provider tests | TODO |
| L9 Ollama release / `keep_alive=0` | TODO |
| L10 Local lifecycle / pressure / no-leak tests | TODO |

## M — Chat Experience

| المهمة | الحالة |
|---|---|
| M1 Chat layout / list | TODO |
| M2 Top document selector | TODO |
| M3 Selected chips / authorization | TODO |
| M4 AskConversationJob | TODO |
| M5 Save snapshots / answer / metrics | TODO |
| M6 Sources drawer / relevance score | TODO |
| M7 Timings | TODO |
| M8 Pending / failure / retry + visual completed-answer reveal | TODO |
| M9 Mixed-profile / context / accessibility E2E | TODO |

v1 يستخدم polling + visual completed-answer reveal، بدون Streaming backend.

## N — Filament

| المهمة | الحالة |
|---|---|
| N1 Core resources | TODO |
| N2 ProcessingRuns resource | TODO |
| N3 Dashboard widgets | TODO |
| N4 Failed / infected filters | TODO |
| N5 Safe retry actions | TODO |
| N6 FastAPI admin chunks endpoint / client | TODO |
| N7 Read-only Qdrant Chunks | TODO |
| N8 Admin audit logging | TODO |
| N9 Authorization / no-vectors tests | TODO |

## O — Security and Operations

| المهمة | الحالة |
|---|---|
| O1 Ownership / IDOR | TODO |
| O2 Qdrant leakage: user / document / run | TODO |
| O3 MIME / size / malware | TODO |
| O4 FastAPI authentication | TODO |
| O5 Private download / chunk authorization | TODO |
| O6 Secret / log redaction | TODO |
| O7 Deletion / reprocessing consistency | TODO |

## P — Final Validation

| المهمة | الحالة |
|---|---|
| P1 PDF / DOCX / TXT E2E | TODO |
| P2 Cloud profile E2E | TODO |
| P3 Hybrid Local profile E2E | TODO |
| P4 Multi-document / mixed-profile chat E2E | TODO |
| P5 Queue / restart / Qdrant persistence | TODO |
| P6 RAG quality / source correctness | TODO |
| P7 Security-scan + AI memory / performance / quality calibration | TODO |
| P8 Cloud-only lightweight image verification | TODO |
| P9 Local Ollama backend / load / release verification | TODO |
| P10 Backup / restore | TODO |
| P11 Final documentation | TODO |

**معيار انتهاء المرحلة:**

Cloud وHybrid Local يعملان كلٌ كمسار مستقل End-to-End، والمحادثة المختلطة تعمل بأمان، وتطابق المصادر والـownership والـQdrant isolation متحقق.

---

# 7. Deployment — DPL

| المهمة | الحالة |
|---|---|
| DPL-1 Oracle Account | TODO |
| DPL-2 Oracle ARM64 VM / eligibility verification | TODO |
| DPL-3 Network 22 / 80 / 443 | TODO |
| DPL-4 Ubuntu / SSH hardening | TODO |
| DPL-5 Docker / Buildx / Compose ARM64 | TODO |
| DPL-6 Clone | TODO |
| DPL-7 Cloud-only env / secrets | TODO |
| DPL-8 Volumes / permissions / free disk | TODO |
| DPL-9 MySQL / Redis / Qdrant / signatures readiness | TODO |
| DPL-10 FastAPI Cloud-only image / health / capabilities | TODO |
| DPL-11 Laravel PHP-FPM / migrations / cache | TODO |
| DPL-12 Queue + Security Scan workers | TODO |
| DPL-13 Nginx | TODO |
| DPL-14 HTTPS | TODO |
| DPL-15 PDF clean / infected / fail-closed E2E | TODO |
| DPL-16 DOCX / ZIP limits E2E | TODO |
| DPL-17 TXT E2E | TODO |
| DPL-18 Cloud-only capability / UI / API | TODO |
| DPL-19 No Local AI packages / weights online | TODO |
| DPL-20 HF Qwen smoke / error / budget | TODO |
| DPL-21 Security / ownership / private ports / profile rejection | TODO |
| DPL-22 Encrypted off-VM backup / restore | TODO |
| DPL-23 Restart / persistence / readiness | TODO |
| DPL-24 Mac / ASUS Local topology verification | TODO |
| DPL-25 Hybrid Local end-to-end processing / chat verification | TODO |

---

# 8. الـSchema الفعلي بعد ARC-1

## 8.1 documents

الـbaseline الحالي:

```text
id
user_id
active_processing_run_id nullable
original_name
stored_name
title nullable
file_path
file_type
mime_type
file_size
sha256 char(64) NOT NULL
status default pending
created_at
updated_at
```

`active_processing_run_id` هو pointer للـProcessingRun الحالية التي تم فهرستها بنجاح.

في baseline التطوير الحالي لا يوجد FK مباشر من `documents.active_processing_run_id` إلى `document_processing_runs.id` بسبب اعتماد ترتيب إنشاء الجدولين.  
Domain/orchestration يجب أن يفرض:

- الـRun تخص نفس Document.
- حالة الـRun = `indexed`.
- التحويل للـRun الجديدة لا يتم قبل نجاح الفهرسة والتحقق.

## 8.2 document_processing_runs

الـbaseline الحالي:

```text
id
document_id
profile
status
profile_snapshot
total_pages nullable
total_chunks default 0
vector_count default 0
vector_dimension nullable
stage_timings_ms
warnings nullable
error_code nullable
failure_reason nullable
qdrant_collection nullable
indexed_at nullable
created_at
updated_at
```

لا توجد حقول خاصة بالمقارنة أو الـtemporary artifacts.

## 8.3 DocumentStatus

```text
pending
scanning
infected
queued
processing
indexing
ready
failed
```

## 8.4 ProcessingRunStatus

```text
pending
processing
indexing
indexed
failed
```

## 8.5 ProcessingProfile

```text
cloud
hybrid_local
```

---

# 9. FastAPI baseline بعد ARC-1

## 9.1 Process Document DTOs

العقود الحالية المثبتة بين Laravel وFastAPI:

```text
ProcessDocumentRequest
- user_id
- document_id
- processing_run_id
- processing_profile: cloud | hybrid_local
- file_type: pdf | docx | txt

ProcessDocumentResponse
- document_id
- processing_run_id
- profile
- status: indexed
- qdrant_collection
- profile_snapshot
- total_pages
- total_chunks
- vector_count
- vector_dimension nullable
- stage_timings_ms
- warnings
```

لا يوجد artifact reference في response، ولم يُبنَ production Process Document endpoint بعد.

## 9.2 Capabilities

Capabilities تصف:

- deployment mode.
- supported profiles.
- available profiles.
- provider capabilities.
- local runtime عند الحاجة.

لا يوجد Compare capability.

## 9.3 Direct Qdrant indexing foundation

الموجود حالياً:

```text
PointPayload
build_point_id()
build_point()
PointScope
upsert_points()
count_points()
delete_points()
QdrantDocumentIndexer
resolve_qdrant_collection()
```

Sparse boundary:

```text
cloud
→ qdrant_client.models.Document

hybrid_local
→ qdrant_client.models.SparseVector
```

الـindexer:

1. يتحقق من توافق counts قبل الكتابة.
2. يبني deterministic points.
3. ينفذ persistent upsert.
4. يعد النقاط exact ضمن user/document/run scope.
5. يفشل إذا persisted count لا يطابق عدد chunks.

لا يوجد production Process Document endpoint/orchestrator بعد؛ سيبنى ضمن H3.

---

# 10. Baseline التنفيذي المعتمد

```text
Upload
→ Validation
→ Security Gate
→ Permanent Private Storage
→ user chooses one trusted profile
→ one ProcessingRun
→ LlamaParse
→ normalization
→ chunking
→ profile-specific dense representation
→ profile-specific sparse representation
→ direct persistent Qdrant indexing
→ exact count verification
→ run indexed
→ active_processing_run_id
→ document ready
```

Oracle:

```text
cloud only
```

Local Demo:

```text
cloud | hybrid_local
```

Qdrant:

```text
rag_documents_cloud
rag_documents_hybrid_local
```

كل Retrieval/Delete/Admin access يجب أن يطبق server-side user/document/run isolation.

Local AI:

```text
single active heavy model
lazy load
release after stage
worker concurrency = 1
shared heavy-resource lock with ClamAV
Ollama keep_alive = 0
```

Conversation context:

```text
آخر تبادلين مكتملين فقط
```

Answer delivery في v1:

```text
polling + completed-answer visual reveal
```

---

# 11. نقطة الاستلام التالية

## آخر مهمة مكتملة — H2 Processing DTOs and Contract Alignment

**الحالة:** `DONE` ومندمجة في `main` عبر PR #82 — `refactor(H2): align processing DTO contracts`.

تم تنفيذ:

- إضافة FastAPI `ProcessDocumentRequest` بعقد typed يقيّد `processing_profile` إلى `cloud | hybrid_local` و`file_type` إلى `pdf | docx | txt`.
- تحديث `ProcessDocumentResponse` وتقييد successful response إلى `status = indexed`.
- إضافة `qdrant_collection` و`profile_snapshot` و`stage_timings_ms` و`warnings` وحقول الصفحات والـchunks والـvectors.
- جعل `vector_dimension` nullable، وإعادة استخدام `ProcessingProfile` و`ProcessingProfileSnapshot` و`ProcessingStage` و`ProcessingWarning`.
- إضافة Laravel DTOs:
  - `ProcessDocumentRequestData`
  - `ProcessDocumentResult`
- استخدام Enums الموجودة في Laravel، ومنها `ProcessingProfile` و`FileType` و`ProcessingRunStatus`، بدل strings حرة.
- جعل `ProcessDocumentRequestData` يحوّل البيانات إلى مفاتيح عقد FastAPI: `user_id` و`document_id` و`processing_run_id` و`processing_profile` و`file_type`.

لم يتم ضمن H2:

- Process Document endpoint.
- `AiServiceClient::processDocument()`.
- Queue orchestration.
- Parsing execution.
- Chunking execution.
- Embeddings execution.
- Sparse generation execution.
- Qdrant indexing orchestration.
- ProcessingRun persistence/transitions.
- `active_processing_run_id` switching.

## Verification

```text
FastAPI focused tests: 4 passed
Laravel DTO focused tests: 2 passed (8 assertions)
FastAPI full regression: 131 passed
Laravel full regression: 52 passed (210 assertions)
Laravel Pint: PASS
```

## المهمة الحالية/التالية

```text
H3 — FastAPI single-profile Process Document API / application orchestration
```

Baseline المهمة التالية:

```text
Processing DTO contracts are aligned between Laravel and FastAPI.
FastAPI has persistent Qdrant indexing primitives but no production Process Document endpoint/orchestrator yet.
AiServiceClient::processDocument(), Queue orchestration, ProcessingRun persistence/transitions,
and active_processing_run_id switching remain for the tasks assigned to them in the Master Plan.
No Compare/Winner/temporary artifact lifecycle exists in the target architecture.
```

---

# 12. التتبع التاريخي

التصاميم والمهام التي أزيلت من الخريطة النشطة لا تحفظ هنا كمهام ملغاة.

للتدقيق التاريخي يرجع إلى Git / Pull Requests، وبشكل خاص الـbaseline التاريخي السابق (وليس حالة `main` الحالية):

```text
main@a1f28097b398b9bb277f85990a55e489bd54d880
```

هذا يحافظ على التاريخ بدون تلويث خريطة التنفيذ الحالية بمهام لم تعد جزءاً من النظام المستهدف.
