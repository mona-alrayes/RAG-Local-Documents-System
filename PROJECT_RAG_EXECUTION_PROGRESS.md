# سجل تنفيذ مشروع RAG

> **المرجع المعماري:** `PROJECT_RAG_MASTER_PLAN.md`  
> **الغرض:** حفظ الحالة التنفيذية الفعلية ونقطة الاستلام بين المحادثات  
> **آخر تحديث:** 2026-09-03
> **الحالة العامة:** قيد التنفيذ — I2 مكتملة ومدموجة في PR #95؛ أصبحت Workspace Dashboard والوثائق الحديثة في Sidebar جاهزة؛ I3 هي المهمة الحالية

---

# CURRENT HANDOFF — نقطة الاستلام

> **هذا هو أول قسم يُقرأ في أي Chat جديد.**

```text
Project Mode: Start From Scratch
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Active Development

Verified Main Commit: fa8041b3afc78e4d21419ece8dd405d425702aba
Last Merged Feature PR on main: #95 — I2 Workspace Dashboard
Latest Task PR: #95 — I2 Workspace Dashboard
Verified I2 Feature Commit:
- 67f96f5da27f528f2fe57bbdd79136d78e255058 — I2 workspace dashboard and document sidebar
Verified I2 Merge Commit: fa8041b3afc78e4d21419ece8dd405d425702aba
Documentation Baseline: تم تسجيل اكتمال I2 وتسليم I3 Documents list / cards / filters كمهمة حالية.

Current Working Branch: main

Latest Completed Architectural Initiative:
ARC-1 — Remove Compare/Winner lifecycle

Latest Completed Task:
I2 — Workspace Dashboard

Current Phase:
I — Blade Documents Experience

Architectural Result:
- كل ProcessingRun تستخدم Processing Profile واحدة موثوقة: cloud | hybrid_local.
- لا يوجد Compare/Winner/temporary artifact lifecycle.
- تبقى الفهرسة الدائمة المباشرة في Qdrant هي المسار المعتمد.
- يبقى active_processing_run_id مؤشر الوثيقة إلى الـRun المفهرسة الحالية.
- تصف Document.status جاهزية الوثيقة، بينما تصف ProcessingRun.status تقدم محاولة واحدة.
- تميز ProcessingRun.kind صراحة بين initial وreprocessing.
- يمثل created_at وقت queued، وتحفظ started_at وindexing_started_at صراحة.
- يسجل Laravel حالة processing عند بدء الـJob ويبقى المالك الوحيد لـBusiness State.
- يرسل FastAPI حدث indexing_started الموثوق بعد dense/sparse وقبل أول Qdrant write.
- يفرض الـCallback تحقق IDs والملكية والحالة السابقة وidempotency والlocking والسر المستقل للاتجاه العكسي.
- ترفض redirects كي لا ينتقل callback secret إلى origin آخر.
- يمنع الفشل النهائي للـCallback أول كتابة في Qdrant.
- يعيد Laravel تحميل وقفل الـRun الفعلية قبل حفظ النجاح، ولا يمكن تجاوز processing مباشرة إلى indexed.
- تسقط initial attempt حالة indexing على الوثيقة، بينما تبقي reprocessing وثيقة ذات active run صالحة في ready.
- Queue retries محدودة: `tries = 3` مع backoff بقيم `15s` ثم `60s`، وليست retries غير محدودة.
- مواءمة timeouts المعتمدة هي FastAPI HTTP `300s` ثم Queue job/worker `330s` ثم Redis `retry_after = 360s`، بحيث يبقى Queue timeout أقل من retry_after.
- تصنف الأعطال إلى retryable temporary failures وterminal permanent failures، مع الحفاظ على structured FastAPI `error.code` داخل Laravel.
- retry المؤقت يعيد استخدام نفس `ProcessingRun` ونفس Queue job semantics دون إنشاء Run جديدة.
- terminal أو exhausted failure تثبت في Laravel عبر `ProcessingRunFailureFinalizer` داخل transaction + locking وبشكل idempotent وآمن.
- terminal finalization تحفظ `status = failed`, `error_code`, safe `failure_reason`, `failed_at`، ولا تغيّر Run أصبحت `indexed` ولا تعيد كتابة `failed_at` عند تكرار finalization.
- Document availability منفصلة عن ProcessingRun attempt status: الفشل النهائي لأول معالجة يجعل Document `failed`، بينما فشل reprocessing يبقي Document `ready` وactive_processing_run_id القديمة دون تغيير إذا بقيت صالحة.
- Qdrant indexing idempotent عند retry لأن deterministic Point IDs مع `upsert` تمنع إنشاء duplicate points لنفس Run.
- يوجه `hybrid_local` إلى Queue مستقلة باسم `ai-local`، بينما يبقى Cloud processing على الـdefault queue.
- يعمل `ai-local` عبر Worker واحد بتنفيذ serialized و`concurrency = 1`.
- يعاد استخدام `LocalHeavyResourceLock` نفسه كقفل Redis عالمي مشترك بين ClamAV وHybrid Local AI.
- اكتساب القفل للـLocal AI bounded؛ lock contention حالة retryable ولا يؤدي إلى انتظار غير محدود.
- retry يحافظ على نفس `processing_run_id` ونفس Processing Profile، ويحرر القفل بأمان داخل `finally`.
- لا يوجد silent fallback من Local إلى Cloud.
- أصبحت طبقة Documents presentation تقرأ `active_run` و`latest_attempt` كحقيقتين منفصلتين وتحدد `document_availability` من active indexed run الصالحة قبل النظر إلى المحاولة الأحدث.
- أصبحت list/detail/dashboard read models user-scoped، مع eager loading للـactive/latest runs، وsearch/filter/sort موثوق، وtimeline آمن للعرض.
- أصبحت `poll_required` و`reprocessing_in_progress` و`allowed_actions` مشتقة Server-side من الحالة الفعلية بدل إعادة تفسيرها في الواجهة.
- لا تعرض presentation resources `qdrant_collection` أو raw `failure_reason` أو `profile_snapshot`؛ وتعرض failure message عامة localized وتحصر warnings في `code` و`stage`.
- أصبح Laravel يملك `ProcessingCapabilityService` typed يتحقق من `available_profiles` ويفشل مغلقًا عند response غير صالحة أو Profile غير متاحة.
- يتحقق `DocumentProcessingDispatcher` من capability قبل إنشاء Initial/Reprocessing Run وقبل أي transaction؛ ففشل lookup أو عدم توفر Profile لا يغير Document state ولا ينشئ Run جديدة.
- FastAPI `/api/v1/capabilities` يفرق بين `supported_profiles` المعمارية و`available_profiles` القابلة للتشغيل فعليًا حسب credentials والـlocal runtime.
- في Cloud تتطلب `cloud` وجود LlamaParse وJina credentials، وفي Local لا تتاح `hybrid_local` إلا مع LlamaParse وruntime محلي `ready`؛ وتعرض provider availability دون كشف secrets.
- تبقى Ready document والـactive indexed run القديمة صالحة عند تعذر بدء reprocessing بسبب capability، كما تبقى محفوظة عند فشل reprocessing وفق H10/H12 presentation semantics.
- أضيف localization عربي/إنكليزي لحالات Document availability وProcessingRun status/kind/profile ورسالة الفشل الآمنة وSecure Upload validation.
- ثبت H13 عقد Upload للواجهة عبر redirect ورسائل localized آمنة، وأضاف Reprocess/Delete application commands محمية بالـownership وIDOR checks.
- Reprocess يعيد استخدام orchestration الموجود مسبقًا، ويرفض الطلب دون active indexed run أو عند وجود محاولة جارية، ويفشل مغلقًا عند عدم توفر Profile المطلوبة.
- Delete يرفض الحذف أثناء `pending/processing/indexing`، وينظف Qdrant اعتمادًا على ProcessingRun data الموثوقة Server-side، ثم permanent/quarantine private storage، ثم processing runs والوثيقة بترتيب آمن.
- تحول أخطاء business/application عند HTTP boundary إلى رسائل UI آمنة بدل كشف تفاصيل داخلية.
- اكتملت H8–H13 وأصبحت عقود القراءة والأوامر جاهزة لتبدأ المرحلة I دون إعادة تعريف Business State داخل الواجهة.
- أصبحت الصفحات المحمية بعد تسجيل الدخول تستخدم App Shell موحداً بواجهة عربية RTL وFlux responsive sidebar قابلة لإعادة الاستخدام.
- الـSidebar مناسبة وثابتة على Desktop، وتتحول على Mobile إلى drawer تفتح وتغلق عبر Flux دون JavaScript مخصص، مع Mobile header وزر فتح القائمة.
- التنقل المركزي يعرض الروابط الفعلية فقط: Workspace وDocuments وAccount Settings، مع Active state حسب route الحالية وبقاء `documents.*` ضمن حالة الوثائق النشطة.
- تعرض الـSidebar هوية المستخدم وإجراء Logout، ويحمي `flux:main` المحتوى من horizontal overflow غير المقصود.
- I1 لم تعِد تصميم محتوى Workspace أو Documents أو Settings، ولم تغيّر business logic أو عقود H12/H13 أو FastAPI/Qdrant/Retrieval/Chat/Database.
- استبدلت I2 صفحة Workspace placeholder بلوحة Dashboard فعلية عبر `WorkspaceController` تستهلك `DocumentReadService` وH12 Presentation Layer بدل إعادة تفسير Business State داخل الواجهة.
- تعرض Workspace إحصائيات إجمالي الوثائق والجاهزة وقيد المعالجة والفاشلة وعدد إعادة المعالجة عند وجودها، إضافة إلى أحدث الوثائق وأحدث حالات الفشل وEmpty State عند عدم وجود وثائق.
- توسع App Shell في I2 بقائمة وثائق داخل الـSidebar قابلة للفتح والإغلاق، وتعرض أحدث 5 وثائق للمستخدم فقط مع مؤشر حالة ملوّن لكل وثيقة.
- أضيفت قائمة إجراءات موحدة للوثيقة: عرض التفاصيل، التحميل، إعادة المعالجة، والحذف، مع احترام presentation hints: `canDownload`, `canReprocess`, `canDelete`.
- أعيد استخدام `DocumentReadService::recentForUser()` مع eager loading للـ`activeProcessingRun` و`latestAttempt` لمنع N+1، وبقيت كل Queries user-scoped لمنع تسريب وثائق مستخدم آخر.
- أضيفت اختبارات I2 للـWorkspace Dashboard والـApp Shell/Sidebar بما يشمل عزل بيانات المستخدم وعرض الحالات والإجراءات.

Latest Verification:
PR #95 merged on GitHub: PASS
PR head commit: 67f96f5da27f528f2fe57bbdd79136d78e255058
PR merge commit: fa8041b3afc78e4d21419ece8dd405d425702aba
main verified at I2 merge commit fa8041b3afc78e4d21419ece8dd405d425702aba before this progress update: PASS
WorkspaceDashboardTest: 2 passed (9 assertions)
AppShellTest: 2 passed (27 assertions)
Laravel full regression: 144 passed (715 assertions)
Laravel Pint on I2 files: PASS
Frontend build: npm run build — PASS
Manual browser verification: PASS
FastAPI: لم تُشغّل الاختبارات لأن I2 لم تغيّر FastAPI.

Current Task:
I3 — Documents list / cards / filters

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
11. لا تبدأ المرحلة I قبل اكتمال Frontend Backend Readiness Gate في H8–H13 والتحقق من عقود الحالات والقراءة والأوامر.

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

## H — Processing Orchestration and Documents UI Backend Readiness

| المهمة | الحالة |
|---|---|
| H1 AiServiceClient | DONE |
| H2 Processing DTOs and contract alignment | DONE |
| H3 FastAPI single-profile Process Document API / application orchestration | DONE |
| H4 ProcessDocumentJob + queue dispatch | DONE |
| H5 Processing metrics / report persistence | DONE |
| H6 Active-run transaction after successful indexing | DONE |
| H7 Safe reprocessing replacement | DONE |
| H8 Aggregate status projector | DONE |
| H9 Accurate processing progress callback + run kind/stage timestamps | DONE |
| H10 Queue retries / timeouts / idempotency / terminal failure finalization | DONE |
| H11 Serialized `ai-local` queue + global heavy-resource lock | DONE |
| H12 Documents presentation read model / polling / capability availability | DONE |
| H13 Upload / reprocess / delete application commands and authorization | DONE |

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
→ truthful processing progress persisted in Laravel
→ frontend-ready read and command contracts
```

