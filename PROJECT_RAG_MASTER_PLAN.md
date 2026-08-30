# الخطة التنفيذية والمعمارية المعتمدة لمشروع RAG

> **المشروع:** نظام ذكي للإجابة عن الأسئلة اعتماداً على الوثائق المحلية  
> **Repository:** `mona-alrayes/RAG-Local-Documents-System`  
> **الواجهة والتطبيق الرئيسي:** Laravel  
> **خدمة الذكاء الاصطناعي:** FastAPI  
> **قاعدة البيانات العلائقية:** MySQL  
> **قاعدة البيانات المتجهية:** Qdrant Local / Self-hosted  
> **أنواع الملفات المدعومة:** PDF, DOCX, TXT  
> **الفحص الأمني للملفات:** ClamAV On-demand عند تفعيله  
> **الواجهة الأمامية:** Blade + Livewire + Flux + Tailwind CSS + JavaScript  
> **لوحة الإدارة:** Filament  
> **المعالجة الخلفية:** Laravel Queue + Redis  
> **المراجع التقنية لمساري RAG:** `cloud_first_rag_colab_fixed_interactive.ipynb` و`hybrid_cloud_parse_local_rag_simple_colab.ipynb`  
> **إستراتيجية المعالجة:** يختار المستخدم Profile واحداً موثوقاً قبل المعالجة: `cloud` أو `hybrid_local` حسب قدرات البيئة  
> **إستراتيجية LLM:** `Qwen/Qwen3.5-9B` عبر Hugging Face Router في Cloud، و`qwen3.5:4b` عبر Ollama محلياً  
> **خطة النشر المرجعية:** Oracle Cloud-only للـ`cloud`، وLocal Demo منفصل للـ`cloud` أو `hybrid_local`  
> **آخر تحديث معماري:** 2026-08-31  
> **Baseline GitHub قبل دمج ARC-1:** `main@a1f28097b398b9bb277f85990a55e489bd54d880`  
> **Baseline المعماري النشط محلياً:** ARC-1 مكتمل على `task/remove-compare-winner-flow`

> [!IMPORTANT]
> هذه النسخة **تستبدل جميع النسخ المعمارية السابقة كمرجع نشط**.
> الأقسام والقرارات التاريخية السابقة، بما فيها القسم 174 ومسار Compare/Winner،
> تبقى محفوظة في Git history عند `main@a1f28097b398b9bb277f85990a55e489bd54d880`
> لأغراض التدقيق فقط، ولا تستخدم لتوجيه أي تنفيذ جديد.
>
> القرار الأعلى والأحدث هو:
>
> **إلغاء Compare/Winner lifecycle بالكامل، والعودة إلى معالجة Profile واحد مختار مسبقاً، مع فهرسة دائمة مباشرة وآمنة في Qdrant.**

---

# 0. بروتوكول التنفيذ ومصادر الحقيقة

## 0.1 مصادر الحقيقة

```text
PROJECT_RAG_MASTER_PLAN.md
    → المرجع الأعلى للمعمارية والنطاق والقرارات

PROJECT_RAG_EXECUTION_PROGRESS.md
    → المرجع الأعلى للحالة التنفيذية ونقطة الاستلام

Git / Pull Requests
    → المرجع التاريخي التفصيلي للـdiffs والقرارات المنفذة
```

لا تعتمد أي مهمة على ذاكرة المحادثات السابقة كمصدر رسمي.

## 0.2 قاعدة تنفيذ المهام

- كل Task مستقلة تنفذ عادةً في Chat مستقل.
- لا تعتبر Task `DONE` إلا بعد:
  1. اكتمال التنفيذ.
  2. نجاح الاختبارات المناسبة.
  3. مراجعة الـdiff.
  4. تحديث ملف التقدم.
- لا نفترض نجاح أي Command أو Test بدون رؤية الناتج.
- لا نوسع Scope المهمة بلا قرار موثق.
- Clean Code وSeparation of Concerns وDependency Inversion مبادئ إلزامية.

## 0.3 آخر تغيير معماري مكتمل

تم تنفيذ المبادرة التالية محلياً على فرع واحد متكامل:

```text
task/remove-compare-winner-flow
```

والنتيجة المعتمدة هي:

```text
one trusted profile per ProcessingRun
→ direct persistent Qdrant indexing
→ exact count verification
→ active_processing_run_id
→ document ready
```

تمت إزالة كل ما يخص Upload Compare/Winner/temporary comparison artifacts من الـactive architecture والكود المحلي.

لا يفترض هذا الملف أن الفرع دُمج إلى `main` حتى يتم ذلك فعلياً عبر Git/PR.

---

# 1. القرار المعماري: إزالة Compare/Winner lifecycle

## 1.1 القرار

أُلغي نهائياً من الـBaseline:

```text
compare orchestration
two processing runs for one upload بغرض المقارنة
document processing comparison
winner selection
loser semantics
temporary comparison artifacts
artifact promotion
comparison TTL
selection expiration
winner promotion
loser cleanup
pre-selection retrieval
trial question
```

لا توجد في النسخة المستهدفة:

```text
Cloud vs Hybrid comparison screen
AwaitingSelection state
Select Winner endpoint
Winner Promotion service
Loser Cleanup service
Comparison expiration scheduler
```

## 1.2 سبب القرار

الهدف الأصلي من المقارنة كان التأكد من أن محتوى الوثيقة استخرج بصورة صحيحة، خصوصاً:

- النص العربي.
- الجداول.
- OCR.
- ترتيب الصفحات والفقرات.
- المحتوى الذي يمثل الأشكال أو الرسومات.
- جودة الـchunks الناتجة.

لكن التنفيذ الفعلي أثبت أن مساري `cloud` و`hybrid_local` يشتركان في مرحلة Parsing نفسها:

```text
Original file
   ↓
LlamaParse Cloud
   ↓
NormalizedDocument
```

ثم يبدأ الاختلاف الحقيقي لاحقاً في:

```text
Dense embeddings
Sparse representation
Reranking
Generation
```

لذلك تشغيل المسارين على الملف نفسه **لا يعطي مقارنة مستقلة لجودة الاستخراج**.

وفي المقابل، مؤشرات مثل:

```text
vector_count
vector_dimension
processing_time
warnings
```

لا تكفي للحكم أي Embedding/Retrieval path أفضل دلالياً.

تقييم جودة Embedding/Retrieval الحقيقي يبقى ضمن RAG Quality Evaluation باستخدام مجموعة أسئلة وقياسات Retrieval، وليس ضمن Upload workflow.

## 1.3 النتيجة

تتحول البنية من:

```text
Upload
→ Cloud run + Hybrid Local run
→ temporary artifacts
→ comparison
→ winner
→ promotion
→ cleanup
```

إلى:

```text
Upload
→ Security Gate
→ choose one trusted Processing Profile
→ one ProcessingRun
→ shared parsing
→ profile-specific processing
→ direct persistent Qdrant indexing
→ verification
→ ready
```

---

# 2. الهدف النهائي للمشروع

يستطيع المستخدم:

1. إنشاء حساب وتسجيل الدخول.
2. رفع PDF أو DOCX أو TXT.
3. تطبيق Validation وSecurity Policy.
4. اختيار Processing Profile متاح:
   - `cloud`
   - `hybrid_local`
5. متابعة حالة المعالجة.
6. استخدام الوثيقة بعد نجاح الفهرسة.
7. إنشاء محادثة.
8. اختيار وثيقة أو عدة وثائق.
9. طرح أسئلة بالعربية أو الإنكليزية.
10. الحصول على إجابة تعتمد على الوثائق المحددة فقط.
11. مشاهدة المصادر.
12. الاحتفاظ بسجل المحادثات.
13. إعادة معالجة الوثيقة بأمان عند الحاجة.
14. إدارة النظام من Filament.

المبدأ الأساسي:

> **Laravel هو مصدر الحقيقة للتطبيق والأعمال والملكية، وFastAPI مسؤول عن AI/RAG، وQdrant يخزن المعرفة المتجهية، وClamAV مسؤول عن فحص الملفات عند تفعيله.**

---

# 3. أوضاع التشغيل الرسمية

## 3.1 Oracle Online

```text
RAG_DEPLOYMENT_MODE=cloud
```

المتاح:

```text
cloud
```

غير المتاح:

```text
hybrid_local
```

قواعد:

- لا Local AI weights.
- لا Torch/Transformers الخاصة بالمسار المحلي في Cloud image.
- لا Ollama.
- لا BGE-M3 أو BGE Reranker محلياً.
- Laravel وFastAPI يرفضان `hybrid_local` Server-side.
- Qdrant تبقى Self-hosted داخل البنية.

## 3.2 Local Demo على Mac/ASUS

```text
RAG_DEPLOYMENT_MODE=local
LOCAL_AI_TOPOLOGY=host_native
```

المتاح حسب Capabilities الفعلية:

```text
cloud
hybrid_local
```

الـTopology:

```text
Docker:
Laravel
Queue workers
MySQL
Redis
Qdrant
Security Scan Worker

Host OS:
FastAPI
Ollama
```

العناوين المرجعية:

```text
Docker Laravel/Queue → Host FastAPI:
http://host.docker.internal:8000

Host FastAPI → Host Ollama:
http://127.0.0.1:11434/v1

Host FastAPI → Qdrant:
http://127.0.0.1:6333
```

---

# 4. Processing Profiles

يوجد Profileان فقط:

```text
cloud
hybrid_local
```

## 4.1 Provider Matrix

| المرحلة | Cloud | Hybrid Local |
|---|---|---|
| Parsing | LlamaParse Cloud | LlamaParse Cloud |
| OCR | LlamaParse ar/en | LlamaParse ar/en |
| Normalization | Shared contract | Shared contract |
| Chunking baseline | SentenceSplitter 800/80 | SentenceSplitter 800/80 |
| Dense embedding | Jina `jina-embeddings-v3` | `BAAI/bge-m3` |
| Dense dimension | 1024 | 1024 |
| Sparse | Qdrant/BM25 multilingual | Local BM25 |
| Retrieval fusion | RRF | RRF |
| Reranker | Jina multilingual reranker | `BAAI/bge-reranker-v2-m3` |
| Generation | HF Router `Qwen/Qwen3.5-9B` | Ollama `qwen3.5:4b` |

تساوي dimension لا يعني توافق فضاء Jina مع BGE؛ لذلك تبقى Collections منفصلة.

---

# 5. Document Pipeline المعتمد

