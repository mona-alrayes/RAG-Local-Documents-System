# سجل تنفيذ مشروع RAG

> **المرجع:** `PROJECT_RAG_MASTER_PLAN.md`  
> **الغرض:** توثيق التنفيذ الفعلي خطوة بخطوة دون تعديل الخطة المعمارية الأصلية.  
> **آخر تحديث:** 2026-08-19
> **الحالة العامة:** قيد التنفيذ

---

# CURRENT HANDOFF — نقطة الاستلام للمحادثة الجديدة

> **هذا القسم هو أول شيء يجب قراءته في أي Chat جديد.**
> يكفي رفع آخر نسخة من هذا الملف وطلب: **«نكمل مشروع RAG حسب ملف التقدم المرفق»**.

```text
Project Mode: Start From Scratch
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Active Development
Verified Main Commit: 5fec8ddb5794fe985bc84ed4acb9f1892f518036
Last Merged PR: #19 — feat(B9): add document processing runs schema
Latest Task PR: #19 — feat(B9): add document processing runs schema
Last Completed Task: B9 — document_processing_runs migration
Current Task: B10 — ProcessingRun model/enums/relations
Current Task Status: TODO
Expected Task Branch: task/B10-processing-run-model
Next Task After Completion: B11 — selected_processing_run_id migration/invariants
Open Blockers: لا يوجد
Required Context: هذا الملف + القسم 174 من الخطة الرئيسية + الـNotebookين المرجعيين عند مهام AI
```

## قاعدة الانتقال بين المحادثات

- كل **Task واحدة** تنفذ في **Chat مستقل**.
- لا نبدأ Task ثانية داخل نفس المحادثة بعد إنهاء الحالية؛ يتم أولاً تحديث هذا الملف ثم فتح Chat جديد.
- آخر نسخة من هذا الملف هي **Single Source of Truth للحالة التنفيذية**.
- لا نعتمد على ذاكرة المحادثات السابقة لتحديد أين وصل المشروع.
- الخطة الرئيسية تحدد **ماذا سنبني**، وهذا الملف يحدد **أين وصلنا فعلياً**.

---

# 1. منهجية العمل

سيتم تنفيذ المشروع وفق القواعد التالية:

1. المشروع يبدأ **من الصفر**؛ لا توجد مرحلة Baseline Audit لكود سابق.
2. نعمل على **Task واحدة محددة في كل Chat**.
3. في بداية كل Chat نقرأ قسم `CURRENT HANDOFF` من آخر نسخة لهذا الملف.
4. لكل Task نحدد قبل التنفيذ:
   - الهدف.
   - سبب وجودها في المعمارية.
   - الملفات أو المكونات المتأثرة.
   - خطوات التنفيذ.
   - الاختبارات المطلوبة.
   - معيار القبول / Definition of Done.
5. لا تعتبر المهمة منجزة لمجرد كتابة الكود.
6. لا يتم وضع الحالة `DONE` إلا بعد نجاح التحقق والاختبارات المناسبة.
7. عند انتهاء المهمة يتم تحديث هذا الملف **في نفس Chat** قبل الانتقال للمحادثة التالية.
8. يسجل لكل مهمة منجزة:
   - ما تم تنفيذه.
   - الملفات التي أُنشئت أو عُدلت.
   - الأوامر المهمة التي تم تشغيلها.
   - الاختبارات ونتائجها.
   - القرارات المعمارية الجديدة.
   - المشاكل أو الملاحظات المتبقية.
   - اسم المهمة التالية.
9. إذا ظهر قرار معماري جديد، يسجل في قسم **القرارات المعمارية**.
10. إذا ظهر عائق، يسجل في قسم **العوائق والملاحظات**.
11. لا يتم القفز إلى مرحلة لاحقة إذا كانت تعتمد على مهمة غير منجزة.
12. الخطة الأصلية تبقى المرجع المعماري الثابت، بينما هذا الملف يمثل **الحالة التنفيذية الفعلية**.
13. عند فتح Chat جديد، لا حاجة لإعادة شرح المشروع؛ آخر نسخة من هذا الملف تكفي لتحديد نقطة المتابعة.

---

# 2. حالات المهام

| الحالة | المعنى |
|---|---|
| `TODO` | لم تبدأ بعد |
| `IN PROGRESS` | قيد التنفيذ |
| `VERIFY` | التنفيذ موجود ويحتاج تحقق/اختبارات |
| `BLOCKED` | متوقفة بسبب اعتماد أو مشكلة |
| `DONE` | منجزة ومتحقق منها |
| `N/A` | غير مطلوبة وفق قرار موثق |

---

# 3. بوابة البداية — P0 Baseline Audit

> **الحالة: `N/A`**
>
> تم إلغاء هذه البوابة لأن القرار النهائي هو **بدء المشروع من الصفر** وليس استكمال Codebase سابق.

| المهمة | الحالة | السبب |
|---|---|---|
| P0 Baseline Audit | N/A | لا يوجد مشروع سابق يحتاج إلى مراجعة قبل التنفيذ |

**الانتقال المباشر:** `A1 — إنشاء Laravel Application`.

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
| B10 ProcessingRun model/enums/relations | TODO |
| B11 selected_processing_run_id migration/invariants | TODO |
| B12 document_processing_comparisons migration/model | TODO |

**معيار انتهاء المرحلة:** Domain الوثائق وRuns والمقارنات ممثلة بوضوح، دون تنفيذ AI.

---

# 6. المرحلة C — Security Pipeline

| المهمة | الحالة |
|---|---|
| C1 ClamAV infrastructure | TODO |
| C2 DocumentSecurityService | TODO |
| C3 Temporary upload flow | TODO |
| C4 Clean path | TODO |
| C5 Infected/fail-closed path | TODO |
| C6 Aggregate status transitions | TODO |
| C7 Security tests | TODO |

**معيار انتهاء المرحلة:** لا يصل ملف غير نظيف إلى Queue أو FastAPI.

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

**معيار انتهاء المرحلة:** Laravel يرى صحة FastAPI وقدرات البيئة، وCloud image لا يحمل Local AI dependencies.

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

**معيار انتهاء المرحلة:** كل Profile ينتج Chunks وVectors وتقريراً صحيحاً دون خلط Providers.

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

**معيار انتهاء المرحلة:** Single أو Compare يكتملان بحالة متسقة بين Laravel وFastAPI وQdrant.

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

### متطلبات UX مرتبطة برفع الملفات

ضمن `J4`:

- عند محاولة رفع ملف مكرر لنفس المستخدم، لا تُنشأ وثيقة جديدة.
- تعرض الواجهة رسالة واضحة بأن الملف مرفوع مسبقًا.
- يجب أن تتضمن الرسالة `original_name` للوثيقة الأصلية، حتى يعرف المستخدم أي ملف موجود مسبقًا.
- تستخدم الواجهة `duplicate_document.id` لإتاحة الانتقال مباشرة إلى صفحة الوثيقة الأصلية.
- لا تعرض الواجهة `sha256` أو `stored_name` أو `file_path` للمستخدم.
- اختلاف اسم الملف المرفوع لا يغيّر حالة duplicate إذا كان المحتوى مطابقًا للوثيقة الأصلية.

