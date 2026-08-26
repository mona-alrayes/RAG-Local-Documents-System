# سجل تنفيذ مشروع RAG

> **المرجع المعماري:** `PROJECT_RAG_MASTER_PLAN.md`
> **الغرض:** حفظ الحالة التنفيذية الفعلية ونقطة الاستلام بين المحادثات، دون تكرار التفاصيل الموجودة في الـMaster Plan أو Git/PRs.
> **آخر تحديث:** 2026-08-26
> **الحالة العامة:** قيد التنفيذ

---

# CURRENT HANDOFF — نقطة الاستلام

> **هذا هو أول قسم يُقرأ في أي Chat جديد.**

```text
Project Mode: Start From Scratch
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Active Development

Verified Main Commit: 8d24fcd42b2ed5d13463872de693ea89b5ccebed
Last Merged PR: #47 — feat(D9): validate startup configuration
Latest Task PR: #47 — feat(D9): validate startup configuration
D9 Implementation Commit: 33d6148933827a2e650601c4ecd98eca98781353
D9 Verification: Python 3.12.13 (.venv); focused D9 startup configuration tests 4 passed; full FastAPI regression 13 passed
Last Completed Task: D9 — Startup configuration validation
Current Task: D10 — Base/cloud/local dependency split
Current Task Status: TODO
Expected Task Branch: task/D10-base-cloud-local-dependency-split
Next Task After Completion: D11 — Local runtime/device resolver + startup probe + resource telemetry

Schema Audit: 2026-08-21 — B12 migration up/down/up + MySQL 8.4.11 verified
Live Tables: 13
Open Blockers: لا يوجد
```

## المراجع المطلوبة

- هذا الملف = **Single Source of Truth للحالة التنفيذية**.
- `PROJECT_RAG_MASTER_PLAN.md` = **المرجع الأعلى للمعمارية والنطاق**.
- القسم `174` هو المرجع المعماري النشط، و`174.20` له الأولوية ضمن القرارات التي يغطيها.
- مهام AI ترجع أيضاً إلى الـNotebookين المرجعيين عند الحاجة.
- **Task واحدة = Chat مستقل**.

---

# 1. قواعد التنفيذ

1. لا تعتبر أي Task `DONE` إلا بعد التنفيذ والتحقق المناسب.
2. لا نفترض نجاح أي أمر دون رؤية مخرجاته.
3. نحافظ على Clean Code وSeparation of Concerns ولا نوسع Scope المهمة.
4. الاختبارات تقتصر على الضروري للمهمة والـregression المرتبط مباشرة بها.
5. أي قرار معماري جديد يجب أن ينعكس في الـMaster Plan؛ هذا الملف يسجل الحالة التنفيذية فقط.
6. Git commits وPull Requests هي المرجع التفصيلي للملفات والـdiffs والأوامر التاريخية، لذلك لا نكررها هنا.
7. عند إغلاق أي Task:
   - نغير حالتها في جدول المرحلة إلى `DONE`.
   - نضيف سطراً مختصراً في سجل الإنجاز.
   - نحدّث `CURRENT HANDOFF`.
   - نحدد المهمة التالية.

## حالات المهام

| الحالة | المعنى |
|---|---|
| `TODO` | لم تبدأ بعد |
| `IN PROGRESS` | قيد التنفيذ |
| `VERIFY` | التنفيذ موجود ويحتاج تحقق |
| `BLOCKED` | متوقفة بسبب عائق |
| `DONE` | منجزة ومتحقق منها |
| `N/A` | غير مطلوبة وفق قرار موثق |

---

# 2. جداول المهام حسب المراحل

> هذه الجداول تبقى كاملة عمداً لأنها المرجع البصري السريع لتغيير حالة كل Task بين `TODO` و`DONE`.
> تفاصيل المعمارية لكل مهمة تبقى في `PROJECT_RAG_MASTER_PLAN.md`، أما التفاصيل التاريخية للتنفيذ فتبقى في Git/PRs.

# 3. بوابة البداية — P0 Baseline Audit

> **الحالة: `N/A`**
>
> تم إلغاء هذه البوابة لأن القرار النهائي هو **بدء المشروع من الصفر** وليس استكمال Codebase سابق.

| المهمة | الحالة | السبب |
|---|---|---|
| P0 Baseline Audit | N/A | لا يوجد مشروع سابق يحتاج إلى مراجعة قبل التنفيذ |

**الانتقال المباشر:** `A1 — إنشاء Laravel Application`.

---

---

# 4. المرحلة A — تأسيس المشروع

| المهمة | الحالة |
|---|---|
| A1 إنشاء Laravel application | DONE |
| A2 إعداد Authentication | DONE |
| A3 إعداد MySQL | DONE |
| A4 إعداد Redis | DONE |
| A5 إعداد Queue | DONE |

**معيار انتهاء المرحلة:** المستخدم يستطيع التسجيل والدخول، والـQueue تعمل بنجاح.

---

# 5. المرحلة B — Documents Foundation

| المهمة | الحالة |
|---|---|
| B1 documents migration وفق Master Plan 174.7.1 | DONE |
| B2 Document model والعلاقات الأساسية | DONE |
| B3 FileType وDocumentStatus enums/casts | DONE |
| B4 DocumentPolicy واختبارات ownership | DONE |
| B5 Documents index/details Blade skeleton | DONE |
| B6 Upload validation لملف واحد | DONE |
| B7 Private storage/download authorization | DONE |
| B8 SHA-256 وسياسة duplicate في Application | DONE |
| B9 document_processing_runs migration | DONE |
| B10 ProcessingRun model/enums/relations | DONE |
| B11 selected_processing_run_id migration/invariants | DONE |
| B12 document_processing_comparisons migration/model | DONE |

**معيار انتهاء المرحلة:** Domain الوثائق وRuns والمقارنات ممثلة بوضوح، دون تنفيذ AI.

---

# 6. المرحلة C — Security Pipeline

| المهمة | الحالة |
|---|---|
| C1 On-demand ClamAV CLI scan worker + persistent signatures + Local heavy-resource lock contract | DONE |
| C2 DocumentSecurityService | DONE |
| C3 Temporary upload flow | DONE |
| C4 Clean path | DONE |
| C5 Infected/fail-closed path | DONE |
| C6 Configurable security-scan routing | DONE |
| C7 Aggregate status transitions | DONE |
| C8 Security tests | DONE |

**معيار انتهاء المرحلة:** يطبق Validation دائماً. يكون فحص ClamAV مفعّلاً افتراضياً؛ عند تفعيله يمر الملف عبر `document_quarantine` و`security-scan` ويطبق Fail-Closed قبل أي AI. يمكن تعطيله فقط بإعداد صريح، وعندها يخزن الملف مباشرة في `documents` بعد Validation من دون Quarantine أو Scan. تعطل ClamAV لا يحوّل المسار تلقائياً إلى bypass. عند تفعيل الفحص يعمل Scan/Signature Update متسلسلاً، وفي Local Demo يشترك مع `ai-local` في القفل العالمي.

---

# 7. المرحلة D — FastAPI Foundation and Capabilities