```text
User Upload
   ↓
Laravel validation
   ↓
Trusted Security Routing
   ↓
┌──────────────────────────┬────────────────────────────┐
│ Scan enabled (default)   │ Scan disabled explicitly   │
│                          │                            │
│ Private quarantine       │ Permanent private storage  │
│ → security-scan queue    │                            │
│ → short-lived clamscan   │                            │
│ → Clean?                 │                            │
│   yes → promote file     │                            │
│   no  → fail closed      │                            │
└──────────────┬───────────┴──────────────┬─────────────┘
               ↓                          ↓
          Permanent Private Storage
                       ↓
           Trusted Processing Profile
             cloud | hybrid_local
                       ↓
               One ProcessingRun
                       ↓
                    FastAPI
                       ↓
                   LlamaParse
                       ↓
              Normalized Documents
                       ↓
                   Chunking
                       ↓
          Profile-specific Embedding
                       ↓
          Profile-specific Sparse data
                       ↓
             Build Qdrant Points
                       ↓
      Direct idempotent persistent upsert
                       ↓
             Verify expected count
                       ↓
             ProcessingRun = indexed
                       ↓
        Document active/current run set
                       ↓
                Document = ready
```

لا توجد أي طبقة temporary comparison artifact بين Processing وQdrant.

---

# 6. Extraction Inspection — فصل المشكلة الأصلية عن Profile Selection

قد تضاف لاحقاً Capability مستقلة باسم:

```text
Extraction Inspection
```

غرضها فحص جودة المدخل قبل تقييم RAG، مثل:

- النص المستخرج.
- عدد الصفحات.
- الصفحات الفارغة أو المشبوهة.
- Markdown الناتج للجداول.
- Chunk samples.
- warnings.
- parsing timings.
- character counts عند الحاجة.

هذه الميزة:

- ليست Compare بين Cloud وHybrid.
- لا تنشئ Runين.
- لا تنشئ Winner.
- لا تحتاج Temporary Qdrant Index.
- لا تمنع الفهرسة إلا إذا صممت لاحقاً كـvalidation gate بقرار مستقل.
- ليست ضمن v1 الحالية إلا إذا أضيفت لاحقاً بقرار مستقل.

---

# 7. توزيع المسؤوليات

## 7.1 Laravel

مسؤول عن:

- Authentication.
- Authorization.
- Policies.
- Ownership.
- Upload validation.
- MIME/extension/size validation.
- SHA-256 وduplicate policy.
- Private file storage.
- Security routing.
- Queue orchestration.
- اختيار Processing Profile من Capabilities موثوقة.
- إنشاء ProcessingRun.
- حفظ حالة ProcessingRun وDocument.
- تحديد الـactive/current indexed run للوثيقة.
- Conversations/Messages/Sources.
- UI وFilament.
- استدعاء FastAPI عبر `AiServiceClient`.

Laravel لا ينفذ:

- Parsing.
- Chunking.
- Embeddings.
- Vector search.
- Reranking.
- Prompt building.
- LLM inference.

## 7.2 FastAPI

مسؤول عن:

- Capability validation.
- Parsing.
- Normalization.
- Chunking.
- Dense embeddings.
- Sparse representation.
- بناء Qdrant Points.
- Persistent Qdrant upsert.
- Count verification كجزء من سلامة الفهرسة.
- Query embeddings.
- Retrieval + RRF.
- Reranking.
- Context building.
- Generation.
- Delete Qdrant points.
- Structured errors/timings.

FastAPI لا يدير:

- Users.
- Ownership source of truth.
- Laravel transactions.
- Document business state.
- Conversations database.

## 7.3 MySQL

يخزن:

```text
Users
Documents
ProcessingRuns
Conversations
Messages
Sources
Application state
Audit metadata
```

ولا يخزن raw vectors.

## 7.4 Qdrant

يخزن:

```text
Persistent chunks
Dense vectors
Sparse vectors/representations
Payload metadata
```

ولا يخزن Users أو Conversations كـbusiness entities.

---

# 8. Laravel Domain وSchema المعتمد

## 8.1 documents

الـbaseline الحالي لجدول `documents` هو:

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

### active_processing_run_id

هذا العمود يشير إلى الـProcessingRun الحالية التي تم فهرستها بنجاح وتمثل النسخة المستخدمة في المحادثات.

في baseline التطوير الحالي لا يوجد FK مباشر من `documents.active_processing_run_id` إلى `document_processing_runs.id` بسبب ترتيب إنشاء الجدولين. لذلك يجب أن يفرض Domain/Orchestration دائماً:

- الـRun تخص نفس Document.
- حالة الـRun = `indexed`.
- لا يتم تبديل الـactive run إلا بعد اكتمال الفهرسة والتحقق بنجاح.

## 8.2 DocumentStatus

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

## 8.3 document_processing_runs

الجدول يبقى مسؤولاً عن تاريخ المعالجة، الـprofile snapshot، failures، timings، وإثبات الفهرسة.

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

لا توجد حقول خاصة بالمقارنة أو temporary artifact lifecycle.

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

كل ProcessingRun تحمل Profile واحدة فقط.

## 8.6 سياسة migrations الحالية

أثناء ARC-1 كانت قاعدة التطوير المحلية فارغة، لذلك تم Consolidate للـbaseline migrations وبناء Schema نظيف عبر `migrate:fresh`.

من الآن فصاعداً:

- إذا أصبحت هناك بيانات يجب الحفاظ عليها أو Release baseline معتمد، تستخدم Forward Migrations.
- لا يعاد تعديل history المنشور عشوائياً بعد استقرار baseline.