ضمن `J9`:

- تعامل حالة duplicate upload كـUX error state واضح وقابل للتصرف، وليس كرسالة خطأ عامة.
- يجب أن تبقى رسالة duplicate مفهومة على الشاشات الصغيرة ومتوافقة مع RTL وAccessibility.

**معيار انتهاء المرحلة:** المستخدم يرفع ويتابع ويقارن ويعتمد النتيجة من واجهة RTL واضحة.

---

# 14. المرحلة K — Conversations Database

| المهمة | الحالة |
|---|---|
| K1 Conversations migration/model | TODO |
| K2 conversation_document unique pivot | TODO |
| K3 Messages + snapshots/metrics | TODO |
| K4 message_sources + run/profile | TODO |
| K5 Policies | TODO |
| K6 Create/list conversations | TODO |
| K7 Multi-document selection | TODO |
| K8 Ready/indexed/runtime-capable filtering | TODO |

**معيار انتهاء المرحلة:** المحادثة تختار ملفاً أو عدة ملفات يملكها المستخدم ومفهرسة فعلياً.

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
| M1 ContextService | TODO |
| M2 Prompt/insufficient-context | TODO |
| M3 LLMProvider interface/factory | TODO |
| M4 HF `Qwen/Qwen3.5-9B` | TODO |
| M5 Ollama `qwen3.5:4b` | TODO |
| M6 No-fallback/provider validation | TODO |
| M7 Answer/sources/timings response | TODO |
| M8 Provider contract tests | TODO |

**معيار انتهاء المرحلة:** التوليد يعمل بالعقد نفسه في Cloud وLocal دون تحميل Local LLM في Online.

---

# 17. المرحلة N — Chat Experience

| المهمة | الحالة |
|---|---|
| N1 Chat layout/list | TODO |
| N2 Top document multi-selector | TODO |
| N3 Selected chips/authorization | TODO |
| N4 AskConversationJob | TODO |
| N5 Save snapshots/answer/metrics | TODO |
| N6 Sources drawer/relevance score | TODO |
| N7 Timings display | TODO |
| N8 Pending/failure/retry | TODO |
| N9 Mixed-profile E2E chat tests | TODO |

**معيار انتهاء المرحلة:** المستخدم يحادث وثيقة أو عدة وثائق ويشاهد المصادر والأزمنة ودرجة صلة كل مصدر.

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
| Q8 Performance report | TODO |
| Q9 Cloud-only lightweight image verification | TODO |
| Q10 Local Ollama profile verification | TODO |
| Q11 Backup/restore | TODO |
| Q12 Final documentation | TODO |

**معيار انتهاء المرحلة:** المساران والمقارنة والمحادثة متعددة الملفات يعملون End-to-End بأمان وبعد Restart.

---

# 21. Deployment — DPL

| المهمة | الحالة |
|---|---|
| DPL-1 Oracle Account | TODO |
| DPL-2 إنشاء VM | TODO |
| DPL-3 إعداد الشبكة | TODO |
| DPL-4 تحديث Ubuntu | TODO |
| DPL-5 تثبيت Docker | TODO |
| DPL-6 Clone المشروع | TODO |
| DPL-7 إعداد `.env` | TODO |
| DPL-8 إنشاء Volumes | TODO |
| DPL-9 تشغيل Infrastructure | TODO |
| DPL-10 تشغيل FastAPI | TODO |
| DPL-11 تشغيل Laravel | TODO |
| DPL-12 تشغيل Queue Worker | TODO |
| DPL-13 تشغيل Nginx | TODO |
| DPL-14 HTTPS | TODO |
| DPL-15 اختبار PDF | TODO |
| DPL-16 اختبار DOCX | TODO |
| DPL-17 اختبار TXT | TODO |
| DPL-18 Cloud-only capability/UI/API test | TODO |
| DPL-19 التحقق أن Online image بلا Torch/Transformers/Ollama/models | TODO |
| DPL-20 HF `Qwen/Qwen3.5-9B` test | TODO |
| DPL-21 Security test | TODO |
| DPL-22 Backup/restore test | TODO |
| DPL-23 Restart/persistence test | TODO |
| DPL-24 Local demo profile منفصل مع Ollama `qwen3.5:4b` | TODO |
| DPL-25 Compare flow على الجهاز المحلي | TODO |

**قاعدة النشر:** النسخة Online تستخدم `RAG_DEPLOYMENT_MODE=cloud` ولا تشغّل أو تنزّل أي Local embedding/reranker/LLM. الـLocal demo مسار تشغيل منفصل على جهاز صاحبة المشروع.

---

# 22. سجل الإنجاز
## 2026-08-20 — B9 document_processing_runs migration

Status: DONE

Task:
B9

Branch:
`task/B9-document-processing-runs-migration`

Pull Request:
#19 — feat(B9): add document processing runs schema

### تم التنفيذ

- إنشاء جدول `document_processing_runs` وفق القسم `174.7.2` من Master Plan.
- إضافة `document_id` كـForeign Key إلى `documents.id`.
- اعتماد `restrictOnDelete` حسب سياسة حذف الوثائق المعتمدة.
- إضافة حقول Profile وحالة الـRun وConfiguration snapshot.
- إضافة عدادات الصفحات والـchunks والـvectors وأبعاد الـvectors.
- إضافة أزمنة مراحل المعالجة والتحذيرات ومعلومات الفشل.
- إضافة تقرير المقارنة والـtemporary artifact metadata وTTL.
- إضافة معلومات Qdrant وحالات indexed/selected/discarded/expired.
- إضافة الفهارس المطلوبة:
  - `(document_id, status)`
  - `(document_id, profile, created_at)`
  - `(status, temporary_expires_at)`
- إبقاء ProcessingRun Model والـEnums والـcasts والعلاقات خارج نطاق B9.
- إبقاء `selected_processing_run_id` خارج B9 للمهمة B11.
- عدم إضافة Queue أو FastAPI أو Qdrant logic أو Processing services.

### الملفات المنشأة أو المعدلة

- `PROJECT_RAG_EXECUTION_PROGRESS.md`
- `laravel-app/database/migrations/2026_08_19_223046_create_document_processing_runs_table.php`

### التحقق

- PASS — migration `up()`.
- PASS — migration `down()` عبر rollback.
- PASS — إعادة تنفيذ migration بعد rollback.
- PASS — `php artisan migrate:status`.
- PASS — فحص Schema الفعلي عبر `php artisan db:table document_processing_runs`.
- PASS — Foreign Key مع `ON DELETE RESTRICT`.
- PASS — الفهارس الثلاثة المطلوبة موجودة.
- PASS — `git diff --check`.
- لم يتم إنشاء Test suite إضافية لأن التحقق المباشر من MySQL كافٍ لنطاق B9.