| المهمة | الحالة |
|---|---|
| D1 FastAPI project | DONE |
| D2 Typed config | DONE |
| D3 Structured logging/correlation IDs | DONE |
| D4 Internal API security | DONE |
| D5 Health endpoint | DONE |
| D6 Versioned DTO schemas | DONE |
| D7 Structured exceptions | DONE |
| D8 Deployment capabilities endpoint | DONE |
| D9 Startup configuration validation | DONE |
| D10 Base/cloud/local dependency split | TODO |
| D11 Local runtime/device resolver + startup probe + resource telemetry | TODO |

**معيار انتهاء المرحلة:** Laravel يرى صحة FastAPI وقدرات البيئة، وCloud image لا يحمل Local AI dependencies، وLocal runtime يعلن Backend/dtype المقاسين بلا Fallback صامت.

---

# 8. المرحلة E — Qdrant

| المهمة | الحالة |
|---|---|
| E1 Local Qdrant + persistent volume | TODO |
| E2 `rag_documents_cloud` collection | TODO |
| E3 `rag_documents_hybrid_local` collection | TODO |
| E4 Dense/sparse configs | TODO |
| E5 Payload indexes | TODO |
| E6 Point builder مع run metadata | TODO |
| E7 Idempotent upsert/count/delete | TODO |
| E8 Cross-user leakage tests | TODO |

**معيار انتهاء المرحلة:** Collections منفصلة ودائمة وآمنة وقابلة للإدخال والاسترجاع والحذف.

---

# 9. المرحلة F — Parsing and Normalization

| المهمة | الحالة |
|---|---|
| F1 Loader interface | TODO |
| F2 LlamaParse provider | TODO |
| F3 PDF loader | TODO |
| F4 DOCX loader | TODO |
| F5 TXT loader | TODO |
| F6 Normalized page/section schema | TODO |
| F7 Reuse parsed result in Compare | TODO |
| F8 Loader tests | TODO |

**معيار انتهاء المرحلة:** الملفات الثلاثة تتحول إلى تمثيل موحد ويمكن مشاركة Parsing في Compare.

---

# 10. المرحلة G — Profile Processing

| المهمة | الحالة |
|---|---|
| G1 ProcessingProfile registry | TODO |
| G2 Cloud chunking | TODO |
| G3 Cloud Jina embeddings | TODO |
| G4 Cloud sparse representation | TODO |
| G5 Hybrid Local chunking | TODO |
| G6 Local BGE-M3 embeddings | TODO |
| G7 Local BM25 | TODO |
| G8 Batch/retry/rate-limit | TODO |
| G9 Metrics/report بلا vectors/cost | TODO |
| G10 Profile isolation tests | TODO |
| G11 Single-active-model coordinator + lazy load/release-after-stage | TODO |

**معيار انتهاء المرحلة:** كل Profile ينتج Chunks وVectors وتقريراً صحيحاً دون خلط Providers، ولا يوجد أكثر من Model ثقيل واحد في الذاكرة؛ يحمل Lazy ويحرر بعد انتهاء Stage قبل تحميل التالي.

---

# 11. المرحلة H — Temporary Artifacts and Promotion

| المهمة | الحالة |
|---|---|
| H1 Private artifact store/opaque refs | TODO |
| H2 Configurable 24h TTL | TODO |
| H3 Temporary retrieval index | TODO |
| H4 Winner promotion | TODO |
| H5 Count verification | TODO |
| H6 Loser cleanup | TODO |
| H7 Scheduled expiration cleanup | TODO |
| H8 Idempotency/failure recovery tests | TODO |

**معيار انتهاء المرحلة:** لا تدخل Qdrant الدائمة إلا نتيجة فائزة متحقق منها.

---

# 12. المرحلة I — Laravel Processing Orchestration

| المهمة | الحالة |
|---|---|
| I1 AiServiceClient | TODO |
| I2 Processing DTOs | TODO |
| I3 ProcessDocumentJob | TODO |
| I4 Single-profile flow | TODO |
| I5 Compare flow وإنشاء Runين | TODO |
| I6 Report persistence | TODO |
| I7 Trial-question flow | TODO |
| I8 Winner selection transaction | TODO |
| I9 Aggregate status projector | TODO |
| I10 Queue retries/timeouts | TODO |
| I11 Laravel serialized `ai-local` queue + global Redis lock shared with `security-scan` for full FastAPI call | TODO |

**معيار انتهاء المرحلة:** Single أو Compare يكتملان بحالة متسقة بين Laravel وFastAPI وQdrant، وكل عمل Local AI ثقيل يمر عبر Queue واحدة ذات concurrency=1، ولا يبدأ قبل انتهاء Security scan وتحرير Process الفحص.

---

# 13. المرحلة J — Blade Documents Experience

| المهمة | الحالة |
|---|---|
| J1 Responsive authenticated app shell/sidebar | TODO |
| J2 Workspace dashboard | TODO |
| J3 Documents list/cards/filters | TODO |
| J4 One-file upload + capability-aware options | TODO |
| J5 Document details/timeline | TODO |
| J6 Comparison screen | TODO |
| J7 Trial-question interaction | TODO |
| J8 Select-winner confirmation/states | TODO |
| J9 Accessibility/responsive/error states | TODO |

**معيار انتهاء المرحلة:** المستخدم يرفع ويتابع ويقارن ويعتمد النتيجة من واجهة RTL واضحة.

---

# 14. المرحلة K — Conversations Database

| المهمة | الحالة |
|---|---|
| K1 Conversations migration/model | TODO |
| K2 conversation_document unique pivot | TODO |
| K3 Messages + document-target snapshots/metrics | TODO |
| K4 message_sources + run/profile | TODO |
| K5 Policies | TODO |
| K6 Create/list conversations | TODO |
| K7 Multi-document selection | TODO |
| K8 Ready/indexed/runtime-capable filtering | TODO |

**معيار انتهاء المرحلة:** المحادثة تختار ملفاً أو عدة ملفات يملكها المستخدم ومفهرسة فعلياً، وتحفظ الرسائل وSnapshot الوثائق لأغراض Audit من دون جدول ذاكرة أو Extractor أو Stream lifecycle.

---

# 15. المرحلة L — Retrieval and Reranking

| المهمة | الحالة |
|---|---|
| L1 Trusted document_targets contract | TODO |
| L2 Cloud query embeddings/retrieval | TODO |
| L3 Hybrid Local query embeddings/retrieval | TODO |
| L4 Mandatory user/document/run filters | TODO |
| L5 Per-profile Dense + BM25 + RRF | TODO |
| L6 Cloud Jina reranker | TODO |
| L7 Local BGE reranker | TODO |
| L8 Cross-profile rank fusion | TODO |
| L9 Metadata/source preservation | TODO |
| L10 Retrieval quality/security tests | TODO |

**معيار انتهاء المرحلة:** الاسترجاع يقتصر على الملفات المختارة ويدعم Profiles مختلطة دون مقارنة raw scores.

---

# 16. المرحلة M — Generation

| المهمة | الحالة |
|---|---|
| M1 ContextService + آخر تبادلين مكتملين كسياق إحالة محدود | TODO |
| M2 Prompt/insufficient-context | TODO |
| M3 LLMProvider interface/registry keyed by trusted processing profile/capabilities | TODO |
| M4 HF `Qwen/Qwen3.5-9B` | TODO |
| M5 Ollama `qwen3.5:4b` | TODO |
| M6 No-fallback/provider validation | TODO |
| M7 Answer/sources/timings response | TODO |
| M8 Provider contract tests | TODO |
| M9 Ollama/FastAPI release-after-stage + `keep_alive=0` coordination | TODO |
| M10 Local lifecycle/pressure recovery/concurrency/no-leak tests | TODO |