---
# 9. ProcessingRun lifecycle الجديد

## 9.1 أول معالجة

```text
pending
   ↓
processing
   ↓
indexing
   ↓
indexed
```

وعند الفشل:

```text
pending/processing/indexing
   ↓
failed
```

## 9.2 Document aggregate lifecycle

مع Security Scan مفعّل:

```text
pending
→ scanning
→ pending
→ queued
→ processing
→ indexing
→ ready
```

عند infected:

```text
pending
→ scanning
→ infected
```

عند processing failure:

```text
queued/processing/indexing
→ failed
```


---

# 10. Direct Persistent Indexing

هذا العقد هو المسار الدائم المعتمد، وقد تم تأسيس primitives الفهرسة المباشرة ضمن ARC-1.

بعد استخراج الـchunks وبناء representations:

1. يشتق FastAPI Collection من `ProcessingProfile` الموثوقة عبر resolver مخصص.
2. لا يقبل اسم Collection غير موثوق من Browser.
3. يبني Points بعقد E6 مع دعم Cloud `models.Document` وHybrid Local `models.SparseVector`.
4. يستخدم deterministic Point IDs.
5. ينفذ idempotent upsert.
6. يتحقق من عدد Points لنطاق:
   - `user_id`
   - `document_id`
   - `processing_run_id`
7. يعيد `IndexingResult` يتضمن collection وpersisted vector count؛ إعلان Run `indexed` يبقى مسؤولية orchestration في Laravel/FastAPI application flow بعد نجاح هذه الخطوة.

Collection routing:

```text
cloud
→ rag_documents_cloud

hybrid_local
→ rag_documents_hybrid_local
```

الـpayload:

```text
user_id
document_id
processing_run_id
processing_profile
file_type
source
page
section
chunk_index
text
```

Named vectors:

```text
dense_vector
bm25_sparse_vector
```

---

# 11. Cloud vs Local Sparse Contract

الاختلاف الحالي يجب احترامه:

```text
Cloud sparse
→ qdrant_client.models.Document

Hybrid Local sparse
→ qdrant_client.models.SparseVector
```

لا نحول أحدهما عشوائياً إلى الآخر.

في direct indexing يجب أن:

- يبقى Profile-specific representation واضحاً.
- يعاد استخدام Point contract الحالي قدر الإمكان.
- لا تنشأ Giant abstraction تجمع أنواعاً غير متوافقة بالقوة.
- تتم validation كاملة قبل أول write عندما يكون ذلك ممكناً.
- يبقى Qdrant infrastructure بعيداً عن Laravel business state.

---

# 12. Process Document API المستهدف

لا يوجد production Process Document endpoint/orchestrator بعد ARC-1؛ سيبنى ضمن المرحلة H.

## Request

Laravel يرسل بيانات موثوقة مشتقة Server-side:

```json
{
  "user_id": 7,
  "document_id": 12,
  "processing_run_id": 81,
  "processing_profile": "cloud",
  "file_type": "pdf"
}
```

مع الملف الخاص عبر القناة الداخلية المعتمدة.

## Response contract baseline

العقد الحالي في FastAPI مبني على:

```json
{
  "document_id": 12,
  "processing_run_id": 81,
  "profile": "cloud",
  "status": "indexed",
  "total_pages": 33,
  "total_chunks": 184,
  "vector_count": 184,
  "vector_dimension": 1024
}
```

أما `qdrant_collection` و`stage_timings_ms` و`warnings` فتدخل ضمن ProcessingRun/report persistence بحسب عقد المرحلة H، ولا يلزم فرضها داخل response قبل تنفيذ orchestration.

---
# 13. Safe Reprocessing

المسار:

```text
Current active Run A = indexed
        ↓
Create Run B with chosen profile
        ↓
Process + index Run B
        ↓
Verify Run B count
        ↓
Only after full success:
switch document active run A → B
        ↓
delete/retire old Run A points safely
```

القواعد:

- لا نحذف Run A points قبل نجاح Run B.
- المحادثات الموجودة تستمر باستخدام active run القديم حتى التحويل.
- التحويل إلى Run B يتم داخل Laravel transaction المناسبة.
- أي Cleanup للـold points يأتي بعد نجاح switch.
- deterministic IDs تمنع duplication داخل الـRun نفسه.
- تغيير Profile أثناء Reprocess مسموح إذا البيئة تدعم Profile الجديدة.

---

# 14. Qdrant Schema

تبقى Collection منفصلة لكل embedding space:

```text
rag_documents_cloud
rag_documents_hybrid_local
```

Dense:

```text
name = dense_vector
size = 1024
distance = COSINE
```

Sparse:

```text
name = bm25_sparse_vector
modifier = IDF
```

Payload indexes:

```text
user_id             INTEGER
document_id         INTEGER
processing_run_id   INTEGER
processing_profile  KEYWORD
```

ممنوع:

```text
RESET_COLLECTION=True
```

في التطبيق.

---

# 15. Security وIsolation

## 15.1 Security Scan