### القرارات

- يحتفظ جدول `document_processing_runs` ببيانات كل محاولة/مسار معالجة بصورة مستقلة عن `documents`.
- محتوى الـchunks الكامل لا يخزن في MySQL؛ التخزين الدائم للـselected chunks سيكون في Qdrant.
- Laravel يعرض Chunks الإدارية لاحقاً عبر FastAPI وليس باتصال مباشر مع Qdrant.
- `total_chunks` و`vector_count` يبدأان من `0`.
- تفاصيل Domain الخاصة بالـProfile والـStatus والـcasts والعلاقات تؤجل إلى B10.

Review Result:
APPROVED — Schema مطابقة للقسم 174.7.2 والتحقق المباشر على MySQL ناجح.

Open Issues:
- لا توجد عوائق تخص B9.

Next Task:
B10 — ProcessingRun model/enums/relations

---

## 2026-08-19 — B8 SHA-256 وسياسة duplicate في Application

Status: DONE

Task:
B8

Branch:
`task/B8-sha256-duplicate-policy`

Pull Request:
#18

Implementation Commit:
`86df0b5b95ce78f64f86c2e56e06fc41a8441e7c`

### تم التنفيذ

- حساب SHA-256 من محتوى الملف المخزن فعليًا على Server-side بعد التخزين الخاص.
- استخدام stream من filesystem لحساب الـhash دون الاعتماد على اسم الملف أو metadata مقدمة من المستخدم.
- حفظ SHA-256 داخل `documents.sha256` عند إنشاء الوثيقة.
- تطبيق سياسة duplicate على مستوى Application ضمن نطاق المستخدم الحالي فقط عبر `user_id + sha256`.
- اعتبار الملف Duplicate حتى عند اختلاف الاسم إذا كان المحتوى مطابقًا.
- السماح لمستخدم مختلف برفع نفس المحتوى دون كشف وجود وثائق مستخدمين آخرين.
- إضافة `DuplicateDocumentException` لفصل منطق التخزين عن طبقة HTTP.
- حذف النسخة الجديدة المخزنة فور اكتشاف duplicate مع الإبقاء على الوثيقة الأصلية.
- إعادة HTTP 422 عند رفع duplicate مع `id` و`original_name` للوثيقة الأصلية فقط.
- عدم كشف `stored_name` أو `file_path` أو SHA-256 في استجابة duplicate.
- إعادة عمود `sha256` إلى `NOT NULL` بعد انتهاء الفترة المؤقتة التي احتاجتها B7.
- الإبقاء على index الحالي `(user_id, sha256)` دون إضافة UNIQUE constraint لأن منع التكرار سياسة Application-level حسب الخطة.
- تحديث fixture واحد من اختبار B7 ليتوافق مع عودة `sha256` إلى `NOT NULL`.
- إبقاء Queue وClamAV وFastAPI وAI/RAG خارج نطاق B8.

### الملفات المنشأة أو المعدلة

- `PROJECT_RAG_EXECUTION_PROGRESS.md`
- `laravel-app/app/Exceptions/DuplicateDocumentException.php`
- `laravel-app/app/Http/Controllers/DocumentController.php`
- `laravel-app/app/Services/Documents/DocumentStorageService.php`
- `laravel-app/database/migrations/2026_08_19_182038_make_sha256_required_on_documents_table.php`
- `laravel-app/tests/Feature/Documents/DocumentPrivateStorageDownloadTest.php`
- `laravel-app/tests/Feature/Documents/DocumentSha256DuplicateTest.php`

### التحقق والاختبارات

- PASS — اختبارات B8: اختباران و14 assertions.
- PASS — اختبارات B6 وB7 المرتبطة بعد تحديث الـfixture.
- PASS — مجموعة Laravel الكاملة: 35 اختبارًا.
- PASS — migration `up()` لتثبيت `sha256 NOT NULL`.
- PASS — migration `down()` لإعادة `sha256` nullable.
- PASS — إعادة تنفيذ migration بعد rollback.
- PASS — Laravel Pint على ملفات المهمة.
- PASS — `git diff --check`.
- PASS — `git diff --cached --check`.
- PASS — التحقق أن duplicate لا ينشئ سجل `Document` إضافيًا ولا يترك ملفًا إضافيًا.

### القرارات

- SHA-256 يحسب حصريًا من المحتوى المخزن Server-side.
- نطاق duplicate هو المستخدم نفسه فقط: `user_id + sha256`.
- الاسم الأصلي لا يدخل في قرار duplicate.
- منع duplicate يبقى Application-level دون `UNIQUE(user_id, sha256)` في B8.
- النسخة الجديدة تحذف عند اكتشاف duplicate، بينما تبقى الوثيقة الأصلية دون تعديل.
- استجابة duplicate تعرض فقط معلومات آمنة تساعد المستخدم على الوصول إلى الوثيقة الأصلية.
- إبقاء معالجة race condition المتزامنة خارج نطاق B8 وعدم إضافة locking أو Redis complexity دون حاجة حالية.

Review Result:
APPROVED — التنفيذ والاختبارات والتحقق المحلي ناجحة، وPR #18 جاهز للدمج بعد تحديث سجل التنفيذ.

Open Issues:
- لا توجد عوائق تخص B8.
- تبقى ملاحظة تنسيق Pint القديمة في `laravel-app/bootstrap/providers.php` خارج نطاق المهمة.

Next Task:
B9 — document_processing_runs migration

---

## 2026-08-19 — B7 Private storage/download authorization

Status: DONE

Task:
B7

Branch:
`task/B7-private-storage-download`

Pull Request:
#16

Reviewed Commit:
`bb82d433cad7d5b2f099391ae3ccb97794d06085`

Merge Commit:
`f4adb45774133b6b081a6682a03d4b85581c1cc7`

### تم التنفيذ

- إعادة استخدام `UploadDocumentRequest` و`SecureDocumentUpload` من B6 دون تكرار منطق التحقق.
- إضافة private filesystem disk مخصص للوثائق داخل `storage/app/private/documents`.
- إضافة `DocumentStorageService` لفصل مسؤولية التخزين وإنشاء سجل الوثيقة عن الـController.
- توليد اسم تخزين Server-side باستخدام ULID دون استخدام أي جزء من الاسم الأصلي.
- استخدام الامتداد الذي اجتاز Validation في B6.
- تخزين الملفات داخل مسار خاص بالمستخدم باستخدام معرف المستخدم واسم التخزين العشوائي.
- إنشاء سجل `Document` من خلال علاقة المستخدم مع metadata الموثوقة.
- استخدام MIME والحجم المكتشفين Server-side.
- منع overwrite عبر التحقق من عدم وجود المسار المولد قبل التخزين.
- حذف الملف المخزن إذا فشل إنشاء سجل `Document`.
- إضافة `DocumentPolicy::download` للتحكم بتنزيل الوثائق حسب الملكية.
- إضافة Route خاص بتنزيل الوثيقة عبر Controller.
- تنزيل الملف كـattachment بالاسم الأصلي للمستخدم.
- إضافة `X-Content-Type-Options: nosniff` إلى استجابة التنزيل.
- التأكد من عدم تخزين الوثائق على public disk وعدم السماح لمستخدم آخر بتنزيلها.
- جعل `sha256` nullable مؤقتًا حتى تنفيذ B8، لأن حساب SHA-256 وسياسة duplicate خارج نطاق B7.
- تحديث اختبار B6 ليتوافق مع انتقال `POST /documents` من validation-only إلى persistence بعد نجاح Validation.
- استخدام fake private storage في الاختبارات لمنع إنشاء ملفات اختبار حقيقية.