**معيار انتهاء المرحلة:** التوليد يعمل بالعقد نفسه في Cloud وLocal دون تحميل Local LLM في Online، ويحرر Qwen بعد Generation عبر `keep_alive=0` بلا Fallback صامت. يسمح بآخر تبادلين مكتملين لفهم الإحالات فقط، وتبقى Chunks المسترجعة مصدر الحقائق، ولا يوجد Streaming backend.

---

# 17. المرحلة N — Chat Experience

| المهمة | الحالة |
|---|---|
| N1 Chat layout/list | TODO |
| N2 Top document multi-selector | TODO |
| N3 Selected chips/authorization | TODO |
| N4 AskConversationJob | TODO |
| N5 Save document-target snapshots/answer/metrics | TODO |
| N6 Sources drawer/relevance score | TODO |
| N7 Timings display | TODO |
| N8 Pending/failure/retry + `جاري التفكير` نابضة + completed-answer reveal | TODO |
| N9 Mixed-profile/context-limit/progressive-reveal accessibility E2E | TODO |

**معيار انتهاء المرحلة:** المستخدم يحادث وثيقة أو عدة وثائق ويشاهد المصادر والأزمنة ودرجة صلة كل مصدر. أثناء الانتظار تظهر `جاري التفكير` بنبض هادئ، وبعد حفظ الإجابة المكتملة يكشفها Frontend تدريجياً كتأثير بصري فقط، مع reduced-motion وإظهار كامل ومن دون NDJSON أو Redis relay أو Replay.

---

# 18. المرحلة O — Filament

| المهمة | الحالة |
|---|---|
| O1 Core Resources | TODO |
| O2 ProcessingRuns/Comparisons Resources | TODO |
| O3 Dashboard widgets | TODO |
| O4 Failed/infected/expired filters | TODO |
| O5 Safe retry actions | TODO |
| O6 FastAPI admin chunks endpoint/client | TODO |
| O7 Read-only paginated Qdrant Chunks | TODO |
| O8 Admin audit logging | TODO |
| O9 Authorization/no-vectors tests | TODO |

**معيار انتهاء المرحلة:** المشرف يراقب النظام ويشاهد Chunks بأمان دون وصول Laravel مباشر إلى Qdrant.

---

# 19. المرحلة P — Security and Operations

| المهمة | الحالة |
|---|---|
| P1 Ownership/IDOR | TODO |
| P2 Qdrant leakage by user/document/run | TODO |
| P3 MIME/size/malware | TODO |
| P4 FastAPI authentication | TODO |
| P5 Private download/chunk authorization | TODO |
| P6 Artifact TTL/permissions | TODO |
| P7 Secret/log redaction | TODO |
| P8 Deletion/reprocessing consistency | TODO |

**معيار انتهاء المرحلة:** لا تسرب بين المستخدمين أو الوثائق أو Runs، ولا تبقى artifacts أو Points يتيمة.

---

# 20. المرحلة Q — Final Validation

| المهمة | الحالة |
|---|---|
| Q1 PDF/DOCX/TXT E2E | TODO |
| Q2 Cloud profile E2E | TODO |
| Q3 Hybrid Local profile E2E | TODO |
| Q4 Compare/select E2E | TODO |
| Q5 Multi-document/mixed-profile chat E2E | TODO |
| Q6 Queue/restart/Qdrant persistence | TODO |
| Q7 RAG quality/source correctness | TODO |
| Q8 Security-scan/AI stage memory-performance-quality calibration على الجهازين | TODO |
| Q9 Cloud-only lightweight image verification | TODO |
| Q10 Local Ollama backend/loaded-model/`keep_alive=0` verification | TODO |
| Q11 Backup/restore | TODO |
| Q12 Final documentation including non-streaming visual reveal disclosure | TODO |

**معيار انتهاء المرحلة:** المساران والمقارنة والمحادثة متعددة الملفات يعملون End-to-End بأمان وبعد Restart؛ ClamAV وBGE/Reranker/Qwen لا يتداخلون في RAM، والسياق لا يتجاوز آخر تبادلين مكتملين، والعرض التدريجي Frontend-only بعد اكتمال الإجابة.

---

# 21. Deployment — DPL

| المهمة | الحالة |
|---|---|
| DPL-1 Oracle Account | TODO |
| DPL-2 إنشاء Oracle ARM64 VM بموارد 2 OCPU/12 GB والتحقق من Free eligibility | TODO |
| DPL-3 إعداد الشبكة وفتح 22/80/443 فقط | TODO |
| DPL-4 تحديث Ubuntu وSSH hardening | TODO |
| DPL-5 تثبيت Docker/Buildx/Compose والتحقق من ARM64 images | TODO |
| DPL-6 Clone المشروع | TODO |
| DPL-7 إعداد Cloud-only `.env` وSecrets permissions | TODO |
| DPL-8 إنشاء Volumes واختبار permissions/free disk | TODO |
| DPL-9 تشغيل MySQL/Redis/Qdrant وتحديث تواقيع ClamAV one-shot مع Health readiness | TODO |
| DPL-10 تشغيل FastAPI Cloud-only image وHealth/Capabilities | TODO |
| DPL-11 تشغيل Laravel PHP-FPM والمigrations/cache | TODO |
| DPL-12 تشغيل Queue Worker وSecurity Scan Worker مع scheduled signature-update job على Queue نفسها | TODO |
| DPL-13 تشغيل Nginx مع upload/timeouts/forwarded headers/log rotation | TODO |
| DPL-14 HTTPS وتجديد تلقائي وHTTP→HTTPS | TODO |
| DPL-15 اختبار PDF مع Clean/infected/fail-closed scan | TODO |
| DPL-16 اختبار DOCX مع حدود ZIP bomb | TODO |
| DPL-17 اختبار TXT والمسار الكامل | TODO |
| DPL-18 Cloud-only capability/UI/API test | TODO |
| DPL-19 التحقق أن Online image بلا Torch/Transformers/Ollama/models | TODO |
| DPL-20 HF `Qwen/Qwen3.5-9B` provider smoke/error/budget test | TODO |
| DPL-21 Security/ownership/private ports/profile rejection test | TODO |
| DPL-22 Encrypted off-VM backup + isolated restore test | TODO |
| DPL-23 Restart/persistence/readiness/startup-race test | TODO |
| DPL-24 Mac/ASUS Local topology: Docker infrastructure + on-demand scan worker + global heavy-resource lock + Host-native FastAPI/Ollama | TODO |
| DPL-25 Compare flow المتسلسل على الجهاز المحلي فقط | TODO |

**قاعدة النشر:** النسخة Online تستخدم `RAG_DEPLOYMENT_MODE=cloud` ولا تشغّل أو تنزّل أي Local embedding/reranker/LLM. في Local demo تبقى البنية الأساسية داخل Docker وتعمل FastAPI/Ollama على Host؛ ينفذ ClamAV عند الطلب كـProcess قصيرة العمر، ويعمل Local AI بModel واحد وconcurrency=1. MySQL مصدر الحقيقة للرسائل، ولا يوجد Redis Stream أو NDJSON أو Chat streaming؛ `جاري التفكير` وProgressive reveal تأثيران بصريان بعد اكتمال الإجابة وفق 174.20.