## Frontend Backend Readiness Gate — القرار المعتمد

لا تبدأ المرحلة I قبل إكمال H8–H13. الهدف هو ألا نكتشف أثناء بناء Blade/Livewire
حاجة متأخرة لتغيير Domain/Schema/Internal API أو قواعد الأعمال الأساسية.

المقصود ليس منع كتابة Livewire/PHP presentation wiring في المرحلة I؛ الممنوع
هو أن تعتمد الواجهة على تخمين `latest run` أو حالات محلية أو اتصال مباشر
بـFastAPI/Qdrant.

### معنى الحالتين

```text
Document.status
→ هل توجد الآن نسخة فعالة وصالحة يمكن للنظام استخدامها؟

ProcessingRun.status
→ أين وصلت محاولة Initial Processing أو Reprocessing محددة؟
```

مثال إعادة معالجة ملف `آثار تدمر.pdf`:

```text
Document.status = ready
active_processing_run_id = Run A

latest_attempt:
kind = reprocessing
run = Run B
status = indexing
```

تبقى Run A مستخدمة في المحادثات حتى نجاح B. ولا تخفي `Ready` تقدم B؛ تعرض
الواجهة الجاهزية وتقدم المحاولة في عنصرين منفصلين.

### H8 — Aggregate status projector