### الملفات المنشأة أو المعدلة

- `PROJECT_RAG_EXECUTION_PROGRESS.md`
- `laravel-app/app/Http/Controllers/DocumentController.php`
- `laravel-app/app/Policies/DocumentPolicy.php`
- `laravel-app/app/Services/Documents/DocumentStorageService.php`
- `laravel-app/config/filesystems.php`
- `laravel-app/routes/web.php`
- `laravel-app/database/migrations/2026_08_16_230641_make_sha256_nullable_on_documents_table.php`
- `laravel-app/tests/Feature/Documents/DocumentPrivateStorageDownloadTest.php`
- `laravel-app/tests/Feature/Documents/DocumentUploadValidationTest.php`

### التحقق والاختبارات

- PASS — اختبارات B6: 4 اختبارات و33 assertions.
- PASS — اختبارات B7: 2 اختبار و22 assertions.
- PASS — مجموعة Laravel الكاملة: 33 اختبارًا و142 assertions.
- PASS — Laravel Pint.
- PASS — `git diff --check`.

### القرارات

- اعتماد disk مستقل باسم `documents` للوثائق الخاصة.
- عدم استخدام الاسم الأصلي في filesystem path نهائيًا.
- اعتماد ULID كاسم تخزين Server-generated.
- إبقاء الاسم الأصلي كـdisplay/download metadata فقط.
- فصل التخزين وإنشاء سجل الوثيقة داخل `DocumentStorageService`.
- تطبيق authorization على download عبر `DocumentPolicy`.
- جعل `sha256` nullable مؤقتًا حتى B8 دون تنفيذ hash أو duplicate policy داخل B7.
- إبقاء ClamAV وQueue وAI خارج نطاق B7.

Review Result:
APPROVED — التنفيذ والاختبارات المحلية ناجحة، بانتظار Commit وPull Request.

Open Issues:
- `sha256` nullable مؤقتًا؛ تتم معالجة SHA-256 وسياسة duplicate في B8.

Next Task:
B8 — SHA-256 وسياسة duplicate في Application

---

## 2026-08-16 — B6 Upload validation لملف واحد

Status: DONE

Task:
B6

Branch:
`task/B6-upload-validation`

Pull Request:
#15

Reviewed Commit:
`b98174e5ad1872763063b198d920024fb3ad4ed6`

Merge Commit:
`b02a94dbe8f35eb4e04cafb2c3bcaa56e7d6fdfc`

### تم التنفيذ

- إضافة Route من نوع `POST /documents` داخل مجموعة `auth` و`verified`.
- إضافة `UploadDocumentRequest` مع Authorization عبر `DocumentPolicy::create`.
- إضافة `SecureDocumentUpload` كقاعدة تحقق أمنية متخصصة.
- السماح برفع ملف واحد فقط من نوع PDF أو DOCX أو TXT.
- مطابقة الامتداد مع MIME المكتشف من المحتوى.
- تطبيق فحص بنيوي أولي لكل نوع:
  - التحقق من توقيع PDF وعلامة النهاية.
  - التحقق من بنية DOCX الأساسية كحزمة OOXML.
  - التحقق من أن TXT نص UTF-8 صالح وليس ملفًا ثنائيًا أو PDF/ZIP متنكرًا.
- رفض أسماء الملفات الخطرة، والمسارات، والامتدادات المركبة، والأسماء المخفية، ومحارف HTML والتحكم.
- دعم أسماء الملفات العربية وUnicode ضمن سياسة أحرف مسموحة.
- اعتماد حد رفع افتراضي قابل للضبط بقيمة 10 MB.
- حماية DOCX من الحزم الشاذة عبر حد 1000 entry و50 MB للحجم غير المضغوط.
- رفض المسارات الداخلية الخطرة داخل حزم DOCX.
- إعلان `ext-zip` كمتطلب منصة في Composer.
- إبقاء الاسم الأصلي كبيانات غير موثوقة وعدم استخدامه للتخزين.
- إعادة `204 No Content` عند نجاح التحقق دون تخزين الملف أو إنشاء سجل `Document`.

### الملفات المنشأة أو المعدلة

- `laravel-app/.env.example`
- `laravel-app/app/Http/Controllers/DocumentController.php`
- `laravel-app/app/Http/Requests/UploadDocumentRequest.php`
- `laravel-app/app/Rules/SecureDocumentUpload.php`
- `laravel-app/composer.json`
- `laravel-app/composer.lock`
- `laravel-app/config/documents.php`
- `laravel-app/routes/web.php`
- `laravel-app/tests/Feature/Documents/DocumentUploadValidationTest.php`

### التحقق والاختبارات

- PASS — قبول PDF وDOCX وTXT السليمة دون Persistence.
- PASS — رفض عدم تطابق الامتداد والمحتوى وDOCX غير السليم.
- PASS — رفض أسماء الملفات الخطرة.
- PASS — رفض الملف المتجاوز للحد المضبوط.
- PASS — اختبارات B6: 4 اختبارات و34 assertions.
- PASS — مجموعة Laravel الكاملة: 31 اختبارًا و121 assertions.
- PASS — ملفات المهمة اجتازت Laravel Pint.
- PASS — `composer validate --no-check-publish`.
- PASS — `git diff --check`.
- PASS — لم يُنشأ أي سجل `Document` ولم يُخزن أي ملف.

### القرارات

- اعتماد 10 MB كحد رفع افتراضي قابل للضبط عبر `DOCUMENT_UPLOAD_MAX_SIZE_KB`.
- عدم الثقة باسم الملف أو امتداده أو MIME منفردًا.
- استخدام الاسم الأصلي كـdisplay metadata فقط.
- تأجيل توليد اسم التخزين العشوائي والتخزين الخاص والتنزيل المفوض إلى B7.
- تأجيل quarantine وClamAV والفحص fail-closed إلى المرحلة C.
- اعتماد 1000 entry و50 MB غير مضغوط كحدود أولية لحزم DOCX.

Review Result:
APPROVED