---

# 22. الحالة التنفيذية الحالية

هذا القسم لا يكرر الـMaster Plan؛ يحتفظ فقط بالمعلومات التنفيذية التي تحتاجها المهام القريبة.

## 22.1 Schema / Domain Snapshot

تم التحقق من الـSchema على MySQL 8.4.11 بعد B12.

### `documents`

- يمثل ملكية الوثيقة وبيانات الملف والحالة التجميعية.
- `user_id` مرتبط بـ`users.id` مع `ON DELETE RESTRICT`.
- `sha256` هو `NOT NULL`.
- منع duplicate يتم على مستوى التطبيق ضمن `(user_id, sha256)` ولا يوجد Unique constraint.
- `selected_processing_run_id` nullable ويرتبط بـ`document_processing_runs.id` مع `ON DELETE RESTRICT`.
- الـForeign Key وحده لا يضمن أن الـselected Run تعود لنفس الوثيقة أو أن حالتها `indexed`؛ هذه Invariants تبقى مسؤولية Domain/Orchestration logic.

### `document_processing_runs`

- يمثل كل Processing Run بصورة مستقلة عن `documents`.
- يحتوي Profile/Status/Snapshot والعدادات والتوقيتات والتحذيرات والأخطاء وTemporary Artifact/Qdrant metadata.
- `total_chunks` و`vector_count` يبدأان من `0`.
- `total_pages` و`vector_dimension` nullable.

### `document_processing_comparisons`

- منفذ وفق القسم `174.7.4`.
- يربط الوثيقة والمستخدم وCloud/Hybrid Runs والـselected Run الاختياري.
- جميع Foreign Keys تستخدم `ON DELETE RESTRICT`.
- الحالات: `processing`, `ready`, `decided`, `expired`, `failed`.
- تطابق جميع الـRuns مع نفس الوثيقة والمستخدم يبقى مسؤولية Domain/Orchestration logic.

## 22.2 Document Components المنفذة

Routes الحالية:

```text
index
show
store
download
```

المكونات المهمة للمسار الحالي:

```text
UploadDocumentRequest
SecureDocumentUpload
DocumentStorageService
DocumentUploadService
ScanDocumentSecurityJob
DocumentSecurityService
DocumentSecurityScanStatus
LocalHeavyResourceLock
```

## 22.3 مسار الرفع المثبت بعد C8

```text
Upload
  ↓
Validation
  ↓
DocumentController
  ↓
DocumentUploadService
  ├── Security Scan enabled/default
  │     ↓
  │   DocumentStorageService::storeQuarantined()
  │     ↓
  │   DocumentStatus::Pending
  │     ↓
  │   dispatch ScanDocumentSecurityJob
  │     ↓
  │   security-scan queue
  │     ↓
  │   Job starts → DocumentStatus::Scanning
  │     ↓
  │   DocumentSecurityService::scan()
  │     ├── clean
  │     │     ↓
  │     │   DocumentUploadService::promoteAfterCleanScan()
  │     │     ↓
  │     │   DocumentStorageService::promoteQuarantined()
  │     │     ↓
  │     │   Permanent Private Storage (`documents`)
  │     │     ↓
  │     │   DocumentStatus::Pending
  │     │
  │     ├── infected
  │     │     ↓
  │     │   DocumentUploadService::rejectAfterUnsafeScan()
  │     │     ↓
  │     │   DocumentStatus::Infected + quarantine retained
  │     │
  │     └── scan_failed
  │           ↓
  │         DocumentUploadService::rejectAfterUnsafeScan()
  │           ↓
  │         DocumentStatus::Failed + quarantine retained
  │
  │   unexpected exception after scan starts
  │     ↓
  │   DocumentStatus::Failed + exception rethrown
  │
  └── Security Scan disabled explicitly
        ↓
      DocumentStorageService::storePermanent()
        ↓
      Permanent Private Storage (`documents`)
        ↓
      DocumentStatus::Pending
```

الحالة المعتمدة والمثبتة بعد C8:

- `DOCUMENT_SECURITY_SCAN_ENABLED=true` هو الوضع الافتراضي.
- اختيار مسار التخزين بقي مركزياً داخل `DocumentUploadService`.
- عند التفعيل/الوضع الافتراضي يخزن الملف أولاً في `document_quarantine` بحالة `pending`، ثم يرسل `ScanDocumentSecurityJob` إلى Queue `security-scan`.
- لا تتحول الوثيقة إلى `scanning` عند مجرد التخزين أو الانتظار في Queue؛ تتحول عند بدء تنفيذ Job فعلياً.
- `clean` وحدها تسمح بالـpromotion إلى `documents`، وبعد نجاح الـpromotion تعود الوثيقة إلى `pending` لأن Processing Job لم يُرسل بعد.
- `infected` يبقى Fail-Closed ويحوّل نفس `Document` إلى `DocumentStatus::Infected` مع إبقاء الملف في quarantine.
- `scan_failed` يبقى Fail-Closed ويحوّل نفس `Document` إلى `DocumentStatus::Failed` مع إبقاء الملف في quarantine.
- أي Exception غير متوقع بعد دخول Job إلى `scanning` يحوّل الوثيقة إلى `failed` ثم يعاد رمي الاستثناء حتى تبقى Queue على علم بالفشل.
- لا يوجد fallback تلقائي من `infected` أو `scan_failed` أو Exception إلى التخزين الدائم.
- عند التعطيل الصريح فقط (`false`) يخزن الملف مباشرة في `documents` بعد Validation ولا يرسل `ScanDocumentSecurityJob`؛ تبقى الحالة `pending`.
- `queued` محجوز لوقت dispatch الفعلي لـProcessing Job المستقبلي (`I3 — ProcessDocumentJob`) ولا يستخدم لمجرد أن الملف أصبح آمناً أو مخزناً دائماً.
- `DocumentStorageService` بقي مسؤولاً عن Storage/SHA-256/Duplicate/Cleanup primitives فقط ولا يقرر Security policy أو Aggregate status workflow.
- لا يصل Upload إلى FastAPI أو Qdrant أو AI Pipeline ضمن مرحلة C.

## 22.4 Security Runtime المنفذ

- ClamAV يعمل عبر `clamscan` كـProcess قصيرة العمر؛ لا يوجد `clamd` دائم.
- Queue الأمنية `security-scan` تعمل بتزامن `1`.
- تواقيع ClamAV محفوظة بشكل دائم ويتم تحديثها مجدولاً.
- `DocumentSecurityService` يعيد:
  - `clean`
  - `infected`
  - `scan_failed`
- أي Failure/Timeout/Exception يطبق Fail-closed ولا يتحول إلى `clean`.
- `LocalHeavyResourceLock` موجود ويعاد استخدامه للأعمال الثقيلة المحلية؛ لا ينشأ Lock موازٍ.

---

## 22.5 Configurable security-scan routing المنفذ في C6

تم تنفيذ قرار C6 وفق العقد المعماري المعتمد:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true   # default