**النطاق:**

- إنشاء مصدر مركزي لقرارات `Document.status` خارج Controller/Job/UI.
- أول معالجة تعكس `queued → processing → indexing → ready/failed`.
- Reprocessing لا تخفض Document من `ready` ما دامت active run القديمة صحيحة ومفهرسة.
- activation يثبت `active_processing_run_id + ready` داخل transaction واحدة.
- failed replacement تبقى محاولة منفصلة ولا تكسر النسخة الفعالة.
- لا يعتمد projector على أحدث Run تلقائياً؛ يستقبل الـRun المقصودة صراحة ويتحقق منها.

**معيار القبول:**

```text
no active run + pending attempt       → queued
no active run + processing attempt    → processing
no active run + indexing attempt      → indexing
indexed + activated run               → ready
terminal initial failure              → failed
valid active A + B in progress         → ready + B progress remains visible
valid active A + failed B              → ready + failed latest attempt
invalid active pointer                 → never ready; no silent fallback
```

### H9 — Accurate processing progress callback

**Schema target عبر Forward Migration:**

```text
kind: initial | reprocessing
created_at = queued_at
started_at nullable
indexing_started_at nullable
indexed_at nullable
failed_at nullable
```

**التسلسل:**

```text
Laravel starts ProcessDocumentJob
→ Run = processing + started_at
→ FastAPI parses/chunks/embeds/builds sparse representation
→ FastAPI sends indexing_started callback
→ Laravel validates Run and stores indexing + indexing_started_at
→ only then FastAPI may perform the first Qdrant write
→ exact count verification
→ indexed response
→ Laravel persists result and activates Run
```

**قواعد الأمان والاتساق:**

- callback URL من FastAPI trusted configuration، وليست قيمة يقبلها request أو Browser.
- secret مستقل لاتجاه FastAPI → Laravel.
- Laravel يتحقق من route/payload IDs وRun ownership والحالة السابقة.
- `indexing_started` idempotent؛ التكرار لا يكرر side effects ولا يعيد الحالة للخلف.
- callback تستخدم bounded retries.
- الفشل النهائي للـcallback يوقف المعالجة قبل أول Qdrant write.
- FastAPI يبلغ عن المرحلة فقط؛ Laravel يبقى مالك Business State.
- الواجهة تعمل polling على Laravel/MySQL فقط.