Default:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true
```

Enabled:

```text
Validation
→ Private Quarantine
→ security-scan queue (1)
→ clamscan
→ Clean → permanent storage
→ Infected/failure/timeout → fail closed
```

Disabled صراحةً:

```text
Validation
→ permanent storage
```

لا يتحول فشل ClamAV إلى bypass تلقائي.

## 15.2 Qdrant Isolation

كل Query/Retrieval/Delete/Admin browse يطبق Server-side filters.

في Retrieval:

```text
user_id = current user
AND
document_id = authorized document
AND
processing_run_id = active run
```

Browser لا يحدد Collection أو Run موثوقة بمفرده.

## 15.3 FastAPI

- Internal API only.
- لا Browser → FastAPI مباشرة.
- Internal API key إلزامي.
- لا Secrets أو raw document content في logs افتراضياً.

---

# 16. Conversations وMixed Profiles

المستخدم يختار Documents، وليس Profile أثناء السؤال.

كل Document جاهزة تشير إلى active indexed ProcessingRun.

Laravel يبني Targets من MySQL:

```json
{
  "document_id": 12,
  "processing_run_id": 81,
  "processing_profile": "cloud",
  "qdrant_collection": "rag_documents_cloud"
}
```

إذا كانت المحادثة تحتوي Cloud وHybrid Local:

1. يجمع FastAPI targets حسب profile.
2. ينشئ Query embedding المناسب لكل مجموعة.
3. ينفذ Dense + Sparse retrieval لكل Collection.
4. يطبق RRF داخل كل Profile.
5. يطبق Reranker المناسب.
6. يدمج النتائج rank-based، وليس raw score comparison.
7. يبني Context موحداً.

---

# 17. Conversation Context

في v1 لا توجد:

```text
conversation_memory_snapshots
memory extractor
memory reducer
long-term memory
```

يسمح فقط بآخر تبادلين مكتملين:

```text
recent_completed_turns
```

لا تدخل:

- Pending messages.
- Failed messages.
- Assistant response غير مكتملة.

Retrieved chunks تبقى مصدر الحقائق الوحيد.

---

# 18. Generation

Provider routing:

```text
cloud active run
→ CloudLLMProvider
→ HF Router
→ Qwen/Qwen3.5-9B