Enabled/default:
Validation
  → storeQuarantined()
  → Security Scan
  → clean → promoteQuarantined() → documents
  → infected / scan_failed → Fail-Closed

Disabled explicitly:
Validation
  → storePermanent()
  → documents
```

تفاصيل التنفيذ المهمة للمهام اللاحقة:

- أضيف الإعداد `security.document_security_scan.enabled` بقيمة افتراضية `true`.
- أضيف `DOCUMENT_SECURITY_SCAN_ENABLED=true` إلى `.env.example`.
- `DocumentUploadService` هو المسؤول الوحيد عن اختيار مسار التخزين.
- direct permanent storage يعمل فقط عندما تكون قيمة الإعداد `false` صراحةً؛ أي قيمة أخرى تبقي المسار الآمن عبر quarantine.
- أعيدت تسمية `DocumentStorageService::store()` إلى `storePermanent()` بعد التحقق من عدم وجود callers مباشرين للاسم القديم.
- `DocumentStorageService` لم يكتسب أي Security policy logic.
- مسارا C4/C5 بقيا دون تغيير وظيفي: Clean promotion محفوظ و`infected`/`scan_failed` يبقيان Fail-Closed.
- التحقق المركز في C6 نجح: `6 tests / 25 assertions`، وPint على الملفات المعدلة و`git diff --check` ناجحان.
- العقود النهائية لـdefault/fail-closed/no-auto-bypass أصبحت مثبتة ضمن C8.

## 22.6 Aggregate status transitions المنفذة في C7

تم تنفيذ C7 من دون إدخال State Machine عامة أو توسيع نطاق Processing:

```text
Security enabled/default:
pending
  → dispatch security job
  → scanning عند بدء Job فعلياً
  → clean      → promotion → pending
  → infected   → infected
  → scan_failed / unexpected failure → failed

Security disabled explicitly:
pending
  → permanent storage
  → pending