### H10 — Queue reliability and terminal failure

- `ProcessDocumentJob` يستخدم retries محدودة: `tries = 3` مع backoff `15s`, `60s`.
- timeout alignment المعتمد: FastAPI HTTP `300s`، Queue job/worker `330s`، Redis `retry_after = 360s`.
- `ProcessingRunFailureClassifier` يميز retryable temporary failures عن terminal permanent failures.
- يحافظ `AiServiceClient` و`AiServiceException` على structured FastAPI `error.code` كي يستخدمه التصنيف والتثبيت النهائي.
- الأخطاء المؤقتة يعاد رميها لتعيد Queue نفس الـJob ونفس `ProcessingRun`، ولا تنشأ Run جديدة أثناء retry.
- `ProcessingRunFailureFinalizer` يثبت terminal/exhausted failure داخل transaction مع row locking وبشكل idempotent.
- terminal finalization تحفظ `status = failed`, `error_code`, safe `failure_reason`, `failed_at`.
- Run التي أصبحت `indexed` محمية من late failure hooks، وإعادة finalization لRun فاشلة لا تعيد كتابة `failed_at` أو سبب الفشل الأول.
- الفشل النهائي لأول معالجة يجعل `Document.status = failed`.
- فشل reprocessing النهائي مع active run قديمة صالحة يبقي `Document.status = ready` و`active_processing_run_id` القديمة دون تغيير.
- Qdrant retry idempotency تعتمد deterministic Point IDs + `upsert`، ولذلك إعادة نفس ProcessingRun لا تنشئ duplicate points.
- H10 لا تنفذ serialized `ai-local` execution أو global heavy-resource lock؛ هذان ضمن H11.

### H11 — Serialized local execution

- يوجه `hybrid_local` إلى Queue مستقلة باسم `ai-local`، بينما يبقى Cloud processing على الـdefault queue.
- يعمل `ai-local` عبر Worker واحد بتنفيذ serialized و`concurrency = 1`.
- يعاد استخدام `LocalHeavyResourceLock` الحالي بدل إنشاء Lock جديد.
- تشترك عمليات ClamAV وHybrid Local AI في نفس Redis global heavy-resource lock.
- Local AI تستخدم bounded lock acquisition ولا تنتظر القفل إلى ما لا نهاية.
- lock contention حالة retryable وتدخل ضمن سياسة H10 المحدودة.
- retry يحافظ على نفس `processing_run_id` ونفس Processing Profile ولا ينشئ Run بديلة.
- يحرر القفل بأمان داخل `finally` بعد استدعاء FastAPI.
- لا يوجد silent fallback من Local إلى Cloud.
- تبقى reliability contract الخاصة بـH10 كما هي: `tries = 3`، backoff `15s, 60s`، FastAPI process timeout `300s`، Job/worker timeout `330s`، Redis `retry_after = 360s`.

### H12 — Documents presentation read contract

**الحالة:** `DONE` ومتحقق منها في PR #92 والمدمجة في `main`.

تم تنفيذ:

- `DocumentAvailability` typed presentation state مع `DocumentAvailabilityResolver` يعلن `Ready` فقط عند وجود active indexed run صالحة، ويحافظ على Ready أثناء محاولة reprocessing أحدث.
- فصل `active_run` عن `latest_attempt` صراحة في Document summary/detail contracts وعدم استخدام latest attempt وحدها لتقرير الجاهزية.
- `DocumentSummaryMapper` يشتق `reprocessing_in_progress`, `poll_required`, `safe_failure`, و`allowed_actions` من الحالة الفعلية.
- `DocumentReadService` يقدم owner-scoped Dashboard/List/Detail read models:
  - Dashboard counts حسب status، active processing count، reprocessing count، recent documents، recent failures.
  - Paginated list مع search على title/original_name، وفلاتر status/file type/profile، وترتيب موثوق.
  - Detail read model مع كامل processing timeline للوثيقة.
- eager loading للـ`activeProcessingRun` و`latestAttempt` والـtimeline لمنع N+1 داخل mappers.
- timeline يعرض profile/status/kind/is_active، pages/chunks، stage timings، timestamps الفعلية `queued/started/indexing/indexed/failed`، وwarnings الآمنة فقط.
- presentation layer لا تمرر `qdrant_collection` أو raw `failure_reason` أو `profile_snapshot`؛ failure المعروضة رسالة عامة localized، وwarnings تختزل إلى `code` و`stage`.
- إضافة Laravel `ProcessingCapabilityService` typed لتحويل `available_profiles` إلى `ProcessingProfile` enums ورفض response غير صالح.
- `DocumentProcessingDispatcher` ينفذ capability check قبل أي Initial/Reprocessing transaction أو إنشاء Run؛ Profile غير متاحة أو capability lookup فاشل يؤدي إلى fail-closed بلا state mutation.
- FastAPI `/api/v1/capabilities` يميز بين:
  - `supported_profiles`: ما يسمح به deployment mode.
  - `available_profiles`: ما يمكن تشغيله فعليًا الآن.
