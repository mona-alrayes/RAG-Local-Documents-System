# سجل تنفيذ مشروع RAG

> **المرجع المعماري:** `PROJECT_RAG_MASTER_PLAN.md`
> **الغرض:** حفظ الحالة التنفيذية الفعلية ونقطة الاستلام بين المحادثات، دون تكرار التفاصيل الموجودة في الـMaster Plan أو Git/PRs.
> **آخر تحديث:** 2026-08-22
> **الحالة العامة:** قيد التنفيذ

---

# CURRENT HANDOFF — نقطة الاستلام

> **هذا هو أول قسم يُقرأ في أي Chat جديد.**

```text
Project Mode: Start From Scratch
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Active Development

Verified Main Commit: f0ee07a452f981f296bee1db8d0af0e7aa052613
Last Merged PR: #28 — feat(C3): add temporary document quarantine flow
Latest Task PR: #28 — feat(C3): add temporary document quarantine flow

C3 Implementation Commit: f4ec89e
Last Completed Task: C3 — Temporary upload flow
Current Task: C4 — Clean path
Current Task Status: TODO
Expected Task Branch: task/C4-clean-path
Next Task After Completion: C5 — Infected/fail-closed path

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
| C4 Clean path | TODO |
| C5 Infected/fail-closed path | TODO |
| C6 Aggregate status transitions | TODO |
| C7 Security tests | TODO |

**معيار انتهاء المرحلة:** لا يصل ملف غير نظيف إلى AI Queue أو FastAPI، ويعمل الفحص وتحديث التواقيع على Queue `security-scan` متسلسلة كعمليات قصيرة العمر تنتهي وتحرر RAM، من دون `clamd` دائم أو Docker socket داخل التطبيق. في Local Demo يشترك Scan/Signature Update و`ai-local` في قفل Redis عالمي يمنع تداخلهما مع أي Model Stage حتى عبر وثيقتين مختلفتين؛ يعطّل القفل في Oracle Cloud-only.

---

# 7. المرحلة D — FastAPI Foundation and Capabilities

| المهمة | الحالة |
|---|---|
| D1 FastAPI project | TODO |
| D2 Typed config | TODO |
| D3 Structured logging/correlation IDs | TODO |
| D4 Internal API security | TODO |
| D5 Health endpoint | TODO |
| D6 Versioned DTO schemas | TODO |
| D7 Structured exceptions | TODO |
| D8 Deployment capabilities endpoint | TODO |
| D9 Startup configuration validation | TODO |
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
DocumentSecurityService
DocumentSecurityScanStatus
LocalHeavyResourceLock
```

## 22.3 مسار الرفع بعد C3

```text
Upload
  ↓
Validation
  ↓
DocumentController
  ↓
DocumentUploadService
  ↓
DocumentStorageService::storeQuarantined()
  ↓
Private Quarantine
```

الحالة المعتمدة:

- الملف الجديد يخزن في `document_quarantine` وليس `documents`.
- SHA-256 وسياسة duplicate تبقيان داخل `DocumentStorageService`.
- حالة الوثيقة تبدأ `pending`.
- التنزيل الرسمي يقرأ من `documents` فقط، لذلك الملف المعزول غير متاح كوثيقة موثوقة.
- لا يصل الملف إلى FastAPI أو Qdrant أو AI Pipeline قبل نجاح Security Pipeline.

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

# 23. Baseline معماري تنفيذي

> التفاصيل الكاملة موجودة في `PROJECT_RAG_MASTER_PLAN.md`. هذه القائمة فقط لمنع فقدان القرارات التي تؤثر مباشرة على التنفيذ القادم.

- Processing Profiles الفعلية: `cloud` و`hybrid_local`؛ أما `compare` فهو Orchestration وليس Profile ثالثة.
- Oracle Online = Cloud-only بلا Local AI weights/dependencies.
- Local Demo = Docker للبنية الأساسية وFastAPI/Ollama على Host.
- Local heavy work = concurrency `1` + global Redis lock + single-active-model + release-after-stage.
- ClamAV = on-demand + fail-closed، بلا `clamd` دائم وبلا Docker socket.
- Qdrant = Collection منفصلة لكل Processing Profile مع mandatory user/document/run filters.
- Persistent Qdrant يحتفظ بالـselected winner فقط بعد التحقق.
- Laravel/MySQL هو مصدر الحقيقة للتطبيق؛ لا توجد DB علائقية مستقلة لـFastAPI في v1.
- المحادثة تستخدم آخر تبادلين مكتملين فقط لفهم الإحالات؛ لا توجد ذاكرة مستخرجة في v1.
- لا يوجد True Streaming/NDJSON/Redis Stream في v1؛ `جاري التفكير` وProgressive Reveal تأثيران Frontend-only.
- LLM Provider يحدد من Processing Profile موثوقة/Capabilities عبر Registry، بلا global `LLM_PROVIDER` switch وبلا Fallback صامت.

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