Open Issues:
- يوجد تنسيق Pint قديم في `bootstrap/providers.php` خارج نطاق B6؛ غير حاجب ولم يُعدّل ضمن المهمة.

Next Task:
B7 — Private storage/download authorization

---

## 2026-08-15 — B5 Documents index/details Blade skeleton

Status: DONE

Task:
B5

Branch:
`task/B5-documents-blade-skeleton`

Pull Request:
#14

Reviewed Commit:
`b7bb41ede04a787d32452c2798d132316ddf4ffd`

### تم التنفيذ

- إنشاء `DocumentController` بمسؤوليتي `index` و`show`.
- إضافة Routes محمية بـ`auth` و`verified` لفهرس الوثائق وتفاصيل الوثيقة.
- تصفية قائمة الوثائق عبر علاقة `documents` للمستخدم الحالي.
- تطبيق `DocumentPolicy::viewAny` عند عرض القائمة.
- تطبيق `DocumentPolicy::view` عند عرض تفاصيل الوثيقة.
- إنشاء صفحتي Blade للفهرس والتفاصيل باستخدام App layout الحالي.
- إضافة Pagination وEmpty state ورابط الانتقال إلى التفاصيل.
- عرض بيانات الوثيقة الحالية دون إضافة Upload أو Processing.

### الملفات المنشأة أو المعدلة

- `laravel-app/app/Http/Controllers/DocumentController.php`
- `laravel-app/resources/views/documents/index.blade.php`
- `laravel-app/resources/views/documents/show.blade.php`
- `laravel-app/routes/web.php`
- `laravel-app/tests/Feature/Documents/DocumentPagesTest.php`

### التحقق والاختبارات

- نجحت اختبارات B5: 3 اختبارات و6 assertions.
- نجحت مجموعة Laravel الكاملة: 27 اختبارًا و87 assertions.
- نجح Laravel Pint.
- نجح `git diff --check`.
- تم التحقق من منع المستخدم من عرض تفاصيل وثيقة لا يملكها.
- لم يُضف Upload أو Processing أو Storage أو Services أو AI integration.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
B6 — Upload validation لملف واحد

---

## 2026-08-15 — B4 DocumentPolicy واختبارات ownership

Status: DONE

Task:
B4

Branch:
`task/B4-document-policy`

Pull Request:
#13

Reviewed Commit:
`62b2fb5679f5beafa4c140022e17490a063975a7`

### تم التنفيذ

- إنشاء `DocumentPolicy` وفق naming conventions القياسية في Laravel.
- السماح للمستخدم المصادق له بقدرتَي `viewAny` و`create`.
- تقييد قدرات `view` و`update` و`delete` بملكية الوثيقة عبر `document.user_id`.
- الاعتماد على Laravel كمصدر الحقيقة للـOwnership.
- استخدام Policy discovery التلقائي دون تسجيل يدوي أو تعديل `bootstrap/app.php`.
- إضافة اختبارَي ownership فقط: السماح للمالك والمنع عن المستخدم الآخر.
- التحقق من القدرات الثلاث داخل كل اختبار باستخدام loop واضح.

### الملفات المنشأة أو المعدلة

- `laravel-app/app/Policies/DocumentPolicy.php`
- `laravel-app/tests/Feature/Policies/DocumentPolicyTest.php`

### التحقق والاختبارات

- نجحت مجموعة اختبارات Laravel الحالية.
- نجح `git diff --check`.
- تم إثبات Policy discovery ضمنيًا عبر `Gate::forUser(...)` في اختبارات ownership.
- تمت مراجعة الـdiff والتأكد من اقتصاره على الملفين المقصودين.
- لم يُنشأ `DocumentFactory` أو Migration أو Route أو Controller أو UI.
- لم يتغير `Document.php` أو `User.php`.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
B5 — Documents index/details Blade skeleton

---

## 2026-08-15 — B3 FileType وDocumentStatus enums/casts

Status: DONE

Task:
B3

Branch:
`task/B3-document-enums-casts`

Pull Request:
#12

Reviewed Commit:
`b82528525455e9d1f980a51d6d76b3c8568b8ab6`

### تم التنفيذ

- إنشاء `FileType` كـstring-backed enum لأنواع الملفات المدعومة.
- إنشاء `DocumentStatus` كـstring-backed enum للحالات التجميعية المعتمدة.
- ربط `file_type` و`status` بالـEnums المناسبة عبر `Document::casts()`.
- الإبقاء على أعمدة قاعدة البيانات من نوع string.
- الحفاظ على استبعاد `user_id` و`status` من Mass Assignment.
- عدم إضافة Transition Logic أو UI helpers أو Business Logic.

### الملفات المنشأة أو المعدلة

- `laravel-app/app/Enums/FileType.php`
- `laravel-app/app/Enums/DocumentStatus.php`
- `laravel-app/app/Models/Document.php`

### التحقق والاختبارات

- نجحت اختبارات Laravel الحالية.
- نجح `git diff --cached --check`.
- تم التحقق من حفظ الملفات بترميز UTF-8 دون BOM.
- تمت مراجعة الـdiff والتأكد من اقتصاره على الملفات الثلاثة المقصودة.
- لم تُضف Tests جديدة لأن التنفيذ يعتمد على سلوك PHP Enum وEloquent casts القياسي.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
B4 — DocumentPolicy واختبارات ownership

---

## 2026-08-15 — B2 Document model والعلاقات الأساسية

Status: DONE

Task:
B2

Branch:
`task/B2-document-model`

Pull Request:
#11

Reviewed Commit:
`17656baf066e6cc5c9c6c80d4dc9f492e021702a`

### تم التنفيذ

- إنشاء `Document` model لتمثيل الوثائق المملوكة للمستخدمين.
- إضافة علاقة `Document::user()` من نوع `BelongsTo`.
- إضافة علاقة `User::documents()` من نوع `HasMany`.
- إضافة Return Types صريحة للعلاقات.
- اعتماد Mass Assignment allowlist لبيانات الوثيقة.
- استبعاد `user_id` و`status` من Mass Assignment.
- إضافة توثيق مختصر بالإنجليزية والعربية.

### الملفات المنشأة أو المعدلة

- `laravel-app/app/Models/Document.php`
- `laravel-app/app/Models/User.php`

### التحقق والاختبارات

- نجحت اختبارات Laravel الحالية.
- نجح `git diff --cached --check`.
- تمت مراجعة الـdiff والتأكد من اقتصاره على الملفين المقصودين.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
B3 — FileType وDocumentStatus enums/casts

---

## 2026-08-15 — B1 إنشاء documents migration

Status: DONE

Task:
B1

Branch:
`task/B1-documents-migration`

Pull Request:
#10

Reviewed Commit:
`4ffb6bd407fc3ebdca95c83f7bed0bdd93f2a5ef`

### تم التنفيذ