- Cloud profile تتاح عند وجود LlamaParse وJina credentials، وHybrid Local تتاح فقط في local deployment مع LlamaParse وlocal runtime جاهز؛ provider statuses تعكس availability القابلة للتحقق منها دون كشف secrets.
- اختبار reprocessing غير المتاح يثبت بقاء `Document.status = ready` و`active_processing_run_id` القديمة وعدم إنشاء Run جديدة أو Queue job.
- فشل reprocessing النهائي يبقى ظاهرًا كـlatest attempt مع الحفاظ على active indexed run القديمة حسب lifecycle المثبت في H10 والمستهلك في H12.
- إضافة localization عربي/إنكليزي لـdocument availability وProcessingRun status/kind/profile ورسالة processing failure الآمنة وSecure Upload validation، مع Resources تعيد labels localized.
- تحديث الاختبارات المتأثرة لعزل FastAPI عبر HTTP fakes/typed capability seam.

Verification المثبت في PR #92:

```text
Laravel: 131 passed (614 assertions)
FastAPI: 157 passed
Ruff: All checks passed
Pint: PASS
```

### H13 — Documents application commands

**الحالة:** `DONE` ومتحقق منها في PR #93 والمدمجة في `main`.

تم تنفيذ:

- تثبيت عقد Upload المناسب للواجهة ليعيد redirect ثابتًا إلى صفحة الوثيقة مع رسائل localized آمنة، بما في ذلك حالة duplicate دون كشف بيانات داخلية.
- إضافة Reprocess application command مع ownership authorization عبر Policy/FormRequest.
- الاعتماد على orchestration الموجود مسبقًا لإعادة المعالجة بدل إنشاء مسار موازٍ.
- منع إعادة المعالجة إذا لم توجد active indexed processing run صالحة.
- منع concurrent processing/reprocessing attempts على الوثيقة.
- fail-closed عندما تكون Processing Profile المطلوبة غير متاحة.
- إضافة Delete application command مع ownership authorization وحماية IDOR.
- منع حذف الوثيقة Server-side أثناء `Pending / Processing / Indexing`.
- تنظيف Qdrant لكل ProcessingRun باستخدام `user_id/document_id/processing_run_id/profile` المشتقة من بيانات موثوقة على السيرفر.
- تنظيف permanent وquarantine private storage عبر `DocumentStorageService`.
- إزالة active run pointer ثم حذف processing runs والوثيقة بترتيب آمن بعد نجاح external cleanup.
- إيقاف الحذف عند فشل Qdrant أو storage cleanup بدل ترك حذف جزئي مخفي.
- تحويل أخطاء business/application إلى رسائل UI ثابتة وآمنة عند HTTP boundary.
- تحديث اختبارات Upload القديمة لتتوافق مع عقد redirect الجديد.
- إضافة focused Feature tests لأوامر Reprocess وDelete تشمل ownership/IDOR/concurrency/unavailable profile/cleanup ordering.

Verification المثبت لـH13:

```text
PR #93 merged on GitHub: PASS
Feature commit:
- cacea37958a84d1efcdce65877bf1ad19fd92ca9
Merge commit:
- 2cf17e21b9d2a1eb80918788841c375aeb1a6ebf

Focused H13 tests:
23 passed (152 assertions)

Laravel full regression:
140 passed (679 assertions)

Laravel Pint:
PASS

FastAPI:
لم يتم تعديل FastAPI في H13، ولم يُسجل FastAPI regression ضمن هذه المهمة.
```

## I — Blade Documents Experience

| المهمة | الحالة |
|---|---|
| I1 Responsive app shell / sidebar | DONE |
| I2 Workspace dashboard | DONE |
| I3 Documents list / cards / filters | TODO |
| I4 One-file upload + capability-aware Cloud/Hybrid Local choice | TODO |
| I5 Document details / processing timeline | TODO |
| I6 Accessibility / responsive / error states | TODO |

### I1 — Responsive App Shell / Sidebar

**الحالة:** `DONE` ومتحقق منها في PR #94 والمدمجة في `main`.

تم تنفيذ:

- إنشاء App Shell موحّد للصفحات المحمية بعد تسجيل الدخول عبر authenticated layout المشترك.
- تحويل الـlayout إلى Flux responsive sidebar قابلة لإعادة الاستخدام مع دعم RTL والواجهة العربية.
- Sidebar مناسبة وثابتة على Desktop، وMobile drawer تفتح وتغلق عبر Flux دون JavaScript مخصص.
- مركزية Navigation على الروابط الفعلية الموجودة فقط: Workspace وDocuments وAccount Settings.
- Active navigation state حسب route الحالية، مع `documents.*` لإبقاء صفحات Documents الفرعية ضمن حالة الوثائق النشطة.
- عرض هوية المستخدم وإجراء Logout داخل الـSidebar.
- إضافة Mobile header مع زر فتح القائمة.
- حماية المحتوى الرئيسي من horizontal overflow غير المقصود.
- الحفاظ على محتوى Workspace وDocuments وSettings دون إعادة تصميم ضمن I1.
- عدم تغيير أي business logic أو عقود H12/H13، وعدم إجراء تغييرات على FastAPI أو Qdrant أو Retrieval أو Chat أو Database.
- تغييرات PR #94 الفعلية محصورة في:
  - `laravel-app/resources/views/components/layouts/app.blade.php`
  - `laravel-app/tests/Feature/AppShellTest.php`

Verification المثبت لـI1:

```text
Focused App Shell test:
1 passed (18 assertions)

Laravel full regression:
141 passed (697 assertions)

Frontend build:
npm run build — PASS

Pint:
./vendor/bin/pint --dirty — PASS
بعد تعديل Pint أعيد تشغيل full Laravel suite — PASS
./vendor/bin/pint --dirty --test — PASS

Manual responsive verification — PASS:
- Desktop sidebar
- Mobile drawer open/close
- Workspace active state
- Documents active state
- Account Settings active state
- لا يوجد horizontal overflow غير مقصود

FastAPI:
لم تُشغّل الاختبارات لأن I1 لم تغيّر FastAPI.
```

### I2 — Workspace Dashboard

**الحالة:** `DONE` ومتحقق منها في PR #95 والمدمجة في `main`.

تم تنفيذ:

- استبدال صفحة Workspace placeholder بلوحة Dashboard فعلية عبر `WorkspaceController`.
- استهلاك `DocumentReadService::dashboardForUser()` وH12 Presentation Layer بدل إعادة تعريف حالات الوثائق أو منطق الأعمال داخل Blade.
- عرض إحصائيات: إجمالي الوثائق، الجاهزة، قيد المعالجة، الفاشلة، وعدد إعادة المعالجة عند وجودها.
- عرض أحدث الوثائق وأحدث حالات الفشل، مع Empty State عند عدم وجود وثائق.
- توسيع App Shell بقسم وثائق داخل الـSidebar قابل للفتح والإغلاق.
- عرض أحدث 5 وثائق للمستخدم داخل Sidebar مع مؤشر حالة ملوّن لكل وثيقة.
- إضافة قائمة إجراءات موحدة لكل وثيقة: عرض التفاصيل، التحميل، إعادة المعالجة، الحذف.
- احترام presentation hints الموجودة: `canDownload`, `canReprocess`, `canDelete` وعدم إعادة تقرير صلاحية الأفعال داخل الواجهة.
- إعادة استخدام H12 Presentation Layer و`DocumentReadService`، وإضافة `recentForUser()` للاستخدام المشترك بين Dashboard وSidebar.
- الحفاظ على user scoping في كل القراءة ومنع تسريب وثائق مستخدم آخر.
- استخدام eager loading للـ`activeProcessingRun` و`latestAttempt` لتجنب N+1.
- إضافة `WorkspaceDashboardTest` وامتداد `AppShellTest` لتغطية الـDashboard والـSidebar وعزل بيانات المستخدم.

Verification المثبت لـI2:

```text
PR #95 merged on GitHub: PASS
Feature commit:
- 67f96f5da27f528f2fe57bbdd79136d78e255058
Merge commit:
- fa8041b3afc78e4d21419ece8dd405d425702aba

WorkspaceDashboardTest:
2 passed (9 assertions)

AppShellTest:
2 passed (27 assertions)

Laravel full regression:
144 passed (715 assertions)

Laravel Pint on I2 files:
PASS

Frontend build:
npm run build — PASS

Manual browser verification:
PASS

FastAPI:
لم تُشغّل الاختبارات لأن I2 لم تغيّر FastAPI.
```

الواجهة تعرض فقط Processing Profiles المتاحة فعلياً من Capabilities.

تبعيات المرحلة I الملزمة:

```text
I2 ← H12 dashboard summary
I3 ← H12 list/filter/read contract
I4 ← H12 capability availability + H13 Upload command
I5 ← H12 details/timeline + H13 Reprocess/Delete commands
I6 ← H8/H9/H10 safe states, polling terminals, and user-safe errors
```

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

Progress callback dependencies:

```text
DPL-7  → Laravel internal base URL + separate callback secret in Cloud secrets
DPL-10 → Cloud FastAPI can emit indexing_started before Qdrant write
DPL-21 → callback route is internal/authenticated and rejects forged transitions
DPL-24 → Host FastAPI can reach Docker Laravel on Mac/ASUS without request-supplied URLs
DPL-25 → Hybrid Local E2E observes processing → indexing → indexed/failed
```

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

## 8.2.1 H9 target extension

تضاف عبر Forward Migration:

```text
kind: initial | reprocessing
started_at nullable
indexing_started_at nullable
failed_at nullable
```

`created_at` يمثل `queued_at`، و`indexed_at` يبقى توقيت النجاح. لا تستخدم
الواجهة `updated_at` لتخمين المرحلة أو نوع المحاولة.

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

# 9. FastAPI baseline الحالي

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

لا يوجد artifact reference في response، والـproduction endpoint `POST /api/v1/documents/process` يستقبل `multipart/form-data` وينفذ Processing Profile موثوقة واحدة لكل request.

## 9.2 Capabilities

Capabilities الحالية تميز بوضوح بين:

```text
supported_profiles
→ Profiles المسموحة معماريًا حسب RAG_DEPLOYMENT_MODE

available_profiles
→ Profiles القابلة لبدء processing فعليًا الآن
```

Semantics المثبتة في H12:

- Cloud deployment يدعم `cloud` فقط.
- Local deployment يدعم `cloud` و`hybrid_local`.
- `cloud` تكون available فقط عند وجود LlamaParse وJina credentials.
- `hybrid_local` تكون available فقط في Local deployment مع LlamaParse credential وlocal runtime snapshot بحالة `ready`.
- provider statuses لـLlamaParse/Jina/BGE تعكس `available/unavailable` عندما يمكن التحقق منها، بينما providers غير المفحوصة مباشرة تبقى `not_checked`.
- `local_runtime` لا يعاد في Cloud mode ويعاد في Local mode عند توفر snapshot.
- Laravel لا يثق بقائمة raw؛ `ProcessingCapabilityService` يحولها إلى `ProcessingProfile` typed ويرفض response غير صالحة.

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