hybrid_local active run
→ LocalLLMProvider
→ Ollama
→ qwen3.5:4b
```

لا يوجد global `LLM_PROVIDER` switch يثق بالBrowser.

لا يوجد silent fallback من Local إلى Cloud.

---

# 19. Local AI Resource Management

يبقى القرار الحالي كما هو:

```text
AI_SERVICE_WORKERS=1
LOCAL_AI_MAX_CONCURRENCY=1
LOCAL_AI_QUEUE_CONCURRENCY=1
LOCAL_MODEL_LIFECYCLE=single_active
LOCAL_RELEASE_MODEL_AFTER_STAGE=true
OLLAMA_MAX_LOADED_MODELS=1
OLLAMA_NUM_PARALLEL=1
OLLAMA_KEEP_ALIVE=0
```

التسلسل:

```text
BGE-M3 load/use/release
→ BGE Reranker load/use/release
→ Ollama generation
→ keep_alive=0
```

في Local Demo:

- `security-scan` و`ai-local` يشتركان في القفل العالمي.
- لا يتداخل `clamscan` مع Local heavy AI.
- لا يوجد silent CPU/Cloud fallback.
- لا يبدأ أكثر من Model ثقيل واحد في الوقت نفسه.

إزالة Compare لا تغير هذه القواعد؛ بل تقلل عدد حالات orchestration.

---

# 20. UI المستهدفة

## 20.1 Upload

صفحة الرفع تعرض:

```text
Cloud
Hybrid Local
```

حسب Capabilities.

لا تعرض:

```text
Compare both
```

في Oracle يظهر Cloud فقط.

## 20.2 Document Details

تعرض:

- الملف.
- الحالة.
- Processing Profile الحالية.
- active run.
- pages/chunks.
- timings.
- warnings.
- Download.
- Reprocess.
- Delete.

لا تعرض:

- winner.
- loser.
- comparison expiry.
- comparison countdown.

## 20.3 حالات الواجهة

الحالات الأساسية:

```text
Pending
Scanning
Queued
Processing
Indexing
Ready
Failed
Infected
Provider unavailable
```

لا توجد:

```text
Awaiting selection
Selection expired
```

## 20.4 Extraction Inspection المستقبلية

إن نُفذت لاحقاً تكون صفحة/لوحة مستقلة للمعاينة، لا شاشة مقارنة.

---

# 21. Filament

Resources المستهدفة:

```text
Users
Documents
DocumentProcessingRuns
Conversations
Messages
```

لا يوجد:

```text
DocumentProcessingComparisons
```

Widgets:

- Documents by status.
- Runs by profile/status.
- Failed/Infected documents.
- Processing durations.
- Qdrant indexed chunk counts.

Qdrant Chunks view:

- Read-only.
- Paginated.
- عبر FastAPI internal endpoint.
- لا اتصال Laravel مباشر بـQdrant.
- لا raw vectors.
- authorization + audit.

---

# 22. Deletion

DocumentDeletionService ينفذ ترتيباً صريحاً:

```text
Authorize
→ delete Qdrant points for all relevant runs
→ detach conversation/source relations as required
→ delete private file
→ delete processing runs in safe FK order
→ delete document
```

لا توجد temporary comparison artifacts ضمن deletion flow.

لا تعتمد على Cascade مخفي للموارد الخارجية.

---

# 23. Observability

Processing metrics المفيدة:

```text
parsing_duration
chunking_duration
embedding_duration
sparse_duration
qdrant_upsert_duration
qdrant_count_duration
total_processing_duration
warnings
vector_count
vector_dimension
```

Question metrics:

```text
query_embedding
retrieval
fusion
reranking
context_building
generation
total
```

لا تسجل:

- raw vectors.
- secrets.
- full document content.
- full prompts في Production.

---

# 24. RAG Quality Evaluation

فصل تقييم الجودة عن Upload workflow.

يمكن إعداد Golden Dataset وقياس:

```text
Recall@K
nDCG@K
retrieval relevance
reranking quality
answer faithfulness
source correctness
```

التقييم يقارن Cloud وHybrid Local أكاديمياً أو في الاختبارات، لكنه **ليس workflow للمستخدم ولا ينشئ Winner للوثيقة**.

---

# 25. Definition of Done للوثيقة

تعتبر الوثيقة Ready عندما:

- مملوكة للمستخدم.
- اجتازت Validation.
- استوفت Security policy.
- موجودة في Private Storage.
- يوجد ProcessingRun واحدة ناجحة ومعتمدة كـactive run.
- Run حالتها `indexed`.
- `total_chunks > 0`.
- Qdrant count يطابق `vector_count`.
- كل Point تحمل user/document/run/profile metadata.
- Collection متوافقة مع Profile.
- `documents.status = ready`.

لا يشترط أي Compare أو Winner.

---

# 26. Definition of Done للسؤال

- User authenticated.
- Conversation مملوكة له.
- Documents مملوكة وجاهزة.
- active runs مفهرسة.
- document targets مبنية Server-side.
- Qdrant filters صحيحة.
- Retrieval/RRF/Reranking نجحت.
- Context مبني من chunks المسترجعة.
- LLM أعاد جواباً.
- Answer + Sources + Timings محفوظة.
- Sources تعود للوثائق المخولة فقط.

---

# 27. قرارات غير قابلة للتفاوض

1. Profile واحد فقط لكل ProcessingRun.
2. المستخدم يختار Profile قبل المعالجة من Capabilities موثوقة.
3. Parsing provider والـnormalization contracts مشتركة بين Profileين، لكن كل Run تعالج Profile واحدة فقط.
4. Extraction Inspection، إن أضيفت، تكون Capability مستقلة عن اختيار Profile.
5. Online = Cloud-only.
6. Local Demo = Cloud أو Hybrid Local فقط.
7. Qdrant Collection منفصلة لكل embedding space.
8. لا Retrieval بدون user/document/run filters.
9. Laravel مصدر الحقيقة للOwnership والـactive run.
10. FastAPI لا يملك Business DB مستقلة.
11. لا raw vectors في Laravel/MySQL.
12. لا silent provider/device fallback.
13. Local heavy work = one active model/operation.
14. Security Scan enabled افتراضياً وFail-Closed عند التفعيل.
15. لا Streaming backend في v1.
16. آخر تبادلين مكتملين فقط كسياق محادثة.
17. Reprocess لا يحذف النسخة العاملة قبل نجاح البديل.
18. `active_processing_run_id` لا يتغير إلا بعد نجاح indexing/count verification.
19. Cloud sparse وHybrid Local sparse يبقيان عقدين مختلفين عند Qdrant boundary.
20. سياسة migrations تتبع حالة البيانات: baseline consolidation مسموح فقط في التطوير الفارغ قبل تثبيت release؛ بعد ذلك تستخدم Forward Migrations.

---
# 28. ARC-1 — التبسيط المعماري المكتمل

تم تنفيذ `ARC-1 — Remove Compare/Winner lifecycle` محلياً على:

```text
task/remove-compare-winner-flow
```

والنتيجة النهائية:

- تنظيف Laravel DB/domain إلى active-run model.
- اعتماد `active_processing_run_id`.
- إزالة comparison schema/domain من baseline التطوير.
- تبسيط DocumentStatus وProcessingRunStatus.
- حذف FastAPI artifact store/TTL/config/tests.
- حذف compare capability/response fields.
- حذف shared parse-result utility التي أنشئت خصيصاً لتغذية Cloud وLocal معاً في مسار المقارنة.
- الإبقاء على normalization وloader contracts كطبقات عامة.
- إضافة `QdrantDocumentIndexer` وprofile-to-collection resolver.
- دعم Cloud sparse `models.Document` وHybrid Local `models.SparseVector` عند Qdrant boundary.
- التحقق من persisted point count بعد upsert.
- عدم بناء Process Document endpoint/job/orchestrator ضمن ARC-1؛ هذه أعمال المرحلة H.

Verification المحلي النهائي:

```text
Laravel: 43 passed (181 assertions)
FastAPI: 129 passed
MySQL: migrate:fresh succeeded
```

Git history يبقى المرجع لأي تصميم سابق لم يعد جزءاً من الخطة النشطة.

---
# 29. خريطة المهام النشطة المعتمدة

هذه هي الخريطة الوحيدة التي تستخدم للتنفيذ الجديد. لا تحتوي مهاماً retired أو فراغات ترقيم ناتجة عن تصميم سابق.

## المرحلة A — Foundation

```text
A1 Laravel application
A2 Authentication
A3 MySQL
A4 Redis
A5 Queue
```

## المرحلة B — Documents Foundation

```text
B1 documents migration
B2 Document model
B3 FileType / DocumentStatus
B4 DocumentPolicy
B5 Documents pages
B6 Upload validation
B7 Private storage / download authorization
B8 SHA-256 / duplicate policy
B9 document_processing_runs migration
B10 ProcessingRun model / enums / relations
B11 active_processing_run_id baseline + active-run relation/invariants
```

## المرحلة C — Security Pipeline

```text
C1 On-demand ClamAV worker / signatures / lock contract
C2 DocumentSecurityService
C3 Temporary upload / quarantine flow
C4 Clean path
C5 Infected / fail-closed path
C6 Configurable security routing
C7 Aggregate security status transitions
C8 Security tests
```

`C3` خاص بالـsecurity quarantine قبل التخزين الدائم، وليس temporary processing storage.

## المرحلة D — FastAPI Foundation

```text
D1 FastAPI project
D2 Typed config
D3 Logging / correlation IDs
D4 Internal API security
D5 Health
D6 Versioned DTOs
D7 Structured exceptions
D8 Capabilities contract
D9 Startup validation
D10 Dependency split
D11 Local runtime / device probe / telemetry
```

## المرحلة E — Qdrant

```text
E1 Qdrant + persistent volume
E2 Cloud collection
E3 Hybrid Local collection
E4 Dense / sparse configs
E5 Payload indexes
E6 Point builder + deterministic run metadata
E7 Idempotent upsert / count / delete
E8 Cross-user leakage tests
E9 Direct document indexer + persisted-count verification
E10 Profile-to-collection resolver + Cloud/Local sparse boundary support
```

## المرحلة F — Parsing and Normalization

```text
F1 Loader interface
F2 LlamaParse provider
F3 PDF loader
F4 DOCX loader
F5 TXT loader
F6 Normalized document/page/section contract + normalization
F7 Loader and normalization tests
```

## المرحلة G — Profile Processing

```text
G1 ProcessingProfile registry
G2 Cloud chunking
G3 Cloud Jina embeddings
G4 Cloud sparse representation
G5 Hybrid Local chunking
G6 Local BGE-M3 embeddings
G7 Local BM25
G8 Batching / retries / rate limits
G9 Processing metrics / report builder
G10 Profile parity / isolation tests
G11 Single-active-model coordinator + release-after-stage
```

## المرحلة H — Processing Orchestration

```text
H1 AiServiceClient
H2 Processing DTOs and contract alignment
H3 FastAPI single-profile Process Document API / application orchestration
H4 ProcessDocumentJob + queue dispatch
H5 Processing metrics / report persistence
H6 Active-run transaction after successful indexing
H7 Safe reprocessing replacement
H8 Aggregate status projector
H9 Queue retries / timeouts / idempotency
H10 Serialized ai-local queue + global heavy-resource lock
```

معيار المرحلة:

```text
one file
→ one trusted profile
→ one ProcessingRun
→ FastAPI processing
→ E9/E10 persistent indexing
→ exact count verification
→ report persistence
→ active_processing_run_id switch
→ document ready
```

## المرحلة I — Blade Documents Experience

```text
I1 Responsive app shell / sidebar
I2 Workspace dashboard
I3 Documents list / cards / filters
I4 One-file upload + capability-aware Cloud/Hybrid Local choice
I5 Document details / processing timeline
I6 Accessibility / responsive / error states
```

## المرحلة J — Conversations Database

```text
J1 Conversations migration / model
J2 conversation_document pivot
J3 Messages + snapshots / metrics
J4 message_sources + processing run / profile provenance
J5 Conversation policies
J6 Create / list conversations
J7 Multi-document selection
J8 Ready / indexed / runtime-capable document filtering
```

## المرحلة K — Retrieval and Reranking

```text
K1 Trusted document_targets
K2 Cloud query embedding / retrieval
K3 Hybrid Local query embedding / retrieval
K4 user / document / run filters
K5 Per-profile Dense + BM25 + RRF
K6 Cloud Jina reranker
K7 Local BGE reranker
K8 Cross-profile rank fusion
K9 Metadata / source preservation
K10 Retrieval quality / security tests
```

Cross-profile يعني دمج نتائج Documents مفهرسة مسبقاً بProfiles مختلفة داخل نفس السؤال، وليس تشغيل أكثر من Profile لنفس Upload.

## المرحلة L — Generation

```text
L1 ContextService + آخر تبادلين مكتملين
L2 Prompt / insufficient-context behavior
L3 LLMProvider registry by trusted profile / capability
L4 Hugging Face Qwen3.5-9B
L5 Ollama qwen3.5:4b
L6 No-fallback / provider validation
L7 Answer / sources / timings contract
L8 Provider tests
L9 Ollama release / keep_alive=0
L10 Local lifecycle / pressure / no-leak tests
```

## المرحلة M — Chat Experience

```text
M1 Chat layout / list
M2 Top document selector
M3 Selected chips / authorization
M4 AskConversationJob
M5 Save snapshots / answer / metrics
M6 Sources drawer / relevance score
M7 Timings
M8 Pending / failure / retry + visual completed-answer reveal
M9 Mixed-profile / context / accessibility E2E
```

## المرحلة N — Filament

```text
N1 Core resources
N2 ProcessingRuns resource
N3 Dashboard widgets
N4 Failed / infected filters
N5 Safe retry actions
N6 FastAPI admin chunks endpoint / client
N7 Read-only Qdrant Chunks
N8 Admin audit logging
N9 Authorization / no-vectors tests
```

## المرحلة O — Security and Operations

```text
O1 Ownership / IDOR
O2 Qdrant leakage: user / document / run
O3 MIME / size / malware
O4 FastAPI authentication
O5 Private download / chunk authorization
O6 Secret / log redaction
O7 Deletion / reprocessing consistency
```

## المرحلة P — Final Validation

```text
P1 PDF / DOCX / TXT E2E
P2 Cloud profile E2E
P3 Hybrid Local profile E2E
P4 Multi-document / mixed-profile chat E2E
P5 Queue / restart / Qdrant persistence
P6 RAG quality / source correctness
P7 Security-scan + AI memory / performance / quality calibration
P8 Cloud-only lightweight image verification
P9 Local Ollama backend / load / release verification
P10 Backup / restore
P11 Final documentation
```

---
# 30. Deployment Tasks

```text
DPL-1 Oracle Account
DPL-2 Oracle ARM64 VM / eligibility verification
DPL-3 Network 22 / 80 / 443
DPL-4 Ubuntu / SSH hardening
DPL-5 Docker / Buildx / Compose ARM64
DPL-6 Clone
DPL-7 Cloud-only env / secrets
DPL-8 Volumes / permissions / free disk
DPL-9 MySQL / Redis / Qdrant / signatures readiness
DPL-10 FastAPI Cloud-only image / health / capabilities
DPL-11 Laravel PHP-FPM / migrations / cache
DPL-12 Queue + Security Scan workers
DPL-13 Nginx
DPL-14 HTTPS
DPL-15 PDF clean / infected / fail-closed E2E
DPL-16 DOCX / ZIP limits E2E
DPL-17 TXT E2E
DPL-18 Cloud-only capability / UI / API
DPL-19 No Local AI packages / weights online
DPL-20 HF Qwen smoke / error / budget
DPL-21 Security / ownership / private ports / profile rejection
DPL-22 Encrypted off-VM backup / restore
DPL-23 Restart / persistence / readiness
DPL-24 Mac / ASUS Local topology verification
DPL-25 Hybrid Local end-to-end processing / chat verification
```

`DPL-25` يثبت:

- اختيار Hybrid Local.
- LlamaParse + normalization.
- BGE-M3/BM25.
- Qdrant persistent indexing.
- BGE reranking.
- Ollama generation.
- `keep_alive=0`.
- عدم تداخل ClamAV مع Local AI.
- Restart/persistence حسب النطاق.

---
# 31. Oracle Deployment Baseline

Oracle يعمل:

```text
Nginx
Laravel/PHP-FPM
Queue Worker
Security Scan Worker
FastAPI Cloud-only
MySQL
Redis
Qdrant
```

External AI:

```text
LlamaParse
Jina
Hugging Face Router
```

لا يعمل:

```text
Ollama
BGE-M3
BGE Reranker
Local AI weights
Compare
```

المنافذ العامة:

```text
80
443
22 عند الحاجة وبحماية
```

غير العامة:

```text
3306
6379
6333
6334
8000
```

---

# 32. Local Demo Baseline

```text
Docker Desktop:
Laravel
MySQL
Redis
Qdrant
Queue workers
Security Scan Worker