- إنشاء migration لجدول `documents` وفق القسم `174.7.1` من الخطة الرئيسية.
- إضافة ملكية الوثيقة عبر `user_id` مع `ON DELETE RESTRICT`.
- إضافة بيانات الملف الخاص: الاسم الأصلي، الاسم المخزن، العنوان، المسار، النوع، MIME، الحجم وSHA-256.
- إضافة الحالة التجميعية للوثيقة بقيمة افتراضية `pending`.
- إضافة فهارس مركبة للحالة وSHA-256 وتاريخ الإنشاء ضمن نطاق المستخدم.
- إبقاء Processing Runs وQdrant وAI metrics خارج نطاق B1.

### الملفات المنشأة أو المعدلة

- `laravel-app/database/migrations/2026_08_15_172815_create_documents_table.php`

### التحقق والاختبارات

- نجح فحص PHP syntax.
- تمت مراجعة SQL الناتجة بواسطة `migrate --pretend`.
- نجح تنفيذ migration.
- نجح Rollback.
- نجحت إعادة تنفيذ migration بعد Rollback.
- نجحت اختبارات Laravel.
- نجح `git diff --cached --check`.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
B2 — Document model والعلاقات الأساسية

---

## 2026-08-15 — A5 إعداد Queue

Status: DONE

Task:
A5

Branch:
`task/A5-queue-setup`

Pull Request:
#9

Reviewed Commit:
`d068820a557322b5af112c74bbcf6ca6861e87ed`

### تم التنفيذ

- اعتماد Redis كاتصال Laravel Queue الافتراضي.
- تحديث `QUEUE_CONNECTION` من `database` إلى `redis` في ملف البيئة النموذجي.
- التحقق من اتصال Laravel بـRedis ومن سلامة Queue الافتراضية.
- إرسال Job اختبارية إلى Redis وتشغيلها بنجاح بواسطة Queue Worker.
- حذف Job الاختبارية بعد اكتمال التحقق، دون ترك كود مؤقت في المشروع.

### الملفات المنشأة أو المعدلة

- `laravel-app/.env.example`

### التحقق والاختبارات

- أعاد Redis الأمر `PONG` وكانت الحاوية في حالة `healthy`.
- أظهر `queue:monitor` أن اتصال `[redis] default` سليم.
- دخلت Job الاختبارية إلى Redis وأصبح عدد المهام المعلقة `1`.
- نفّذ Worker الـJob بنجاح، ثم عاد عدد المهام المعلقة إلى `0`.
- أكد السجل تنفيذ Job باستخدام الاتصال `redis`.
- لم توجد Jobs فاشلة.
- نجحت اختبارات Laravel: `22 passed (75 assertions)`.
- نجح `git diff --check`.
- لا يوجد CI مهيأ للـCommit على GitHub.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
B1 — إنشاء documents migration

---

## 2026-08-15 — A4 إعداد Redis

Status: DONE

Task:
A4

Branch:
`task/A4-redis-setup`

Pull Request:
#7

Reviewed Commit:
`9ba1908d37cd934e8d59defafa7afd5f7b6dbcfc`

Merge Commit:
`419444c838168da2071c6e41e3af6836b3c4670e`

### تم التنفيذ

* إضافة خدمة Redis 8.10 Alpine إلى Docker Compose.
* إضافة Health Check باستخدام `redis-cli ping`.
* تقييد المنفذ المنشور على `127.0.0.1`.
* إضافة متغير البيئة `REDIS_FORWARD_PORT`.
* إضافة Docker named volume دائم باسم `redis_data`.
* تفعيل AOF باستخدام `appendonly yes`.
* اعتماد `appendfsync everysec`.
* إبقاء Queue وCache وSessions بدون تغيير ضمن نطاق A4.

### الملفات المعدلة

* `laravel-app/.env.example`
* `laravel-app/compose.yaml`

### الاختبارات والتحقق

* PASS — `docker compose config --quiet`.
* PASS — `docker compose up -d --wait redis`.
* PASS — انتقال حاوية Redis إلى `healthy`.
* PASS — ظهور الربط `127.0.0.1:6379->6379/tcp`.
* PASS — `redis-cli ping` أعاد `PONG`.
* PASS — التحقق من أن `appendonly` يساوي `yes`.
* PASS — التحقق من أن `appendfsync` يساوي `everysec`.
* PASS — بقاء مفتاح الاختبار بعد إعادة تشغيل Redis.
* PASS — تفعيل امتداد PhpRedis في PHP.
* PASS — اتصال Laravel بـRedis وإعادة `true` من `ping()`.
* PASS — تنظيف مفتاح اختبار Persistence.
* PASS — `php artisan test`: عدد 22 اختبارًا و75 assertion.
* PASS — إعادة التحقق من الاختبارات وRedis على Merge Commit.
* PASS — التأكد من أن `.env` مستبعد بواسطة `.gitignore`.
* PASS — `git diff --check`.

Review Result:
APPROVED — تمت مراجعة PR #7 يدويًا بعد الدمج وفق GitHub Review Protocol، ولم تظهر مشاكل من مستوى BLOCKER أو MAJOR. لا توجد GitHub Actions أو مراجعات GitHub مسجلة؛ اعتمد القرار على مراجعة الـdiff وأدلة التحقق المحلي وإعادة التحقق على النسخة المدمجة.

### القرارات

* استخدام Redis 8.10 Alpine.
* استخدام AOF مع `appendfsync everysec` لتحقيق توازن بين المتانة والأداء.
* حفظ بيانات Redis في Docker named volume دائم.
* نشر منفذ Redis على Loopback فقط في التطوير المحلي.
* عدم إضافة Redis authentication محليًا بسبب تقييد المنفذ على Loopback؛ وفي Production يبقى Redis داخل الشبكة الداخلية دون منفذ عام.
* تأجيل تفعيل Redis كـQueue backend وتشغيل Queue Worker إلى A5.

Open Issues:

* None

Next Task:
A5 — إعداد Queue

---

## 2026-08-15 — A3 إعداد MySQL

Status: DONE

Task:
A3

Branch:
`task/A3-mysql-setup`

Pull Request:
#5

Reviewed Commit:
`d5b07049f63a71953a359e435fedb6e344903bb2`

Merge Commit:
`e14a70cbae750e4af63df0179d78521418021547`

### تم التنفيذ

- تحويل اتصال Laravel الافتراضي من SQLite إلى MySQL.
- إضافة إعدادات MySQL الآمنة إلى `.env.example`.
- إضافة خدمة MySQL 8.4 في `laravel-app/compose.yaml`.
- إضافة Health Check لخدمة MySQL.
- إضافة Volume دائم باسم `mysql_data`.
- اعتماد `utf8mb4` و`utf8mb4_unicode_ci`.
- تقييد المنفذ المنشور على `127.0.0.1`.
- التأكد من عدم تتبع ملف `.env` أو كلمات المرور المحلية.

### الملفات المعدلة

- `laravel-app/.env.example`
- `laravel-app/config/database.php`
- `laravel-app/compose.yaml`