يستخدم production Process Document endpoint هذا الـindexer مباشرة ضمن H3، ولا يعيد `status="indexed"` إلا بعد نجاح الـindexing والتحقق من exact persisted count.

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

## المهمة السابقة المكتملة — H12 Documents Presentation Read Model / Polling / Capability Availability

**الحالة:** `DONE` ومتحقق منها في PR #92، والمدمجة في `main` عند merge commit `abda4d358f027213556c9cedb6a7b45984a57f50`.

تم تنفيذ:

- Documents presentation read/query layer مستقرة للـBlade/Livewire بدون نقل business-state interpretation إلى الواجهة.
- `DocumentAvailability` وresolver يفصلان جاهزية الوثيقة عن حالة أحدث محاولة Processing، مع أولوية active indexed run الصالحة.
- `DocumentSummaryData` / `DocumentDetailData` وProcessingRun summary/detail data مع HTTP Resources جاهزة للعرض.
- فصل صريح بين `active_run` و`latest_attempt`، ودعم `reprocessing_in_progress`, `poll_required`, `allowed_actions`, وsafe failure presentation.
- `DocumentReadService` يقدم user-scoped dashboard/list/detail، مع search/filter/sort/pagination وeager loading لتجنب N+1.
- detail timeline تعرض pages/chunks/stage timings وtimestamps الفعلية، وتحذف من العرض raw failure details وQdrant internals/profile snapshot.
- typed `ProcessingCapabilityService` في Laravel، مع fail-closed عند unavailable/invalid capability قبل إنشاء أو dispatch أي Initial/Reprocessing Run.
- FastAPI `/api/v1/capabilities` صار يقرر `available_profiles` من credentials/runtime الفعلية بدل إرجاع قائمة فارغة ثابتة، مع فصلها عن `supported_profiles`.
- Ready document والـactive run القديمة لا تتغيران عند رفض reprocessing بسبب capability غير متاحة؛ ولا تنشأ Run أو Queue job جديدة في هذه الحالة.
- Safe reprocessing failure semantics السابقة تبقى ظاهرة عبر latest attempt دون إسقاط Ready active version.
- Localization عربي/إنكليزي لحالات الوثيقة والـProcessingRun والرسائل الآمنة وsecure-upload validation.

### Verification H12

```text
PR #92 merged on GitHub: PASS
Feature commit:
- cabd2c5bfb5f016ff12eb838fd228fd1e114c236
Merge commit:
- abda4d358f027213556c9cedb6a7b45984a57f50

Laravel:
131 passed (614 assertions)

FastAPI:
157 passed

Pint:
PASS

Ruff:
All checks passed
```

## المهمة السابقة المكتملة — H13 Documents Application Commands

**الحالة:** `DONE` ومتحقق منها في PR #93، والمدمجة في `main` عند merge commit `2cf17e21b9d2a1eb80918788841c375aeb1a6ebf`.

تم تنفيذ:

- تثبيت Upload contract المناسب للواجهة مع redirect إلى صفحة الوثيقة ورسائل localized آمنة، وحالة duplicate لا تكشف تفاصيل داخلية.
- إضافة Reprocess application command محمي بالـownership authorization ويعيد استخدام `DocumentProcessingDispatcher` وsafe reprocessing orchestration الموجود مسبقًا.
- رفض Reprocess دون active indexed processing run صالحة، أو عند وجود محاولة `pending/processing/indexing` جارية، أو عندما تكون Processing Profile المطلوبة غير متاحة.
- إضافة Delete application command مع ownership / IDOR protection.
- منع Delete أثناء وجود ProcessingRun بحالة `Pending / Processing / Indexing` لتجنب races مع Queue/Qdrant/storage/DB.
- تنظيف Qdrant لكل Run باستخدام ProcessingRun data الموثوقة Server-side بدل مدخلات Browser.
- تنظيف permanent/quarantine private storage عبر storage service.
- إزالة active pointer وحذف processing runs ثم الوثيقة بترتيب آمن بعد نجاح external cleanup.
- تحويل أخطاء business/application عند HTTP boundary إلى رسائل UI localized وآمنة.
- تحديث اختبارات Upload القديمة لعقد redirect الجديد وإضافة focused Feature tests لـReprocess وDelete.

### Verification H13

```text
PR #93 merged on GitHub: PASS
Feature commit:
- cacea37958a84d1efcdce65877bf1ad19fd92ca9
Merge commit:
- 2cf17e21b9d2a1eb80918788841c375aeb1a6ebf

Focused H13 tests:
23 passed (152 assertions)

Laravel full regression:
140 passed (679 assertions)

Laravel Pint:
PASS

FastAPI:
لم يتم تعديل FastAPI في H13، ولم يُسجل FastAPI regression ضمن هذه المهمة.
```

## المهمة السابقة المكتملة — I1 Responsive App Shell / Sidebar

**الحالة:** `DONE` ومتحقق منها في PR #94، والمدمجة في `main` عند merge commit `cd8698ef8ed643bc7fdb96539664c98f9a63924e`.

تم تنفيذ:

- إنشاء App Shell موحّد للصفحات المحمية بعد تسجيل الدخول وتحويل authenticated layout المشترك إلى Sidebar responsive قابلة لإعادة الاستخدام.
- دعم RTL والواجهة العربية باستخدام Flux responsive sidebar.
- Sidebar مناسبة على Desktop وMobile drawer تفتح وتغلق دون JavaScript مخصص.
- Navigation مركزية للروابط الموجودة فعلياً: Workspace وDocuments وAccount Settings.
- Active navigation حسب route، مع بقاء صفحات `documents.*` ضمن active state الخاص بالوثائق.
- هوية المستخدم وLogout داخل الـSidebar، وMobile header مع زر فتح القائمة.
- حماية المحتوى الرئيسي من horizontal overflow غير المقصود.
- عدم إعادة تصميم محتوى Workspace أو Documents أو Settings وعدم تغيير business logic أو عقود H12/H13.
- لا تغييرات على FastAPI أو Qdrant أو Retrieval أو Chat أو Database.
- الملفات المتغيرة في PR #94 فقط:
  - `laravel-app/resources/views/components/layouts/app.blade.php`
  - `laravel-app/tests/Feature/AppShellTest.php`

### Verification I1

```text
PR #94 merged on GitHub: PASS
Feature commit:
- 98d43d14895ac05329b03c7d0e5540da128fb79c
Merge commit:
- cd8698ef8ed643bc7fdb96539664c98f9a63924e

Focused App Shell test:
1 passed (18 assertions)

Laravel full regression:
141 passed (697 assertions)

Frontend build:
npm run build — PASS

Pint:
./vendor/bin/pint --dirty — PASS
بعد تعديل Pint أعيد تشغيل full Laravel suite — PASS
./vendor/bin/pint --dirty --test — PASS

Manual responsive verification — PASS:
- Desktop sidebar
- Mobile drawer open/close
- Workspace active state
- Documents active state
- Account Settings active state
- لا يوجد horizontal overflow غير مقصود

FastAPI:
لم تُشغّل الاختبارات لأن I1 لم تغيّر FastAPI.
```

## آخر مهمة مكتملة — I2 Workspace Dashboard

**الحالة:** `DONE` ومتحقق منها في PR #95، والمدمجة في `main` عند merge commit `fa8041b3afc78e4d21419ece8dd405d425702aba`.

تم تنفيذ:

- استبدال Workspace placeholder بلوحة Dashboard فعلية تعتمد `WorkspaceController`.
- استخدام `DocumentReadService` وH12 Presentation Layer لقراءة Dashboard user-scoped دون إعادة تفسير Business State في الواجهة.
- عرض إجمالي الوثائق والجاهزة وقيد المعالجة والفاشلة وعدد إعادة المعالجة عند وجودها.
- عرض أحدث الوثائق وأحدث حالات الفشل وEmpty State.
- توسيع App Shell بقسم وثائق داخل Sidebar قابل للفتح والإغلاق.
- عرض أحدث 5 وثائق للمستخدم داخل Sidebar، مع مؤشر حالة ملوّن.
- توفير إجراءات عرض التفاصيل والتحميل وإعادة المعالجة والحذف مع احترام `canDownload`, `canReprocess`, `canDelete`.
- إعادة استخدام `DocumentReadService::recentForUser()` مع eager loading لتجنب N+1.
- الحفاظ على user scoping ومنع تسريب وثائق مستخدم آخر.
- إضافة اختبارات Dashboard وSidebar.

### Verification I2

```text
PR #95 merged on GitHub: PASS
Feature commit:
- 67f96f5da27f528f2fe57bbdd79136d78e255058
Merge commit:
- fa8041b3afc78e4d21419ece8dd405d425702aba

WorkspaceDashboardTest:
2 passed (9 assertions)

AppShellTest:
2 passed (27 assertions)

Laravel full regression:
144 passed (715 assertions)

Laravel Pint on I2 files:
PASS

Frontend build:
npm run build — PASS

Manual browser verification:
PASS

FastAPI:
لم تُشغّل الاختبارات لأن I2 لم تغيّر FastAPI.
```

## المهمة الحالية/التالية

```text
I3 — Documents list / cards / filters
```

Baseline المهمة التالية:

```text
دُمجت I2 في main عبر PR #95 وأصبحت Workspace Dashboard الفعلية وقائمة أحدث الوثائق داخل الـSidebar جاهزتين.
تبدأ I3 — Documents list / cards / filters وفق خريطة المهام في PROJECT_RAG_MASTER_PLAN.md، وتستهلك H12 list/filter/read contract بدلاً من إعادة تفسير حالات Documents محلياً.
يبقى App Shell وWorkspace Dashboard الناتجان عن I1/I2 قاعدة الواجهة؛ لا يعاد تصميمهما ضمن I3 إلا بما يلزم لدمج صفحة الوثائق ضمن نفس العقود.
لا تغيّر المرحلة I عقود H12/H13 أو Business State داخل Blade/JavaScript دون توثيق gap معماري صريح.
لا يوجد Compare/Winner/temporary artifact lifecycle في المعمارية المستهدفة.
```

---

# 12. التتبع التاريخي

التصاميم والمهام التي أزيلت من الخريطة النشطة لا تحفظ هنا كمهام ملغاة.

للتدقيق التاريخي يرجع إلى Git / Pull Requests، وبشكل خاص الـbaseline التاريخي السابق (وليس حالة `main` الحالية):

```text
main@a1f28097b398b9bb277f85990a55e489bd54d880
```

هذا يحافظ على التاريخ بدون تلويث خريطة التنفيذ الحالية بمهام لم تعد جزءاً من النظام المستهدف.