Host:
FastAPI
Ollama
```

Profiles:

```text
cloud
hybrid_local
```

لا Compare.

---

# 33. Backup / Recovery

Backup:

```text
Original Documents
MySQL
Qdrant snapshots/config
required configuration
```

إذا ضاع Qdrant وبقي:

```text
Original Documents + MySQL
```

يمكن إعادة معالجة الوثائق وإعادة بناء vectors.

---

# 34. الاختبارات النهائية الإلزامية

## Security

- ownership/IDOR.
- private download.
- MIME/extension/size.
- ClamAV clean/infected/fail-closed.
- FastAPI internal auth.
- Qdrant filters.

## Processing

- Cloud direct processing.
- Hybrid Local direct processing.
- deterministic point IDs.
- idempotent upsert.
- count verification.
- failure before active-run switch.
- no duplicate points after retry.

## Reprocessing

- old active run remains usable until replacement succeeds.
- failed replacement does not break current document.
- successful replacement switches active run atomically.
- old points cleaned only after switch.

## Conversations

- single document.
- multiple documents.
- mixed profiles.
- source correctness.
- no cross-user leakage.

## Local resources

- single active model.
- no duplicate model load.
- release after stage.
- `keep_alive=0`.
- global lock with ClamAV.
- no silent fallback.

---

# 35. الخلاصة المعمارية

النسخة المستهدفة:

```text
Secure Upload
→ One trusted Processing Profile
→ Shared LlamaParse
→ Profile-specific embedding/sparse
→ Direct persistent Qdrant indexing
→ Verified active ProcessingRun
→ Retrieval/Reranking
→ Generation
→ Answer + Sources
```

ولا تحتوي البنية النشطة على أي workflow مزدوج لمعالجة Upload واحد أو أي طبقة promotion/storage مؤقتة بين processing والفهرسة الدائمة.

هذا القرار يقلل:

- عدد حالات الـState Machine.
- عدد الموارد المؤقتة.
- عدد failure paths.
- coupling بين Laravel وFastAPI.
- cleanup/scheduler requirements.
- schema complexity.
- UI complexity.

ويحافظ على القيمة التقنية الأساسية للمشروع:

- Cloud وHybrid Local كمسارين مستقلين.
- Shared parsing contracts.
- Qdrant isolation.
- ProcessingRun audit.
- Safe reprocessing.
- Mixed-profile retrieval.
- Local resource management.
- RAG quality evaluation مستقلة وصحيحة منهجياً.