```

تفاصيل التنفيذ:

- أضيف `ScanDocumentSecurityJob` كطبقة orchestration للفحص الأمني، ويعمل على Queue المعرفة في `security.clamav.queue` مع default `security-scan`.
- `DocumentUploadService` يرسل الـJob فقط للمسار enabled/default بعد نجاح التخزين في quarantine.
- الـJob يثبت `scanning` عند بداية التنفيذ قبل استدعاء `DocumentSecurityService`.
- Clean path يعيد استخدام C4 كما هو، وUnsafe path يعيد استخدام C5 كما هو.
- بعد clean promotion تعود الحالة إلى `pending` انتظاراً لـProcessing dispatch الفعلي لاحقاً.
- المسار disabled لا يرسل Security Job ويبقى `pending` بعد التخزين الدائم.
- تم عزل اختبارات C4/C5 عن Queue الفعلية باستخدام `Queue::fake()` بعد أن أصبح `DocumentUploadService::store()` يرسل Job عند تفعيل الفحص.
- أضيف اختبار مركز يثبت `pending → scanning → clean → pending` مع انتقال الملف من quarantine إلى permanent storage.
- التحقق النهائي في C7 نجح: `7 tests / 33 assertions`، و`git diff --check` ناجح.
- Security matrix الأشمل أغلقت في C8 من دون تغيير production semantics.

## 22.7 Security tests المنفذة في C8

أغلقت C8 بأقل توسع اختباري ممكن، مع إعادة استخدام اختبارات C3–C7 وعدم إنشاء Matrix مكررة:

- عُدل اختبار explicit bypass الموجود ليثبت أن `DOCUMENT_SECURITY_SCAN_ENABLED=false`:
  - لا يرسل `ScanDocumentSecurityJob`.
  - لا يستخدم quarantine.
  - يخزن مباشرة في permanent storage.
  - يبقي `DocumentStatus::Pending`.
- أضيف اختبار Job مركّز لـ`scan_failed` يثبت:
  - الحالة النهائية `failed`.
  - بقاء الملف في `document_quarantine`.
  - غياب الملف عن `documents`.
  - عدم وجود automatic fallback إلى permanent storage.
- بقيت عقود clean وinfected والتفعيل الافتراضي مغطاة بالاختبارات القائمة، لذلك لم تُكرر.
- لم يتغير أي production code في C8.
- مجموعة Security المركزة نجحت بعد Pint: `8 tests / 39 assertions`.
- Pint على الملفين المعدلين نجح، و`git diff --check` نجح.

## 22.8 FastAPI Typed Configuration المنفذة في D2

تم تنفيذ D2 كطبقة إعدادات مركزية صغيرة ومنفصلة دون إدخال مسؤوليات D3 وما بعدها:

- أضيف `app/core/config.py` مع `Settings` مبنية على `pydantic-settings`.
- أضيف `DeploymentMode` كـ`StrEnum` بقيمتي `cloud` و`local`.
- أضيف دعم القراءة من Environment Variables وملف `.env` مع تجاهل المفاتيح الإضافية غير المعروفة في هذه المرحلة.
- `app_name` و`app_version` أصبحا مصدرهما `Settings` بدلاً من hardcoding داخل `app/main.py`.
- أضيف `get_settings()` مع `lru_cache` لتوفير instance مركزية للإعدادات.
- أضيف الاعتماد `pydantic-settings==2.12.0` إلى `pyproject.toml`.
- لم تدخل Structured Logging أو Correlation IDs أو Internal API Security أو Health أو AI/Qdrant/Parsing/Providers ضمن D2.
- التحقق من القيمة الافتراضية أعاد `RAG_DEPLOYMENT_MODE=local` بنجاح.
- override عبر `RAG_DEPLOYMENT_MODE=cloud` نجح.
- القيمة غير الصالحة `banana` رُفضت بـPydantic `ValidationError` مع exit code `1`.
- FastAPI app import أعاد `RAG AI Service 0.1.0` بنجاح.
- `pip check` أعاد `No broken requirements found.` و`git diff --check` نجح.
- D2 تم التحقق منها باستخدام Python `3.12.13` داخل `fastapi-app/.venv`؛ بعد إعداد بيئة D3 أصبحت `.venv` الحالية Python `3.12.14`. يبقى القيد المعتمد للمشروع `>=3.12,<3.13`، ولا يستخدم Python العام من Miniconda `3.13.13`.

## 22.9 Structured Logging/Correlation IDs المنفذة في D3

تم تنفيذ D3 كطبقة Logging مركزية ومستقلة، دون إدخال Security أو Health أو DTOs أو AI:

- أضيف `app/core/logging.py` ويحتوي:
  - `ContextVar` لحفظ `correlation_id` ضمن سياق الطلب بصورة async-safe.
  - دوال `get_correlation_id()` و`set_correlation_id()` و`reset_correlation_id()`.
  - `JsonFormatter` لإخراج Application logs بصيغة JSON.
  - `configure_logging()` لتجهيز Root logger بصورة مركزية.
- الحقول الأساسية في Structured JSON log هي:
  - `timestamp`
  - `level`
  - `logger`
  - `message`
  - `correlation_id`
  - `exception` عند وجود Exception info.
- أضيف `app/middleware/correlation_id.py` كـPure ASGI middleware:
  - يطبق فقط على HTTP requests.
  - يعيد استخدام `X-Correlation-ID` القادم إذا كان ASCII صالحاً وغير فارغ وطوله لا يتجاوز 128 حرفاً.
  - يولد UUID4 جديداً عند غياب المعرّف أو عدم صلاحيته.
  - يضع نفس المعرّف في ContextVar أثناء تنفيذ الطلب.
  - يضيف نفس `x-correlation-id` إلى response headers.
  - يعيد ContextVar إلى حالته السابقة داخل `finally`.
- أضيف logger باسم `app.request` ويسجل `Request completed` بينما correlation context ما زال فعالاً.
- تم ربط `configure_logging()` و`CorrelationIdMiddleware` داخل `create_app()` في `app/main.py`.
- Uvicorn startup/access logs بقيت ضمن logging الخاص بـUvicorn؛ Structured JSON المضاف في D3 يخص Application logs ولم يتم توسيع المهمة لإعادة تصميم Uvicorn logging.
- تم التحقق Runtime عبر `/docs` دون إنشاء Health endpoint قبل D5:
  - correlation ID المرسل من العميل ظهر نفسه في response header وStructured JSON log.
  - عند غياب header تم توليد UUID وظهر نفسه في response header وStructured JSON log.
- `pip check` و`compileall` و`git diff --check` نجحت.
- بيئة التحقق الحالية لـFastAPI هي `fastapi-app/.venv` مع Python `3.12.14`.
- لم تدخل Internal API Security أو Health أو DTOs أو Structured Exceptions الكامل أو AI/Qdrant/Parsing/Providers ضمن D3.

## 22.10 Internal API Security المنفذة في D4

تم تنفيذ D4 كطبقة مصادقة داخلية مركزية وصغيرة تحمي FastAPI من الوصول المباشر، مع الحفاظ على D3 وعدم إدخال مسؤوليات D5 وما بعدها:

- أضيف `internal_api_key` إلى `Settings` كـ`SecretStr | None` ويقرأ من Environment Variable باسم `INTERNAL_API_KEY` دون hardcoding للقيمة.
- كانت القيمة `None` مسموحة ضمن D4 لأن Startup Configuration Validation كان مؤجلاً إلى D9؛ بعد D9 أصبح Startup يرفض `INTERNAL_API_KEY` المفقود أو الفارغ قبل استقبال الطلبات.
- أضيف `app/middleware/internal_api_auth.py` كـPure ASGI middleware مركزي:
  - يطبق فقط على HTTP requests.
  - يقرأ `X-Internal-API-Key` من request headers.
  - missing/empty key يرفض بـ`401 Unauthorized`.
  - invalid key يرفض بـ`401 Unauthorized`.
  - valid key يسمح للطلب بالمرور إلى FastAPI.
  - يقارن المفتاح باستخدام `secrets.compare_digest` على bytes بدلاً من مقارنة عادية للقيم السرية.
  - لا يسجل قيمة المفتاح ولا يعيدها في response body.
- رُبط `InternalApiAuthMiddleware` داخل `create_app()`، ثم بقي `CorrelationIdMiddleware` هو الغلاف الخارجي للطلبات، لذلك الطلبات المرفوضة أمنياً تستمر بالحصول على Correlation ID وتبقى Structured Application Logging من D3 فعالة.
- لم يُضف أي استثناء عام لمسارات HTTP؛ FastAPI بقيت Internal API وليست Browser-facing API.
- أضيف أقل اختبار مطلوب فقط في `tests/test_internal_api_security.py`:
  - missing key → `401`.
  - invalid key → `401`.
  - valid key → يصل إلى FastAPI؛ استخدم `/` وأثبت الوصول عبر `404` الطبيعي لعدم وجود endpoint في هذه المرحلة، من دون إنشاء Health endpoint قبل D5.
- أضيفت test dependencies كـoptional dependencies فقط في `pyproject.toml`: `pytest==9.1.1` و`httpx2==2.12.0`، ولم تضاف إلى Runtime dependencies.
- التحقق النهائي تم داخل `fastapi-app/.venv` باستخدام Python `3.12.14`: `compileall` نجح، واختبارات D4 الثلاثة أعادت `3 passed`، و`pip check` أعاد `No broken requirements found.`، و`git diff --cached --check` نجح.
- لم تدخل Health endpoint أو Versioned DTO schemas أو Structured Exceptions framework الكامل أو Deployment capabilities أو AI/Qdrant/Parsing/Providers أو أي تعديل Laravel ضمن D4.

## 22.11 Deployment Capabilities المنفذة في D8

تم تنفيذ D8 كعقد Capabilities داخلي صغير ومفصول عن الـRoute والإعدادات، من دون إدخال Startup validation أو provider probes أو AI/Qdrant:

- أضيف `GET /api/v1/capabilities` ويخضع تلقائياً لـInternal API Security وCorrelation ID الموجودين مسبقاً.
- أضيف `DeploymentCapabilitiesResponse` ويعرض:
  - `deployment_mode`
  - `supported_profiles`
  - `available_profiles`
  - `compare_available`
  - `providers`
- `supported_profiles` تعني ما تسمح به بيئة النشر معمارياً، أما `available_profiles` فتعني ما ثبتت جاهزيته فعلياً؛ لا يتم الخلط بين المفهومين.
- في `cloud` تكون الـProfiles المدعومة `cloud` فقط.
- في `local` تكون الـProfiles المدعومة `cloud` و`hybrid_local`.
- `compare` بقي Orchestration مشتقة وليس Processing Profile ثالثة؛ لذلك يمثلها الحقل `compare_available` فقط.
- LlamaParse ممثل كـProvider مشترك للمسارين لأن Parsing في Cloud وHybrid Local يعتمد LlamaParse Cloud وفق الخطة الحالية.
- Cloud-specific providers الحالية: Jina embeddings + Jina reranker + Hugging Face LLM.
- Hybrid-local-specific providers الحالية: BGE-M3 embeddings + BGE reranker + Ollama LLM.
- حالات Provider المعرفة في العقد: `available`, `unavailable`, `not_checked`.
- ضمن D8 لا توجد فحوص جاهزية فعلية، لذلك تبقى جميع Providers بحالة `not_checked`، و`available_profiles=[]`، و`compare_available=false` بدلاً من ادعاء جاهزية غير متحققة.
- `CapabilitiesService` يحوي منطق بناء القدرات، بينما `capabilities_routes.py` مسؤول عن HTTP فقط، و`schemas/capabilities.py` مسؤول عن DTOs؛ بقيت المسؤوليات منفصلة.
- لا يعرض endpoint أي Secrets.
- التحقق المركز لـD8 نجح بحالتي Cloud وLocal: `2 passed` داخل `fastapi-app/.venv` على Python `3.12.14`، و`git diff --cached --check` نجح.
- D9 أُنجز لاحقاً كطبقة Startup configuration validation، بينما D11 يبقى مسؤولاً عن Local runtime/device probe.

## 22.12 Startup Configuration Validation المنفذة في D9

تم تنفيذ D9 كطبقة تحقق مركزية تعمل عند FastAPI startup دون إدخال مسؤوليات D10 أو D11:

- أضيف `validate_startup_configuration()` داخل `app/core/config.py` فوق نفس Typed Settings الموجودة منذ D2، دون إنشاء نظام إعدادات موازٍ.
- أصبح `RAG_DEPLOYMENT_MODE` مطلوباً بشكل صريح عند Startup، مع بقاء القيم المقبولة Typed إلى `cloud` أو `local`.
- أصبح `INTERNAL_API_KEY` مطلوباً وغير فارغ عند Startup، ولا تظهر قيمته في رسائل الخطأ.
- أضيف `LOCAL_AI_TOPOLOGY` كإعداد Typed، والقيمة المدعومة حالياً هي `host_native`.
- عند `RAG_DEPLOYMENT_MODE=local` يجب تحديد `LOCAL_AI_TOPOLOGY=host_native`.
- عند `RAG_DEPLOYMENT_MODE=cloud` يجب ألا يكون `LOCAL_AI_TOPOLOGY` مضبوطاً، لمنع Configuration تعلن Cloud مع Local AI runtime.
- رُبط التحقق بـFastAPI `lifespan` بحيث يفشل Startup قبل استقبال Requests، من دون كسر import/application factory.
- أضيف `fastapi-app/.env.example` للإعدادات المطلوبة فعلياً في هذه المرحلة فقط.
- لم تدخل فحوص Ollama أو Models أو GPU/MPS أو Qdrant أو Provider credentials/readiness؛ تبقى هذه المسؤوليات للمراحل اللاحقة.
- التحقق النهائي نجح داخل `fastapi-app/.venv` باستخدام Python `3.12.13`: اختبارات D9 المركزة `4 passed`، وكامل FastAPI regression `13 passed`.

---

# 23. Baseline معماري تنفيذي

> التفاصيل الكاملة موجودة في `PROJECT_RAG_MASTER_PLAN.md`. هذه القائمة فقط لمنع فقدان القرارات التي تؤثر مباشرة على التنفيذ القادم.

- Processing Profiles الفعلية: `cloud` و`hybrid_local`؛ أما `compare` فهو Orchestration وليس Profile ثالثة.
- Oracle Online = Cloud-only بلا Local AI weights/dependencies.
- Local Demo = Docker للبنية الأساسية وFastAPI/Ollama على Host.
- Local heavy work = concurrency `1` + global Redis lock + single-active-model + release-after-stage.
- Security scan = ClamAV on-demand افتراضياً (`DOCUMENT_SECURITY_SCAN_ENABLED=true`) مع Fail-Closed؛ التعطيل مسموح فقط بإعداد صريح ويؤدي إلى direct permanent storage بعد Validation، بلا fallback تلقائي عند فشل الفاحص. `scanning` يبدأ عند تشغيل Security Job فعلياً، و`queued` لا يستخدم قبل dispatch حقيقي لـProcessing Job.
- Qdrant = Collection منفصلة لكل Processing Profile مع mandatory user/document/run filters.
- Persistent Qdrant يحتفظ بالـselected winner فقط بعد التحقق.
- Laravel/MySQL هو مصدر الحقيقة للتطبيق؛ لا توجد DB علائقية مستقلة لـFastAPI في v1.
- المحادثة تستخدم آخر تبادلين مكتملين فقط لفهم الإحالات؛ لا توجد ذاكرة مستخرجة في v1.
- لا يوجد True Streaming/NDJSON/Redis Stream في v1؛ `جاري التفكير` وProgressive Reveal تأثيران Frontend-only.
- LLM Provider يحدد من Processing Profile موثوقة/Capabilities عبر Registry، بلا global `LLM_PROVIDER` switch وبلا Fallback صامت.
- تشغيل أوامر FastAPI محلياً يعتمد `fastapi-app/.venv` وPython 3.12.x، وليس Python العام على النظام إذا كان خارج النطاق `>=3.12,<3.13`.
- FastAPI ليس API عاماً للمستخدم النهائي؛ المسار المعتمد هو `Browser → Laravel → FastAPI`. المصادقة الداخلية تستخدم `X-Internal-API-Key`؛ D9 يرفض Startup إذا كان `INTERNAL_API_KEY` مفقوداً/فارغاً، بينما المفتاح المفقود أو غير الصالح في Request يرفض بـ`401`، وCorrelation ID يبقى فعالاً حول طبقة المصادقة.
- أخطاء التطبيق المنظمة في FastAPI تستخدم `ApplicationException` مع Handler مركزي يعيد `error.code` و`error.message` و`correlation_id` ويحافظ على Structured Logging؛ أي أخطاء Application جديدة لاحقاً تبنى فوق هذا العقد بدلاً من إنشاء JSON أخطاء خاص بكل Route.
- عقد D8 يفصل بين `supported_profiles` كقدرة مسموحة معمارياً و`available_profiles` كجاهزية مؤكدة؛ `compare_available` مشتقة ولا يمثل Profile ثالثة، وProvider readiness لا يفترض قبل التحقق الفعلي.
- عقد D9 يفرض Configuration صريحة ومتوافقة: `cloud` يمنع `LOCAL_AI_TOPOLOGY`، و`local` يتطلب `LOCAL_AI_TOPOLOGY=host_native`؛ لا يتضمن ذلك أي Runtime/provider/device probe.

---

# 24. سجل الإنجاز المختصر

> **القاعدة الجديدة:** سطر واحد لكل Task منجزة. التفاصيل الكاملة محفوظة في Git commits وPull Requests.

| Task | PR | ملخص الإنجاز |
|---|---:|---|
| A1 — Laravel Application | #2 | Laravel 13 + Livewire/Flux + Vite؛ tests/build PASS |
| A2 — Authentication | #3 | Fortify + auth/email verification/settings RTL؛ tests/build PASS |
| A3 — MySQL | #5 | MySQL 8.4 + persistent volume + migrations/tests PASS |
| A4 — Redis | #7 | Redis 8.10 AOF + health/persistence؛ tests PASS |
| A5 — Queue | #9 | Redis default queue + worker smoke test؛ tests PASS |
| B1 — documents migration | #10 | Documents schema + ownership/indexes |
| B2 — Document model | #11 | Document/User relations + mass-assignment boundaries |
| B3 — Enums/casts | #12 | `FileType` + `DocumentStatus` |
| B4 — DocumentPolicy | #13 | Ownership policy + minimal tests |
| B5 — Documents pages | #14 | index/show Blade + authorization |
| B6 — Upload validation | #15 | PDF/DOCX/TXT validation + MIME/content/name/size/ZIP safeguards |
| B7 — Private storage/download | #16 | private `documents` disk + ULID names + authorized download |
| B8 — SHA-256/duplicate | #18 | Server-side SHA-256 + per-user duplicate policy |
| B9 — Processing runs schema | #19 | `document_processing_runs` migration |
| B10 — ProcessingRun domain | #21 | Model/enums/casts/relations |
| B11 — Selected processing run | #23 | nullable FK + relation؛ cross-table invariants deferred to Domain logic |
| B12 — Processing comparisons | #24 | comparison schema/model/status/relations |
| C1 — ClamAV runtime | #25 | on-demand scan worker + persistent signatures + shared heavy-resource lock |
| C2 — DocumentSecurityService | #26 | clean/infected/scan_failed contract + fail-closed + sanitized logging |
| C3 — Temporary upload flow | #28 | quarantine + `DocumentUploadService`; 9 tests / 60 assertions; Pint/diff-check PASS |
| C4 — Clean path | #30 | ترقية آمنة من quarantine إلى `documents` مع clean-only gate والحفاظ على نفس `Document`؛ 2 tests / 8 assertions؛ Pint/diff-check PASS |
| C5 — Infected/fail-closed path | #32 | `infected` → `infected` و`scan_failed` → `failed` مع إبقاء الملف في quarantine ومنع promotion؛ 2 tests / 8 assertions؛ Pint/diff-check PASS |
| C6 — Configurable security-scan routing | #33 | enabled افتراضياً → quarantine؛ disabled صراحةً → permanent storage؛ 6 tests / 25 assertions؛ Pint/diff-check PASS |
| C7 — Aggregate status transitions | #34 | Security Job orchestration + `pending → scanning → clean → pending`؛ disabled يبقى `pending`؛ 7 tests / 33 assertions؛ Pint/diff-check PASS |
| C8 — Security tests | #35 | explicit bypass + scan_failed no-fallback coverage؛ 8 tests / 39 assertions؛ Pint/diff-check PASS |
| D1 — FastAPI project | #37 | FastAPI foundation + Python 3.12 baseline + application factory؛ runtime verification PASS |
| D2 — Typed config | #39 | `pydantic-settings` + typed deployment mode + centralized app settings؛ env/validation/import/pip/diff checks PASS |
| D3 — Structured logging/correlation IDs | #40 | Central JSON logging + async-safe ContextVar correlation IDs + request/response propagation; supplied/generated ID verification + compileall/diff-check PASS |
| D4 — Internal API security | #41 | `X-Internal-API-Key` + `SecretStr` + centralized ASGI auth; missing/invalid/valid coverage؛ 3 tests + compileall/pip/diff-check PASS |
| D5 — Health endpoint | #42 | `GET /api/v1/health` + internal auth + Correlation ID preserved؛ 1 focused test + D4/D5 regression + compileall/pip/diff-check PASS |
| D6 — Versioned DTO schemas | #43 | Pydantic DTOs لعقود document processing وRAG؛ nullable `total_pages`؛ 2 focused tests + D4–D6 regression + compileall/pip/diff-check PASS |
| D7 — Structured exceptions | #44 | `ApplicationException` + ErrorResponse + handler مركزي مع Correlation ID؛ 1 focused test + D4–D7 regression + compileall/pip PASS |
| D8 — Deployment capabilities | #46 | `GET /api/v1/capabilities` + Cloud/Local supported profiles + truthful `not_checked` readiness؛ 2 tests + diff-check PASS |
| D9 — Startup configuration validation | #47 | Startup validation مركزية لـdeployment mode وInternal API key وLocal AI topology؛ 4 focused tests + 13 FastAPI regression tests PASS |

## ملاحظات تنفيذية تاريخية تستحق الاحتفاظ

- B12 Schema تم التحقق منه فعلياً على MySQL 8.4.11 مع rollback وإعادة migration.
- البيانات الخاصة بالمعالجة بقيت في Processing Runs وليست داخل `documents`.
- 2026-08-22: أعيد تنظيم ملف التقدم نفسه ليبقى خفيفاً بين المحادثات: جداول المهام بقيت كاملة، بينما اختُصر سجل الإنجاز واعتمد Git/PRs للتاريخ التفصيلي.
- C8 أغلقت من دون تعديل production code؛ اقتصرت على سد فجوات الاختبارات الأمنية المتبقية.
- D1 أنشأت FastAPI foundation مستقلاً داخل `fastapi-app` مع Python 3.12 baseline وApplication Factory.
- D2 أضافت Typed Configuration مركزية باستخدام `pydantic-settings` وربطت metadata التطبيق بها، مع إبقاء Logging/Security/Health/AI خارج النطاق.
- D3 أضافت Structured JSON logging مركزية وCorrelation IDs عبر Pure ASGI middleware وContextVar، مع الحفاظ على Security وHealth خارج النطاق.
- D4 أضافت Internal API authentication مركزية باستخدام `X-Internal-API-Key` و`SecretStr` و`compare_digest`، مع Fail-Closed للمفتاح المفقود/غير الصالح والحفاظ على Correlation ID حول طبقة المصادقة.
- بيئة FastAPI الرسمية محلياً هي `fastapi-app/.venv` ضمن Python 3.12.x؛ D2 تم التحقق منها على `3.12.13`، وD3–D8 على `3.12.14`، وD9 على `3.12.13`. Python العام من Miniconda `3.13.13` خارج قيد المشروع ولا يستخدم.
- D7 أضافت عقد Structured Exceptions مركزي مع `ApplicationException` و`ErrorResponse` وCorrelation ID، دون إدخال D8 أو AI/Qdrant/Parsing/Providers.
- D8 أضافت Capabilities endpoint داخلياً بعقد يفصل supported عن available ويترك readiness بحالة `not_checked` حتى الفحص الفعلي، من دون Provider calls.
- D9 أضافت Startup validation مركزية عبر FastAPI lifespan، مع إلزام deployment mode وInternal API key ورفض Cloud/Local topology المتعارضة، دون Runtime/provider/device probes.
- لا توجد عوائق حالية.

---

# 25. المهمة الحالية

```text
D10 — Base/cloud/local dependency split
Status: TODO
Expected Branch: task/D10-base-cloud-local-dependency-split
Next: D11 — Local runtime/device resolver + startup probe + resource telemetry
```

> تفاصيل نطاق D10 وعقد فصل Base/Cloud/Local dependencies تؤخذ من `PROJECT_RAG_MASTER_PLAN.md`، مع الحفاظ على أن Cloud deployment لا يحمل Local AI packages أو weights، ودون إدخال D11 runtime/device probing.

---

# 26. العوائق والملاحظات

- لا توجد عوائق حالية.
- توجد ملاحظة تنسيق Pint قديمة في `laravel-app/bootstrap/providers.php` خارج نطاق المهام الحالية؛ غير حاجبة.
- عند تنفيذ مهام FastAPI استخدم `fastapi-app/.venv` الرسمية مع Python 3.12.x؛ البيئة الحالية Python `3.12.13`، ولا تستخدم Miniconda Python `3.13.13`.

---

# 27. قالب إغلاق أي Task

من الآن فصاعداً لا نضيف سجلاً طويلاً. يكفي:

```markdown
| [Task ID] — [Task Name] | #[PR] | [3–12 كلمة تلخص المنجز]؛ tests/Pint PASS |
```

ثم:

1. تغيير حالة المهمة في جدول مرحلتها إلى `DONE`.
2. تحديث `CURRENT HANDOFF`.
3. استبدال قسم `# 25. المهمة الحالية` بالمهمة التالية.
4. تسجيل أي Invariant أو قرار تنفيذي جديد فقط إذا كان سيؤثر على المهام اللاحقة.

---

# 28. بدء Chat جديد

ارفع آخر نسخة من هذا الملف واكتب:

```text
نكمل مشروع RAG حسب ملف التقدم المرفق. نفذ المهمة الحالية فقط، وبعد نجاحها حدّث ملف التقدم باختصار وحدد المهمة التالية.
```

ارفع `PROJECT_RAG_MASTER_PLAN.md` أيضاً عندما تحتاج المهمة تفاصيل معمارية أو عقوداً غير موجودة في هذا الـhandoff.