## ملاحظات تنفيذية تاريخية تستحق الاحتفاظ

- B12 Schema تم التحقق منه فعلياً على MySQL 8.4.11 مع rollback وإعادة migration.
- البيانات الخاصة بالمعالجة بقيت في Processing Runs وليست داخل `documents`.
- 2026-08-22: أعيد تنظيم ملف التقدم نفسه ليبقى خفيفاً بين المحادثات: جداول المهام بقيت كاملة، بينما اختُصر سجل الإنجاز واعتمد Git/PRs للتاريخ التفصيلي.
- لا توجد عوائق حالية.

---

# 25. المهمة الحالية

```text
C4 — Clean path
Status: TODO
Expected Branch: task/C4-clean-path
Next: C5 — Infected/fail-closed path
```

## الهدف

تنفيذ مسار الوثيقة التي يعيد `DocumentSecurityService` نتيجتها `clean`، بحيث تنتقل بأمان من الـPrivate Quarantine إلى التخزين الخاص الدائم، ولا تصبح جاهزة للمراحل اللاحقة قبل اكتمال النقل بنجاح.

```text
Private Quarantine
  ↓
DocumentSecurityService
  ↓
clean
  ↓
Permanent Private Storage (`documents`)
  ↓
Ready for subsequent processing
```

## ملاحظة البدء

ابدأ من المكونات الموجودة ولا تكرر مسؤولياتها:

- `DocumentSecurityService`
- `DocumentSecurityScanStatus`
- `DocumentUploadService`
- `DocumentStorageService`
- `DocumentStatus`
- disk `document_quarantine`
- disk `documents`
- أي Queue/Job قائم مرتبط بالـSecurity أو Processing

حدود المسؤوليات المطلوبة:

```text
Controller              → HTTP فقط
DocumentUploadService   → Upload/Security orchestration
DocumentSecurityService → Scan contract
DocumentStorageService  → Storage/SHA-256/Duplicate/Cleanup primitives
```

## نطاق C4

- التعامل مع نتيجة `clean` فقط.
- نقل الملف من `document_quarantine` إلى `documents` بطريقة آمنة.
- الحفاظ على `Document` metadata و`file_path` متسقين.
- حذف النسخة المؤقتة بعد نجاح النقل.
- عدم اعتبار الملف جاهزاً للمراحل التالية قبل اكتمال النقل.
- ربط النجاح بالمرحلة التالية فقط بالقدر المحدد في الـMaster Plan.

## خارج النطاق

- `infected` / `scan_failed` / fail-closed orchestration الكامل → C5.
- Aggregate status transitions الكامل → C6.
- Security test matrix الكامل → C7.
- FastAPI / Parsing / Embeddings / Qdrant / RAG implementation.

## الاختبارات المطلوبة

اختبارات مركزة فقط:

- نتيجة `clean` تنقل الملف من quarantine إلى permanent storage.
- لا تبقى نسخة quarantine بعد نجاح النقل.
- بيانات `Document` تبقى متسقة.
- لا يحدث promotion قبل نجاح الفحص والنقل.

## Definition of Done

- Clean path يعمل ضمن حدود Laravel/Security الحالية.
- النقل آمن ولا يترك artifact مؤقتاً غير ضروري.
- لا يتكرر منطق SHA-256/duplicate/storage.
- لا يدخل منطق C5 أو AI ضمن C4.
- Pint و`git diff --check` والاختبارات المركزة ناجحة.
- يتغير C4 في الجدول إلى `DONE` ويضاف سطر مختصر لسجل الإنجاز.
- يحدّث `CURRENT HANDOFF` إلى C5.

---

# 26. العوائق والملاحظات

- لا توجد عوائق حالية.
- توجد ملاحظة تنسيق Pint قديمة في `laravel-app/bootstrap/providers.php` خارج نطاق المهام الحالية؛ غير حاجبة.

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