### الاختبارات والتحقق

- PASS — `docker compose config --quiet`.
- PASS — تشغيل MySQL وانتقال الحاوية إلى `healthy`.
- PASS — ظهور الربط `127.0.0.1:3306->3306/tcp`.
- PASS — نجاح `Test-NetConnection` إلى المنفذ 3306.
- PASS — `php artisan config:clear`.
- PASS — `php artisan migrate`.
- PASS — `php artisan migrate:status`.
- PASS — التحقق من استخدام Driver بقيمة `mysql`.
- PASS — التحقق من قاعدة `rag_local_documents`.
- PASS — التحقق من `utf8mb4` و`utf8mb4_unicode_ci`.
- PASS — `php artisan test`.
- PASS — اختبار الموقع يدويًا.
- PASS — `git diff --check`.

Review Result:
تم دمج PR #5؛ تم تجاوز المراجعة الرسمية النهائية بقرار مالكة المشروع بعد نجاح الاختبارات والتحقق المحلي.

### القرارات

- استخدام MySQL 8.4 لخدمة قاعدة البيانات المحلية.
- حفظ البيانات في Docker named volume دائم.
- نشر منفذ MySQL على Loopback فقط.
- استخدام شبكة Compose الافتراضية في بيئة التطوير، لأن الشبكة `internal` منعت Docker Desktop من نشر المنفذ إلى Windows host.

Open Issues:
- None

Next Task:
A4 — إعداد Redis

---

## 2026-08-13 — A2 إعداد Authentication

Status: DONE

Task:
A2

Branch:
`task/A2-authentication`

Pull Request:
#3

Merged Commit:
`62d96a1`

### تم التنفيذ

- تثبيت وإعداد Laravel Fortify.
- إضافة إنشاء الحساب وتسجيل الدخول والخروج.
- إضافة استعادة كلمة المرور وإعادة تعيينها.
- تفعيل التحقق من البريد الإلكتروني.
- حماية مساحة العمل وإعدادات الحساب بواسطة `auth` و`verified`.
- إضافة تحديث الاسم والبريد الإلكتروني وكلمة المرور.
- إعادة طلب التحقق عند تغيير البريد الإلكتروني.
- إنشاء واجهات عربية RTL للمصادقة والصفحة الرئيسية ومساحة العمل وإعدادات الحساب.
- إضافة Rate Limiting لمحاولات تسجيل الدخول.
- إضافة اختبارات Feature لمسارات المصادقة والحماية وإعدادات الحساب.

### الملفات المنشأة أو المعدلة

- `laravel-app/app/Actions/Fortify/`
- `laravel-app/app/Models/User.php`
- `laravel-app/app/Providers/FortifyServiceProvider.php`
- `laravel-app/config/fortify.php`
- `laravel-app/database/migrations/`
- `laravel-app/resources/views/auth/`
- `laravel-app/resources/views/components/`
- `laravel-app/resources/views/settings/`
- `laravel-app/resources/views/workspace/`
- `laravel-app/resources/views/welcome.blade.php`
- `laravel-app/resources/css/app.css`
- `laravel-app/routes/web.php`
- `laravel-app/tests/Feature/Authentication/`
- `laravel-app/composer.json`
- `laravel-app/composer.lock`
- `laravel-app/.env.example`

### الأوامر المهمة

- `php artisan migrate`
- `php artisan migrate:status`
- `php artisan route:list -vv`
- `php artisan test`
- `npm run build`
- `git diff --check`

### الاختبارات والتحقق

- PASS — إنشاء الحساب.
- PASS — تسجيل الدخول ببيانات صحيحة.
- PASS — رفض بيانات الدخول الخاطئة.
- PASS — تسجيل الخروج.
- PASS — التحقق من البريد الإلكتروني.
- PASS — رفض رابط تحقق غير موقّع.
- PASS — استعادة كلمة المرور وإعادة تعيينها.
- PASS — حماية مساحة العمل وإعدادات الحساب.
- PASS — تحديث بيانات الحساب وكلمة المرور.
- PASS — كامل PHPUnit test suite.
- PASS — Vite production build.
- PASS — الاختبار اليدوي لجميع تدفقات المصادقة.

Review Result:
تم دمج PR #3؛ تم تجاوز المراجعة الرسمية بقرار مالكة المشروع.

### القرارات

- استخدام Fortify كطبقة Backend للمصادقة مع واجهات Blade مخصصة.
- اشتراط تسجيل الدخول والتحقق من البريد للوصول إلى مساحة العمل.
- إبقاء المصادقة الثنائية وPasskeys غير مفعّلتين ضمن نطاق A2.
- الاحتفاظ بالـmigrations التي أنشأها Fortify لهذه الميزات.

Open Issues:
- None

Next Task:
A3 — إعداد MySQL

---

## 2026-08-12 — إنشاء سجل التنفيذ واعتماد البدء من الصفر

- تم اعتماد ملف الخطة الرئيسية كمرجع معماري ثابت.
- تم اعتماد هذا الملف كسجل الحالة التنفيذية ومرجع الـhandoff بين المحادثات.
- تم اعتماد سياسة: **Task واحدة = Chat مستقل**.
- تم إلغاء `P0 Baseline Audit` لأن المشروع سيبدأ من الصفر.
- لم يتم وضع أي مهمة تقنية على أنها `DONE` حتى الآن.
- المهمة الحالية: **A1 — إنشاء Laravel Application**.

---

## 2026-08-12 — GitHub Repository Bootstrap

- تم إنشاء وربط المستودع الرسمي: `mona-alrayes/RAG-Local-Documents-System`.
- تم اعتماد `main` كـDefault Branch.
- تم رفع ملفات إدارة المشروع والـRAG Notebook المرجعي إلى root المستودع.
- تم اعتماد GitHub كمصدر الحقيقة للكود بعد بدء التنفيذ.
- لم يبدأ التنفيذ التقني للمهمة `A1` بعد؛ حالتها ما تزال `TODO`.
- أول Branch تنفيذي مخطط: `task/A1-laravel-foundation`.

---

## 2026-08-13 — A1 إنشاء Laravel Application

Status: DONE

Task:
A1

Branch:
`task/A1-laravel-foundation`

Pull Request:
#2

Reviewed Commit:
`ca2e1db`

### تم التنفيذ

- إنشاء Laravel 13 داخل `laravel-app/`.
- تثبيت Livewire 4 وFlux UI Free 2.
- إعداد Blade وTailwind CSS 4 وVite وJavaScript.
- إضافة ملفات Composer وnpm lock.
- إبقاء `ai-service/` مؤجلاً إلى D1.

### الملفات المنشأة أو المعدلة

- `laravel-app/`

### الأوامر المهمة

- `composer require livewire/livewire:^4.0 livewire/flux:^2.0`
- `npm install`
- `npm run build`
- `php artisan test`

### الاختبارات والتحقق

- PASS — Laravel Framework 13.25.0.
- PASS — Livewire 4.4.0.
- PASS — Flux 2.16.0.
- PASS — PHPUnit: 2 tests passed.
- PASS — Vite production build.
- PASS — npm audit: 0 vulnerabilities.
- PASS — `.env` و`vendor/` و`node_modules/` و`public/build/` مستبعدة من Git.

Review Result:
APPROVED

Open Issues:
- None

Next Task:
A2 — إعداد Authentication

---
---

# 23. القرارات المعمارية

| التاريخ | القرار | السبب |
|---|---|---|
| 2026-08-12 | إبقاء الخطة الأصلية ثابتة وإنشاء سجل تنفيذ منفصل | فصل Architecture Plan عن Execution State ومنع تشويه المرجع الأساسي |
| 2026-08-12 | بدء المشروع من الصفر وإلغاء P0 | لا يوجد Codebase سابق يحتاج إلى Baseline Audit |
| 2026-08-12 | Task واحدة لكل Chat | تقليل استهلاك نافذة السياق وتحسين التنظيم والتتبع |
| 2026-08-12 | اعتماد `CURRENT HANDOFF` في أعلى ملف التقدم | تمكين أي محادثة جديدة من معرفة نقطة الاستكمال فوراً |
| 2026-08-15 | اعتماد Profiles: `cloud` و`hybrid_local` و`compare` في البيئة المحلية | تمكين مقارنة مسارين للوثيقة نفسها ثم حفظ الفائز فقط |
| 2026-08-15 | جعل Online deployment Cloud-only | إبقاء النسخة المنشورة خفيفة بلا تنزيل Embedding/Reranker/LLM محلي |
| 2026-08-15 | اعتماد `Qwen/Qwen3.5-9B` عبر Hugging Face Router للـCloud | هو اسم النموذج المثبت في المرجع التقني Cloud |
| 2026-08-15 | اعتماد Ollama `qwen3.5:4b` للتوليد المحلي | النموذج موجود محلياً لدى صاحبة المشروع ولا يدخل نسخة Online |
| 2026-08-15 | فصل Document عن Processing Runs | الوثيقة تمثل الملكية والملف والحالة التجميعية، بينما Run يمثل النماذج والنتائج والأخطاء والأزمنة |
| 2026-08-15 | عدم إضافة قاعدة بيانات علائقية مستقلة لـFastAPI في v1 | Laravel/MySQL مصدر الحقيقة، وFastAPI يستخدم temporary artifacts وQdrant فقط |
| 2026-08-15 | حفظ selected winner فقط في Persistent Qdrant | منع مضاعفة الفهرس، مع إبقاء تقارير Audit للمقارنة في MySQL |
| 2026-08-15 | Collection منفصلة لكل Embedding profile | منع خلط فضاءات Jina وBGE-M3 حتى إن تساوت الأبعاد |
| 2026-08-15 | اختيار وثيقة أو عدة وثائق لكل محادثة | البحث يقيد بـuser/document/selected-run metadata ولا يبحث في كامل Qdrant |
| 2026-08-15 | دمج نتائج Profiles المختلطة رتبياً | raw reranker scores من نماذج مختلفة غير قابلة للمقارنة المباشرة |
| 2026-08-15 | عرض Sources وtimings ودرجة صلة المصدر | `reranker_score` لا يمثل دقة الإجابة ولا يسمى Confidence |
| 2026-08-15 | عرض Chunks في Filament عبر FastAPI read-only | الحفاظ على Separation of Concerns ومنع وصول Laravel المباشر إلى Qdrant |
| 2026-08-16 | اعتماد حد رفع افتراضي 10 MB قابل للضبط | منع استنزاف الموارد مع إبقاء السياسة قابلة للتغيير حسب بيئة التشغيل |
| 2026-08-16 | عدم الثقة باسم الملف أو امتداده أو MIME منفردًا | تطبيق دفاع متعدد الطبقات ضد التنكر والمسارات والأسماء الخطرة |
| 2026-08-16 | اعتماد 1000 entry و50 MB غير مضغوط كحدود DOCX أولية | تقليل خطر ZIP bombs واستنزاف الذاكرة والمعالج |
| 2026-08-16 | إبقاء التخزين وإعادة التسمية لـB7 وClamAV للمرحلة C | الحفاظ على فصل المسؤوليات ومنع الأمان الشكلي |
---

# 24. العوائق والملاحظات

- لا توجد عوائق حالية.
- توجد ملاحظة تنسيق Pint قديمة في `laravel-app/bootstrap/providers.php` خارج نطاق B6؛ لا تؤثر في الاختبارات أو تشغيل المهمة.
---

# 25. المهمة الحالية

```text
B10 — ProcessingRun model/enums/relations
Status: TODO
```

## الهدف

إنشاء ProcessingRun Model مع الـEnums والـcasts والعلاقات اللازمة لتمثيل سجلات document_processing_runs داخل Laravel بصورة واضحة وقابلة للصيانة، وربط كل Run بالوثيقة التابعة لها وفق المعمارية المعتمدة في القسم 174، دون إدخال منطق المعالجة الفعلي أو اختيار الـselected run في هذه المهمة.

## ملاحظة البدء

تُراجع بنية جدول document_processing_runs المنجزة في B9 والقسم 174.7.2 قبل التنفيذ. يقتصر نطاق B10 على إنشاء Model والـEnums والـcasts والعلاقات الخاصة بـProcessingRun وDocument بما يتوافق مع حالات الـRun والـProfiles المعتمدة. يبقى selected_processing_run_id وقيود اختيار الـRun الرسمية للمهمة B11، ولا يتم إدخال Queue أو FastAPI أو Qdrant أو Processing Services ضمن B10.

---

# 26. قالب إغلاق أي Task

يضاف سجل مشابه لما يلي بعد التحقق من كل مهمة:

```markdown
## YYYY-MM-DD — [Task ID] [Task Name]

Status: DONE

### تم التنفيذ
- ...

### الملفات المنشأة أو المعدلة
- `path/to/file`

### الأوامر المهمة
- `...`

### الاختبارات والتحقق
- PASS — ...

### القرارات
- ...

### مشاكل/ملاحظات متبقية
- لا يوجد / ...

### المهمة التالية
- [Next Task ID] — [Next Task Name]
```

# 27. تعليمات بدء Chat جديد

في كل محادثة جديدة:

1. ارفع **آخر نسخة** من `PROJECT_RAG_EXECUTION_PROGRESS.md`.
2. اكتب:

```text
نكمل مشروع RAG حسب ملف التقدم المرفق. نفذ المهمة الحالية فقط، وبعد نجاحها حدّث ملف التقدم وحدد المهمة التالية.
```

3. لا حاجة لرفع الخطة الرئيسية في كل مرة، إلا إذا كانت المهمة تحتاج الرجوع إلى تفاصيل معمارية أو قرارات موجودة فيها.

