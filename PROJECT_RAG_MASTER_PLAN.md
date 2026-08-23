# الخطة التنفيذية التفصيلية لتحويل نموذج RAG إلى تطبيق ويب متكامل

> **المشروع:** نظام ذكي للإجابة عن الأسئلة اعتماداً على الوثائق المحلية  
> **الواجهة والتطبيق الرئيسي:** Laravel  
> **خدمة الذكاء الاصطناعي:** FastAPI  
> **قاعدة البيانات العلائقية:** MySQL  
> **قاعدة البيانات المتجهية:** Qdrant Local  
> **أنواع الملفات المدعومة:** PDF, DOCX, TXT  
> **الفحص الأمني للملفات:** ClamAV  
> **الواجهة الأمامية:** Blade + Livewire + Flux + Tailwind CSS + JavaScript  
> **لوحة الإدارة:** Filament  
> **المعالجة الخلفية:** Laravel Queue + Redis  
> **المراجع التقنية لمساري RAG:** الملفان `cloud_first_rag_colab_fixed_interactive.ipynb` و`hybrid_cloud_parse_local_rag_simple_colab(5).ipynb`
> **إستراتيجية المعالجة:** Cloud أو Hybrid Local أو Compare في البيئة المحلية، وCloud فقط في النشر Online
> **إستراتيجية LLM:** `Qwen/Qwen3.5-9B` عبر Hugging Face Router في Cloud، و`qwen3.5:4b` عبر Ollama محلياً
> **خطة النشر المرجعية:** Oracle Cloud Always Free + Docker Compose لمسار `cloud` فقط؛ الـLocal/Compare Demo يعمل منفصلاً على أجهزة المشروع
> **آخر تحديث للخطة:** 2026-08-24
> **مرجع المطابقة التنفيذية:** `main@6addaf1e50863543d02a29423ac04a5b3303f72b` + Laravel migrations + MySQL schema الفعلي؛ آخر مطابقة تنفيذية لمسار Documents/Security بتاريخ 2026-08-24

> [!IMPORTANT]
> القسم **174 — التعديل المعماري المعتمد** هو المرجع الأحدث والملزم لكل ما يخص مسارات المعالجة، قواعد البيانات، Qdrant، المحادثة، صفحات Blade، Filament والنشر. داخل القسم 174 تكون الأولوية للقسم **174.20 — قرار التبسيط النهائي** عند أي تعارض مع 174.19 أو ما قبله. أبقينا الأقسام السابقة لحفظ سياق القرارات وعدم فقدان أي معلومة تاريخية.

## Baseline التنفيذ النشط

أي Task جديدة يجب أن تبدأ من هذا الـBaseline ثم ترجع إلى قسمها التفصيلي:

```text
Oracle Online        = cloud profile فقط، بلا Local AI dependencies أو weights
Mac/ASUS Local Demo  = cloud | hybrid_local | compare
Provider routing     = trusted processing profile + capabilities، بلا global LLM_PROVIDER switch
Security scan        = configurable؛ enabled افتراضياً → on-demand clamscan + fail-closed، disabled صراحةً → direct permanent storage بعد Validation
Local heavy work     = global Redis lock + single_active model + release after every stage
Conversation context = آخر تبادلين مكتملين فقط، بلا ذاكرة مستخرجة
Answer delivery      = polling + جاري التفكير + completed-answer visual reveal، بلا Streaming backend
Deployment tasks     = DPL-1–23 Oracle Cloud-only، وDPL-24–25 Local Demo فقط
```

---

# 0. بروتوكول تنفيذ الخطة بين المحادثات

هذه الوثيقة هي **المرجع المعماري الثابت** للمشروع: تحدد البنية، المراحل، الحدود بين الأنظمة، ومعايير الإنجاز.

أما الحالة التنفيذية الفعلية فتدار في الملف:

```text
PROJECT_RAG_EXECUTION_PROGRESS.md
```

ويعتبر هذا الملف هو **Single Source of Truth** لمعرفة أين وصل التنفيذ.

## 0.1 نقطة البداية

تم اعتماد القرار التالي:

```text
المشروع يبدأ من الصفر.
لا يوجد Codebase سابق يجب تدقيقه.
P0 Baseline Audit = N/A.
أول مهمة فعلية = A1 — إنشاء Laravel Application.
```

## 0.2 قاعدة المحادثات

للحفاظ على نافذة السياق والتنظيم:

- كل **Task تنفيذية واحدة** تنفذ في **Chat مستقل**.
- لا يتم بدء المهمة التالية قبل:
  1. تنفيذ المهمة الحالية.
  2. اختبارها.
  3. التحقق من معيار القبول.
  4. تحديث ملف التقدم.
- في بداية Chat جديد، يتم رفع آخر نسخة من ملف التقدم فقط في الوضع الطبيعي.
- ترفع هذه الخطة الرئيسية أيضاً عندما تتطلب المهمة الرجوع إلى تفاصيلها.

## 0.3 قاعدة الذاكرة والاستمرارية

لا يعتمد المشروع على ذاكرة المحادثات السابقة كمصدر رسمي للحالة.

الاستمرارية تكون عبر:

```text
Master Plan     → ماذا سنبني ولماذا؟
Progress File   → ماذا أنجزنا وأين وصلنا؟
Current Handoff → ما المهمة التي يجب تنفيذها الآن؟
```

## 0.4 Definition of Done لكل Task

أي Task لا تعتبر `DONE` إلا بعد:

- اكتمال التنفيذ المطلوب.
- نجاح الاختبارات أو التحقق المناسب.
- تسجيل الملفات التي تغيرت.
- تسجيل القرارات أو المشاكل المهمة.
- تحديث `CURRENT HANDOFF`.
- تحديد المهمة التالية بوضوح.

## 0.5 صيغة بدء أي Chat جديد

```text
نكمل مشروع RAG حسب ملف التقدم المرفق. نفذ المهمة الحالية فقط، وبعد نجاحها حدّث ملف التقدم وحدد المهمة التالية.
```

---

# 1. الهدف من المشروع

الهدف هو تحويل نموذج الـRAG التجريبي الموجود في ملف Colab إلى تطبيق ويب منظم وقابل للصيانة والتوسع، بحيث يتمكن المستخدم من:

1. إنشاء حساب وتسجيل الدخول.
2. رفع وثائق خاصة به بصيغ:
   - PDF
   - DOCX
   - TXT
3. فحص الملفات أمنياً باستخدام ClamAV قبل معالجتها افتراضياً، مع إمكانية تعطيل الفحص بإعداد تشغيلي صريح يمرر الملف بعد Validation مباشرة إلى Private Storage الدائم.
4. متابعة حالة معالجة كل وثيقة.
5. إنشاء محادثة جديدة.
6. اختيار وثيقة واحدة أو مجموعة وثائق للمحادثة.
7. طرح أسئلة باللغة العربية أو الإنكليزية.
8. الحصول على إجابة تعتمد فقط على الوثائق المحددة.
9. مشاهدة المصادر المرتبطة بالإجابة.
10. الاحتفاظ بسجل المحادثات والرسائل.
11. إدارة الوثائق والمحادثات من واجهة المستخدم.
12. توفير لوحة إدارة Filament للمشرفين.
13. تخزين الـvectors والـmetadata في Qdrant يعمل محلياً بدلاً من Qdrant Cloud.

المبدأ المعماري الأساسي هو:

> **Laravel مسؤول عن التطبيق والمستخدمين والأمان والواجهات، بينما FastAPI مسؤول فقط عن الذكاء الاصطناعي ومسار RAG.**

---

# 2. نطاق النسخة الأولى

تركز النسخة الأولى على بناء نظام واضح ومستقر بدون إدخال تعقيد غير ضروري.

## 2.1 الميزات الأساسية

- Authentication.
- Email verification.
- إدارة المستخدمين.
- رفع الملفات.
- فحص الملفات عبر ClamAV افتراضياً، مع configurable explicit bypass موثق للبيئات التي تعطل Security Scan عمداً.
- تخزين الملفات بشكل خاص.
- دعم PDF / DOCX / TXT.
- معالجة الملفات عبر Laravel Queue.
- إرسال الملفات الآمنة إلى FastAPI.
- Parsing.
- Chunking.
- Embeddings.
- تخزين Dense + Sparse vectors.
- Qdrant Local.
- Hybrid Search.
- RRF Fusion.
- Reranking.
- توليد الإجابات.
- عرض المصادر.
- إدارة المحادثات.
- اختيار عدة وثائق لكل محادثة.
- Filament Dashboard.
- Logging.
- Error handling.
- User isolation.

## 2.2 أشياء لا نحتاجها في النسخة الأولى

لتجنب تضخيم المشروع، لا يلزم مبدئياً:

- Microservices متعددة.
- Kubernetes.
- Event-driven architecture معقد.
- Kafka.
- أكثر من Vector Database.
- أكثر من LLM في الوقت نفسه.
- معالجة فيديو أو صوت.
- Excel.
- PowerPoint.
- OCR محلي إضافي ما دام LlamaParse يغطي PDF المطلوب.
- WebSocket architecture معقدة.

يمكن إضافة هذه الأمور لاحقاً عند الحاجة.

---

# 3. المصدر التقني الحالي: ماذا يفعل الـNotebook؟

الـNotebook المرفق يحتوي حالياً على Pipeline تجريبي يدعم PDF، ويستخدم:

```text
PDF
  ↓
LlamaParse Cloud
  ↓
Markdown Pages
  ↓
LlamaIndex Documents
  ↓
SentenceSplitter
  ↓
Chunks
  ↓
Jina Embeddings
  ↓
Qdrant
  ├── Dense Vector
  └── BM25 Sparse Vector
  ↓
Hybrid Retrieval
  ↓
RRF Fusion
  ↓
Jina Reranker
  ↓
Context Builder
  ↓
Qwen 3.5 عبر Hugging Face Router
  ↓
Answer + Sources
```

## 3.1 الإعدادات الحالية المهمة

الإعدادات الموجودة في الـNotebook:

```text
Embedding Model:
jina-embeddings-v3

Embedding Dimension:
1024

Reranker:
jina-reranker-v2-base-multilingual

LLM:
Qwen/Qwen3.5-9B

Chunk Size:
800

Chunk Overlap:
80

Dense Candidates:
12

Sparse Candidates:
12

RRF Top K:
12

Rerank Top N:
5
```

هذه القيم تعتبر Defaults للنسخة الأولى، لكن يجب نقلها إلى Configuration بدلاً من بقائها Hardcoded داخل الكود.

---

# 4. التعديلات المطلوبة على نموذج الـNotebook

هناك ثلاثة تعديلات أساسية على النسخة الحالية.

## 4.1 دعم ثلاثة أنواع ملفات

بدلاً من PDF فقط:

```text
PDF
DOCX
TXT
```

يجب بناء `DocumentLoaderService` يختار الـLoader المناسب بناءً على نوع الملف.

## 4.2 إضافة ClamAV مع Security Scan قابل للضبط

الوضع الافتراضي الآمن:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true

Upload
  ↓
Laravel Validation
  ↓
Private Quarantine
  ↓
security-scan queue (concurrency=1)
  ↓
short-lived clamscan Process
  ↓
Clean?
  ├── Yes → تثبيت Clean ثم Promotion إلى Private Storage الدائم
  └── No/Failure/Timeout → Fail-closed وعدم تشغيل AI
```

المسار الاختياري عند التعطيل الصريح:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=false

Upload
  ↓
Laravel Validation
  ↓
Permanent Private Storage
  ↓
متابعة المعالجة اللاحقة
```

قواعد القرار:

- `true` هو الـDefault والمرجع الموصى به للنشر Online.
- `false` قرار تشغيلي صريح، وليس fallback تلقائياً إذا تعطل ClamAV.
- إذا كان الفحص مفعلاً وفشل الفاحص أو غابت/تقادمَت التواقيع أو حدث Timeout يبقى Fail-Closed.
- Validation تبقى إلزامية في الحالتين، لكنها لا تعد Antivirus ولا تعادل فحص malware.
- لا يقرر `DocumentStorageService` هل الفحص مفعل؛ القرار في Upload/Security orchestration.

## 4.3 استخدام Qdrant Local

يتم إلغاء:

```text
QDRANT_URL = cloud URL
QDRANT_API_KEY
```

واستخدام Qdrant محلي، مثلاً داخل Docker:

```text
http://qdrant:6333
```

كما يجب إلغاء فكرة:

```python
RESET_COLLECTION = True
```

لأنها مناسبة للتجربة فقط.

في التطبيق الحقيقي تكون البيانات دائمة.

---

# 5. المعمارية العامة

```text
┌──────────────────────────────────────────────────────────────┐
│                         Browser                              │
│                                                              │
│      Blade + Livewire + Flux + Tailwind + JavaScript         │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             ▼
┌──────────────────────────────────────────────────────────────┐
│                         Laravel                              │
│                                                              │
│ Authentication                                               │
│ Authorization                                                │
│ Users                                                        │
│ Documents                                                    │
│ Conversations                                                │
│ Messages                                                     │
│ Sources                                                      │
│ Private Storage                                              │
│ Security Scan Queue / Worker                                 │
│ Laravel Queue                                                │
│ Filament                                                     │
└────────────────────────────┬─────────────────────────────────┘
                             │
                             │ Internal REST API
                             ▼
┌──────────────────────────────────────────────────────────────┐
│                         FastAPI                              │
│                                                              │
│ Document Processing                                          │
│ Retrieval                                                    │
│ Reranking                                                    │
│ Context Building                                             │
│ LLM Generation                                               │
└───────────────┬──────────────────┬───────────────────────────┘
                │                  │
                ▼                  ▼
       ┌────────────────┐   ┌────────────────────┐
       │ Qdrant         │   │ External AI APIs   │
       │ self-hosted    │   │ Llama/Jina/HF     │
       └────────────────┘   └────────────────────┘
                                  │
                                  └── Local Demo only: Host BGE/Ollama
```

---

# 6. توزيع المسؤوليات

## 6.1 Laravel

Laravel مسؤول عن:

- تسجيل المستخدم.
- تسجيل الدخول.
- التحقق من البريد.
- إدارة الجلسات.
- Authorization.
- Policies.
- رفع الملفات.
- File validation.
- MIME validation.
- File size validation.
- SHA-256.
- تنسيق Security Scan Worker ونتيجة `clamscan` Fail-closed.
- Private Storage.
- Documents.
- Conversations.
- Messages.
- Sources.
- Queue jobs.
- التواصل مع FastAPI.
- تخزين نتيجة FastAPI.
- عرض النتائج.
- Filament Dashboard.
- Logs المتعلقة بالتطبيق.

Laravel **ليس مسؤولاً** عن:

- Embeddings.
- Vector Search.
- Reranking.
- Chunking.
- Parsing.
- Prompt construction.
- LLM generation.

---

## 6.2 FastAPI

FastAPI مسؤول عن:

- قراءة الملفات.
- اختيار الـLoader.
- Parsing.
- Normalization.
- Chunking.
- Embeddings.
- بناء Dense vectors.
- بناء BM25 sparse vectors.
- التخزين في Qdrant.
- حذف vectors الخاصة بوثيقة.
- Query embedding.
- Qdrant filters.
- Hybrid Retrieval.
- RRF Fusion.
- Reranking.
- Context building.
- Prompt building.
- الاتصال بالـLLM.
- إرجاع Answer + Sources.

FastAPI **لا يدير**:

- Users.
- Login.
- Sessions.
- صفحات التطبيق.
- Conversations database.
- Laravel permissions.
- Filament.

---

# 7. البنية المقترحة لمشروع Laravel

```text
laravel-app/
│
├── app/
│   ├── Enums/
│   │   ├── DocumentStatus.php
│   │   ├── FileType.php
│   │   ├── ProcessingProfile.php
│   │   ├── ProcessingRunStatus.php
│   │   ├── MessageRole.php
│   │   └── MessageStatus.php
│   │
│   ├── Filament/
│   │   ├── Resources/
│   │   ├── Pages/
│   │   └── Widgets/
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Requests/
│   │   └── Middleware/
│   │
│   ├── Jobs/
│   │   ├── ProcessDocumentJob.php
│   │   ├── AskConversationJob.php
│   │   └── DeleteDocumentVectorsJob.php
│   │
│   ├── Livewire/
│   │   ├── Documents/
│   │   └── Chat/
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Document.php
│   │   ├── ProcessingRun.php
│   │   ├── Conversation.php
│   │   ├── Message.php
│   │   └── MessageSource.php
│   │
│   ├── Policies/
│   │   ├── DocumentPolicy.php
│   │   └── ConversationPolicy.php
│   │
│   ├── Services/
│   │   ├── Ai/
│   │   │   ├── AiServiceClient.php
│   │   │   └── DTO/
│   │   │
│   │   ├── Documents/
│   │   │   ├── DocumentStorageService.php
│   │   │   ├── DocumentUploadService.php        # مخطط للـquarantine/scan orchestration
│   │   │   ├── DocumentSecurityService.php
│   │   │   └── DocumentProcessingService.php
│   │   │
│   │   └── Conversations/
│   │       └── ConversationMessageService.php
│   │
│   └── Exceptions/
│
├── database/
│   └── migrations/
│
├── resources/
│   └── views/
│
├── routes/
│   ├── web.php
│   └── console.php
│
└── tests/
```

الفكرة الأساسية:

> Controllers وLivewire Components يجب أن تكون خفيفة، ولا تحتوي منطق أعمال كبير.

الأسماء الموجودة فعلياً حتى B10 تشمل `DocumentStorageService` و`Document` و`ProcessingRun` والـEnums الأربعة أعلاه. بقية العناصر في الشجرة هدف معماري للمهام اللاحقة وليست ادعاءً بأنها منفذة الآن.

---

# 8. البنية المقترحة لمشروع FastAPI

```text
ai-service/
│
├── app/
│   ├── main.py
│   │
│   ├── api/
│   │   └── v1/
│   │       ├── documents.py
│   │       ├── rag.py
│   │       └── health.py
│   │
│   ├── core/
│   │   ├── config.py
│   │   ├── exceptions.py
│   │   ├── logging.py
│   │   └── security.py
│   │
│   ├── schemas/
│   │   ├── documents.py
│   │   ├── rag.py
│   │   └── common.py
│   │
│   ├── clients/
│   │   ├── llama_cloud_client.py
│   │   ├── jina_client.py
│   │   ├── huggingface_client.py
│   │   └── qdrant_client.py
│   │
│   ├── loaders/
│   │   ├── base.py
│   │   ├── pdf_loader.py
│   │   ├── docx_loader.py
│   │   └── txt_loader.py
│   │
│   ├── services/
│   │   ├── document_loader_service.py
│   │   ├── document_processor.py
│   │   ├── chunking_service.py
│   │   ├── embedding_service.py
│   │   ├── vector_store_service.py
│   │   ├── retrieval_service.py
│   │   ├── reranker_service.py
│   │   ├── context_service.py
│   │   └── generation_service.py
│   │
│   ├── rag/
│   │   └── prompts.py
│   │
│   └── tests/
│
├── requirements.txt
├── pyproject.toml
├── Dockerfile
└── README.md
```

---

# 9. قاعدة البيانات في Laravel

## 9.1 users

الحالة المنفذة حالياً هي جدول Laravel/Fortify الفعلي التالي:

```text
id
name
email                         UNIQUE
email_verified_at             NULL
password
two_factor_secret             NULL
two_factor_recovery_codes     NULL
two_factor_confirmed_at       NULL
remember_token                NULL
created_at                    NULL
updated_at                    NULL
```

أعمدة المصادقة الثنائية موجودة بسبب Migrations الحزمة، لكن 2FA وPasskeys غير مفعّلتين كميزة مستخدم ضمن نطاق A2 الحالي.

## 9.2 جداول Laravel الداعمة المنفذة

توجد في MySQL فعلياً، إضافة إلى `users` والجداول Domain المذكورة لاحقاً:

```text
password_reset_tokens
sessions
cache
cache_locks
jobs
job_batches
failed_jobs
migrations
passkeys
```

`passkeys.user_id` يستخدم `cascadeOnDelete` كما أنشأته الحزمة، بينما `documents.user_id` يستخدم `restrictOnDelete` عمداً بسبب الموارد الخارجية والملف الخاص.

---

# 10. جدول `documents` المنفذ حالياً

جدول `documents` يمثل هوية الوثيقة وملكيتها وبيانات الملف والحالة التجميعية فقط. الـSchema الفعلي بعد B8 هو:

| العمود | النوع الفعلي | Null / Default |
|---|---|---|
| `id` | BIGINT UNSIGNED | PK, auto increment |
| `user_id` | BIGINT UNSIGNED | NOT NULL، FK إلى `users.id` مع `ON DELETE RESTRICT` |
| `original_name` | VARCHAR(255) | NOT NULL |
| `stored_name` | VARCHAR(255) | NOT NULL |
| `title` | VARCHAR(255) | NULL |
| `file_path` | VARCHAR(1024) | NOT NULL |
| `file_type` | VARCHAR(16) | NOT NULL |
| `mime_type` | VARCHAR(255) | NOT NULL |
| `file_size` | BIGINT UNSIGNED | NOT NULL |
| `sha256` | CHAR(64) | NOT NULL بعد B8 |
| `status` | VARCHAR(32) | NOT NULL، default `pending` |
| `created_at` | TIMESTAMP | NULL |
| `updated_at` | TIMESTAMP | NULL |

الفهارس الفعلية:

```text
documents_user_id_status_index (user_id, status)
documents_user_id_sha256_index (user_id, sha256)
documents_user_id_created_at_index (user_id, created_at)
```

لا توجد حالياً في `documents` الأعمدة `failure_reason` أو `total_pages` أو `total_chunks` أو `qdrant_collection` أو `processed_at`. خصائص التنفيذ موجودة في `document_processing_runs`. كذلك لا يوجد بعد `selected_processing_run_id` لأن B11 ما زالت TODO، ولا يوجد جدول `document_processing_comparisons` لأن B12 ما زالت TODO.

## 10.1 file_type

القيم المسموحة:

```text
pdf
docx
txt
```

## 10.2 status

القيم المعتمدة والمنفذة في `DocumentStatus`:

```text
pending
scanning
infected
queued
processing
awaiting_selection
indexing
ready
failed
selection_expired
```

### معنى الحالات

#### pending

تم إنشاء سجل الوثيقة ولم تبدأ المعالجة بعد.

#### scanning

يتم فحص الوثيقة باستخدام ClamAV.

#### infected

ClamAV اعتبر الملف مصاباً أو مشبوهاً.

يجب:

- رفض الملف.
- عدم إرساله إلى FastAPI.
- إبقاؤه في Quarantine أو حذفه وفق سياسة الاحتفاظ الآمنة المعتمدة، من دون نقله إلى Private Storage الدائم.
- تسجيل سبب الرفض.

#### queued

الملف آمن وتم إرسال Job لمعالجته.

#### processing

FastAPI بدأ معالجة الوثيقة.

#### awaiting_selection

اكتملت Runs المقارنة وأصبح القرار بانتظار اختيار المستخدم، ولا تكون الوثيقة Ready بعد.

#### indexing

تم اختيار Run ويجري تثبيت نتيجته والتحقق منها في Qdrant.

#### ready

انتهت المعالجة بنجاح وأصبح الملف قابلاً للاستخدام في المحادثات.

#### failed

حدث خطأ أثناء Parsing أو Embedding أو التخزين أو الاتصال بالخدمات.

#### selection_expired

انتهت مهلة المقارنة قبل اعتماد Run؛ لا تُفهرس أي نتيجة تلقائياً.

---

# 11. conversations

```text
id
user_id
title
created_at
updated_at
```

كل Conversation مملوكة لمستخدم واحد.

---

# 12. conversation_document

Pivot table:

```text
conversation_id
document_id
created_at
```

الغرض:

السماح للمحادثة باستخدام وثيقة واحدة أو عدة وثائق.

مثال:

```text
Conversation 25
  ├── Document 8
  ├── Document 11
  └── Document 19
```

يجب ألا يسمح Laravel بربط Document لا يملكه المستخدم.

---

# 13. messages

```text
id
conversation_id
role
content
status
error_message
created_at
updated_at
```

## role

```text
user
assistant
```

## status

مثلاً:

```text
pending
processing
completed
failed
```

---

# 14. message_sources

```text
id
message_id
document_id
source_number
page
section
chunk_index
qdrant_point_id
reranker_score
text_preview
created_at
```

هذا الجدول مهم لعرض المصادر بدون الحاجة لإعادة Query إلى Qdrant.

---

# 15. علاقات الـModels

```text
User
 ├── hasMany Documents
 └── hasMany Conversations

Conversation
 ├── belongsTo User
 ├── belongsToMany Documents
 └── hasMany Messages

Document
 ├── belongsTo User
 ├── hasMany ProcessingRuns
 ├── belongsToMany Conversations
 └── hasMany MessageSources

ProcessingRun
 └── belongsTo Document

Message
 ├── belongsTo Conversation
 └── hasMany MessageSources
```

الحالة التنفيذية حتى B10: علاقات `User ↔ Document` و`Document ↔ ProcessingRun` منفذة. علاقات Conversations وMessages مخطط لها ولم تنفذ بعد. بعد B11 ستضاف علاقة الـselected run بصورة صريحة مع بقاء شرط أن الـRun المختار يعود إلى الوثيقة نفسها Domain invariant لا يضمنه الـForeign Key وحده.

---

# 16. رفع الملفات في Laravel

واجهة رفع الوثيقة يجب أن تقبل:

```text
.pdf
.docx
.txt
```

ولا تعتمد على extension وحده.

يجب التحقق من:

1. Authentication.
2. Authorization.
3. Extension.
4. MIME Type.
5. الحجم.
6. اسم الملف.
7. SHA-256.
8. Security Scan policy: ClamAV عند تفعيله، أو explicit configured bypass عند تعطيله.

الحالة الحالية حتى B10 تنفذ البنود 1–7؛ بند ClamAV يبدأ في المرحلة C. لذلك `POST /documents` الحالي يخزن مباشرة على الـdisk الخاص بحالة `pending` ولا يدعي أن الفحص الأمني منفذ بعد.

---

# 17. التخزين الآمن للملفات

الملفات لا تخزن في:

```text
public/
```

بل في Private Storage.

مثلاً:

```text
storage/app/private/documents/{user_id}/
```

ويتم إنشاء `stored_name` عشوائي.

مثال:

```text
01J5Z8YQ6D7M2K4N9P3R5T7V8W.pdf
```

الاسم المنفذ حالياً هو ULID يولده الخادم مع الامتداد الذي اجتاز Validation، ولا يستخدم الاسم الأصلي كاسم فعلي في التخزين.

---

# 18. Configurable Document Security Flow

الفحص الأمني مفعّل افتراضياً:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true
```

عند التفعيل:

```text
User
  ↓
Upload
  ↓
Laravel request validation
  ↓
Private Quarantine
  ↓
security-scan queue
  ↓
short-lived clamscan
  ↓
Clean?
  ├── Yes → Permanent Private Storage
  └── No/Failure/Timeout → infected/fail-closed
```

عند التعطيل الصريح:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=false

User
  ↓
Upload
  ↓
Laravel request validation
  ↓
Permanent Private Storage
```

قواعد مهمة:

- لا يوجد `clamd` دائم. عند تفعيل الفحص يملك Security Scan Worker ClamAV CLI وQuarantine/signature volumes ولا يملك Docker socket.
- عند تفعيل الفحص، كل نتيجة غير Clean، بما فيها missing/stale signatures أو Timeout، تمنع المعالجة.
- عند تعطيل الفحص لا يحاول النظام تشغيل ClamAV ولا يستخدم Quarantine كمرحلة إلزامية.
- لا يجوز تحويل failure في ClamAV إلى مسار التعطيل تلقائياً؛ bypass لا يحدث إلا من Configuration موثوقة محمّلة من الخادم.
- Validation إلزامية في المسارين، لكنها لا تعد بديلاً عن malware scanning.
- FastAPI يستقبل فقط ملفاً أصبح مسموحاً للمعالجة وفق Policy الحالية: Clean موثق عندما يكون الفحص مفعلاً، أو ملفاً Validation-success مخزناً دائماً عندما يكون الفحص معطلاً صراحةً.

---

# 19. خدمات رفع وتخزين الوثيقة

## الحالة المنفذة بعد C4 — `DocumentStorageService` و`DocumentUploadService`

- Initial upload ينشئ `Document` ويحسِب SHA-256 ويطبق duplicate policy عبر `DocumentStorageService`.
- المسار المنفذ حالياً يخزن Initial Upload في `document_quarantine`.
- C4 أضافت `promoteQuarantined()` لنقل نفس الملف ونفس `Document` إلى `documents` بعد Clean.
- الـAPI التاريخي `store()` يستطيع التخزين المباشر في `documents` لكنه لا يمثل اسماً واضحاً للعقد الجديد.

## الهدف المخطط لـC6 — Configurable security-scan routing

- إضافة إعداد موثوق مثل:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true
```

- `DocumentUploadService` وحده يقرر المسار:
  - Enabled → `storeQuarantined()` ثم Security Scan ثم Clean promotion.
  - Disabled explicitly → تخزين مباشر دائم.
- يفضل إعادة تسمية `DocumentStorageService::store()` إلى `storePermanent()` بعد التحقق من callers، حتى لا يوجد API مبهم يسمح بتجاوز Security Pipeline بطريق الخطأ.
- `DocumentStorageService` يبقى مسؤولاً عن Storage/SHA-256/Duplicate/Cleanup primitives ولا يقرأ Feature flag لاتخاذ قرار orchestration.
- لا يتحول Scan failure إلى direct storage تلقائياً.
- C6 لا تنفذ Aggregate status transitions؛ هذه تنتقل إلى C7.
- Upload request لا يطلق Parsing أو Embedding مباشرة.

---

# 20. DocumentSecurityService

مسؤول فقط عن الأمن المرتبط بالملف.

مثلاً:

```text
scan(filePath)
```

ويرجع نتيجة موحدة:

```text
clean
infected
scan_failed
```

ينفّذ داخل Security Scan Worker ويشغّل `clamscan` بوسائط Array عبر Process API، لا Shell command مبنياً من اسم الملف.

من المهم التفريق بين:

```text
infected
```

و:

```text
ClamAV unavailable
```

لأن تعطل ClamAV لا يعني أن الملف مصاب.

لكن في الوضع الآمن:

> إذا فشل الفحص الأمني، لا تتم متابعة المعالجة حتى ينجح الفحص.

---

# 21. ProcessDocumentJob

يتم تشغيله عبر Laravel Queue.

المسار:

```text
ProcessDocumentJob
  ↓
Load Document
  ↓
Ensure status = queued
  ↓
Update status → processing
  ↓
Create/Update ProcessingRun
  ↓
DocumentProcessingService
  ↓
AiServiceClient
  ↓
POST FastAPI /documents/process
  ↓
Receive result
  ↓
Update ProcessingRun:
  total_pages
  total_chunks
  vector_count / vector_dimension
  stage_timings_ms / warnings
  comparison_report / temporary artifact metadata
```

ثم يطبق Laravel إحدى النهايتين:

```text
single profile → promotion ناجح → Run = indexed → selected run transaction → Document = ready
compare        → Runان = ready_for_comparison → Document = awaiting_selection
```

في حال Exception تحفظ معلومات الفشل على الـRun، ثم يسقط Document إلى الحالة التجميعية المناسبة:

```text
ProcessingRun.status = failed
ProcessingRun.error_code = ...
ProcessingRun.failure_reason = ...
Document.status = failed  # فقط عندما لا توجد نتيجة صالحة أخرى وفق projector
```

---

# 22. AiServiceClient

هذا هو المكان الوحيد تقريباً في Laravel الذي يعرف تفاصيل FastAPI.

مسؤول عن:

```text
processDocument()
ask()
deleteDocument()
health()
```

الفائدة:

إذا تغير FastAPI URL أو API schema لاحقاً، لا نعدل Livewire أو Jobs أو Controllers.

---

# 23. API بين Laravel وFastAPI

يفضل أن تكون Versioned:

```text
/api/v1/
```

## 23.1 معالجة وثيقة

```http
POST /api/v1/documents/process
```

يرسل Laravel:

```text
document_id
user_id
processing_run_id
processing_profile
file_type
file
```

النتيجة:

```json
{
  "document_id": 152,
  "processing_run_id": 901,
  "profile": "cloud",
  "status": "ready_for_comparison",
  "total_pages": 33,
  "total_chunks": 184,
  "vector_count": 184,
  "vector_dimension": 1024,
  "temporary_artifact_ref": "opaque-token"
}
```

بالنسبة إلى TXT أو DOCX:

`total_pages` يمكن أن يكون `null` إذا لم توجد دلالة موثوقة للصفحات.

كل هذه القيم تخص `document_processing_runs` ولا تضاف إلى `documents`. العقد النهائي التفصيلي وعمليات Promotion/Compare في 174.4–174.11.

---

# 24. حذف وثيقة من Qdrant

```http
DELETE /api/v1/documents/{document_id}
```

لكن لا يجب الاعتماد على `document_id` وحده.

يرسل أو يتحقق أيضاً من:

```text
user_id
```

ثم يحذف FastAPI جميع الـpoints التي تحقق:

```text
user_id = X
AND
document_id = Y
```

---

# 25. سؤال RAG

```http
POST /api/v1/rag/ask
```

Request:

```json
{
  "user_id": 7,
  "conversation_id": 51,
  "document_ids": [12, 17, 33],
  "question": "ما هي أهم نتائج الدراسة؟"
}
```

Response:

```json
{
  "answer": "أظهرت الدراسة ... [المصدر 1]",
  "sources": [
    {
      "source_number": 1,
      "document_id": 12,
      "page": 15,
      "section": null,
      "chunk_index": 48,
      "qdrant_point_id": "...",
      "reranker_score": 0.91,
      "text_preview": "..."
    }
  ],
  "debug": {
    "retrieved_count": 12,
    "reranked_count": 5
  }
}
```

يمكن تعطيل `debug` في Production إذا لم يكن مطلوباً.

---

# 26. Health Endpoint

```http
GET /api/v1/health
```

يفضل أن يفحص:

- FastAPI.
- Qdrant.
- Config.
- optionally external providers.

مثال:

```json
{
  "status": "ok",
  "qdrant": "ok"
}
```

---

# 27. Authentication بين Laravel وFastAPI

FastAPI ليس API عاماً للمستخدم النهائي.

يجب أن يكون الاتصال:

```text
Browser
  ↓
Laravel
  ↓
FastAPI
```

وليس:

```text
Browser → FastAPI
```

يمكن تأمين الاتصال بمفتاح داخلي:

```text
X-Internal-API-Key
```

ويكون المفتاح في Environment Variables فقط.

---

# 28. Document Loader Architecture

يجب توحيد معالجة الملفات الثلاثة خلف Interface واحدة.

مثلاً:

```text
BaseDocumentLoader
  │
  ├── PdfDocumentLoader
  ├── DocxDocumentLoader
  └── TxtDocumentLoader
```

ثم:

```text
DocumentLoaderService
  ↓
detect file type
  ↓
choose loader
  ↓
return normalized documents
```

---

# 29. PDF Loader

بالاعتماد على الـNotebook:

```text
PDF
  ↓
LlamaParse
  ↓
Agentic parsing
  ↓
OCR languages: ar + en
  ↓
Markdown
  ↓
Page-by-page documents
```

يحتفظ Metadata مثل:

```text
user_id
document_id
source
file_type
page
```

مثال:

```json
{
  "user_id": 7,
  "document_id": 12,
  "file_type": "pdf",
  "page": 6
}
```

---

# 30. DOCX Loader

الهدف هو استخراج المحتوى النصي المنظم قدر الإمكان.

يجب التعامل مع:

- Paragraphs.
- Headings.
- Tables.
- Lists.

ثم إخراج Documents موحدة.

Metadata المقترحة:

```text
user_id
document_id
source
file_type
section
heading
```

بالنسبة إلى page:

لا يجب اختراع أرقام صفحات غير موجودة بشكل موثوق.

يمكن أن تكون:

```text
page = null
```

---

# 31. TXT Loader

المسار:

```text
TXT
  ↓
Encoding handling
  ↓
Normalize text
  ↓
Document
```

يجب التأكد من:

- UTF-8.
- عدم تخريب الأحرف العربية.
- إزالة BOM إذا وجد.
- التعامل مع line endings بشكل موحد.

Metadata:

```text
user_id
document_id
source
file_type
page = null
```

---

# 32. Normalized Document Model

بغض النظر عن Loader، المخرج يجب أن يكون موحداً:

```text
Document
  ├── text
  └── metadata
```

مثال:

```json
{
  "text": "النص المستخرج...",
  "metadata": {
    "user_id": 7,
    "document_id": 12,
    "file_type": "pdf",
    "page": 5,
    "section": null
  }
}
```

بهذا الشكل، Chunking لا يحتاج لمعرفة هل الملف PDF أو DOCX أو TXT.

---

# 33. Chunking

نحافظ مبدئياً على إعدادات الـNotebook:

```text
SentenceSplitter

chunk_size = 800
chunk_overlap = 80
```

لكن يجب وضعها في Configuration:

```text
CHUNK_SIZE=800
CHUNK_OVERLAP=80
```

ولا تكون hardcoded داخل `chunking_service.py`.

---

# 34. Metadata لكل Chunk

كل Chunk يجب أن يحمل:

```text
user_id
document_id
file_type
source
page
section
chunk_index
```

ويمكن إضافة:

```text
title
heading
```

عند توفرها.

هذه metadata أساسية للأمان ولعرض المصادر.

---

# 35. Embedding Service

بحسب الـNotebook:

```text
Model:
jina-embeddings-v3

Dimensions:
1024
```

هناك مهمتان مختلفتان:

```text
retrieval.passage
retrieval.query
```

عند معالجة الوثيقة:

```text
passage embedding
```

وعند سؤال المستخدم:

```text
query embedding
```

يجب الحفاظ على هذا الفصل.

---

# 36. Rate Limit Handling

الـNotebook يحتوي آلية لمعالجة Jina rate limits.

يجب الحفاظ على الفكرة داخل Service:

- batching.
- retry.
- maximum retries.
- waiting between batches.
- meaningful exceptions.

الإعدادات الحالية:

```text
EMBED_BATCH_SIZE=6
WAIT_BETWEEN_BATCHES=3
RATE_LIMIT_RETRY_WAIT=30
MAX_RETRIES=5
```

يمكن الاحتفاظ بها كDefaults قابلة للتعديل.

---

# 37. Qdrant Local

يعمل Qdrant محلياً.

في Docker:

```text
qdrant:6333
```

FastAPI يتصل مثلاً بـ:

```text
QDRANT_URL=http://qdrant:6333
```

في النسخة المحلية لا نحتاج Qdrant Cloud API key.

---

# 38. Qdrant Collection

نستخدم Collection منفصلة لكل فضاء Embedding:

```text
rag_documents_cloud
rag_documents_hybrid_local
```

لا ننشئ Collection لكل User أو لكل Document.

الأسباب:

- منع مقارنة vectors من فضاء Jina مع vectors من فضاء BGE-M3 حتى لو كان البعد 1024 في كليهما.
- إبقاء الإدارة والفلاتر موحدة داخل كل Profile.
- لا تتكاثر الـcollections بدون حاجة.

---

# 39. Dense Vector Configuration

حسب الـNotebook:

```text
vector name:
dense_vector

size:
1024

distance:
COSINE
```

---

# 40. Sparse Vector Configuration

حسب الـNotebook:

```text
vector name:
bm25_sparse_vector

model:
Qdrant/bm25

modifier:
IDF

tokenizer:
multilingual
```

هذا يسمح بالـHybrid Retrieval بين semantic search وlexical search.

---

# 41. Point Structure في Qdrant

كل Chunk يصبح Point.

مثال:

```text
Point
│
├── id = UUID
│
├── dense_vector
│
├── bm25_sparse_vector
│
└── payload
    ├── user_id
    ├── document_id
    ├── processing_run_id
    ├── processing_profile
    ├── file_type
    ├── source
    ├── page
    ├── section
    ├── chunk_index
    └── text
```

---

# 42. أهم تعديل أمني على Qdrant

الـNotebook التجريبي يبحث في كامل Collection.

هذا غير مقبول في تطبيق Multi-user.

كل Query يجب أن يحتوي Filter:

```text
user_id = authenticated user

AND

document_id IN selected document ids

AND

processing_run_id IN selected run ids
```

مثال:

```text
user_id = 7
AND
document_id IN [12, 17, 33]
AND
processing_run_id IN [41, 52, 78]
```

هذه القاعدة يجب تطبيقها على:

- Dense prefetch.
- Sparse prefetch.
- deletion.
- أي query آخر.

يستخدم كل target أيضاً `processing_profile` لاختيار Collection والـquery embedding/reranker الصحيحين؛ لا تقارن raw scores بين Profiles مباشرة.

---

# 43. Defense in Depth

يجب تطبيق العزل الأمني في مستويين:

## المستوى الأول: Laravel

Laravel يتحقق من أن المستخدم يملك Documents المطلوبة.

## المستوى الثاني: FastAPI / Qdrant

FastAPI يمرر user filter إلى Qdrant.

بهذا لا يكون الأمان معتمداً على طبقة واحدة فقط.

---

# 44. Hybrid Retrieval

المسار الأصلي في الـNotebook:

```text
Question
  ↓
Query Embedding
  ↓
┌──────────────────┬────────────────────┐
│ Dense Retrieval  │ BM25 Retrieval     │
└──────────┬───────┴──────────┬─────────┘
           │                  │
           └────── RRF ───────┘
                    ↓
               Top Candidates
```

الإعدادات:

```text
DENSE_CANDIDATES=12
SPARSE_CANDIDATES=12
RRF_TOP_K=12
```

---

# 45. RRF Fusion

يستخدم Qdrant:

```text
Fusion.RRF
```

الغرض هو دمج:

```text
semantic similarity
+
lexical relevance
```

بدلاً من الاعتماد على نوع واحد من البحث.

---

# 46. Reranking

بعد الحصول على 12 نتيجة تقريباً:

```text
Jina Reranker
```

الموديل:

```text
jina-reranker-v2-base-multilingual
```

ثم:

```text
RERANK_TOP_N=5
```

أي يتم إرسال أفضل 5 مقاطع تقريباً إلى بناء السياق.

---

# 47. Context Builder

يقوم ببناء سياق مثل:

```text
[المصدر 1]
الملف: study.pdf
الصفحة: 12

النص...

[المصدر 2]
الملف: report.docx
القسم: النتائج

النص...
```

بالنسبة إلى DOCX وTXT، لا يجب إجبار `page` على قيمة وهمية.

يمكن عرض:

```text
القسم
```

أو:

```text
المقطع
```

بدلاً من الصفحة عندما لا تتوفر.

---

# 48. Prompt

الـPrompt الأساسي الموجود في الـNotebook مناسب كنقطة بداية.

المبادئ:

1. استخدام السياق فقط.
2. عدم استخدام المعرفة الخارجية.
3. عدم اختراع معلومات.
4. الإقرار بعدم كفاية الوثائق عند الضرورة.
5. استخدام `[المصدر N]`.
6. العربية افتراضياً.
7. عدم رفض الجواب لمجرد وجود مصدر واحد.

يفضل وضع Prompt في:

```text
app/rag/prompts.py
```

وليس داخل endpoint.

---

# 49. LLM Generation — Provider موحّد وبيئتان منفصلتان

الـNotebook الأصلي يستخدم:

```text
Qwen/Qwen3.5-9B
```

عبر Hugging Face Router.

في التطبيق النهائي **لا يتم ربط منطق RAG مباشرة بمزوّد واحد**. نعرّف Provider Registry، ويختار Laravel/FastAPI المزود من Processing profile موثوقة بعد التحقق من Capabilities:

```text
cloud profile       → CloudLLMProvider
hybrid_local profile → LocalLLMProvider
compare             → الطرفان بالتتابع
```

- Oracle Online يستخدم `cloud` فقط.
- Mac/ASUS Local Demo يسجل Cloud وLocal providers حسب جاهزيتهما ويستخدم كليهما عند اختبار `compare`.
- لا يتحول Oracle إلى Local بإجراء Config بسيط، لأن Images والـDependencies والـTopology مختلفة عمداً.

## 49.1 المسار الأول: Cloud LLM

المسار الافتراضي المتوافق مباشرة مع الـNotebook:

```text
FastAPI
   ↓
CloudLLMProvider
   ↓
Hugging Face Router / OpenAI-compatible API
   ↓
Qwen/Qwen3.5-9B
```

هذا المسار مناسب عندما نريد:

- جودة أعلى من نموذج محلي صغير.
- عدم استهلاك RAM وCPU السيرفر في توليد الإجابة.
- سهولة النشر.
- المحافظة على سلوك الـNotebook الحالي.
- استخدام GPU موجود لدى المزوّد بدلاً من تشغيله داخل VM المشروع.

لكن يجب الانتباه أن رصيد Hugging Face المجاني للمستخدم المجاني محدود، لذلك لا يجب اعتبار Cloud LLM مجانياً بشكل دائم عند زيادة الاستخدام.

## 49.2 المسار الثاني: Self-hosted Local LLM على أجهزة المشروع

المسار الثاني يشغل النموذج تحت إدارتنا:

```text
FastAPI
   ↓
LocalLLMProvider
   ↓
OpenAI-compatible Local Endpoint
   ↓
Host-native Ollama
   ↓
qwen3.5:4b
```

هذا المسار مناسب لـ:

- Local Demo على Mac/ASUS.
- اختبار `hybrid_local` و`compare`.
- إثبات Self-hosted generation.
- تقليل تكلفة كل Request.
- إبقاء سياق Generation على الجهاز بعد Parsing الخارجي.

لا يعمل هذا المسار على Oracle، ولا يوصف بأنه Offline بالكامل لأن LlamaParse Cloud ما زال مستخدماً.

## 49.3 لا Local AI على Oracle

القرار النهائي:

```text
Oracle Cloud-only → Qwen3.5-9B عبر Hugging Face Router
Mac/ASUS Local Demo → Ollama qwen3.5:4b
```

## 49.4 Provider Interface

التصميم المقترح:

```text
LLMProvider
│
├── CloudLLMProvider
└── LocalLLMProvider
```

واجهة موحدة منطقياً:

```text
generate(messages, options) -> LLMResponse
```

ويجب ألا يعرف:

```text
RetrievalService
RerankerService
ContextService
Laravel
```

أي Provider مستخدم.

## 49.5 Factory

مثلاً:

```text
LLMProviderRegistry
   ↓
validated processing_profile + capabilities
   ↓
cloud → CloudLLMProvider
hybrid_local → LocalLLMProvider
compare → both sequentially
```

لا يقبل الـRegistry قيمة Provider مباشرة من Browser. يحدد `RAG_DEPLOYMENT_MODE` Profiles المسموحة، ويختار الـRun الموثوق Provider المناسب. هذا ضروري لأن Compare يحتاج Providerين في الطلب نفسه ولا يمكن تمثيله بمفتاح `LLM_PROVIDER` عالمي.

## 49.6 إعدادات Cloud LLM

```text
CLOUD_LLM_BASE_URL=https://router.huggingface.co/v1
CLOUD_LLM_MODEL=Qwen/Qwen3.5-9B
HF_TOKEN=...
```

## 49.7 إعدادات Local LLM

```text
LOCAL_LLM_BASE_URL=http://127.0.0.1:11434/v1
LOCAL_LLM_MODEL=qwen3.5:4b
LOCAL_LLM_API_KEY=none
```

تستخدم هذه القيمة لأن FastAPI وOllama يعملان على Host OS. لا تستخدم متغيرات Local LLM في Oracle.

## 49.8 توحيد OpenAI-compatible API

يفضل استخدام بروتوكول Chat Completions متوافق مع OpenAI في كلا المسارين.

هذا يسمح لـFastAPI باستخدام Client abstraction واحدة تقريباً:

```text
Cloud:
https://router.huggingface.co/v1/chat/completions

Local:
http://127.0.0.1:11434/v1/chat/completions
```

الفرق يصبح فقط:

```text
base_url
model
api_key
```

وهذه نقطة مهمة لتقليل Vendor Lock-in.

## 49.9 عدم تغيير بقية الـRAG

كلا المسارين يستقبلان نفس:

```text
System Prompt
+
Retrieved Context
+
User Question
```

ويعيدان:

```text
Answer
```

لذلك:

```text
Retrieval
RRF
Reranking
Sources
Qdrant
Laravel Chat
```

لا تتغير عند التبديل بين Local وCloud LLM.

---

# 50. Ask RAG Service

بدلاً من تابع واحد ضخم مثل `ask_rag()`، يتم تقسيمه:

```text
RagService.ask()
  ↓
RetrievalService.retrieve()
  ↓
RerankerService.rerank()
  ↓
ContextService.build()
  ↓
GenerationService.generate()
  ↓
Answer + Sources
```

بهذا يمكن اختبار كل مرحلة بشكل مستقل.

---

# 51. رحلة السؤال كاملة

```text
User
  ↓
Livewire
  ↓
Validate question
  ↓
Authorize conversation
  ↓
Get selected documents
  ↓
Verify documents belong to user
  ↓
Save user message
  ↓
Save assistant placeholder
  ↓
Dispatch AskConversationJob
  ↓
Queue Worker
  ↓
AiServiceClient
  ↓
FastAPI /rag/ask
  ↓
Query Embedding
  ↓
Qdrant filtered Hybrid Search
  ↓
RRF
  ↓
Reranker
  ↓
Context
  ↓
Qwen
  ↓
Answer + Sources
  ↓
Laravel
  ↓
Save assistant message
  ↓
Save message_sources
  ↓
Livewire refresh
```

---

# 52. AskConversationJob

المسؤوليات:

1. تحميل Conversation.
2. التحقق من وجود Message pending.
3. تحميل document_ids المحددة.
4. إرسال السؤال إلى FastAPI.
5. استلام Answer.
6. تحديث assistant message.
7. تخزين Sources.
8. التعامل مع failures.

لا يضع Prompt ولا يجري vector search.

---

# 53. واجهة المحادثة

التصميم المقترح:

```text
┌────────────────────────────────────────────────────────┐
│ App                                                    │
├──────────────┬─────────────────────────────────────────┤
│ Conversations│ Selected documents                      │
│              │ [study.pdf] [report.docx]               │
│ + New Chat   ├─────────────────────────────────────────┤
│              │                                         │
│ Chat 1       │ User                                    │
│ Chat 2       │ ما هي النتائج الرئيسية؟                │
│ Chat 3       │                                         │
│              │ Assistant                               │
│              │ أظهرت الدراسة أن... [المصدر 1]         │
│              │                                         │
│              │ Sources                                 │
│              │ study.pdf - page 15                     │
│              │                                         │
│              ├─────────────────────────────────────────┤
│              │ [ اكتب سؤالك... ]       [إرسال]         │
└──────────────┴─────────────────────────────────────────┘
```

---

# 54. اختيار الوثائق داخل المحادثة

لا يسمح باستخدام:

```text
pending
scanning
queued
processing
failed
infected
```

الوثائق المتاحة للمحادثة يجب أن تكون فقط:

```text
documents.status = ready
selected_processing_run_id موجود
selected run status = indexed
Runtime/Collection الخاصة بالـprocessing_profile متاحة
```

---

# 55. تغيير الوثائق المرتبطة بمحادثة

عند إضافة Document إلى Conversation:

1. Verify owner.
2. Verify aggregate status = ready.
3. Verify selected Run belongs to the document and is indexed and runtime-capable.
4. attach pivot.

عند الإزالة:

1. Verify owner.
2. detach pivot.

لا تحتاج هذه العملية لتعديل Qdrant.

Qdrant يحتوي كل chunks، والـfilter أثناء السؤال يحدد ما يستخدم.

---

# 56. واجهة Documents

المقترح:

```text
My Documents

Name              Type   Status       Size
------------------------------------------------
study.pdf         PDF    Ready        4.2 MB
report.docx       DOCX   Processing   2.1 MB
notes.txt         TXT    Ready        120 KB
```

Actions:

- View details.
- Download.
- Reprocess.
- Delete.

---

# 57. حذف الوثيقة

الحذف يجب ألا يكون مجرد:

```text
DELETE FROM documents
```

بل:

```text
Authorize
  ↓
Delete Qdrant points
  ↓
Delete temporary artifacts
  ↓
Delete physical private file
  ↓
Delete/Detach pivot and source references
  ↓
Delete comparisons and processing runs in an explicit service order
  ↓
Delete database record
```

ينفذ ذلك عبر `DocumentDeletionService`/Jobs مع معالجة حالات الفشل. تمنع مفاتيح `RESTRICT` حذف `documents` أو الـRuns قبل تنظيف الموارد والعلاقات صراحة.

---

# 58. Reprocessing

عند إعادة معالجة وثيقة:

1. إبقاء الـselected run القديم وPoints الخاصة به قابلة للاستخدام أثناء إنشاء Run جديدة.
2. تحويل الحالة التجميعية إلى الحالة المناسبة من دون كسر المحادثات القائمة.
3. إرسال `ProcessDocumentJob` وإنشاء Run جديدة.
4. استخراج chunks جديدة وحفظها كـtemporary artifact.
5. بعد نجاح المعالجة والاختيار، Promotion idempotent للـRun الجديدة والتحقق من count.
6. داخل Transaction تحديث `selected_processing_run_id` وحالات الـRuns والوثيقة.
7. تنظيف Points/artifacts القديمة بعد نجاح التحويل فقط.

يجب ألا تتراكم duplicates لنفس الوثيقة، ولا يجوز حذف النسخة العاملة قبل ثبوت نجاح البديل.

---

# 59. Filament Dashboard

لوحة Filament مخصصة للمشرف.

## Resources

- Users.
- Documents.
- Conversations.
- Messages.

## Widgets

- Total Users.
- Total Documents.
- Ready Documents.
- Processing Documents.
- Failed Documents.
- Infected Uploads.
- Conversations.
- Messages.

---

# 60. Filament Document Details

يفضل عرض:

```text
Owner
Original Name
Type
MIME
Size
SHA-256
Status
Failure Reason
Total Pages
Total Chunks
Processed At
Created At
```

يمكن إضافة Action:

```text
Retry Processing
```

للملفات failed فقط.

---

# 61. Laravel Queue

يفضل Redis كـQueue backend.

Jobs الأساسية:

```text
ProcessDocumentJob
AskConversationJob
DeleteDocumentVectorsJob
```

ولا حاجة مبدئياً إلى عدد كبير من أنواع Jobs.

---

# 62. Queue Separation

إذا أصبح المشروع أكبر، يمكن لاحقاً فصل:

```text
documents
rag
maintenance
```

لكن في النسخة الأولى يمكن استخدام Queue واحدة، ثم التطوير عند الحاجة.

---

# 63. Queue Retries

يجب التفريق بين:

- خطأ مؤقت.
- خطأ دائم.

مثال خطأ مؤقت:

```text
Jina 429
FastAPI timeout
Temporary network failure
```

يمكن retry.

مثال خطأ دائم:

```text
corrupt document
unsupported format
invalid parsing output
```

لا يفيد retry المتكرر.

---

# 64. Timeouts

معالجة الوثائق قد تستغرق وقتاً.

لذلك:

- Browser لا ينتظر FastAPI مباشرة.
- Laravel request لا يعالج الملف synchronously.
- Queue job هو المسؤول عن العملية الطويلة.

واجهة المستخدم تعرض:

```text
Processing...
```

وتتابع الحالة عبر Livewire.

---

# 65. Error Handling

كل خدمة يجب أن تعيد Exception واضحة.

مثلاً:

```text
DocumentParsingException
EmbeddingException
QdrantException
RerankingException
GenerationException
```

Laravel لا يحتاج التفاصيل التقنية الداخلية كاملة للمستخدم.

المستخدم يرى رسالة مناسبة مثل:

```text
تعذر معالجة الوثيقة.
```

بينما logs تحتوي التفاصيل التقنية.

---

# 66. Logging

## Laravel Logs

تسجل:

```text
user_id
document_id
conversation_id
message_id
job id
request correlation id
```

## FastAPI Logs

تسجل:

```text
document_id
user_id
operation
duration
retrieved count
reranked count
errors
```

لا يجب تسجيل:

- API keys.
- Passwords.
- Tokens.
- محتوى حساس بدون حاجة.

---

# 67. Correlation ID

من المفيد أن يرسل Laravel:

```text
X-Request-ID
```

إلى FastAPI.

ويظهر نفس ID في logs في النظامين.

يساعد كثيراً في debugging.

---

# 68. Security Checklist

الحد الأدنى:

- Authentication.
- Email verification.
- CSRF protection.
- Authorization Policies.
- User ownership checks.
- Private file storage.
- MIME validation.
- Extension validation.
- File-size limits.
- ClamAV عند تفعيل Security Scan، مع explicit server-side configuration عند تعطيله وعدم وجود fallback تلقائي.
- SHA-256.
- Random stored filenames.
- FastAPI internal authentication.
- Qdrant not exposed publicly.
- Qdrant payload filtering.
- Redis not exposed publicly.
- MySQL not exposed publicly.
- secrets in `.env`.
- Rate limiting.
- logging without secrets.

---

# 69. Qdrant Network Security

Qdrant المحلي لا يجب أن يكون متاحاً مباشرة للإنترنت.

الصحيح:

```text
FastAPI → Qdrant
```

وليس:

```text
Internet → Qdrant
```

إذا استخدم Docker، Qdrant يكون على internal network.

---

# 70. Docker Infrastructure

التصميم المقترح:

```text
services:

laravel
nginx
mysql
redis
queue-worker
fastapi
qdrant
clamav
```

ويمكن لاحقاً إضافة:

```text
scheduler
```

---

# 71. Docker Networking

مثلاً:

```text
public network:
nginx

internal app network:
laravel
queue-worker
security-scan-worker
mysql
redis
fastapi
qdrant
```

وجود `fastapi` داخل الشبكة يخص Oracle Cloud Compose. في Local Demo تعمل FastAPI على Host ويصل إليها Docker عبر `host.docker.internal:8000`.

لا حاجة لتعريض:

```text
3306
6379
6333
```

للإنترنت في production.

---

# 72. Environment Variables في Laravel

مثال:

```text
RAG_DEPLOYMENT_MODE=cloud|local
AI_SERVICE_BASE_URL=http://fastapi:8000
AI_SERVICE_API_KEY=...
AI_SERVICE_TIMEOUT=600

QUEUE_CONNECTION=redis

DOCUMENT_SECURITY_SCAN_ENABLED=true
CLAMAV_SCAN_MODE=on_demand_cli
CLAMAV_SCAN_QUEUE=security-scan
CLAMAV_SCAN_CONCURRENCY=1
CLAMAV_SCAN_TIMEOUT=300
CLAMAV_SIGNATURE_DIR=/var/lib/clamav
CLAMAV_FAIL_CLOSED=true
```

في Local Demo تصبح `AI_SERVICE_BASE_URL=http://host.docker.internal:8000`، وتضاف إعدادات القفل العالمي المذكورة في 174.20. لا توجد `CLAMAV_HOST/PORT` لأن لا `clamd` دائم.

---

# 73. Environment Variables في FastAPI

مثال:

```text
RAG_DEPLOYMENT_MODE=cloud|local
QDRANT_URL=http://qdrant:6333
QDRANT_CLOUD_COLLECTION=rag_documents_cloud
QDRANT_HYBRID_LOCAL_COLLECTION=rag_documents_hybrid_local

JINAAI_API_KEY=...
LLAMA_CLOUD_API_KEY=...
HF_TOKEN=...

CLOUD_EMBED_MODEL=jina-embeddings-v3
CLOUD_EMBED_DIM=1024

CLOUD_RERANK_MODEL=jina-reranker-v2-base-multilingual
CLOUD_LLM_MODEL=Qwen/Qwen3.5-9B

LOCAL_EMBED_MODEL=BAAI/bge-m3
LOCAL_RERANK_MODEL=BAAI/bge-reranker-v2-m3
LOCAL_LLM_BASE_URL=http://127.0.0.1:11434/v1
LOCAL_LLM_MODEL=qwen3.5:4b

CHUNK_SIZE=800
CHUNK_OVERLAP=80

DENSE_CANDIDATES=12
SPARSE_CANDIDATES=12
RRF_TOP_K=12
RERANK_TOP_N=5
```

Cloud image لا يثبّت Local dependencies ولا يحتاج متغيرات Local. Local Host FastAPI يستخدم `QDRANT_URL=http://127.0.0.1:6333` لأن Qdrant منشورة على Loopback فقط.

---

# 74. Config Management

لا تستخدم:

```python
MODEL_NAME = "..." 
```

في عدة ملفات.

يجب أن تكون جميع الإعدادات ضمن:

```text
core/config.py
```

وتقرأ من Environment Variables.

---

# 75. Clean Code Principles

## Single Responsibility

مثال:

```text
EmbeddingService
```

لا يجب أن يقوم بالـReranking.

```text
DocumentSecurityService
```

لا يقوم بالتخزين في Qdrant.

## Separation of Concerns

```text
Laravel = application
FastAPI = AI
Qdrant = vectors
ClamAV = security scanning
MySQL = relational data
Redis = queue
```

## Dependency Direction

واجهة المستخدم لا تتصل مباشرة بـFastAPI.

```text
UI
 ↓
Laravel application services
 ↓
AiServiceClient
 ↓
FastAPI
```

---

# 76. API DTOs

يجب استخدام DTOs/Schemas واضحة بدلاً من arrays غير المنظمة.

في Laravel:

```text
ProcessDocumentResponseData
RagAnswerData
RagSourceData
```

وفي FastAPI:

```text
ProcessDocumentResponse
RagQueryRequest
RagQueryResponse
RagSource
```

هذا يمنع أخطاء التغيير في API contract.

---

# 77. Validation لنتيجة FastAPI

Laravel يجب ألا يفترض أن أي JSON من FastAPI صحيح.

يتم التحقق من:

```text
answer exists
sources is array
document_id types
score types
status
```

إذا كانت الاستجابة غير صحيحة:

```text
UnexpectedAiServiceResponseException
```

---

# 78. Testing Strategy

نقسم الاختبارات إلى عدة مستويات.

## Laravel Unit Tests

اختبار:

- Document services.
- Validators.
- DTOs.
- Policies.

## Laravel Feature Tests

اختبار:

- Upload.
- unauthorized document access.
- conversation creation.
- document selection.
- message creation.

## Queue Tests

اختبار:

- successful processing.
- failed processing.
- retries.
- status transitions.

---

# 79. ClamAV / Security Routing Tests

عند `DOCUMENT_SECURITY_SCAN_ENABLED=true` يجب اختبار:

1. Clean file.
2. Infected test file.
3. ClamAV unavailable.
4. Timeout.
5. Invalid response.

في حالة ClamAV unavailable مع الفحص مفعلاً:

> لا نرسل الملف إلى FastAPI ولا ننتقل تلقائياً إلى direct storage.

ويجب اختبار المسار القابل للضبط أيضاً:

6. الـDefault هو Security Scan enabled.
7. عند `DOCUMENT_SECURITY_SCAN_ENABLED=false` يمر الملف بعد Validation إلى permanent private storage من دون إنشاء Scan/Quarantine requirement.
8. تعطيل الفحص لا يعطل Validation أو ownership/duplicate safeguards.

---

# 80. FastAPI Unit Tests

اختبار كل Service منفرداً:

```text
PDF Loader
DOCX Loader
TXT Loader
Chunking
Embedding abstraction
Qdrant payload builder
Filters
Retrieval
Reranking
Context building
Prompt building
```

---

# 81. FastAPI Integration Tests

اختبار:

```text
document
 ↓
loader
 ↓
chunking
 ↓
vectors
 ↓
qdrant
```

ثم:

```text
question
 ↓
filtered retrieval
 ↓
sources
```

---

# 82. أهم Security Test

إنشاء:

```text
User A
Document A
```

و:

```text
User B
Document B
```

ثم جعل User A يحاول السؤال باستخدام `Document B`.

النتيجة يجب أن تكون:

```text
0 information from Document B
```

حتى لو حاول تعديل request يدوياً.

---

# 83. Source Accuracy Tests

يجب اختبار أن:

- المصدر المعروض يعود إلى Document الصحيح.
- الصفحة صحيحة في PDF.
- section صحيح في DOCX.
- chunk_index صحيح.
- source_number في النص يطابق المصدر المحفوظ.

---

# 84. RAG Quality Evaluation

يمكن إعداد مجموعة أسئلة لكل وثيقة.

تصنيف الأسئلة:

- Direct factual.
- Cross-section.
- Semantic.
- Keyword-sensitive.
- Arabic.
- English.
- Mixed Arabic/English.
- Insufficient context.

ثم قياس:

- Retrieval relevance.
- Reranking quality.
- Answer faithfulness.
- Source correctness.

---

# 85. Performance Metrics

يفضل تسجيل:

```text
parsing duration
chunking duration
embedding duration
qdrant upload duration
retrieval duration
reranking duration
LLM duration
total question duration
```

هذه البيانات مفيدة جداً في تقرير الماجستير.

---

# 86. مراحل التنفيذ الأصلية — سجل تاريخي غير نشط

> [!WARNING]
> هذا القسم حتى نهاية القسم 100 يحفظ التسلسل الأولي فقط ولا يستخدم للتنفيذ أو لتسمية المهام. خريطة المهام الوحيدة النشطة هي 174.16 كما تنعكس حرفياً في `PROJECT_RAG_EXECUTION_PROGRESS.md`. عند اختلاف رقم أو نطاق أو Definition of Done، يحذف الالتباس لصالح 174.16–174.20 وملف التقدم.

> **قاعدة التنفيذ:** كل بند مرقّم مثل `A1`, `A2`, `B1` ... يمثل Task مستقلة افتراضياً ويجب تنفيذها في Chat مستقل، إلا إذا تم تسجيل قرار صريح في ملف التقدم بدمج مهمتين صغيرتين دون الإضرار بالتتبع.
>
> **نقطة البداية الرسمية:** `A1 — إنشاء Laravel Application`.

---

# المرحلة A — تأسيس المشروع

## A1. إنشاء Laravel application

- Laravel.
- Blade.
- Livewire.
- Flux.
- Tailwind.
- JavaScript.

## A2. إعداد Authentication

- Fortify.
- register.
- login.
- logout.
- forgot password.
- email verification.

## A3. إعداد MySQL

## A4. إعداد Redis

## A5. إعداد Queue

### معيار انتهاء المرحلة

المستخدم يستطيع التسجيل والدخول، والـQueue تعمل بنجاح.

---

# المرحلة B — Documents Domain

## B1. إنشاء documents migration

## B2. Document model

## B3. DocumentStatus enum

## B4. DocumentPolicy

## B5. صفحة Documents

## B6. Upload validation

دعم:

```text
pdf
docx
txt
```

## B7. Private storage

## B8. SHA-256

### معيار انتهاء المرحلة

المستخدم يستطيع رفع ملف صالح ويظهر في قائمة ملفاته بدون معالجة AI بعد.

---

# المرحلة C — ClamAV

## C1. إضافة Security Scan Worker مع ClamAV CLI وتواقيع دائمة

## C2. DocumentSecurityService

## C3. Temporary upload flow

## C4. scan clean files

## C5. reject infected files

## C6. status transitions

```text
pending → scanning → queued
```

أو:

```text
pending → scanning → infected
```

## C7. Tests

### معيار انتهاء المرحلة

لا يمكن لملف لم يجتز `clamscan` بنجاح الوصول إلى Queue الخاصة بمعالجة AI. لا يوجد `clamd` دائم أو Docker socket، وفي Local Demo يمنع القفل العالمي تداخل Scan مع أي Local AI Stage.

---

# المرحلة D — FastAPI Foundation

## D1. إنشاء مشروع FastAPI

## D2. config

## D3. logging

## D4. internal API security

## D5. health endpoint

## D6. schemas

## D7. structured exceptions

### معيار انتهاء المرحلة

Laravel يستطيع الاتصال بـFastAPI داخلياً والحصول على Health response.

---

# المرحلة E — Qdrant Local

## E1. تشغيل Qdrant محلياً

## E2. إضافة Docker volume

حتى لا تضيع البيانات عند restart.

## E3. إنشاء `rag_documents_cloud` و`rag_documents_hybrid_local`

## E4. Dense vector config

```text
1024 + COSINE
```

## E5. Sparse vector config

```text
BM25 + IDF
```

## E6. Payload indexes إذا كانت مطلوبة للأداء

خصوصاً:

```text
user_id
document_id
processing_run_id
processing_profile
```

## E7. اختبار الاتصال

### معيار انتهاء المرحلة

FastAPI يستطيع إدخال واسترجاع وحذف Points من Qdrant المحلي.

---

# المرحلة F — Document Loaders

## F1. Base loader interface

## F2. PDF Loader

يحافظ على مسار LlamaParse من الـNotebook.

## F3. DOCX Loader

## F4. TXT Loader

## F5. Normalized document schema

## F6. Loader tests

### معيار انتهاء المرحلة

أي ملف من الأنواع الثلاثة يتحول إلى Documents موحدة قابلة للـChunking.

---

# المرحلة G — Chunking + Embeddings

## G1. ChunkingService

الإعداد المبدئي:

```text
800 / 80
```

## G2. EmbeddingService

```text
jina-embeddings-v3
1024
```

## G3. Passage/query separation

## G4. Batch processing

## G5. Retry/rate limit handling

## G6. Metadata propagation

### معيار انتهاء المرحلة

كل Document ينتج chunks لها vectors صحيحة وmetadata كاملة.

---

# المرحلة H — Qdrant Ingestion

## H1. Point builder

## H2. UUID generation

## H3. Dense vectors

## H4. BM25 sparse representation

## H5. upload batches

## H6. count verification

## H7. delete by user/document

## H8. reprocessing without duplicates

### معيار انتهاء المرحلة

يمكن معالجة وثيقة كاملة، ثم رؤية جميع chunks الخاصة بها داخل Qdrant.

---

# المرحلة I — Laravel ↔ FastAPI Document Processing

## I1. AiServiceClient

## I2. `POST /documents/process`

## I3. ProcessDocumentJob

## I4. status handling

## I5. حفظ `total_chunks` على ProcessingRun

## I6. حفظ `total_pages` على ProcessingRun

## I7. حفظ `failure_reason` على ProcessingRun وإسقاط حالة Document التجميعية

## I8. UI status refresh

### معيار انتهاء المرحلة

المستخدم يرفع PDF/DOCX/TXT، يمر الملف بـClamAV، ثم Queue، ثم FastAPI، ثم يصبح Ready.

---

# المرحلة J — Conversations

## J1. conversations migration

## J2. conversation_document

## J3. messages

## J4. message_sources

## J5. ConversationPolicy

## J6. create conversation

## J7. list conversations

## J8. select documents

## J9. only ready documents

### معيار انتهاء المرحلة

المستخدم يستطيع إنشاء محادثة واختيار عدة وثائق يملكها.

---

# المرحلة K — Retrieval

## K1. Query embedding

## K2. security filter

```text
user_id
document_ids
```

## K3. Dense retrieval

## K4. BM25 retrieval

## K5. RRF

## K6. top 12

## K7. debug endpoint/helper في development فقط

### معيار انتهاء المرحلة

السؤال يسترجع المقاطع ذات الصلة فقط من Documents المختارة للمستخدم نفسه.

---

# المرحلة L — Reranking

## L1. Jina Reranker client

## L2. convert Qdrant points إلى reranker nodes

## L3. rerank

## L4. top 5

## L5. preserve metadata

### معيار انتهاء المرحلة

أفضل خمسة chunks بعد reranking جاهزة لبناء السياق.

---

# المرحلة M — Generation

## M1. ContextService

## M2. Prompt file

## M3. GenerationService

## M4. Qwen via HF

## M5. sources array

## M6. insufficient-information behavior

### معيار انتهاء المرحلة

FastAPI يعيد Answer + Sources بشكل ثابت ومنظم.

---

# المرحلة N — Laravel Chat Flow

## N1. إرسال سؤال

## N2. save user message

## N3. assistant placeholder

## N4. AskConversationJob

## N5. FastAPI request

## N6. save answer

## N7. save sources

## N8. Livewire refresh

## N9. error display

### معيار انتهاء المرحلة

المستخدم يجري محادثة كاملة مع مجموعة وثائق ويشاهد المصادر.

---

# المرحلة O — Filament

## O1. UserResource

## O2. DocumentResource

## O3. ConversationResource

## O4. dashboard widgets

## O5. failed documents filters

## O6. infected uploads monitoring

## O7. retry action

### معيار انتهاء المرحلة

المشرف يستطيع مراقبة حالة النظام والوثائق دون التعامل مع قاعدة البيانات يدوياً.

---

# المرحلة P — الاختبارات الأمنية

## P1. ownership

## P2. IDOR attempts

## P3. Qdrant leakage tests

## P4. ClamAV failures

## P5. MIME spoofing

## P6. invalid file extensions

## P7. file size

## P8. FastAPI API key

## P9. private file download authorization

### معيار انتهاء المرحلة

لا يستطيع مستخدم الوصول أو البحث أو التنزيل من وثائق مستخدم آخر.

---

# المرحلة Q — الاختبارات النهائية

## Q1. PDF

- textual.
- Arabic.
- tables.
- scanned content عند توفره.

## Q2. DOCX

- paragraphs.
- headings.
- tables.

## Q3. TXT

- Arabic UTF-8.
- English.
- mixed text.

## Q4. RAG questions

## Q5. queue failures

## Q6. service restarts

## Q7. Qdrant persistence

### معيار انتهاء المرحلة

النظام يعمل End-to-End بعد إعادة تشغيل الخدمات ولا تضيع vectors.

---

# 87. ترتيب التنفيذ المختصر الأصلي — غير مرجعي

الترتيب الذي أنصح باتباعه بدون القفز بين المراحل:

```text
1. Laravel foundation
2. Auth
3. Documents DB
4. Upload UI
5. Private Storage
6. ClamAV
7. Queue
8. FastAPI foundation
9. Qdrant Local
10. PDF/DOCX/TXT loaders
11. Chunking
12. Embeddings
13. Qdrant ingestion
14. Laravel → FastAPI processing
15. Conversations DB
16. Document selection
17. Hybrid Retrieval
18. Qdrant security filters
19. Reranker
20. Context + Prompt
21. LLM
22. Laravel Chat Job
23. Sources UI
24. Filament
25. Security tests
26. RAG evaluation
27. Performance evaluation
28. Documentation
```

---

# 88. Definition of Done للوثيقة

تعتبر الوثيقة جاهزة عندما:

- يملكها المستخدم الصحيح.
- اجتازت validation.
- استوفت Security policy: اجتازت ClamAV عندما يكون الفحص مفعلاً، أو كان الفحص معطلاً صراحةً من Configuration موثوقة.
- موجودة في Private Storage.
- FastAPI استطاع قراءتها.
- تم استخراج محتواها.
- تم Chunking.
- تم Embedding.
- تم رفع Dense + Sparse representations.
- يوجد `selected_processing_run_id` يعود إلى الوثيقة نفسها.
- الـselected Run حالتها `indexed` و`total_chunks > 0` و`vector_count` مطابق.
- تحمل جميع نقاط Qdrant `user_id` و`document_id` و`processing_run_id` و`processing_profile`.
- `documents.status = ready` كنتيجة تجميعية، ولا تخزن حقول العدادات أو الفشل في `documents`.

---

# 89. Definition of Done للسؤال

يعتبر السؤال ناجحاً عندما:

- المستخدم authenticated.
- Conversation له.
- document_ids له.
- كل Documents ready.
- تم حفظ user message.
- تم إرسال Job.
- FastAPI طبق user/document filters.
- تم Hybrid Retrieval.
- تم Reranking.
- تم بناء Context.
- LLM أعاد جواباً.
- تم حفظ answer.
- تم حفظ sources.
- ظهرت النتيجة للمستخدم.

---

# 90. قواعد غير قابلة للتفاوض

## قاعدة 1

لا يصل ملف إلى FastAPI إلا بعد استيفاء Security policy الحالية: Clean موثق إذا كان `DOCUMENT_SECURITY_SCAN_ENABLED=true`، أو Validation ناجحة + permanent private storage إذا كان الفحص معطلاً صراحةً. لا يوجد bypass تلقائي بسبب فشل ClamAV.

## قاعدة 2

لا يخزن ملف خاص داخل public storage.

## قاعدة 3

لا يجري Qdrant query بدون `user_id + document_id + processing_run_id`.

## قاعدة 4

عند السؤال يجب تحديد document targets المخولة مع Profile/Collection الخاصة بكل selected Run؛ لا تكفي `document_ids` منفردة.

## قاعدة 5

Laravel هو مصدر الحقيقة بالنسبة للمستخدم والملكية.

## قاعدة 6

Qdrant يخزن المعرفة المسترجعة، وليس Users أو Conversations.

## قاعدة 7

FastAPI لا يدير Authentication للمستخدم النهائي.

## قاعدة 8

لا نستخدم `RESET_COLLECTION=True` في التطبيق.

## قاعدة 9

أي Reprocess يجب أن يمنع تكرار vectors القديمة.

## قاعدة 10

DOCX/TXT لا يعطى لهما رقم صفحة وهمي.

---

# 91. الشكل النهائي للـDocument Pipeline

```text
                 User Upload
                     │
                     ▼
             Laravel Validation
                     │
                     ▼
          Trusted Security Routing
                     │
          ┌──────────┴──────────┐
          │                     │
          ▼                     ▼
 Scan enabled (default)   Scan disabled explicitly
          │                     │
          ▼                     │
 Private Quarantine             │
          │                     │
 security-scan queue (1)        │
          │                     │
 short-lived clamscan           │
     ┌────┴────┐                │
     ▼         ▼                │
   Clean   Infected/Failure     │
     │         │                │
     ▼         ▼                │
 Promotion  Fail closed         │
     │                          │
     └──────────────┬───────────┘
                    ▼
          Permanent Private Storage
                    │
                    ▼
              Document Record
                    │
                    ▼
               Laravel Queue
                    │
                    ▼
                  FastAPI
                    │
                    ▼
          DocumentLoaderService
                    │
             ┌──────┼───────┐
             ▼      ▼       ▼
            PDF    DOCX     TXT
             │      │       │
             └──────┼───────┘
                    ▼
              Normalized Docs
                    │
                    ▼
                Chunking
                    │
                    ▼
        Trusted Processing Profile
           ┌────────┼───────────────┐
           ▼        ▼               ▼
        cloud   hybrid_local      compare
           │        │          both sequentially
           └────────┴───────────────┘
                    │
                    ▼
     Embeddings + Sparse + Reranking
                    │
                    ▼
       Promote selected run only to Qdrant
                    │
                    ▼
          Ready / Awaiting Selection
                    │
                    ▼
                  Laravel
```

`DOCUMENT_SECURITY_SCAN_ENABLED=true` هو Default. المسار الأيمن لا يستخدم تلقائياً عند تعطل ClamAV؛ لا يعمل إلا إذا عطّل المشغّل الفحص صراحةً.

---

# 92. الشكل النهائي للـQuestion Pipeline

```text
                 User Question
                      │
                      ▼
                   Laravel
                      │
                      ▼
             Authorization
                      │
                      ▼
        Authorized document targets
 user_id + document_id + selected_processing_run_id
                      │
                      ▼
            Save User Message
                      │
                      ▼
          AskConversationJob
                      │
                      ▼
                   FastAPI
                      │
                      ▼
        Group by selected run profile
                      │
          ┌───────────┴───────────┐
          ▼                       ▼
      Cloud group          Hybrid Local group
   Jina query/reranker      BGE query/reranker
          │                       │
          └───────────┬───────────┘
                      ▼
          rank-based cross-profile fusion
                      │
                      ▼
                 Top 5 Chunks
                      │
                      ▼
                Build Context
                      │
                      ▼
                    Prompt
                      │
                      ▼
        Provider Registry by trusted profile
          Cloud HF or Local Ollama
                      │
                      ▼
              Answer + Sources
                      │
                      ▼
                  Laravel
                      │
          ┌───────────┴───────────┐
          ▼                       ▼
    Save Message            Save Sources
          │                       │
          └───────────┬───────────┘
                      ▼
                   Livewire
                      │
                      ▼
                    User
```

---

# 93. الحدود بين الأنظمة

## Laravel يعرف

```text
Who is the user?
Which documents belong to them?
Which documents are selected?
What conversation is active?
What messages exist?
```

## FastAPI يعرف

```text
How to parse?
How to chunk?
How to embed?
How to search?
How to rerank?
How to generate?
```

## Qdrant يعرف

```text
Vectors
Chunks
Metadata
```

## ClamAV يعرف

```text
Is the uploaded file safe enough to continue?
```

هذا الفصل هو أساس قابلية الصيانة والتوسع.

---

# 94. النتيجة المعمارية النهائية

التطبيق النهائي يتكون من أربع طبقات أساسية:

```text
Presentation
Blade + Livewire + Flux + Tailwind + JavaScript

        ↓

Application
Laravel
Auth + Documents + Conversations + Messages + Queue + Filament

        ↓

AI
FastAPI
Loaders + Chunking + Embeddings + Retrieval + Reranking + Generation

        ↓

Infrastructure
MySQL + Redis + ClamAV CLI/signatures + Qdrant + Private Storage
```

في Cloud profile تستخدم الخدمات الخارجية:

```text
LlamaParse
Jina Embeddings
Jina Reranker
Hugging Face / Qwen
```

وفي `hybrid_local` تستخدم BGE-M3 وBGE Reranker وOllama محلياً، مع بقاء LlamaParse Cloud للـParsing.

بينما أصبح:

```text
Qdrant = Local
```

والملفات المدعومة:

```text
PDF + DOCX + TXT
```

والفحص الأمني:

```text
Default: Validation → Quarantine → ClamAV → Clean → Permanent Storage → FastAPI
Explicit disabled mode: Validation → Permanent Storage → FastAPI
```

تعطيل ClamAV ليس fallback تلقائياً عند الفشل.

---

# 95. أولويات التصميم

عند تنفيذ المشروع يجب إعطاء الأولوية بهذا الترتيب:

1. Security.
2. Correct user isolation.
3. Correct document processing.
4. Retrieval quality.
5. Source accuracy.
6. Error handling.
7. Maintainability.
8. Performance.
9. UI polish.
10. Advanced optimizations.

أي تحسين في السرعة أو الواجهة لا يجب أن يأتي على حساب عزل بيانات المستخدمين أو صحة المصادر.

---

# 96. ملاحظة حول مفهوم "Local"

في Oracle، Qdrant والملفات والبنية الأساسية ذاتية الاستضافة، لكن Profile المنشورة تستخدم خدمات خارجية:

```text
LlamaParse Cloud
Jina API
Hugging Face Router
```

في Local Demo تصبح Embeddings وReranking وGeneration محلية عبر BGE/Ollama، لكن LlamaParse Cloud يبقى خارجياً.

لذلك هذه النسخة ليست Offline بالكامل.

إذا أصبح الهدف لاحقاً أن يكون النظام Offline بالكامل، يستبدل LlamaParse أيضاً بParser/OCR محلي بقرار مستقل، مع الحفاظ على Interfaces والـServices.

---

# 97. الخلاصة التنفيذية

النسخة المقترحة ليست إعادة كتابة للـNotebook، بل تحويل منظم له من Prototype إلى Application Architecture.

يتم الاحتفاظ بقلب الـRAG مع Provider-specific stages:

```text
Parsing
→ Chunking
→ Jina Embeddings أو BGE-M3
→ Dense + BM25
→ Qdrant
→ Hybrid Search
→ RRF
→ Jina Reranker أو BGE Reranker
→ Context
→ Cloud Qwen أو Ollama qwen3.5:4b
→ Answer + Sources
```

مع إضافة متطلبات التطبيق الحقيقي:

```text
Laravel
Authentication
Authorization
PDF/DOCX/TXT
Configurable ClamAV security gate
Private Storage
Queue
Conversations
Document Selection
Per-user isolation
Qdrant Local
Filament
Logging
Testing
```

وبهذا يصبح المشروع بسيطاً من حيث البنية العامة، لكنه نظيف وقابل للتوسع والصيانة ومناسب للتحويل لاحقاً إلى نظام RAG إنتاجي أكثر تقدماً.


---

# 98. إستراتيجية LLM النهائية في المشروع

المشروع يدعم رسمياً مسارين في نفس الكود:

```text
                         RagGenerationService
                                  │
                                  ▼
                           LLMProviderRegistry
                                  │
                    ┌─────────────┴─────────────┐
                    │                           │
                    ▼                           ▼
              CloudLLMProvider            LocalLLMProvider
                    │                           │
                    ▼                           ▼
            Hugging Face Router           Host-native Ollama
                    │                           │
                    ▼                           ▼
             Qwen3.5-9B                   qwen3.5:4b
```

Online يسجل Cloud provider فقط. Local Demo يسجل Providers المتاحة ويختارها حسب Processing profile، وقد يستخدم Cloud وLocal بالتتابع في Compare. لا يوجد `LLM_PROVIDER` عالمي في العقد النهائي، لأن الاعتماد عليه سيجعل Compare غير قابل للتمثيل.

ويمكن لاحقاً إضافة Providers أخرى بدون تعديل منطق الـRAG.

---

# 99. سياسة Fallback بين Local وCloud

في النسخة الأولى لا يفعّل fallback تلقائي بدون تحكم، لأن ذلك قد يرسل سياقاً كان مفترضاً أن يبقى محلياً إلى Cloud Provider.

الوضع الافتراضي:

```text
ALLOW_CLOUD_FALLBACK=false
```

إذا كان Processing profile:

```text
hybrid_local
```

وفشل Local LLM، تفشل عملية الإجابة وتظهر رسالة مناسبة بدلاً من إرسال السياق تلقائياً إلى Cloud.

يمكن مستقبلاً جعل fallback اختيارياً بقرار إداري صريح.

---

# 100. المقارنة العملية بين المسارين

| البند | Cloud LLM | Self-hosted Local LLM |
|---|---|---|
| سهولة النشر | عالية | متوسطة |
| استهلاك RAM على السيرفر | منخفض | مرتفع |
| استهلاك CPU على السيرفر | منخفض | مرتفع |
| سرعة الإجابة على Free VM | أفضل عادة | أبطأ |
| الخصوصية | السياق يخرج إلى مزوّد خارجي | السياق يبقى داخل البنية |
| تكلفة كل Request | تعتمد على Provider | لا توجد تكلفة API مباشرة |
| الاعتماد على الإنترنت | نعم | لا بعد تنزيل النموذج |
| تشغيل نموذج أكبر | أسهل | يحتاج Hardware أقوى |
| مناسب لـOracle Free | معتمد | غير مسموح في الخطة النهائية |
| النموذج | Qwen3.5-9B عبر Provider | Ollama `qwen3.5:4b` على Mac/ASUS |

---

# 101. الهدف من خطة النشر المجاني

الهدف هو نشر نسخة Online:

```text
Demo / Master Thesis / Small-scale POC
```

بأقل تكلفة ممكنة، مع المحافظة على نفس Architecture.

الخطة المرجعية:

```text
Oracle Cloud Always Free
Ubuntu ARM64
Docker Engine
Docker Compose
```

وتشغيل Cloud-only:

```text
Nginx
Laravel
Laravel Queue Worker
Security Scan Worker مع `clamscan` عند الطلب
FastAPI
MySQL
Redis
Qdrant Local
Scheduled `UpdateClamAvSignaturesJob` على Queue الأمنية نفسها
```

وتستخدم الخدمات الخارجية للمراحل الآتية:

```text
LlamaParse Cloud
Jina Embeddings API
Jina Reranker API
Hugging Face Router / Qwen3.5-9B
```

لا يشغّل Oracle أي Local embedding أو reranker أو LLM. يعمل `hybrid_local` و`compare` فقط في Local Demo منفصل على Mac أو ASUS وفق 174.19 و174.20.

---

# 102. حدود Oracle Always Free التي تعتمد عليها الخطة

بحسب توثيق Oracle الرسمي المحدث في 2026، موارد Ampere A1 المجانية تعادل إجمالاً:

```text
2 OCPU
12 GB RAM
```

ضمن:

```text
1,500 OCPU hours / month
9,000 GB-hours memory / month
```

والـBlock Storage المجاني يصل إجمالاً إلى:

```text
200 GB
```

بين Boot Volumes وBlock Volumes، مع عدد محدود من Block Volume Backups المجانية.

> يجب مراجعة Oracle Free Tier قبل أي نشر فعلي لأن الحدود والسياسات يمكن أن تتغير.

---

# 103. قيود Oracle Free المهمة

## 103.1 Home Region

Always Free compute يجب إنشاؤه في:

```text
Home Region
```

لذلك اختيار المنطقة أثناء إنشاء الحساب مهم.

## 103.2 Out of Host Capacity

قد تظهر رسالة:

```text
Out of host capacity
```

عند عدم توفر Ampere A1 في المنطقة/Availability Domain. هذه مشكلة توفر موارد وليست خطأ في المشروع.

## 103.3 Idle Instance Reclamation

Oracle تنص على أن Always Free Compute الخامل قد يتم استرداده.

بحسب الوثائق، يمكن اعتبار VM خاملة عندما تكون خلال فترة 7 أيام ضمن شروط استخدام منخفض للـCPU والشبكة والذاكرة.

لذلك Always Free ممتاز للـDemo والـPOC، لكنه ليس بديلاً عن Production SLA مدفوع.

---

# 104. اختيار الـVM

المقترح:

```text
Shape:
VM.Standard.A1.Flex

OCPU:
2

Memory:
12 GB

OS:
Ubuntu LTS ARM64
```

ونستخدم VM واحدة لتبسيط الشبكة والإدارة.

---

# 105. التخزين

يمكن بدء المشروع مثلاً بـ:

```text
Boot Volume: 100 GB
```

وترك جزء من الحد المجاني للتوسع أو Backup strategy.

داخل السيرفر يجب أن تكون البيانات الدائمة خارج Container filesystem المؤقت.

Volumes المطلوبة:

```text
mysql_data
redis_data
qdrant_data
clamav_db
laravel_private_storage
```

---

# 106. المكونات التي تعمل داخل Docker

```text
┌──────────────── Oracle VM ────────────────┐
│                                          │
│  nginx                                   │
│  laravel                                 │
│  queue-worker                            │
│  security-scan-worker + clamscan         │
│  fastapi cloud-only                      │
│  mysql                                   │
│  redis                                   │
│  qdrant                                  │
│                                          │
└──────────────────────────────────────────┘
```

---

# 107. لماذا Docker Compose مناسب هنا؟

النسخة المطلوبة لا تحتاج Kubernetes.

Docker Compose يوفر:

- Service isolation.
- internal DNS.
- restart policies.
- persistent volumes.
- environment configuration.
- reproducible deployment.
- سهولة نقل المشروع إلى VPS آخر.

Qdrant يدعم رسمياً `AArch64/arm64`.

---

# 108. Docker Networks

نستخدم شبكتين:

```text
public
internal
```

## public

تضم:

```text
nginx
laravel
```

## internal

تضم:

```text
laravel
    queue-worker
    security-scan-worker
    fastapi
    mysql
    redis
    qdrant
```

ولا يتم نشر Ports الداخلية للعالم الخارجي.

---

# 109. المنافذ

المتاح للعالم الخارجي فقط:

```text
80
443
```

SSH:

```text
22
```

ويفضل تقييده قدر الإمكان.

لا ننشر Public:

```text
3306 MySQL
6379 Redis
6333 Qdrant
6334 Qdrant gRPC
8000 FastAPI
```

---

# 110. مسار الاتصال

```text
Internet
   │
   ▼
Nginx :443
   │
   ▼
Laravel
   │
   ├── MySQL
   ├── Redis
   ├── Security Scan Worker
   │      └── clamscan + persistent signatures
   │
   └── FastAPI
          │
          ├── Qdrant
          ├── LlamaParse
          ├── Jina
          └── Hugging Face Router
```

---

# 111. Docker Compose Conceptual Structure

```yaml
services:

  nginx:
    depends_on:
      - laravel

  laravel:
    depends_on:
      - mysql
      - redis

  queue-worker:
    depends_on:
      - redis
      - fastapi

  security-scan-worker:
    # Laravel worker image + ClamAV CLI + freshclam
    # queue: security-scan, concurrency: 1
    volumes:
      - laravel_private_storage:/app/storage/app/private
      - clamav_db:/var/lib/clamav

  fastapi:
    depends_on:
      - qdrant

  mysql:
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    volumes:
      - redis_data:/data

  qdrant:
    volumes:
      - qdrant_data:/qdrant/storage

```

لا توجد خدمة `clamd` أو `local-llm` أو updater دائمة في Compose الخاصة بـOracle. يملك `security-scan-worker` تطبيق Laravel و`clamscan/freshclam` مع وصول مقيد إلى Private quarantine وsignature volume. يطلق Laravel Scheduler مهمة `UpdateClamAvSignaturesJob` على Queue `security-scan` نفسها، فتتسلسل تلقائياً مع عمليات الفحص ولا تحتاج Docker socket.

---

# 112. Profile Online الوحيد — Deployment مع Cloud Providers

هذا هو الخيار الموصى به للنسخة المنشورة على Oracle Always Free.

تشغيل:

```text
Nginx
Laravel
Queue
Security Scan Worker
FastAPI Cloud-only
MySQL
Redis
Qdrant
```

ولا نثبّت أو نشغّل:

```text
Ollama
llama.cpp
BGE-M3
BGE Reranker
Torch
Transformers
Local model weights
```

إعداد البيئة:

```text
RAG_DEPLOYMENT_MODE=cloud
```

ثم:

```text
FastAPI Cloud-only
  ├── LlamaParse Cloud
  ├── Jina Embeddings/Reranker
  └── Hugging Face Router → Qwen/Qwen3.5-9B
```

---

# 113. لماذا Cloud-only هو المسار الوحيد على Oracle Free؟

لأن موارد Oracle تبقى مخصصة إلى:

```text
`clamscan` عند الطلب فقط
Qdrant
MySQL
Laravel
FastAPI
Queue
Redis
```

بدلاً من استهلاك عدة GB إضافية في BGE أو LLM محلي. لا تتداخل عملية الفحص مع Model محلي لأن Cloud deployment لا يملك نماذج محلية أصلاً.

هذا يعطي:

- Stability أفضل.
- أقل احتمال OOM.
- أسرع Response.
- مساحة أكبر لـQdrant.
- قدرة أفضل على معالجة Jobs.

---

# 114. تكلفة Cloud LLM

بحسب توثيق Hugging Face الحالي، حساب Free يحصل على رصيد شهري محدود لـInference Providers.

القيمة الحالية وقت تحديث الخطة:

```text
$0.10 / month
```

وهي قابلة للتغيير.

لذلك:

```text
Cloud LLM != guaranteed $0 forever
```

البنية التحتية قد تبقى ضمن Free Tier، لكن استهلاك الـCloud LLM يمكن أن يتجاوز الرصيد المجاني.

---

# 115. الفصل النهائي بين Online وLocal Demo

لا يوجد Local LLM profile على Oracle. النشر العام ثابت على:

```text
RAG_DEPLOYMENT_MODE=cloud
```

أما `hybrid_local` و`compare` فيعملان حصراً على Mac أو ASUS ضمن Local Demo منفصل. لا يتم تحويل Oracle إلى Local Mode بتغيير Environment، ولا تُنقل أوزان Ollama/BGE إليه.

---

# 116. نموذج Local Demo المعتمد خارج Oracle

يستخدم Local Demo:

```text
Ollama model: qwen3.5:4b
FastAPI: Host-native
Ollama: Host-native
Infrastructure: Docker Desktop
```

لا يعتمد المشروع `llama.cpp` أو `Qwen3-4B-GGUF` في Baseline النهائي. تفاصيل دورة الحياة والذاكرة في 174.19 و174.20.

---

# 117. Endpoint الـLocal Demo

على الجهاز المضيف:

```text
Host FastAPI → http://127.0.0.1:11434/v1
Docker Laravel/Queue → http://host.docker.internal:8000
```

هذه العناوين تخص Docker Desktop المحلي فقط ولا تستخدم في Oracle Cloud.

---

# 118. إعداد Context للـLocal Demo

القيم المرجعية:

```text
LOCAL_LLM_CONTEXT_SIZE=8192
RERANK_TOP_N=5
MAX_GENERATION_TOKENS=700
```

تثبت أو تعدل عبر Q8 بعد قياس الجودة والذاكرة على الجهازين؛ لا تطبق على Cloud deployment.

---

# 119. Concurrency للـLocal Demo

```text
LOCAL_AI_MAX_CONCURRENCY=1
LOCAL_AI_QUEUE_CONCURRENCY=1
OLLAMA_NUM_PARALLEL=1
```

تعمل مراحل BGE وReranker وQwen بالتتابع. لا تؤثر هذه الحدود في Cloud-only Oracle الذي لا يحمل نماذج محلية.

---

# 120. إدارة الموارد المحلية

تطبق سياسة `single_active` والتحرير بعد كل Stage وفق 174.20. لا يستخدم Local Demo Oracle VM، ولا يُدّعى أنه Production متعدد المستخدمين.

---

# 121. Resource Budget تقريبي — Oracle Cloud-only

هذه أرقام أولية لإجمالي الحمل وليست ضماناً:

```text
Idle infrastructure بلا scan    3–6 GB
أثناء clamscan واحدة            6–10 GB total
الحد المتاح على VM المقترحة     12 GB
```

- لا توجد أوزان BGE أو Qwen في Oracle RAM.
- Queue `security-scan` ذات concurrency=1.
- إذا اقترب Qdrant أو MySQL من الحد، يمنع رفع التوازي وتنفذ Q8/DPL-23 قياساً فعلياً قبل اعتماد النشر.
- Swap شبكة أمان محدودة وليست معيار نجاح للأداء.

---

# 122. Resource Budget — Local Demo

يعتمد Local Demo الأرقام والمعايير المحددة في 174.20.9 على أجهزة 16 GB. لا تجمع Peaks لأن `clamscan` وBGE-M3 وReranker وQwen مراحل متبادلة وليست أحمالاً متزامنة.

---

# 123. Qdrant في النشر المجاني

يتم تشغيل Qdrant محلياً مع Persistent Volume:

```text
qdrant_data:/qdrant/storage
```

يجب عدم وضع البيانات على Container filesystem المؤقت.

Qdrant يدعم ARM64 رسمياً.

---

# 124. حماية Qdrant

لا ننشر:

```text
6333
6334
```

للإنترنت.

Qdrant يكون على Internal Docker Network.

ويمكن إضافة Qdrant API key للدفاع الإضافي.

---

# 125. Qdrant Payload Security

كل Point يجب أن يحتوي:

```text
user_id
document_id
```

وكل Retrieval يجب أن يطبق:

```text
user_id = current_user
AND
document_id IN selected_documents
```

هذه القاعدة لا تتغير بعد Deployment.

---

# 126. تخزين Qdrant على Disk

إذا بدأ عدد الـvectors يكبر وأصبحت RAM محدودة، يمكن ضبط Collection لاستخدام On-Disk storage حيثما يلزم.

هذا قد يزيد latency لكنه مناسب أكثر لبيئة Free Tier محدودة RAM.

---

# 127. ClamAV في Deployment

في الـReference deployment يبقى `DOCUMENT_SECURITY_SCAN_ENABLED=true` افتراضياً. يمكن تعطيله فقط بقرار تشغيلي صريح؛ عند التعطيل لا يبدأ Security Scan Worker لمسار الملفات الجديدة ولا يعتبر ذلك استجابة لفشل الفاحص.

عند التفعيل لا يعمل `clamd` كخدمة دائمة. يملك `security-scan-worker` ClamAV CLI ويشغّل Process قصيرة العمر:

```text
Private quarantine
  → security-scan queue (concurrency=1)
  → clamscan
  → clean / infected / scan_failed
  → process exits
```

فقط الملفات النظيفة تنتقل إلى:

```text
Queue → FastAPI
```

قاعدة تواقيع ClamAV تحفظ في Volume:

```text
clamav_db
```

يحدّثها `UpdateClamAvSignaturesJob` دورياً عبر `freshclam` على Queue `security-scan` نفسها، لذلك لا يتزامن Update مع Scan. يملك العامل وصولاً إلى مسار Quarantine والـsignature volume فقط ولا يملك Docker socket. Missing أو stale signatures وTimeout وأي Process failure كلها Fail-closed.

---

# 128. Failure Policy لـClamAV

تنطبق هذه السياسة عندما يكون `DOCUMENT_SECURITY_SCAN_ENABLED=true`.

إذا كان ClamAV:

```text
unavailable
```

لا نتعامل مع الملف على أنه Clean.

نستخدم:

```text
fail closed
```

أي:

```text
scan failed
   ↓
document not processed
```

ثم retry حسب السياسة.

---

# 129. MySQL

MySQL يخزن:

```text
Users
Documents metadata
Conversations
Messages
Sources
Application state
```

ولا يخزن الـvectors.

يجب استخدام Volume:

```text
mysql_data
```

---

# 130. Redis

Redis يستخدم لـ:

```text
Laravel Queue
optional cache
optional rate-limit coordination
```

يمكن تفعيل Persistence إذا تطلبت استراتيجية الـQueue ذلك.

---

# 131. Laravel Queue Worker

يعمل Container منفصل:

```text
queue-worker
```

مثلاً:

```text
php artisan queue:work
```

مع:

- timeout مناسب لمعالجة الملفات.
- tries محدودة.
- backoff.
- failed_jobs.

---

# 132. Scheduler

إذا استخدم المشروع Scheduled Commands، يمكن:

- cron على Host.
- أو Scheduler container.

في النسخة البسيطة يمكن استخدام Container منفصل صغير.

---

# 133. Nginx

Nginx هو Public Entry Point:

```text
Internet
  ↓
Nginx
  ↓
Laravel
```

FastAPI لا يحتاج Public Route في النسخة العادية.

---

# 134. HTTPS مجاني

الخيار الطبيعي:

```text
Let's Encrypt
```

وهو يوفر TLS certificates مجانية.

إذا كان لدينا Domain/Subdomain يتم توجيهه إلى Public IP ثم إصدار Certificate وتجديدها آلياً.

في 2026 أصبح Let's Encrypt يدعم أيضاً IP address certificates قصيرة العمر عبر Certbot الحديث، لكن Domain/Subdomain يظل أبسط في معظم حالات النشر.

---

# 135. Domain

Oracle Free Tier لا يعني تلقائياً الحصول على Domain مجاني دائم.

لذلك هناك فرق بين:

```text
Hosting Cost = $0
```

و:

```text
Custom Domain Cost
```

يمكن للـDemo أن يعمل على IP أو Subdomain متاح، بينما Custom Domain مدفوع غالباً.

---

# 136. Firewall

على Oracle Security List / NSG وعلى Ubuntu Firewall:

Public:

```text
22   SSH
80   HTTP
443  HTTPS
```

Internal only:

```text
3306
6379
6333
6334
8000
```

---

# 137. SSH Security

يفضل:

```text
SSH key authentication
```

مع:

- تعطيل root login المباشر.
- تحديث النظام.
- Fail2ban اختياري.
- تقييد Source IP إذا كان عملياً.

---

# 138. تثبيت Docker

على Ubuntu يتم تثبيت:

```text
Docker Engine
Docker CLI
containerd
Buildx
Docker Compose plugin
```

من repository الرسمي لـDocker.

ثم:

```bash
docker --version
docker compose version
```

---

# 139. بنية الملفات على السيرفر

```text
/opt/rag-system/
│
├── docker-compose.yml
├── .env
│
├── nginx/
│
├── laravel-app/
│
├── ai-service/
│
├── backups/
│
└── scripts/
    ├── deploy.sh
    ├── backup.sh
    ├── restore.sh
    └── health-check.sh
```

---

# 140. Secrets

لا تخزن داخل Git:

```text
APP_KEY
DB_PASSWORD
AI_SERVICE_API_KEY
HF_TOKEN
JINAAI_API_KEY
LLAMA_CLOUD_API_KEY
QDRANT_API_KEY
```

تكون في `.env` أو secret management لاحقاً.

---

# 141. Deployment Flow من Git

```text
Git Repository
   ↓
Backup + verify free disk
   ↓
git pull
   ↓
docker compose build
   ↓
docker compose up -d
   ↓
Laravel migrations
   ↓
cache config/routes/views
   ↓
health checks + cloud capability smoke tests
```

- تستخدم Images وإصدارات pinned تدعم `linux/arm64`؛ يمنع `latest` في النسخة المعتمدة.
- Laravel يعمل عبر PHP-FPM خلف Nginx، وليس `php artisan serve`.
- لا تنفذ Migration قبل نجاح Backup وفحص الاتصال بقاعدة البيانات.
- أي Migration غير backward-compatible يحتاج Maintenance window موثق؛ هذه الخطة Demo وليست Zero-downtime production rollout.

---

# 142. أول Deployment — Laravel

بعد Build:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

الملفات الخاصة لا توضع خلف Public `storage:link`.

---

# 143. أول Deployment — FastAPI

يجب تنفيذ:

```text
GET /api/v1/health
```

والتحقق من:

```text
FastAPI
Qdrant
configuration
```

ولا يعتبر النظام Ready إذا لم يستطع الوصول إلى Qdrant.

---

# 144. Health Checks

كل Container دائم مهم يملك Health Check:

```text
mysql
redis
qdrant
fastapi
```

لا يوجد Health endpoint لـ`clamscan` لأنها Process قصيرة العمر. بدلاً منه تتحقق Readiness الخاصة بـ`security-scan-worker` من وجود binary صالح وقاعدة تواقيع موجودة وغير متجاوزة للحد الزمني، ويختبر DPL-15 مسار Clean/Deny فعلياً.

لا تكفي `depends_on` وحدها؛ تعتمد الخدمات الحرجة `condition: service_healthy` أو Startup retry منظم حتى لا تبدأ قبل جاهزية MySQL وRedis وQdrant.

---

# 145. Cloud LLM Health

لا نرسل Generation كاملة في كل Health Check حتى لا نستهلك الرصيد المجاني.

يتم اختبار Provider عند الحاجة أو بفحص دوري منخفض التكرار.

---

# 146. Local LLM Health

يخص هذا Local Demo فقط، وليس Oracle. يمكن فحص endpoint مناسب مثل قائمة النماذج أو endpoint readiness حسب Ollama.

FastAPI في Local Mode يجب أن يكتشف غياب LLM ويعيد خطأ منضبطاً.

---

# 147. Deployment Command — Cloud Mode

```text
RAG_DEPLOYMENT_MODE=cloud
```

ثم:

```bash
docker compose up -d
```

ولا يتم تشغيل Local LLM service.

---

# 148. Deployment Command — Local Mode

لا ينفذ Local Mode على Oracle ولا يوجد `local-llm` Compose profile هناك. يشغّل DPL-24 البنية المحلية على Mac أو ASUS: Infrastructure داخل Docker Desktop وFastAPI/Ollama على Host OS.

---

# 149. اختبار Cloud Mode

```text
Upload
  ↓
ClamAV
  ↓
FastAPI
  ↓
Qdrant
  ↓
Question
  ↓
HF/Qwen3.5-9B
  ↓
Answer + Sources
```

---

# 150. اختبار Local Mode

```text
Upload
  ↓
ClamAV
  ↓
FastAPI
  ↓
Qdrant
  ↓
Question
  ↓
Host-native Ollama
  ↓
qwen3.5:4b
  ↓
Answer + Sources
```

ينفذ هذا السيناريو في DPL-24/DPL-25 على الجهاز المحلي فقط، ولا يدخل Acceptance Criteria الخاصة بنجاح Oracle Cloud-only.

---

# 151. الفصل بين بيئتي Cloud وLocal

- Oracle ثابت على `RAG_DEPLOYMENT_MODE=cloud` ولا يتحول إلى Local بتغيير Environment.
- Local Demo يملك Environment منفصلة ويعرض `cloud/hybrid_local/compare` حسب Capabilities.
- Laravel وFastAPI يرفضان Profile غير مسموحة Server-side؛ إخفاء الخيار في UI وحده غير كافٍ.
- Collections تبقى منفصلة بين Cloud وHybrid Local، ولا يغيّر Model أو dimension على Collection موجودة بصمت.

---

# 152. Backup Strategy

يجب عمل Backup على الأقل لـ:

```text
MySQL
Laravel private documents
Qdrant
configuration
```

لا يكفي حفظ Backup على Volume داخل VM نفسها. تحفظ نسخة مشفرة ومقيدة الصلاحيات خارج الـVM وفق Retention محددة، ولا تحتوي Secrets plaintext. يثبت DPL-22 الاسترجاع إلى بيئة معزولة، وليس إنشاء الملف فقط.

---

# 153. أولوية النسخ الاحتياطي

الأولوية:

```text
1. Original Documents
2. MySQL
3. Qdrant Snapshots
```

السبب أن Qdrant يمكن إعادة بنائه من الوثائق الأصلية إذا بقيت الوثائق وبيانات MySQL سليمة.

---

# 154. Recovery Strategy

إذا ضاع Qdrant ولكن بقي:

```text
MySQL
+
Original Documents
```

يمكن تشغيل:

```text
Reprocess Documents
```

لإعادة بناء الـvectors.

---

# 155. مراقبة مساحة القرص

أهم ما قد يملأ القرص:

```text
Docker images
Docker logs
Qdrant
MySQL
Uploaded files
LLM model
Backups
```

مراقبة:

```bash
df -h
docker system df
```

---

# 156. Log Rotation

يجب تفعيل Log Rotation لـ:

```text
Laravel logs
Docker logs
FastAPI logs
Nginx logs
```

ولا يسمح لها بالنمو غير المحدود.

تضبط Docker logging driver بحد أقصى للحجم وعدد الملفات، وتطبّق Laravel daily rotation وNginx logrotate. لا تسجل Tokens أو نصوص الوثائق أو محتوى Prompts.

---

# 157. تحديث المشروع

```text
Backup
  ↓
git pull
  ↓
docker compose build
  ↓
docker compose up -d
  ↓
php artisan migrate --force
  ↓
health checks
```

بعد Health checks تنفذ Cloud capability test وطلب سؤال صغير مضبوط الكلفة قبل إنهاء Maintenance window.

---

# 158. تحديث Qdrant

قبل تغيير Version:

- Snapshot/Backup.
- مراجعة compatibility.
- Pin version بدلاً من `latest` في Production-like demo.

---

# 159. تحديث ClamAV

يجب تحديث:

- ClamAV CLI/Image version pinned ومدعوم.
- virus signatures عبر `UpdateClamAvSignaturesJob` مجدولة على `security-scan` نفسها.

تختبر التواقيع بعد التحديث، ويعتبر غيابها أو تقادمها سبباً لعدم جاهزية الفحص. لا يبدأ `clamd`.

---

# 160. Free Deployment مع Cloud LLM — التقييم

```text
Oracle Infrastructure  → Free ضمن الحدود
Qdrant                  → Local / Free
ClamAV CLI/signatures   → Local / Free، عند الطلب
MySQL                   → Local / Free
Redis                   → Local / Free
Laravel                 → Free software
FastAPI                 → Free software
```

ويبقى:

```text
LlamaParse API
Jina API
Cloud LLM API
```

مرتبطة بخطط/credits خارجية.

لذلك ليست كل سلسلة AI مضمونة `$0` للأبد.

---

# 161. Free Deployment مع Local LLM — التقييم

لا يشغّل Oracle Local LLM. عند تشغيل Ollama `qwen3.5:4b` على Mac أو ASUS:

```text
LLM API cost per request = $0
```

لكن المقابل:

```text
higher latency
higher RAM
higher CPU
lower concurrency
```

وهذا مناسب لإثبات Self-hosted LLM path في مشروع الماجستير، لكنه Local Demo منفصل لا جزء من Online hosting.

---

# 162. لماذا لا نعتمد Hugging Face ZeroGPU كـDeployment رئيسي مجاني؟

ZeroGPU مفيد للتجارب وله GPU quotas.

بحسب التوثيق الحالي، حساب Free يحصل على Usage quota يومية محدودة، لكن الاستضافة نفسها لها قيود حسب نوع الحساب.

لذلك:

```text
ZeroGPU = optional experiment
```

وليس البنية الرئيسية المضمونة للمشروع.

---

# 163. Dedicated Hugging Face Inference Endpoints

لا تعتبر ضمن الخطة المجانية.

هي Dedicated infrastructure مدفوعة حسب نوع العتاد ووقت التشغيل.

لذلك لا نخلط بينها وبين:

```text
HF Inference Providers monthly credits
```

---

# 164. خطة Deployment الموصى بها للمناقشة

## الوضع الأساسي

```text
Oracle Always Free
+
Cloud-only processing and generation
```

للـDemo الأسرع والأكثر استقراراً.

## الوضع الثاني

```text
Mac/ASUS Local Demo
+
Host-native FastAPI/Ollama
+
Docker infrastructure
```

لإظهار أن المعمارية تدعم Hybrid Local وCompare وSelf-hosted generation، من دون تحميل Oracle أوزاناً محلية.

هذا يوضح أكاديمياً trade-off بين:

```text
Performance
Cost
Privacy
Infrastructure
```

---

# 165. خطة تنفيذ Deployment خطوة بخطوة

## DPL-1 — إنشاء Oracle Account

- اختيار Home Region بعناية.
- تفعيل الحساب.
- التأكد من Always Free eligibility.

## DPL-2 — إنشاء VM

```text
VM.Standard.A1.Flex
2 OCPU
12 GB RAM
Ubuntu ARM64
```

يسجل Proof أن Shape وOCPU/RAM/Boot volume ضمن Always Free limits الفعلية للحساب، لأن السعة المجانية غير مضمونة.

## DPL-3 — إعداد الشبكة

فتح فقط:

```text
22
80
443
```

## DPL-4 — تحديث Ubuntu

```bash
sudo apt update
sudo apt upgrade
```

## DPL-5 — تثبيت Docker

Docker Engine + Buildx + Compose plugin من Repository الرسمي، ثم إثبات `linux/arm64` لكل Image pinned مستخدمة.

## DPL-6 — Clone المشروع

```bash
git clone ...
```

## DPL-7 — إعداد `.env`

Laravel + FastAPI + infrastructure، مع:

```text
RAG_DEPLOYMENT_MODE=cloud
```

صلاحيات الملف `600`، ولا يحتوي Git أو Backup غير المشفر على Secrets.

## DPL-8 — إنشاء Volumes

MySQL/Qdrant/ClamAV signatures/private documents، مع اختبار permissions وfree disk.

## DPL-9 — تشغيل Infrastructure

```text
mysql
redis
qdrant
signature volume seeded بواسطة `freshclam` one-shot من Security Scan Worker image
```

لا يوجد `clamd`. تتحقق المهمة من Health checks وقاعدة تواقيع حديثة، وتثبت أن `depends_on` يستخدم Readiness مناسبة لا مجرد بدء Container.

## DPL-10 — تشغيل FastAPI

يبنى Cloud-only image ثم Health/Capabilities. يفشل الاختبار إذا احتوت Image على Torch/Transformers/Ollama أو أوزان BGE/Qwen محلية.

## DPL-11 — تشغيل Laravel

Laravel PHP-FPM خلف الشبكة الداخلية، ثم Backup gate وmigrations وcache commands؛ يمنع `php artisan serve`.

## DPL-12 — تشغيل Queue Worker

تشغيل Worker عادي و`security-scan-worker` منفصل بQueue `security-scan` وconcurrency=1. يحتوي Scan worker ClamAV CLI ويشارك Private quarantine وsignature volume فقط، بلا Docker socket.

## DPL-13 — تشغيل Nginx

ربط Laravel PHP-FPM، وضبط forwarded headers و`client_max_body_size` وtimeouts وlog rotation.

## DPL-14 — HTTPS

Let's Encrypt مع Domain/Subdomain موثوق وتجديد آلي واختبار HTTP→HTTPS.

## DPL-15 — اختبار PDF

Validation + private quarantine + on-demand `clamscan` + خروج Process + Queue + FastAPI + Qdrant، مع Clean وinfected/fail-closed cases.

## DPL-16 — اختبار DOCX

اختبار DOCX صالح End-to-End، ورفض ملف متنكر أو ZIP bomb يتجاوز `1000` entry أو `50 MB` غير مضغوط، مع بقاء الملف في Quarantine وعدم إطلاق AI عند الرفض.

## DPL-17 — اختبار TXT

اختبار TXT صالح End-to-End مع encoding مدعومة، ورفض binary/malformed/oversized input، ثم إثبات ظهور الإجابة والمصادر من Qdrant.

## DPL-18 — Cloud-only Capability/UI/API Test

- UI يعرض `cloud` فقط.
- Laravel وFastAPI يرفضان `hybrid_local/compare` المعدلة يدوياً بحالة `422`.
- لا Local model capability في الاستجابة.

## DPL-19 — Lightweight Online Image Verification

يفحص SBOM/package list وfilesystem للـImage لإثبات غياب Torch/Transformers/Ollama/BGE/Qwen weights، مع اختبار Startup لا ينزّل Model.

## DPL-20 — Hugging Face Cloud Generation Test

Smoke test فعلي لـ`Qwen/Qwen3.5-9B` عبر Provider المعتمد، مع Timeout وerror mapping وbudget awareness، من دون افتراض أن وجود Model card يعني توافر Provider دائماً.

## DPL-21 — Security Test

اختبار User A ضد Document User B، private downloads، FastAPI authentication، Qdrant filters، modified profile requests، infected/timeout/stale-signature fail-closed، وعدم نشر المنافذ الداخلية.

## DPL-22 — Backup/Restore Test

Backup مشفر لـMySQL والوثائق وQdrant configuration/snapshot إلى موقع خارج الـVM، ثم Restore فعلي في بيئة معزولة والتحقق من الملكية وعدد الوثائق والـvectors.

## DPL-23 — Restart/Persistence/Readiness Test

```bash
docker compose restart
```

ثم إثبات بقاء MySQL وQdrant والملفات والتواقيع، وعودة الخدمات وفق Health checks من دون Startup race.

## DPL-24 — Local Topology Verification

على Mac وASUS فقط: Docker infrastructure + on-demand scan worker، وFastAPI/Ollama على Host OS، مع `host.docker.internal` وLoopback-only Qdrant وModel واحد فعال، وقفل Redis عالمي يمسكه Laravel workers لمنع تداخل Scan مع أي استدعاء FastAPI محلي.

## DPL-25 — Local Compare Flow

ينفذ Compare كاملاً على الجهاز المحلي بالتتابع، ويثبت عدم تداخل `clamscan` وBGE/Reranker/Qwen وعدم رفع سوى الفائز إلى Qdrant.

---

# 166. Acceptance Criteria للنشر المجاني

يعتبر Oracle Cloud-only Deployment ناجحاً عندما:

- الموقع يعمل عبر HTTPS.
- Authentication يعمل.
- PDF/DOCX/TXT ترفع.
- `clamscan` تفحص الملفات عند الطلب وتخرج قبل انتقال الملف إلى AI.
- missing/stale signatures أو Timeout أو Process failure تمنع المعالجة Fail-closed.
- الملفات الخاصة غير عامة.
- Queue تعمل.
- FastAPI داخلي.
- Qdrant داخلي ومحلي.
- Qdrant data persistent.
- User isolation يعمل.
- Cloud processing/generation يعملان فعلياً.
- UI وLaravel وFastAPI يقبلون `cloud` فقط ويرفضون Local/Compare.
- Online image لا يحتوي Local AI dependencies أو weights ولا يشغّل Ollama.
- Filament يعمل.
- Restart لا يحذف البيانات.
- Backup قابل للاسترجاع.

نجاح Local Demo وCompare يقاس منفصلاً في DPL-24/DPL-25 على Mac وASUS، وليس شرطاً لتشغيل Oracle نفسه.

---

# 167. متى ننتقل من Free Deployment؟

عندما يظهر أحد التالي:

```text
زيادة المستخدمين المتزامنين
OOM
بطء Local LLM غير مقبول
Qdrant data كبيرة
Oracle capacity/idle limitations
حاجة إلى SLA
حاجة إلى GPU دائم
حاجة إلى High Availability
```

---

# 168. مسار التوسع لاحقاً

يمكن نقل الخدمات بدون إعادة تصميم التطبيق.

مثلاً:

```text
MySQL Local → Managed MySQL
Qdrant Local → Qdrant Cloud
Local Ollama → GPU vLLM server
Cloud HF Router → Different Provider
```

بسبب فصل Interfaces.

---

# 169. الشكل النهائي للـOracle Cloud-only Deployment

```text
                         Internet
                            │
                            ▼
                       HTTPS / Nginx
                            │
                            ▼
                         Laravel/PHP-FPM
                   ┌─────────┼───────────────┐
                   │         │               │
                   ▼         ▼               ▼
                 MySQL     Redis      Queue Workers
                                         │
                          ┌──────────────┴──────────────┐
                          ▼                             ▼
                  Security Scan Worker              FastAPI
                  clamscan on-demand            cloud-only image
                          │                       ┌─────┴─────┐
                          ▼                       ▼           ▼
                private quarantine             Qdrant   External AI
                                                            │
                                                 ┌──────────┼──────────┐
                                                 ▼          ▼          ▼
                                             LlamaParse    Jina     HF Router
                                                                         │
                                                                         ▼
                                                                  Qwen3.5-9B
```

---

# 170. الشكل النهائي للـLocal Demo على Mac/ASUS

```text
                   Docker Desktop infrastructure
          ┌────────────┬─────────┬────────┬───────────────┐
          ▼            ▼         ▼        ▼               ▼
       Laravel       MySQL     Redis    Qdrant    Security Scan Worker
          │                                            clamscan on-demand
          │
          └── host.docker.internal:8000
                              │
                              ▼
                    Host-native FastAPI
                    ┌─────────┼───────────┐
                    ▼         ▼           ▼
                 BGE-M3   BGE Reranker  Host Ollama
                 lazy       lazy         qwen3.5:4b
                    └─────────┴───────────┘
                     one active model at a time
```

في هذه النسخة يبقى:

```text
LlamaParse Cloud للـParsing فقط
```

أما Embedding وReranking وGeneration فهي محلية. لا توصف هذه النسخة بأنها Fully Local ما دام LlamaParse خارجياً.

---

# 171. ملاحظة أكاديمية مهمة حول مصطلح Local

لدينا ثلاثة مستويات:

## المستوى 1 — Oracle Cloud-only مع Vector Database ذاتية الاستضافة

```text
Qdrant داخل Oracle
+
Cloud parsing/embedding/reranking/generation
```

## المستوى 2 — Hybrid Local Demo المعتمد

```text
LlamaParse Cloud
+
Local BGE-M3/BM25/BGE Reranker/Qdrant/Ollama
```

الـParser وحده يبقى Cloud.

## المستوى 3 — Fully Local RAG

```text
Local Parser/OCR
Local Embeddings
Local Reranker
Local Qdrant
Local LLM
```

الـOnline deployment يحقق المستوى الأول، والـLocal Demo يحقق المستوى الثاني.

أما Fully Local RAG فهو توسع مستقبلي، ولا ندعي تحقيقه ما دام LlamaParse يعمل عبر Cloud API.

---

# 172. المصادر الرسمية لخطة Deployment

تم التحقق من المعلومات بتاريخ:

```text
2026-08-21
```

## Oracle Cloud Free Tier

- Oracle Cloud Infrastructure Free Tier  
  https://docs.oracle.com/en-us/iaas/Content/FreeTier/freetier.htm

- Always Free Resources  
  https://docs.oracle.com/en-us/iaas/Content/FreeTier/freetier_topic-Always_Free_Resources.htm

## Docker

- Docker Engine on Ubuntu  
  https://docs.docker.com/engine/install/ubuntu/

- Docker Compose plugin  
  https://docs.docker.com/compose/install/linux/

- Compose startup order and health checks
  https://docs.docker.com/compose/how-tos/startup-order/

- Docker Desktop host networking
  https://docs.docker.com/desktop/features/networking/networking-how-tos/

## Qdrant

- Installation  
  https://qdrant.tech/documentation/installation/

- Security  
  https://qdrant.tech/documentation/security/

- Local Quickstart  
  https://qdrant.tech/documentation/quick-start/

## ClamAV

- ClamAV Docker documentation  
  https://docs.clamav.net/manual/Installing/Docker.html

## Hugging Face

- Inference Providers Pricing  
  https://huggingface.co/docs/inference-providers/en/pricing

- ZeroGPU  
  https://huggingface.co/docs/hub/main/en/spaces-zerogpu

- Qwen/Qwen3.5-9B  
  https://huggingface.co/Qwen/Qwen3.5-9B

## Let's Encrypt

- Free TLS certificates  
  https://letsencrypt.org/

---

# 173. القرار المعماري النهائي

## 173.1 قرار إدارة التنفيذ

إضافة إلى القرارات المعمارية التقنية، يعتمد المشروع آلية تنفيذ ثابتة:

```text
Start From Scratch
Task واحدة لكل Chat
Master Plan = Architecture Source
Progress File = Execution Source of Truth
CURRENT HANDOFF = نقطة الاستلام في المحادثة التالية
```

ولا يتم الانتقال بين المهام اعتماداً على الذاكرة أو التخمين، بل على آخر حالة موثقة في ملف التقدم.


الخطة النهائية تعتمد:

```text
Laravel
+
FastAPI
+
Qdrant ذاتية الاستضافة
+
ClamAV CLI عند الطلب
+
MySQL
+
Redis
+
Laravel Queue
```

وتفصل بيئتي التشغيل بوضوح:

```text
Oracle Online = cloud only
Mac/ASUS Demo = cloud | hybrid_local | compare
```

### Cloud mode

```text
RAG_DEPLOYMENT_MODE=cloud
Qwen/Qwen3.5-9B
via Hugging Face Router
```

لا يحتوي Oracle Torch/Transformers/Ollama/BGE/Qwen weights، ويرفض Local/Compare Server-side.

### Local mode

على Mac أو ASUS فقط:

```text
Host-native FastAPI
Ollama qwen3.5:4b
BGE-M3 + BGE Reranker
single_active lifecycle
```

لا يتحول Oracle إلى Local Mode بتغيير Environment. تشترك البيئتان في Contracts التطبيق، لكن لكل منهما Dependencies وTopology وقدرات منشورة منفصلة.

هذه هي البنية التي يجب اعتمادها في تنفيذ المشروع من الآن فصاعداً.

---

# 174. التعديل المعماري المعتمد — Cloud / Hybrid Local / Compare

## 174.1 حالة القرار ونطاقه

هذا القسم قرار معماري نهائي معتمد بتاريخ 2026-08-15، وليس فكرة مؤجلة. وهو يوسّع الخطة الأصلية دون تغيير نقطة التنفيذ الحالية:

```text
Last Completed Task: B10 — ProcessingRun model/enums/relations
Current Task: B11 — selected_processing_run_id migration/invariants
Current Task Status: TODO
Expected Task Branch: task/B11-selected-processing-run
```

لا تُنفّذ في B11 مسؤوليات اختيار الفائز الفعلي أو مقارنة B12 أو المعالجة أو FastAPI. تبقى حالة التنفيذ التفصيلية في `PROJECT_RAG_EXECUTION_PROGRESS.md` هي المرجع الأعلى للـHandoff ضمن هذه النسخة المنقحة.

## 174.2 المصطلحات الرسمية

| المصطلح | المعنى |
|---|---|
| `cloud` | LlamaParse Cloud ثم Jina Embeddings وJina Reranker عبر API، والتوليد بواسطة `Qwen/Qwen3.5-9B` عبر Hugging Face Router |
| `hybrid_local` | LlamaParse Cloud فقط للـParsing، ثم Chunking وBGE-M3 وBM25 وBGE Reranker محلياً، والتوليد بواسطة Ollama `qwen3.5:4b` |
| `compare` | تشغيل `cloud` و`hybrid_local` للملف نفسه، عرض تقرير مقارنة وسؤال اختبار، ثم اعتماد مسار واحد فقط |
| selected run | نتيجة المعالجة التي اختارها المستخدم وأصبحت النسخة الرسمية للوثيقة |
| temporary artifacts | Chunks وvectors ونتائج وسيطة خاصة غير دائمة تحفظ داخل FastAPI حتى الاختيار أو انتهاء TTL |
| persistent index | الـPoints النهائية للـselected run داخل Qdrant |

`hybrid_local` ليس Offline بالكامل لأن Parsing ما زال يستخدم LlamaParse Cloud. لا يجوز وصفه في الواجهة أو التقرير بأنه Fully Local.

## 174.3 أوضاع التشغيل والقدرات

### Online / Cloud deployment

```text
RAG_DEPLOYMENT_MODE=cloud
```

- يظهر للمستخدم خيار `cloud` فقط.
- تختفي خيارات `hybrid_local` و`compare` من Blade.
- يرفض Laravel أي Request معدّل يطلب Local أو Compare بحالة `422`.
- يتحقق FastAPI أيضاً من Capability المسموحة ولا يعتمد على الواجهة.
- لا تُثبّت ولا تُحمّل أوزان Embedding أو Reranker محلية.
- لا تُشغّل Ollama، ولا تُضمّن Torch/Transformers أو ملفات Qwen/BGE أو GPU runtimes في Image النشر.
- تبقى Qdrant محلية داخل بنية Docker وبـPersistent Volume.

### Local demonstration

```text
RAG_DEPLOYMENT_MODE=local
LOCAL_AI_TOPOLOGY=host_native
LOCAL_LLM_BASE_URL=http://127.0.0.1:11434/v1
LOCAL_LLM_MODEL=qwen3.5:4b
```

- تظهر الخيارات `cloud` و`hybrid_local` و`compare`.
- يحتاج Cloud وCompare مفاتيح LlamaParse وJina وHugging Face حسب المرحلة.
- يستخدم Hybrid Local النموذج المثبت فعلياً في Ollama: `qwen3.5:4b`.
- تعمل خدمات البنية التحتية `Laravel + Queue + MySQL + Redis + Qdrant` داخل Docker، ويعمل فحص ClamAV كعملية `clamscan` قصيرة العمر عند الطلب داخل Security Scan Worker مخصص، بينما تعمل `FastAPI + Ollama` كخدمتين Host-native على الجهاز المحلي للاستفادة من مسرّع الجهاز.
- يتصل FastAPI المضيف بـOllama عبر `127.0.0.1:11434`، وتتصل خدمات Docker بـFastAPI عبر `host.docker.internal:8000`. تبقى العناوين قابلة للضبط ولا يجوز تثبيت `http://ollama` كافتراض وحيد.
- هذه Topology واحدة على macOS وWindows؛ الاختلاف الداخلي فقط هو Backend الذي يحسمه Device resolver، وليس Processing Profile جديداً.
- لا يوجد Fallback صامت بين Providers. الإعداد غير المتوافق يفشل مبكراً برسالة واضحة.

### إعداد Cloud LLM

```text
CLOUD_LLM_BASE_URL=https://router.huggingface.co/v1
CLOUD_LLM_MODEL=Qwen/Qwen3.5-9B
HF_TOKEN=...
```

اسم نموذج Hugging Face الرسمي المعتمد مأخوذ من الـNotebook المرجعي:
`Qwen/Qwen3.5-9B`.

### فصل الاعتمادات

ينشأ FastAPI بملفات Dependencies منفصلة، وتبقى Cloud image مستقلة عن بيئة Local Host-native:

```text
base/cloud   = FastAPI + HTTP clients + Qdrant client + parsing utilities
local-native = base + PyTorch + local embedding/reranker dependencies على Host OS
ollama       = خدمة Host-native مستقلة ولا تدخل داخل FastAPI image
```

لا نعتمد على GPU passthrough داخل Docker Desktop للمسار المحلي. الهدف أن تبقى النسخة Online خفيفة وقابلة للنشر ضمن Free Tier قدر الإمكان، وأن يستخدم المسار المحلي Metal/MPS أو Intel XPU/Vulkan عند توفره. الخدمات الخارجية نفسها قد تخضع لحصص أو Credits، لذلك لا نعد بأن سلسلة AI مجانية بلا حدود.

### مصفوفة Providers المعتمدة

| المرحلة | Cloud profile | Hybrid Local profile |
|---|---|---|
| Parsing | LlamaParse Cloud | LlamaParse Cloud |
| Chunking | إعداد Profile قابل للضبط، baseline `800/80` | SentenceSplitter baseline `800/80` |
| Dense embedding | `jina-embeddings-v3` عبر Jina API، dimension 1024 | `BAAI/bge-m3` محلي، dimension 1024 |
| Sparse | Qdrant/BM25 multilingual | BM25 محلي |
| Fusion | RRF | RRF |
| Reranker | `jina-reranker-v2-base-multilingual` عبر Jina API | `BAAI/bge-reranker-v2-m3` محلي |
| Generation | HF Router `Qwen/Qwen3.5-9B` | Ollama `qwen3.5:4b` |

أي تغيير لاحق في Model name أو dimension يحتاج Migration/Collection strategy موثقة، ولا يغيّر قيمة Environment بصمت على Collection موجودة.

### Environment variables المرجعية

Laravel:

```text
RAG_DEPLOYMENT_MODE=cloud|local
AI_SERVICE_BASE_URL=http://fastapi:8000
AI_SERVICE_API_KEY=...
AI_SERVICE_TIMEOUT=600
QUEUE_CONNECTION=redis
DOCUMENT_SECURITY_SCAN_ENABLED=true
CLAMAV_SCAN_MODE=on_demand_cli
CLAMAV_SCAN_QUEUE=security-scan
CLAMAV_SCAN_CONCURRENCY=1
CLAMAV_SCAN_TIMEOUT=300
```

القيمة أعلاه لـ`AI_SERVICE_BASE_URL` تخص Cloud/Docker topology. في Local topology يستخدم Docker caller القيمة `http://host.docker.internal:8000`، بينما تستخدم أدوات المضيف `http://127.0.0.1:8000`.

FastAPI المشتركة:

```text
RAG_DEPLOYMENT_MODE=cloud|local
QDRANT_URL=http://qdrant:6333
QDRANT_CLOUD_COLLECTION=rag_documents_cloud
QDRANT_HYBRID_LOCAL_COLLECTION=rag_documents_hybrid_local
TEMP_ARTIFACT_ROOT=/app/data/artifacts
TEMP_ARTIFACT_TTL_HOURS=24
CHUNK_SIZE=800
CHUNK_OVERLAP=80
DENSE_CANDIDATES=12
SPARSE_CANDIDATES=12
RRF_TOP_K=12
RERANK_TOP_N=5
```

Cloud providers:

```text
LLAMA_CLOUD_API_KEY=...
JINAAI_API_KEY=...
HF_TOKEN=...
CLOUD_EMBED_MODEL=jina-embeddings-v3
CLOUD_RERANK_MODEL=jina-reranker-v2-base-multilingual
CLOUD_LLM_BASE_URL=https://router.huggingface.co/v1
CLOUD_LLM_MODEL=Qwen/Qwen3.5-9B
```

Local providers:

```text
LOCAL_EMBED_MODEL=BAAI/bge-m3
LOCAL_RERANK_MODEL=BAAI/bge-reranker-v2-m3
LOCAL_LLM_BASE_URL=http://127.0.0.1:11434/v1
LOCAL_LLM_MODEL=qwen3.5:4b
AI_SERVICE_WORKERS=1
LOCAL_AI_MAX_CONCURRENCY=1
LOCAL_DEVICE=auto
LOCAL_DTYPE=auto
LOCAL_MODEL_LIFECYCLE=single_active
LOCAL_RELEASE_MODEL_AFTER_STAGE=true
LOCAL_MIN_AVAILABLE_MEMORY_RATIO=0.15
LOCAL_AI_QUEUE=ai-local
LOCAL_AI_QUEUE_CONCURRENCY=1
LOCAL_EMBED_BATCH_SIZE=2
LOCAL_RERANK_BATCH_SIZE=2
LOCAL_EMBED_MAX_LENGTH=1024
LOCAL_RERANK_MAX_LENGTH=1024
LOCAL_LLM_CONTEXT_SIZE=8192
MAX_GENERATION_TOKENS=700
OLLAMA_MAX_LOADED_MODELS=1
OLLAMA_NUM_PARALLEL=1
OLLAMA_KEEP_ALIVE=0
```

لا تستخدم قيمة `http://ollama:11434/v1` إلا إذا شُغّلت Ollama عمداً كخدمة Docker في بيئة أخرى مدعومة ومقاسة.

لا توجد مفاتيح حقيقية داخل Git. يقرأ Frontend القدرات من Laravel configuration/Capabilities service، لا من Environment مباشرة.

## 174.4 تجربة رفع الوثيقة

- يرفع المستخدم ملفاً واحداً في كل عملية Upload.
- الأنواع: PDF وDOCX وTXT.
- Validation وAuthorization وSHA-256/duplicate safeguards إلزامية دائماً.
- Security Scan مفعّل افتراضياً عبر:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true
```

### عند تفعيل Security Scan

```text
Validation
  → Private Quarantine
  → security-scan queue
  → short-lived clamscan
  → clean
  → Permanent Private Storage
```

- لا يصل ملف غير Clean إلى FastAPI.
- فشل تشغيل الفاحص أو تقادم/غياب التواقيع أو Timeout يطبق Fail-Closed.
- لا يبدأ أي عمل AI قبل انتهاء Process الفحص ونجاح Promotion.

### عند تعطيل Security Scan صراحةً

```text
DOCUMENT_SECURITY_SCAN_ENABLED=false

Validation
  → Permanent Private Storage
  → متابعة Processing orchestration
```

- لا يستخدم Quarantine/ClamAV كمرحلة إلزامية لهذا Upload.
- Validation لا تعتبر Antivirus؛ هذا Trade-off أمني يتحمله المشغّل ويجب أن يكون واضحاً في Configuration/diagnostics.
- لا يسمح النظام بالانتقال إلى هذا المسار تلقائياً عند فشل ClamAV.
- قرار المسار يصدر من Laravel server-side configuration، لا من Browser request.
- بعد اكتمال Security routing يختار المستخدم مسار المعالجة من القدرات المتاحة في البيئة.
- لا يرتبط اختيار Cloud/Hybrid/Compare بتفعيل ClamAV أو تعطيله.

### Cloud فقط أو Hybrid Local فقط

يُنشأ Processing Run واحد. عند نجاحه:

1. تتحقق FastAPI من عدد الـchunks والـvectors.
2. ترفع النتائج إلى Collection الدائمة الصحيحة.
3. يتحقق count بعد الرفع.
4. يصبح Run هو selected run.
5. تصبح الوثيقة `ready`.

### Compare

1. يُنفّذ LlamaParse مرة واحدة قدر الإمكان وتُستخدم النتيجة الطبيعية المشتركة كمدخل للمسارين.
2. ينشأ Run للـCloud وRun للـHybrid Local.
3. لكل Run يُنفّذ Chunking وEmbedding وبناء Sparse representation الخاصة به.
   أي مراحل تستخدم BGE أو Ollama تمر عبر بوابة Local AI ذات `concurrency=1` وتُنفّذ تسلسلياً؛ لا تُحمّل أو تُشغّل النماذج المحلية الثقيلة بالتوازي.
4. تحفظ النتائج مؤقتاً داخل FastAPI ولا ترفع بعد إلى Persistent Qdrant.
5. ترسل FastAPI إلى Laravel تقريراً مضغوطاً لكل Run، دون قيم الـvectors.
6. يكتب المستخدم سؤال اختبار اختياري لكنه موصى به قبل القرار.
7. يعرض النظام أفضل المقاطع ومصادرها لكل Run.
8. يضغط المستخدم `اعتماد هذا المسار`.
9. ترفع FastAPI نتيجة الفائز فقط إلى Qdrant بشكل idempotent.
10. بعد نجاح الرفع والتحقق تُحذف artifacts للخاسر، ويُحتفظ بتقرير Audit في MySQL.

### انتهاء المقارنة

- TTL الافتراضي للـtemporary artifacts هو 24 ساعة وقابل للضبط.
- إذا لم يختر المستخدم قبل انتهاء TTL تصبح المقارنة `expired`.
- لا يُفهرس أي مسار تلقائياً عند انتهاء الوقت.
- يستطيع المستخدم إعادة المعالجة لإنشاء مقارنة جديدة.
- لا يُحذف الخاسر قبل نجاح Promotion للفائز والتحقق من العدد.

## 174.5 تقرير المقارنة

يتضمن التقرير لكل مسار:

- اسم Profile.
- Providers والنماذج وإصداراتها أو Configuration snapshot.
- عدد الصفحات عندما يكون قابلاً للتحديد.
- عدد الـchunks.
- عينات محدودة من الـchunks مع `chunk_index` وبدايات ونهايات التقسيم.
- زمن Parsing وChunking وEmbedding وبناء Sparse index والاختبار والإجمالي.
- الأخطاء والتحذيرات.
- أبعاد الـvectors وعددها، من دون إرسال قيم vectors إلى Laravel.
- سؤال الاختبار.
- أفضل المقاطع المسترجعة ومصادرها ودرجات الصلة.

لا يحتوي التقرير ولا شاشة المقارنة على تقدير تكلفة Cloud.

## 174.6 حدود المسؤوليات

### Laravel

- Authentication وAuthorization وOwnership.
- مصدر الحقيقة للمستخدمين والوثائق وحالة المعالجة والاختيار.
- تخزين الملف الأصلي الخاص.
- تنسيق Security Scan Worker وتشغيل `clamscan` بوسائط آمنة من دون Shell أو Docker socket داخل Laravel.
- Queue وJobs.
- إنشاء Runs والمقارنة وحفظ التقارير.
- إرسال الخيارات الموثوقة إلى FastAPI.
- صفحات Blade/Livewire وFilament.
- حفظ المحادثات والرسائل والمصادر والأزمنة.

### FastAPI

- Capability validation.
- Parsing وChunking وEmbedding وSparse representation.
- Temporary artifact lifecycle.
- Retrieval وRRF وReranking وContext وGeneration.
- Promotion idempotent إلى Qdrant والتحقق من count.
- حذف Points.
- إرجاع تقارير ومصادر وtimings منظمة.

FastAPI لا يدير المستخدمين ولا المحادثات ولا يصبح مصدر الحقيقة لحالة الأعمال.

### MySQL

يخزن Application state وAudit metadata. لا يخزن raw vectors ولا Qdrant points ولا الملفات المؤقتة الكبيرة.

### قاعدة بيانات FastAPI

لا نضيف قاعدة بيانات علائقية مستقلة لـFastAPI في النسخة الأولى. يستخدم:

- Private temporary storage للـartifacts.
- Manifest داخلي محدود لكل Artifact.
- Qdrant للنسخة الدائمة.
- MySQL عبر Laravel كمصدر الحقيقة للأعمال.

أي Artifact reference يعاد إلى Laravel يجب أن يكون opaque token، لا filesystem path داخلياً.

## 174.7 Schema Laravel المعتمد

تمت مطابقة هذا القسم مع ملفات migrations ومع MySQL 8.4.11 الفعلي على `main@f23d8f6ef9a641826888cc08dd99dcc8fb72e8bb`. جميع Migrations حتى B9 منفذة. B11 وB12 ما زالتا مخططتين وليستا جزءاً من الـSchema الحالي.

### 174.7.1 جدول `documents` — منفذ في B1 ومثبت بعد B8

الـSchema الحالي يحتوي الأعمدة التالية فقط:

| العمود | النوع الفعلي | Null | الملاحظة |
|---|---|---:|---|
| `id` | BIGINT UNSIGNED | لا | Primary key |
| `user_id` | BIGINT UNSIGNED | لا | FK إلى users مع `restrictOnDelete` |
| `original_name` | VARCHAR(255) | لا | الاسم الذي رفعه المستخدم |
| `stored_name` | VARCHAR(255) | لا | اسم آمن وغير قابل للتخمين |
| `title` | VARCHAR(255) | نعم | عنوان عرض اختياري |
| `file_path` | VARCHAR(1024) | لا | مسار Private Storage فقط |
| `file_type` | VARCHAR(16) | لا | `pdf/docx/txt`، والتحويل إلى Enum في B3 |
| `mime_type` | VARCHAR(255) | لا | لا يغني عن التحقق الفعلي |
| `file_size` | BIGINT UNSIGNED | لا | Bytes |
| `sha256` | CHAR(64) | لا | Hash للملف |
| `status` | VARCHAR(32) | لا | Default: `pending` |
| `created_at` / `updated_at` | TIMESTAMP | نعم | Laravel timestamps |

الفهارس:

```text
INDEX documents_user_id_status_index (user_id, status)
INDEX documents_user_id_sha256_index (user_id, sha256)
INDEX documents_user_id_created_at_index (user_id, created_at)
```

لا يفرض الـSchema الحالي `UNIQUE(user_id, sha256)`؛ سياسة منع تكرار الملف منفذة في B8 على مستوى Application داخل نطاق المستخدم.

لا يحتوي `documents` حالياً الأعمدة التالية:

```text
failure_reason
total_pages
total_chunks
qdrant_collection
processed_at
embedding model
reranker model
timings
comparison report
```

هذه خصائص Processing Run وليست هوية الوثيقة.

سيضاف `selected_processing_run_id` في B11، وليس موجوداً في قاعدة البيانات الحالية. جدول Runs موجود منذ B9، لكن الربط العكسي ينتظر Migration مستقلة حتى يبقى تاريخ الـSchema وترتيب مفاتيحه واضحين.

سياسة الحذف:

- لا نعتمد Cascade مخفياً من user إلى documents لأن للوثيقة ملفاً خاصاً وPoints خارج MySQL.
- `restrictOnDelete` يجبر التطبيق على تنفيذ DocumentDeletionService أولاً.
- Service يحذف Qdrant points وtemporary artifacts والملف الخاص والعلاقات ثم السجلات.
- لا يطبق B1 Soft Deletes دون Task وسياسة Cleanup صريحة.

### 174.7.2 جدول `document_processing_runs`

منفذ في B9 وممثل Domain في B10. الـSchema الفعلي هو:

| العمود | النوع / Null / Default | الغرض |
|---|---|---|
| `id` | BIGINT UNSIGNED، PK | Run ID |
| `document_id` | BIGINT UNSIGNED، NOT NULL | FK إلى `documents.id` مع `ON DELETE RESTRICT` |
| `profile` | VARCHAR(32)، NOT NULL | `cloud` أو `hybrid_local` |
| `status` | VARCHAR(32)، NOT NULL | حالة Run، بلا Default على مستوى DB |
| `profile_snapshot` | JSON، NOT NULL | Providers/models/config الفعلي وقت التشغيل |
| `total_pages` | INT UNSIGNED، NULL | عدد الصفحات الموثوق |
| `total_chunks` | BIGINT UNSIGNED، NOT NULL، default `0` | عدد chunks |
| `vector_count` | BIGINT UNSIGNED، NOT NULL، default `0` | عدد vectors |
| `vector_dimension` | INT UNSIGNED، NULL | الأبعاد |
| `stage_timings_ms` | JSON، NOT NULL | أزمنة المراحل |
| `warnings` | JSON، NULL | تحذيرات منظمة |
| `error_code` | VARCHAR(255)، NULL | رمز ثابت |
| `failure_reason` | TEXT، NULL | رسالة منقحة لا تحتوي Secrets |
| `comparison_report` | JSON، NULL | العينات والنتيجة المضغوطة، بلا vectors |
| `temporary_artifact_ref` | VARCHAR(255)، NULL | Opaque token |
| `temporary_expires_at` | TIMESTAMP، NULL | TTL |
| `qdrant_collection` | VARCHAR(255)، NULL | Collection الفعلية |
| `indexed_at` | TIMESTAMP، NULL | وقت اكتمال الفهرسة |
| `selected_at` | TIMESTAMP، NULL | وقت الاعتماد |
| `discarded_at` | TIMESTAMP، NULL | وقت الاستبعاد |
| `expired_at` | TIMESTAMP، NULL | وقت الانتهاء |
| `created_at` / `updated_at` | TIMESTAMP، NULL | Audit |

الفهارس:

```text
document_processing_runs_document_id_status_index (document_id, status)
document_processing_runs_document_id_profile_created_at_index (document_id, profile, created_at)
document_processing_runs_status_temporary_expires_at_index (status, temporary_expires_at)
```

حالات Run:

```text
pending
processing
ready_for_comparison
selected
indexing
indexed
discarded
failed
expired
```

### 174.7.3 ربط selected run — مخطط B11 وغير منفذ بعد

بعد إنشاء Runs، تضيف Migration مستقلة:

```text
documents.selected_processing_run_id NULL
FK → document_processing_runs.id
restrictOnDelete
INDEX
```

يتحقق Domain Service أن الـRun يعود إلى الوثيقة نفسها وأن حالته `indexed`. تغيير هذا الحقل وعلامات Run يتم داخل Transaction في Laravel بعد تأكيد FastAPI نجاح Qdrant.

### 174.7.4 جدول `document_processing_comparisons` — مخطط B12 وغير منفذ بعد

| العمود | الغرض |
|---|---|
| `id` | Comparison ID |
| `document_id` | الوثيقة |
| `user_id` | صاحب القرار، للدفاع والتدقيق |
| `cloud_run_id` | Run الأول |
| `hybrid_local_run_id` | Run الثاني |
| `selected_run_id` nullable | الفائز |
| `status` | `processing/ready/decided/expired/failed` |
| `trial_question` TEXT nullable | سؤال المقارنة |
| `decided_at` nullable | وقت الاعتماد |
| `expires_at` | نهاية TTL |
| timestamps | Audit |

تفرض Services أن جميع Runs تعود إلى Document وUser نفسيهما. لا تحفظ Cloud cost.

### 174.7.5 حالات Document التجميعية

```text
pending
scanning
infected
queued
processing
awaiting_selection
indexing
ready
failed
selection_expired
```

`ready` تعني حصراً: يوجد selected run حالته `indexed` ويمكن استخدامه في المحادثة.

### 174.7.6 المحادثات والرسائل

يبقى `conversation_document` هو Pivot الذي يحدد وثيقة واحدة أو عدة وثائق لكل محادثة، مع Unique مركب:

```text
UNIQUE(conversation_id, document_id)
```

إضافات `messages`:

```text
document_ids_snapshot JSON NULL
processing_profiles JSON NULL
llm_provider VARCHAR(32) NULL
llm_model VARCHAR(255) NULL
processing_time_ms BIGINT UNSIGNED NULL
processing_metrics JSON NULL
```

`document_ids_snapshot` يحفظ نطاق البحث لحظة السؤال حتى تبقى الرسالة قابلة للتدقيق إذا غيّر المستخدم ملفات المحادثة لاحقاً.

يبقى `message_sources` ويضاف عند الحاجة:

```text
processing_run_id
processing_profile
source_title
```

`reranker_score` يعني **درجة صلة المصدر بالسؤال**، ولا يسمى دقة الإجابة أو Confidence.

## 174.8 Qdrant Schema

نستخدم Collection منفصلة لكل مساحة Embedding لتجنب مقارنة vectors من نماذج مختلفة:

```text
rag_documents_cloud
rag_documents_hybrid_local
```

كلاهما Dense dimension = 1024 حالياً، لكن تساوي الأبعاد لا يعني توافق فضاءي النموذجين.

Payload كل Chunk:

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

Indexes الإلزامية للأمان والأداء:

```text
user_id
document_id
processing_run_id
processing_profile
```

معرّف Point يكون UUID ثابتاً مشتقاً أو مسجلاً بطريقة تجعل Promotion/Reprocess idempotent. لا يستخدم `RESET_COLLECTION=True`.

## 174.9 اختيار ملفات المحادثة والبحث المقيّد

تجربة المستخدم المطلوبة:

- يمكن للمستخدم أن يرفع ملفات متعددة عبر عمليات Upload منفصلة.
- بعد أن تصبح الملفات `ready` تظهر ضمن مكتبته.
- في أعلى أي محادثة يوجد زر `اختيار الملفات`.
- يختار المستخدم ملفاً واحداً أو عدة ملفات يملكها.
- الاختيار لا يعتمد على مكان المعالجة؛ يعتمد على selected runs الموجودة فعلياً في Qdrant.

Laravel لا يأخذ IDs موثوقة من Browser. في كل سؤال:

1. يتحقق من Ownership للمحادثة.
2. يقرأ `conversation_document` من MySQL.
3. يتحقق أن كل Document مملوك للمستخدم و`ready`.
4. يتحقق أن selected run `indexed`.
5. يبني `document_targets` من بيانات الخادم.
6. يحفظ Snapshot على الرسالة.
7. يرسل الطلب إلى FastAPI.

شكل Target:

```json
{
  "document_id": 12,
  "processing_run_id": 81,
  "processing_profile": "cloud",
  "qdrant_collection": "rag_documents_cloud"
}
```

FastAPI يطبق دائماً:

```text
user_id = current user
AND document_id IN selected documents
AND processing_run_id = selected run for each document
```

ولا يبحث في كل Qdrant.

### محادثة تحتوي Profiles مختلفة

إذا اختيرت وثيقة Cloud ووثيقة Hybrid Local:

1. يجمع FastAPI Targets حسب Profile/Collection.
2. ينشئ Query embedding الصحيح لكل مجموعة.
3. ينفذ Dense + BM25 retrieval داخل كل Collection.
4. يطبق RRF داخل كل Profile.
5. يطبق Reranker الموافق للمسار.
6. يدمج النتائج النهائية بدمج رتبي مثل RRF، لا بمقارنة raw scores من نماذج مختلفة.
7. يبني Context موحداً مع الحفاظ على مصدر كل Chunk.

لا يطلب من المستخدم اختيار Profile في شاشة المحادثة.

في Cloud-only deployment لا تعرض Blade وثيقة يتطلب selected run الخاص بها Provider غير متاح في تلك البيئة، ويشرح النظام سبب عدم توفرها بدلاً من تنفيذ Fallback.

## 174.10 عقد سؤال RAG

### Request من Laravel إلى FastAPI

```json
{
  "user_id": 7,
  "conversation_id": 51,
  "message_id": 900,
  "document_targets": [
    {
      "document_id": 12,
      "processing_run_id": 81,
      "processing_profile": "cloud",
      "qdrant_collection": "rag_documents_cloud"
    }
  ],
  "question": "ما أهم النتائج؟",
  "recent_completed_turns": [
    {
      "user": "لخص المنهجية.",
      "assistant": "تعتمد المنهجية على ..."
    }
  ]
}
```

`recent_completed_turns` اختياري ومحدود بآخر تبادلين مكتملين فقط من المحادثة نفسها، وكل تبادل يتكون من سؤال User وجواب Assistant مكتمل. لا توجد ذاكرة مستخرجة أو Summaries أو Snapshots ذاكرة مستقلة، ولا تدخل رسالة Pending أو Failed في هذا الحقل. يستخدم هذا السياق لفهم الإحالات الحوارية فقط، بينما تبقى Chunks المسترجعة من الوثائق مصدر الحقائق الوحيد.

### Response

```json
{
  "answer": "أظهرت الوثيقة ... [المصدر 1]",
  "llm": {
    "provider": "hugging_face",
    "model": "Qwen/Qwen3.5-9B"
  },
  "processing_profiles": ["cloud"],
  "sources": [
    {
      "source_number": 1,
      "document_id": 12,
      "document_title": "study.pdf",
      "processing_run_id": 81,
      "processing_profile": "cloud",
      "page": 15,
      "section": null,
      "chunk_index": 48,
      "qdrant_point_id": "uuid",
      "reranker_score": 0.91,
      "text_preview": "..."
    }
  ],
  "timings_ms": {
    "query_embedding": 30,
    "retrieval": 80,
    "fusion": 5,
    "reranking": 120,
    "context_building": 4,
    "generation": 900,
    "total": 1139
  }
}
```

`debug` مثل retrieved counts متاح في Development فقط. Laravel يتحقق من DTO schema ولا يحفظ استجابة غير صالحة كإجابة مكتملة.

## 174.11 FastAPI Endpoints المطلوبة

```text
GET  /api/v1/capabilities
GET  /api/v1/health
POST /api/v1/documents/process
POST /api/v1/document-comparisons/{comparison_id}/query
POST /api/v1/document-comparisons/{comparison_id}/select
DELETE /api/v1/documents/{document_id}
POST /api/v1/rag/ask
GET  /api/v1/admin/documents/{document_id}/chunks
```

- `capabilities` يعيد Deployment mode وProfiles المتاحة وصحة Providers دون Secrets.
- `process` يستقبل Run IDs صادرة من Laravel ولا ينشئ ملكية جديدة.
- `query` يعيد Top chunks للتجربة دون نقل vectors.
- `select` ينفذ Promotion idempotent ويعيد count verification.
- Admin chunks endpoint داخلي، paginated، ويتطلب API authentication وبيانات فلترة موثوقة.

كل endpoint يحمل Correlation ID ويدعم أخطاء منظمة وtimeouts وidempotency keys حيث يلزم.

## 174.12 تصميم صفحات Blade بعد تسجيل الدخول

يُطوّر القالب الحالي `resources/views/components/layouts/app.blade.php` من Header بسيط إلى App Shell RTL responsive.

### Navigation

Desktop sidebar:

- لوحة التحكم.
- ملفاتي.
- المحادثات.
- الإعدادات.
- رابط Filament يظهر للمشرف فقط.

Mobile:

- زر لفتح Drawer بنفس العناصر.
- Focus trap وEscape وARIA labels.

Top bar:

- عنوان الصفحة.
- حالة خدمات مختصرة عند الحاجة.
- اسم المستخدم.
- الإعدادات.
- تسجيل الخروج.

### لوحة التحكم `/workspace`

تحل محل النص المؤقت الحالي وتعرض:

- بطاقة عدد الملفات الكلي.
- Ready / Processing / Awaiting selection / Failed.
- عدد المحادثات.
- آخر الملفات مع الحالة والإجراء التالي.
- آخر المحادثات.
- زر `رفع ملف`.
- زر `محادثة جديدة` معطّل مع تفسير إذا لم توجد وثيقة قابلة للمحادثة.
- بطاقة Deployment mode: Cloud أو Local، والقدرات المتاحة بصياغة مفهومة.

### قائمة الملفات `/documents`

- Table على Desktop وCards على Mobile.
- الاسم والعنوان والنوع والحجم والحالة وتاريخ الرفع.
- selected processing profile للوثيقة الجاهزة.
- مؤشرات Processing وAwaiting selection وExpired وFailed.
- بحث وفرز وتصفية بالحالة والنوع.
- Actions: تفاصيل، تنزيل مصرح، إعادة معالجة، مقارنة عند توفرها، حذف.
- لا تعرض Qdrant collection أو IDs تقنية للمستخدم العادي.

### صفحة رفع ملف `/documents/upload`

- Dropzone وFile picker، ملف واحد فقط.
- الأنواع والحجم المسموحان.
- Radio cards للقدرات المتاحة:
  - Cloud.
  - Hybrid Local.
  - Compare both.
- في Online Cloud-only يظهر Cloud ثابتاً ولا تظهر الخيارات الأخرى.
- وصف الخصوصية والاعتماد على Cloud لكل خيار بدقة.
- لا تعرض تقدير تكلفة.
- Progress لحالات Upload وScan وQueue.
- الأخطاء بجانب الحقل مع Retry آمن.
- عند محاولة رفع محتوى مكرر لنفس المستخدم، لا تُنشأ وثيقة جديدة ولا تبدأ معالجة جديدة.
- تعرض الواجهة رسالة واضحة بأن الملف مرفوع مسبقاً، وتتضمن `original_name` للوثيقة الأصلية حتى يعرف المستخدم أي ملف موجود مسبقاً.
- تستخدم الواجهة `duplicate_document.id` لإتاحة إجراء واضح للانتقال مباشرة إلى صفحة الوثيقة الأصلية بدل إعادة رفعها.
- لا تعرض الواجهة `sha256` أو `stored_name` أو `file_path` ضمن رسالة التكرار.
- اختلاف اسم الملف المرفوع لا يغيّر حالة duplicate إذا كان محتواه مطابقاً للوثيقة الأصلية.

### تفاصيل الوثيقة `/documents/{document}`

- بيانات الملف وSHA-256 والحالة.
- Timeline: Upload → Scan → Processing → Selection/Indexing → Ready.
- selected profile ومعلومات Run الملخصة.
- عدد الصفحات/chunks ومدة المعالجة والتحذيرات.
- زر المحادثة مع هذه الوثيقة إذا كانت ready.
- Download/Delete/Reprocess وفق Policy.
- لا تعرض raw vectors.

### شاشة المقارنة `/documents/{document}/comparison`

تعرض Cloud مقابل Hybrid Local في عمودين أو Tabs على الهاتف:

- Profile والنماذج.
- أزمنة المراحل والإجمالي.
- الصفحات والـchunks وأبعاد/عدد vectors.
- Chunk samples وحدود التقسيم.
- Errors/Warnings.
- حقل `سؤال اختبار`.
- زر تشغيل الاختبار.
- أفضل المقاطع والمصادر لكل مسار.
- `reranker_score` بعنوان `درجة صلة المصدر`.
- زر `اعتماد هذا المسار` لكل طرف مع Confirmation واضح.
- Countdown/تاريخ انتهاء artifacts.
- لا يوجد Cost field.

سؤال الاختبار موصى به وليس إلزامياً تقنياً؛ يجوز الاعتماد بدونه بعد Confirmation يوضح أن المقارنة النوعية لم تُجر.

### قائمة المحادثات `/conversations`

- New conversation.
- العنوان وآخر رسالة ووقت التحديث وعدد الملفات المحددة.
- Empty state يوجه أولاً لرفع وثيقة.

### المحادثة `/conversations/{conversation}`

- Sidebar أو قائمة للمحادثات.
- أعلى المحادثة زر واضح `اختيار الملفات`.
- Modal multi-select يعرض فقط وثائق المستخدم الجاهزة والمفهرسة والمدعومة في Runtime الحالي.
- Search داخل الوثائق وSelect all visible.
- Chips بأسماء الملفات المختارة مع إزالة مصرح بها.
- لا يوجد اختيار Cloud/Local هنا.
- يمنع إرسال سؤال بلا وثيقة مع رسالة واضحة.
- الرسائل تدعم Pending/Processing/Failed/Retry.
- أثناء Pending/Processing تظهر عبارة `جاري التفكير` بنبض بصري هادئ، من دون Streaming حقيقي أو أجزاء جواب غير مكتملة.
- بعد حفظ الإجابة المكتملة في MySQL، يكشف Frontend النص تدريجياً كتأثير بصري محلي فقط. لا يعاد التأثير عند فتح رسالة قديمة أو Reload، ويوجد إجراء لإظهار النص كاملاً فوراً.
- عند تفعيل `prefers-reduced-motion` تظهر الإجابة كاملة بلا نبض أو حركة تدريجية.
- جواب المساعد يعرض اسم نموذج التوليد بشكل ثانوي.
- قسم Sources قابل للفتح يعرض الوثيقة والصفحة/القسم وpreview ودرجة صلة المصدر.
- قسم Performance يعرض الزمن الإجمالي وتفصيل المراحل.
- لا تستخدم عبارة `دقة الإجابة` للـreranker.

### حالات الواجهة الإلزامية

- Loading skeletons.
- Empty states.
- Permission denied.
- Provider unavailable.
- Expired comparison.
- Processing failure مع correlation/reference مناسب.
- Offline/network retry دون تكرار Job.
- RTL كامل، keyboard navigation، contrast مناسب، responsive.

## 174.13 Filament

تضاف الموارد:

- Users.
- Documents.
- DocumentProcessingRuns.
- DocumentProcessingComparisons.
- Conversations.
- Messages.

في `DocumentResource` توجد صفحة/Action read-only باسم `Qdrant Chunks`:

- تتصل Laravel بـFastAPI internal admin endpoint فقط.
- لا تتصل Laravel مباشرة بـQdrant.
- تستخدم pagination.
- ترسل `user_id` و`document_id` و`selected_processing_run_id`.
- تعرض النص الكامل للـchunk، الصفحة/القسم، `chunk_index`، Profile، Run ID، Point ID وmetadata.
- لا تعرض raw vectors.
- لا تسمح بتعديل أو حذف Chunk منفرد من Filament.
- تسجل عملية المشاهدة في Admin audit log.

Widgets:

- Documents by aggregate status.
- Runs by Profile/status.
- Comparisons awaiting decision/expired.
- Failed/Infected documents.
- Processing duration percentiles.
- Qdrant indexed chunk counts.

## 174.14 الأمان والخصوصية

- Laravel مصدر الحقيقة للـOwnership.
- Browser لا يرسل IDs يعتمد عليها دون إعادة تحميلها وتفويضها.
- FastAPI لا يقبل Request من Browser مباشرة.
- Internal API key، request signing أو mTLS لاحقاً؛ لا Secrets في logs.
- Qdrant غير مكشوف للإنترنت.
- كل Retrieval/Delete/Admin browse يطبق user/document/run filters.
- Temporary artifacts في مسار خاص، بأذونات محدودة، وCleanup دوري.
- Chunk samples في Laravel تعتبر بيانات وثيقة خاصة وتخضع للـPolicy نفسها.
- Logs لا تحتوي محتوى الوثيقة الكامل أو prompts/sources إلا في وضع Debug محلي صريح.
- Errors العائدة للمستخدم منقحة؛ التفاصيل التقنية في logs مع Correlation ID.

## 174.15 الاختبارات ومعايير القبول

### Database

- Migrations up/down على MySQL.
- Foreign keys والفهارس والأطوال صحيحة.
- لا يمكن ربط Run بوثيقة أخرى.
- لا يمكن اختيار Run غير indexed أو تابع لمستخدم آخر.
- Unique pivot للمحادثة.

### Capabilities

- Cloud mode يعرض ويقبل Cloud فقط.
- Tampered local/compare request في Cloud mode يعود `422`.
- Cloud image لا يحتوي local models أو Torch/Transformers/Ollama.
- Local mode يعلن الخيارات وفق صحة Providers الفعلية.
- Config غير المتوافق يفشل عند startup.

### Local resources and portability

- نفس القيم الدلالية لإعدادات الموارد تعمل على Mac mini M4 بذاكرة 16 GB وعلى ASUS Vivobook S16 بمعالج Intel Core Ultra 7 وذاكرة 16 GB؛ يختلف Backend المحسوم تلقائياً فقط.
- لا يبدأ أكثر من FastAPI worker واحد، ولا تنفذ أكثر من عملية Local AI ثقيلة واحدة في الوقت نفسه.
- لا يحدث تحميل مكرر للنموذج نفسه عند وصول طلبات متزامنة، ولا يحرر نموذج يحمل Lease فعالة.
- بعد كل Stage يحرر النموذج المحلي قبل تحميل النموذج الثقيل التالي، وتعود الذاكرة إلى Idle envelope مقاس بلا نمو تراكمي عبر دورات التحميل والإخلاء.
- لا يسبب اختبار المعالجة أو المحادثة OOM أو Restart لأي خدمة.
- لا يحدث Fallback صامت من MPS/XPU/CUDA إلى CPU ولا من Local provider إلى Cloud provider.
- فرق جودة FP16 على مجموعة التحقق الثابتة لا يتجاوز نقطة مئوية واحدة مقارنة بخط FP32، وإلا يُوثق قرار دقة مختلف بدلاً من تمريره بصمت.

### Compare

- ينشأ Runان فقط للوثيقة نفسها.
- التقرير لا يحتوي vector values ولا cost.
- سؤال الاختبار يعيد مصادر صحيحة لكل مسار.
- اختيار الفائز idempotent.
- لا يُحذف الخاسر قبل نجاح الفائز.
- Cleanup يعلّم expired ويحذف artifacts بعد TTL.

### Security scan lifecycle

- الـDefault هو `DOCUMENT_SECURITY_SCAN_ENABLED=true`.
- عند التفعيل يبقى الملف في Quarantine حتى نجاح `clamscan` بخروج Clean موثق.
- يعمل Scan واحد فقط في الوقت نفسه. في Local Demo يشترك `security-scan` و`ai-local` في قفل Redis عالمي، لذلك لا يتداخل فحص أي ملف مع أي Local AI Stage حتى لو كانا لوثيقتين مختلفتين.
- غياب التواقيع أو فشل تشغيل Process أو Timeout أو خروج غير معروف يعامل Fail-Closed ولا يفعّل bypass.
- تنتهي Process الفحص بعد كل ملف وتتحرر ذاكرتها؛ تبقى التواقيع المحدثة على Volume دائم.
- عند `DOCUMENT_SECURITY_SCAN_ENABLED=false` يخزن Upload الصالح مباشرة في permanent private storage ولا ينشأ Scan requirement؛ يجب إثبات أن القرار جاء من Configuration موثوقة لا من Request المستخدم.
- لا يملك Laravel أو أي Container تطبيق وصولاً إلى Docker socket.

### Qdrant

- Collections منفصلة.
- payload indexes موجودة.
- count بعد Promotion يساوي expected vector_count.
- Reprocess لا يكرر Points.
- كل query مفلتر بـuser/document/run.

### Conversations

- ملف واحد وعدة ملفات.
- ملفات Profiles متجانسة ومختلطة.
- تغيير الملفات يؤثر على الأسئلة الجديدة فقط ويحفظ Snapshot القديم.
- User A لا يستطيع اختيار أو البحث في Document User B حتى بطلب يدوي.
- المصادر تعود للوثائق المختارة فقط.
- timing totals منطقية، ودرجة reranker تعرض كصلة مصدر.
- لا يوجد جدول أو Extractor لذاكرة محادثة. يسمح فقط بآخر تبادلين مكتملين من المحادثة نفسها كسياق إحالة محدود، ولا تعتبر إجابات المساعد السابقة مصدراً للحقائق.

### Filament

- المشرف فقط يرى Chunks.
- pagination والفلاتر إلزامية.
- لا vectors ولا direct edit/delete.
- كل Browse audited.

### UI

- Cloud-only لا يظهر خيارات غير متاحة.
- Upload ملف واحد.
- عند duplicate upload لنفس المستخدم تظهر رسالة قابلة للتصرف تذكر `original_name` للوثيقة الأصلية وتتيح الانتقال إليها عبر `duplicate_document.id`، دون كشف `sha256` أو `stored_name` أو `file_path`.
- حالة duplicate متوافقة مع RTL وAccessibility والشاشات الصغيرة، ولا تعرض كرسالة خطأ عامة فقط.
- Comparison responsive وبحالات loading/error/expired.
- اختيار عدة وثائق من أعلى المحادثة.
- Sources وtimings ظاهرة ويمكن الوصول إليها بلوحة المفاتيح.
- `جاري التفكير` مرئية ومفهومة لقارئ الشاشة من دون إعلان متكرر مزعج، والتأثير التدريجي لا يغير النص المحفوظ ولا يؤخر إتاحته للمستخدم الذي يفضل تقليل الحركة.

## 174.16 خريطة المهام المنقحة

هذه الخريطة تستبدل تفاصيل المراحل B–Q القديمة عند التعارض، مع إبقاء A1–A5 منجزة كما هي. كل Task تنفذ في Chat وفرع وPR مستقلين ما لم يسجل ملف التقدم دمجاً صريحاً.

### المرحلة B — Documents Foundation

- `B1` documents migration وفق 174.7.1 فقط.
- `B2` Document model والعلاقات الأساسية.
- `B3` FileType وDocumentStatus enums/casts.
- `B4` DocumentPolicy واختبارات ownership.
- `B5` Documents index/details Blade skeleton.
- `B6` Upload validation لملف واحد PDF/DOCX/TXT.
- `B7` Private storage/download authorization.
- `B8` SHA-256 وسياسة duplicate على مستوى Application.
- `B9` document_processing_runs migration.
- `B10` ProcessingRun model/enums/relations.
- `B11` selected_processing_run_id migration وDomain invariants.
- `B12` document_processing_comparisons migration/model.

### المرحلة C — Security Pipeline

- `C1` On-demand ClamAV CLI scan worker مع signature volume دائم وQueue `security-scan` متسلسلة، من دون `clamd` دائم أو Docker socket داخل Laravel.
- `C2` DocumentSecurityService.
- `C3` Temporary upload flow.
- `C4` clean path.
- `C5` infected/fail-closed path عندما يكون Security Scan مفعّلاً.
- `C6` configurable security-scan routing: `DOCUMENT_SECURITY_SCAN_ENABLED=true` افتراضياً، direct permanent storage عند التعطيل الصريح، وإعادة تسمية storage API المباشر إلى `storePermanent()` بعد التحقق من callers.
- `C7` aggregate status transitions للمسارين: enabled (`pending → scanning → ...`) وdisabled (`pending → queued` عند dispatch الفعلي).
- `C8` security tests لكلا المسارين مع إثبات عدم وجود fallback تلقائي عند فشل ClamAV.

### المرحلة D — FastAPI Foundation and Capabilities

- `D1` FastAPI project.
- `D2` typed config.
- `D3` structured logging/correlation IDs.
- `D4` internal API security.
- `D5` health.
- `D6` versioned DTO schemas.
- `D7` structured exceptions.
- `D8` deployment capabilities endpoint.
- `D9` startup configuration validation.
- `D10` base/cloud/local dependency split.
- `D11` Local runtime/device resolver, startup probe, readiness details and resource telemetry.

### المرحلة E — Qdrant

- `E1` Local Qdrant + persistent volume.
- `E2` cloud collection.
- `E3` hybrid-local collection.
- `E4` dense/sparse configs.
- `E5` payload indexes.
- `E6` Point builder with run metadata.
- `E7` idempotent upsert/count/delete.
- `E8` cross-user leakage tests.

### المرحلة F — Parsing and Normalization

- `F1` Loader interface.
- `F2` LlamaParse provider.
- `F3` PDF loader.
- `F4` DOCX loader.
- `F5` TXT loader.
- `F6` normalized document/page/section schema.
- `F7` shared parse result reuse for Compare.
- `F8` loader tests.

### المرحلة G — Profile Processing

- `G1` ProcessingProfile interface/registry.
- `G2` Cloud chunking.
- `G3` Cloud Jina embeddings.
- `G4` Cloud sparse representation.
- `G5` Hybrid Local chunking.
- `G6` Local BGE-M3 embeddings.
- `G7` Local BM25.
- `G8` batching/retries/rate limits.
- `G9` metrics/report builder بلا vectors/cost.
- `G10` profile parity and isolation tests.
- `G11` single-active-model coordinator مع Lazy load وLease للاستخدام الحالي وتحرير صريح بعد كل Stage وقياسات قبل/بعد.

### المرحلة H — Temporary Artifacts and Promotion

- `H1` private artifact store/opaque references.
- `H2` 24-hour TTL configuration.
- `H3` temporary retrieval index.
- `H4` winner promotion.
- `H5` count verification.
- `H6` loser cleanup.
- `H7` scheduled expiration cleanup.
- `H8` idempotency/failure recovery tests.

### المرحلة I — Laravel Processing Orchestration

- `I1` AiServiceClient.
- `I2` processing DTOs.
- `I3` ProcessDocumentJob.
- `I4` single-profile flow.
- `I5` compare flow وإنشاء Runين.
- `I6` report persistence.
- `I7` test-question endpoint flow.
- `I8` winner selection transaction.
- `I9` aggregate status projector.
- `I10` queue retries/timeouts.
- `I11` dedicated `ai-local` queue/routing with one worker and bounded wait/cancellation behavior.

### المرحلة J — Blade Documents Experience

- `J1` authenticated responsive app shell/sidebar.
- `J2` workspace dashboard.
- `J3` documents list/cards/filters.
- `J4` one-file upload page, capability-aware options, and duplicate-document UX linking to the original document.
- `J5` document details/timeline.
- `J6` comparison screen.
- `J7` trial-question interaction.
- `J8` select-winner confirmation/states.
- `J9` accessibility/responsive/error states, including actionable duplicate-upload states.

### المرحلة K — Conversations Database

- `K1` conversations migration/model.
- `K2` conversation_document unique pivot.
- `K3` messages migration with snapshots/metrics.
- `K4` message_sources with run/profile.
- `K5` policies.
- `K6` create/list conversations.
- `K7` multi-document selection.
- `K8` ready/indexed/runtime-capable filtering.

### المرحلة L — Retrieval and Reranking

- `L1` trusted document_targets contract.
- `L2` Cloud query embeddings/retrieval.
- `L3` Hybrid Local query embeddings/retrieval.
- `L4` mandatory user/document/run filters.
- `L5` per-profile Dense + BM25 + RRF.
- `L6` Cloud Jina reranker.
- `L7` Local BGE reranker.
- `L8` cross-profile rank fusion.
- `L9` metadata/source preservation.
- `L10` retrieval quality/security tests.

### المرحلة M — Generation

- `M1` ContextService مع Chunks المسترجعة وآخر تبادلين مكتملين فقط كسياق إحالة محدود، بلا ذاكرة مستخرجة أو جدول إضافي.
- `M2` prompt/insufficient-context behavior.
- `M3` LLMProvider interface/registry keyed by trusted Processing profile and capabilities، بلا global provider switch.
- `M4` HF Router `Qwen/Qwen3.5-9B`.
- `M5` Ollama `qwen3.5:4b`.
- `M6` no-fallback/provider validation.
- `M7` answer/sources/timings response.
- `M8` provider contract tests.
- `M9` Ollama/FastAPI resource coordination مع `keep_alive=0` وتحرير النموذج المحلي بعد انتهاء Generation.
- `M10` Local resource lifecycle, pressure recovery and no-leak concurrency tests.

### المرحلة N — Chat Experience

- `N1` chat layout/list.
- `N2` top document multi-selector.
- `N3` selected chips and authorization.
- `N4` AskConversationJob.
- `N5` save snapshots/answer/metrics.
- `N6` sources drawer with relevance score.
- `N7` timings display.
- `N8` pending/failure/retry مع حالة `جاري التفكير` النابضة وprogressive reveal بصري بعد اكتمال الإجابة.
- `N9` mixed-profile end-to-end chat واختبارات عدم وجود Streaming backend واحترام reduced-motion وعدم إعادة التحريك للرسائل القديمة.

### المرحلة O — Filament

- `O1` Resources الأساسية.
- `O2` ProcessingRuns/Comparisons resources.
- `O3` dashboard widgets.
- `O4` failed/infected/expired filters.
- `O5` safe retry actions.
- `O6` FastAPI admin chunks endpoint/client.
- `O7` read-only paginated Qdrant Chunks view.
- `O8` admin audit logging.
- `O9` authorization/no-vectors tests.

### المرحلة P — Security and Operations

- `P1` ownership/IDOR.
- `P2` Qdrant leakage across users/documents/runs.
- `P3` MIME/size/malware.
- `P4` FastAPI authentication.
- `P5` private download and chunk-sample authorization.
- `P6` artifact TTL/permissions.
- `P7` secret/log redaction.
- `P8` deletion/reprocessing consistency.

### المرحلة Q — Final Validation and Deployment

- `Q1` PDF/DOCX/TXT E2E.
- `Q2` Cloud profile E2E.
- `Q3` Hybrid Local profile E2E.
- `Q4` Compare/select E2E.
- `Q5` multi-document/mixed-profile chat E2E.
- `Q6` queue/service restart and Qdrant persistence.
- `Q7` RAG quality/source correctness.
- `Q8` security-scan/AI stage memory/performance/quality calibration report on both approved 16 GB devices using the same semantic configuration.
- `Q9` Cloud-only lightweight image verification.
- `Q10` Local Ollama profile verification, including selected accelerator/backend, loaded-model count, load/generation/release timings و`keep_alive=0` بعد كل Generation.
- `Q11` backup/restore.
- `Q12` final documentation.

## 174.17 Definition of Done الجديدة

### Document ready

- الملف مملوك للمستخدم واجتاز Validation، واستوفى Security policy: Clean موثق إذا كان الفحص مفعلاً، أو explicit trusted configuration عطّل الفحص.
- يوجد selected processing run واحد.
- Run حالته `indexed`.
- Qdrant count مطابق وpayload كامل.
- لا توجد artifacts خاسرة غير منتهية بلا سبب.
- الوثيقة تظهر كخيار محادثة فقط إذا كان Runtime يستطيع الاستعلام من Profile المختار.

### Compare decided

- التقرير للطرفين محفوظ بلا vectors أو cost.
- سؤال الاختبار، إن استخدم، ومصادره محفوظة.
- الفائز رُفع وتحقق count.
- Transaction حدّث selected run والحالات.
- الخاسر حُذف من التخزين المؤقت بعد النجاح.

### Question completed

- Conversation والمستندات مخولة.
- Snapshot محفوظ.
- Qdrant filters تشمل user/document/run.
- Profiles المختلطة عولجت دون مقارنة raw scores.
- Answer وsources وtimings والنموذج محفوظون.
- كل Source يعرض درجة صلة المصدر لا ادعاء دقة الإجابة.

## 174.18 قرارات غير قابلة للتفاوض

1. Online لا يحمل أي Local embedding/reranker/LLM model.
2. Cloud deployment يقبل Cloud processing فقط في UI وbackend.
3. Local deployment يمكنه Cloud أو Hybrid Local أو Compare.
4. Compare لا يرسل raw vectors إلى Laravel ولا يعرض Cloud cost.
5. لا يدخل Qdrant إلا selected winner.
6. لا Retrieval بلا `user_id + document_id + processing_run_id`.
7. Laravel هو مصدر الحقيقة للملكية والاختيار.
8. FastAPI بلا قاعدة أعمال علائقية مستقلة في v1.
9. المحادثة تختار وثيقة أو عدة وثائق، لا Processing profile.
10. `reranker_score` درجة صلة مصدر وليست دقة إجابة.
11. Filament يشاهد Chunks عبر FastAPI read-only ولا يتصل مباشرة بـQdrant.
12. Local generation model هو `qwen3.5:4b` عبر Ollama.
13. Cloud generation model هو `Qwen/Qwen3.5-9B` عبر Hugging Face Router.
14. B1 يبقى ضيقاً وينشئ جدول documents فقط وفق 174.7.1.
15. توجد سياسة موارد محلية واحدة لجميع الأجهزة المدعومة؛ Backend العتاد تفصيل Runtime وليس Processing Profile ولا خطة مستقلة لكل جهاز.
16. في Local topology تعمل FastAPI وOllama على Host OS، وتبقى Laravel وQueue وMySQL وRedis وQdrant داخل Docker؛ عند تفعيل Security Scan يعمل ClamAV كعملية `clamscan` قصيرة العمر داخل Security Scan Worker مخصص.
17. يعمل FastAPI بعملية واحدة، وتنفذ عملية Local AI ثقيلة واحدة فقط في الوقت نفسه عبر Queue وSemaphore دفاعية.
18. نماذج BGE تُحمّل Lazy، ولا يوجد أكثر من نموذج AI ثقيل فعال في الوقت نفسه؛ يحرر كل نموذج بعد انتهاء مرحلته قبل تحميل النموذج التالي.
19. لا يوجد Fallback صامت بين الأجهزة أو الدقة أو Providers؛ الفشل يعاد كخطأ منظم وقابل للتشخيص.
20. `DOCUMENT_SECURITY_SCAN_ENABLED=true` هو Default. عند التفعيل لا يبدأ AI إلا بعد Clean موثق وانتهاء Process الفحص؛ عند التعطيل الصريح يمكن المتابعة بعد Validation والتخزين الدائم. فشل ClamAV لا يغيّر القيمة ولا يفعّل bypass.
21. NPU/OpenVINO وQuantization لنماذج BGE ليسا Baseline للنسخة الأولى، ولا يضافان إلا بقرار لاحق مدعوم بقياسات Q8.

## 174.19 إدارة الموارد المحلية الموحّدة

### 174.19.1 السلطة والنطاق

> **حالة هذا القسم:** تبقى تفاصيله مرجعاً للأجهزة والقياس والـBackends فقط. استبدل القسم 174.20 نهائياً أحكام ClamAV الدائم وLRU/TTL متعدد النماذج وKeep-alive؛ عند أي تعارض تطبق 174.20.

هذا القسم هو المرجع الملزم لكل ما يخص استهلاك RAM/accelerator memory وتشغيل نماذج Local AI. عند التعارض، يستبدل افتراضات الموارد القديمة في الأقسام التي بُنيت على Free VM أو `llama.cpp` داخل Docker.

ينطبق على المسارين المحليين `hybrid_local` و`compare` وعلى أي محادثة تستخدم وثيقة ذات selected run محلي. لا يغيّر Profiles الرسمية الثلاثة:

```text
cloud
hybrid_local
compare
```

الهدف هو Configuration دلالية واحدة تعمل بلا تخصيص يدوي على:

- Mac mini M4 بذاكرة موحدة 16 GB.
- ASUS Vivobook S16 بذاكرة 16 GB ومعالج Intel Core Ultra 7.

لا توجد خطة Mac وخطة ASUS. يختار Runtime الـBackend المدعوم، بينما تبقى حدود الـQueue والـbatch والسياق وسياسة `single_active` نفسها. لا يعني ذلك استخدام binary wheel مطابق على نظامي التشغيل؛ يسمح D10 بـplatform markers أو Bootstrap مثبت ومدقق لاختيار Build PyTorch المتوافق، من دون تفريع منطق التطبيق أو ملفات Configuration الدلالية. النسخة Online Cloud-only خارج هذا الـbudget ولا تثبت أو تحمل أي أوزان محلية.

### 174.19.2 Topology المحلية المعتمدة

| مكان التشغيل | الخدمات | السبب |
|---|---|---|
| Docker Compose | Laravel، Queue worker، Security Scan Worker مع `clamscan/freshclam`، MySQL، Redis، Qdrant | عزل البنية التحتية وثبات الشبكات والـVolumes من دون `clamd` أو updater دائمة |
| Host OS | FastAPI local runtime | الوصول المباشر إلى MPS على macOS أو XPU/CPU على Windows بلا افتراض GPU passthrough من Docker Desktop |
| Host OS | Ollama | استخدام Metal أو Vulkan/المسرّع الذي يكتشفه Ollama وإبقاء LLM خارج FastAPI |

العناوين الافتراضية:

```text
Host FastAPI → Host Ollama:        http://127.0.0.1:11434/v1
Docker Laravel/Queue → Host API:  http://host.docker.internal:8000
Host diagnostics → Host API:      http://127.0.0.1:8000
Host FastAPI → Docker Qdrant:     http://127.0.0.1:6333 أو عنوان منشور قابل للضبط
```

- ترتبط FastAPI محلياً على `0.0.0.0:8000` فقط عند الحاجة لوصول Docker، مع بقاء Authentication الداخلي إلزامياً وعدم فتح المنفذ للإنترنت.
- لا يثبت أي Endpoint في الكود؛ جميع القيم Environment/configuration.
- على Cloud/Docker تبقى العناوين الداخلية مثل `http://fastapi:8000` صالحة لذلك الـTopology فقط.
- ينشر Docker منفذ Qdrant على Loopback المحلي فقط، مثل `127.0.0.1:6333:6333`، حتى يصل إليه FastAPI المضيف من دون كشفه للشبكة العامة.
- `TEMP_ARTIFACT_ROOT=/app/data/artifacts` قيمة Container؛ في Local Host-native يضبط لمسار Host خاص دائم وقابل للتنظيف. يبقى المرجع العائد إلى Laravel opaque token ولا يرسل filesystem path.
- لا يتم نقل Laravel أو Qdrant إلى Host لمجرد تخفيف الذاكرة، ولا يتم تشغيل BGE داخل Laravel worker.

### 174.19.3 Device resolver والدقة

ينفذ `D11` حسم الجهاز مرة واحدة عند Startup بالترتيب الحتمي التالي:

```text
CUDA → Intel XPU → Apple MPS → CPU
```

القواعد:

1. `LOCAL_DEVICE=auto` يعني اختيار أول Backend ينجح في Capability probe فعلي، لا الاكتفاء بوجود package أو اسم جهاز.
2. `LOCAL_DTYPE=auto` يختار FP16 على accelerator وFP32 على CPU.
3. يشمل Probe إنشاء Tensor صغير وتنفيذ عملية قصيرة والتحقق من إمكان تحرير الذاكرة، من دون تحميل BGE-M3 وReranker معاً.
4. ينشر `/api/v1/health` و`/api/v1/capabilities` الـBackend والدقة المختارين وحالة الـprobe، بلا معلومات حساسة.
5. إذا طلبت قيمة صريحة مثل `LOCAL_DEVICE=xpu` ولم تنجح، تصبح Local capability غير جاهزة ويظهر سبب منظم.
6. إذا حدث OOM أو خطأ Backend بعد الاختيار، لا ينتقل Runtime بصمت إلى CPU. يطبق مسار الاسترداد المحدود ثم يعيد `local_resource_exhausted` أو `local_backend_failed`.
7. اختيار CPU مسموح فقط كنتيجة Startup معلنة لـ`auto` عندما لا يوجد Accelerator صالح، أو باختيار صريح من المشغّل.
8. NPU ليس ضمن Resolver في v1، ولا يضاف OpenVINO أو مسار رابع خاص بجهاز ASUS.

بالنسبة إلى Ollama، يسجل التطبيق الـbackend الفعلي الذي أعلنه Runtime في Logs/diagnostics عند توفره. لا يفترض أن وجود Intel GPU يعني أن Vulkan استخدم فعلاً.

### 174.19.4 ما الذي يُدار كنموذج ثقيل

الأوزان الثقيلة المدارة هي:

- `BAAI/bge-m3` للـDense embedding المحلي.
- `BAAI/bge-reranker-v2-m3` للـReranking المحلي.
- `qwen3.5:4b` داخل Ollama، ويدار عبر Ollama API/keep-alive لا داخل Python process.

أما:

- RRF فهو خوارزمية دمج رتب ولا يملك أوزاناً تُحمّل.
- BM25 ليس Transformer model؛ بياناته/فهرسه تُدار ضمن artifacts أو Qdrant حسب تنفيذ G7 ولا يدخل Model cache الخاصة بـBGE.
- Qdrant وMySQL وRedis وClamAV ليست نماذج، ولا تطبق عليها سياسة unload الخاصة بالأوزان.

تبقى ملفات الأوزان في Disk cache المحلية بعد أول تنزيل. إخلاء نموذج من RAM/accelerator memory لا يعيد تنزيله في الطلب التالي.

### 174.19.5 دورة حياة النموذج المحلي

استُبدل تصميم Registry متعدد الـEntries وسياسة LRU/TTL بالمنسق البسيط `single_active` المحدد في 174.20.3. يحمل FastAPI نموذجاً ثقيلاً واحداً فقط عند بدء مرحلته، ويحرره بعد انتهائها، مع Worker واحد وبوابة تزامن واحدة. تبقى ملفات الأوزان على القرص ولا يعاد تنزيلها عند كل استخدام.

### 174.19.6 Concurrency والـQueues

الحماية مزدوجة:

1. يوجّه Laravel كل Job يستخدم BGE أو Ollama إلى Queue موثوقة باسم `ai-local` ويعمل لها Worker واحد.
2. يطبق FastAPI Semaphore داخلية بقيمة `LOCAL_AI_MAX_CONCURRENCY=1` كحماية من الطلبات التي تصل خارج Queue أو من أكثر من caller.
3. عند الحاجة لتجنب حجب Event loop، تنفذ عمليات inference في bounded executor سعته `1` تحت الـSemaphore نفسها؛ لا تستخدم زيادة Uvicorn workers كبديل.

```text
LOCAL_AI_QUEUE=ai-local
LOCAL_AI_QUEUE_CONCURRENCY=1
LOCAL_AI_MAX_CONCURRENCY=1
LOCAL_HEAVY_RESOURCE_LOCK_ENABLED=true
LOCAL_HEAVY_RESOURCE_LOCK_KEY=rag:local:heavy-resource
LOCAL_HEAVY_RESOURCE_LOCK_TIMEOUT=600
```

قواعد التوجيه:

- Cloud-only processing/chat يبقى على Queue منفصلة ولا يحتاج Local model lease.
- `hybrid_local` يمر عبر `ai-local`.
- `compare` يعيد استخدام Parsed result المشترك، ثم يمرر كل مرحلة Local ثقيلة تسلسلياً عبر البوابة نفسها.
- محادثة Mixed-profile تستخدم `ai-local` إذا احتوى أي Target على `hybrid_local`; لا تشغّل Local query embedding/reranker/generation بالتوازي.
- في Local Demo يكتسب كل من Laravel `security-scan-worker` وLaravel `ai-local` worker القفل العالمي نفسه `rag:local:heavy-resource`. يمسك `ai-local` القفل طوال استدعاء FastAPI المحلي وكل مراحله، بينما يمسك Security worker القفل طوال Process الفحص أو تحديث التواقيع. يستخدم القفل Owner token وTTL/heartbeat، ويحرر في `finally` بعملية compare-and-delete ذرّية؛ يبقى Redis داخل Docker ولا يحتاج Host FastAPI إلى اتصال مباشر به.
- في Oracle Cloud-only يعطّل هذا القفل لأن لا Model محلياً، وتبقى `security-scan` متسلسلة وحدها.
- انتظار الـSemaphore يقاس منفصلاً عن زمن التنفيذ الفعلي، وله Timeout وإلغاء منظم وIdempotency؛ لا تبقى Jobs معلقة بلا حد.
- لا يرفع التوازي لتسريع جهاز أقوى قبل قياس Q8 وإصدار قرار معماري جديد، حفاظاً على الخطة الموحدة.

### 174.19.7 Batching وحدود السياق

القيم الابتدائية المتوازنة:

```text
LOCAL_EMBED_BATCH_SIZE=2
LOCAL_RERANK_BATCH_SIZE=2
LOCAL_EMBED_MAX_LENGTH=1024
LOCAL_RERANK_MAX_LENGTH=1024
DENSE_CANDIDATES=12
SPARSE_CANDIDATES=12
RRF_TOP_K=12
RERANK_TOP_N=5
LOCAL_LLM_CONTEXT_SIZE=8192
MAX_GENERATION_TOKENS=700
```

- الـbatch قابلة للخفض تلقائياً مرة واحدة فقط إلى `1` في مسار الاسترداد من ضغط الذاكرة.
- لا يزيد Context أو Top-N لإخفاء مشكلة جودة قبل قياس أثره على RAM/latency.
- أي تعديل دائم لهذه القيم يسجل في Q8 مع قياس جودة وأداء، ولا ينشئ Profile خاصاً بجهاز بعينه.

### 174.19.8 تنسيق Ollama

يعمل Ollama process باستمرار، لكن أوزان Qwen لا تبقى في الذاكرة بعد اكتمال Generation:

```text
OLLAMA_MAX_LOADED_MODELS=1
OLLAMA_NUM_PARALLEL=1
OLLAMA_KEEP_ALIVE=0
```

- يرسل Provider `keep_alive=0` مع كل طلب Generation، ويتحقق `M9` من التحرير عبر Ollama lifecycle/diagnostics API.
- لا يطلب إخلاء Qwen أثناء Generation فعالة؛ يبدأ التحرير بعد اكتمالها فقط.
- يسجل `load_duration` ووقت التحرير لتقييم كلفة التحميل من القرص.
- لا يحمل Ollama أكثر من Model واحد ولا ينفذ أكثر من Generation واحدة بالتوازي في Baseline.

عند الانتقال بين BGE وGeneration يتحقق المنسق من تحرير النموذج السابق أولاً ثم يعيد قياس الذاكرة، وفق 174.20.3.

### 174.19.9 الخدمات التي تبقى عاملة

- تبقى Qdrant وMySQL وRedis وLaravel وQueue عاملة، لأنها طبقات حالة/تنسيق وليست Model cache.
- لا يبقى `clamd` عاملاً. تشغّل Queue `security-scan` عملية `clamscan` قصيرة العمر لكل ملف، وتنتهي العملية وتتحرر ذاكرتها قبل إطلاق أي مرحلة AI، وفق 174.20.2.
- تبقى تواقيع ClamAV محفوظة ومحدثة على القرص، ويبقى الفشل Fail-closed.
- لا توضع Docker memory limits عشوائية قبل قياس Q8؛ الحدود القاسية غير المقاسة قد تحول الضغط الطبيعي إلى OOM/Restart.
- يبقى Qdrant على Persistent Volume. تفعيل on-disk/cold tier خيار تحسين بعد القياس فقط إذا أثبت Q8 حاجة فعلية، وليس Baseline مسبقاً.
- لا تستخدم Swap كبديل عن RAM أو كدليل نجاح للأداء؛ إن وجدت فهي شبكة أمان للنظام ويجب أن تظهر في القياسات.

### 174.19.10 مسار ضغط الذاكرة والفشل

قبل Load، وعند Memory pressure/OOM قابل للاسترداد، ينفذ Coordinator بالترتيب:

1. يمنع دخول عملية Local AI ثقيلة ثانية.
2. يكتسب القفل العالمي ويتحقق أن لا عملية فحص أو Local AI أخرى فعالة وأن النموذج السابق تحرر.
3. يشغل Python GC وBackend cache cleanup أو `keep_alive=0` حسب الـRuntime، ثم يعيد قياس available memory.
4. يعيد المحاولة مرة واحدة فقط مع Batch مصغرة حتى `1` إذا كانت المرحلة تدعم batching.
5. إذا لم يتحقق حد الذاكرة أو تكرر OOM، ينهي الطلب بخطأ منظم `local_resource_exhausted` مع `stage`, `backend`, `requested_model`, `retryable` وCorrelation ID، من دون Secrets أو محتوى وثيقة.

ممنوع:

- Retry loop بلا حد.
- التحول الصامت إلى CPU أو Cloud.
- إخلاء Model قيد الاستخدام.
- تشغيل `clamscan` ومرحلة AI ثقيلة في الوقت نفسه.
- قتل Qdrant أو MySQL لتوفير RAM مؤقتاً.
- اعتبار نجاح Response بعد Restart تلقائي للخدمة اختباراً ناجحاً.

### 174.19.11 Observability الإلزامية

تسجل Metrics منظمة لكل Request/Stage:

```text
selected_backend
selected_dtype
model_id
model_state_before/after
model_load_duration_ms
queue_wait_ms
heavy_resource_lock_wait_ms
active_duration_ms
process_rss_bytes
system_available_memory_bytes_before/after
system_available_memory_bytes_after_release
accelerator_allocated_bytes
accelerator_cached_or_reserved_bytes
release_duration_ms
ollama_unload_count
retry_count
peak_memory_by_stage
scan_process_peak_rss_bytes
```

- تستخدم Metrics المناسبة المتاحة لكل Backend، ولا تختلق قيمة Accelerator عند CPU.
- لا تسجل prompts أو نص الوثيقة أو vectors.
- تعرض Capabilities حالة Backend و`active_model` الحالية أو `none` وسبب عدم الجاهزية المنقح، لا مسارات الملفات أو Secrets.
- Q8 يفصل زمن تحميل الأوزان عن inference والتحرير حتى لا يخفي المتوسط كلفة دورة `load/use/release`.

### 174.19.12 اختبارات القبول والقياس

تنفذ Q8 وQ10 على الجهازين بالقيم الدلالية نفسها. يسمح فقط بأن تختلف نتيجة `LOCAL_DEVICE=auto` والـbackend الذي يعلنه Ollama.

سيناريو القياس الأدنى:

1. Startup بلا أوزان BGE محملة وتسجيل baseline.
2. فحص أمني بعملية قصيرة العمر، ثم إثبات خروجها وتحرير القفل قبل بدء Embedding، مع طلب AI لوثيقة أخرى لإثبات أن المنع عالمي.
3. Cold embedding ثم إثبات التحرير، وبعده Cold reranking ثم إثبات التحرير.
4. سؤال Hybrid Local كامل مع Ollama وإثبات `keep_alive=0` بعد Generation.
5. Compare يعيد استخدام Parse ويثبت عدم توازي مرحلتين Local ثقيلتين.
6. ضغط متعمد آمن يختبر خفض Batch مرة واحدة والخطأ المنظم عند الحاجة.
7. ثلاث دورات load/use/release لاكتشاف تسرب أو نمو تراكمي.
8. طلبان متزامنان يثبتان عدم duplicate load وأن العملية الثانية تنتظر البوابة، وScan متزامن يثبت انتظار القفل العالمي.

معايير النجاح:

- لا OOM ولا Restart للخدمات في السيناريو الطبيعي.
- لا أكثر من FastAPI worker واحد ولا أكثر من Local heavy operation واحدة.
- لا duplicate model loads ولا تحرير لنموذج قيد الاستخدام.
- بعد أول دورة Warm-up/unload يحدد Idle envelope المقاس؛ في الدورتين اللاحقتين لا يظهر نمو RSS تراكمي يتجاوز الأكبر من `256 MiB` أو `10%` من ذلك الـenvelope من دون تفسير موثق.
- تبقى ذاكرة النظام المتاحة فوق الحد الأدنى أثناء Load العادي، أو يفشل الطلب مبكراً بخطأ منظم قبل OOM.
- فرق مقياس الجودة المختار مسبقاً، مثل Recall@K أو nDCG@K على Golden set ثابتة، بين FP16 وFP32 لا يتجاوز نقطة مئوية واحدة.
- يسجل التقرير أزمنة ومعدل الذاكرة الأقصى لكل Stage، وload/inference/release، والـbackend والدقة والإصدارات.
- نجاح الجهازين لا يعني تساوي الزمن؛ المطلوب سلوك آمن ومتسق، وتوثق فروق الأداء الفعلية بلا افتراضات مسبقة.

### 174.19.13 نطاق المهام الجديدة

| المهمة | نطاقها الملزم | لا يشمل |
|---|---|---|
| `D11` | Device resolver، dtype policy، startup probe، readiness/capabilities وtelemetry adapters | تحميل BGE business flow أو Queue routing |
| `G11` | منسق `single_active`، التحميل الكسول، تحرير النموذج بعد مرحلته، memory gates وmetrics | Ollama model lifecycle أو Laravel queue |
| `I11` | `ai-local` queue، routing trusted profiles، worker concurrency=1، وقفل Redis عالمي مشترك مع `security-scan` مع timeout/owner token/atomic release | تنفيذ النماذج نفسها |
| `M9` | Ollama `keep_alive=0` والتحقق من التحرير قبل/بعد Generation | تغيير النموذج المحلي المعتمد |
| `M10` | lifecycle/concurrency/pressure/recovery/no-leak tests | Hardware-specific tuning منفصل لكل جهاز |
| `Q8` | تقرير RAM/accelerator/latency/quality على الجهازين وتثبيت Baseline | رفع concurrency أو إدخال quantization تلقائياً |
| `Q10` | إثبات backend الفعلي وloaded-model count وload/generation/release timings و`keep_alive=0` لـOllama | دعم NPU أو Runtime جديد |

تعتمد المهام `D11/G11/I11/M9/M10/Q8/Q10` هذا القسم Required Context إلزامياً عند فتح Chat التنفيذ الخاص بها.

### 174.19.14 ما يؤجل لما بعد Baseline

لا يدخل v1 قبل إثبات الحاجة بقياسات Q8:

- ONNX/OpenVINO أو INT8 لنماذج BGE.
- NPU execution.
- أكثر من Local AI request متزامن.
- أكثر من FastAPI worker في Local mode.
- Qdrant on-disk vectors أو Docker hard memory limits.
- Profile أو ملف Environment مختلف لكل جهاز.

إذا أثبت Q8 أن BGE FP32 على CPU لا يحقق الحد المقبول أو أن الذاكرة لا تستقر، يفتح قرار تحسين مستقل يقارن ONNX/INT8 بجودة FP32. لا يغير Baseline بصمت.

### 174.19.15 المراجع التنفيذية الرسمية

- FastAPI deployment concepts وحقيقة أن كل worker يملك ذاكرته: https://fastapi.tiangolo.com/deployment/concepts/
- PyTorch MPS: https://docs.pytorch.org/docs/stable/notes/mps.html
- PyTorch Intel XPU: https://docs.pytorch.org/docs/stable/notes/get_start_xpu.html
- Ollama FAQ للـkeep-alive والتوازي: https://docs.ollama.com/faq
- Ollama GPU/Vulkan support: https://docs.ollama.com/gpu
- BGE-M3 model card: https://huggingface.co/BAAI/bge-m3
- BGE Reranker v2-m3 model card: https://huggingface.co/BAAI/bge-reranker-v2-m3
- ClamAV Docker/resource guidance: https://docs.clamav.net/manual/Installing/Docker.html
- Docker Desktop host networking: https://docs.docker.com/desktop/features/networking/networking-how-tos/
- Qdrant storage options: https://qdrant.tech/documentation/manage-data/storage/

---

## 174.20 قرار التبسيط النهائي — الفحص عند الطلب، نموذج واحد، سياق محدود، وعرض تدريجي بصري

### 174.20.1 السلطة والغاية

هذا القسم قرار نهائي معتمد ومحدّث بتاريخ 2026-08-24، وهو المرجع الأعلى عند أي تعارض مع 174.19 أو الأقسام الأقدم. غايته إبقاء المشروع قابلاً للتنفيذ والفهم على جهازي 16 GB مع جعل Security Scan قابلاً للضبط من دون تحويل أعطال الفاحص إلى تجاوز أمني صامت.

القرارات الأساسية:

1. يبقى ClamAV جزءاً رسمياً من المشروع ويكون Security Scan مفعّلاً افتراضياً، لكنه يمكن تعطيله فقط بإعداد تشغيلي صريح وموثوق.
2. عند تفعيل الفحص يبقى Fail-Closed ولا يوجد fallback تلقائي إلى direct storage إذا فشل ClamAV أو التواقيع أو Timeout.
3. عند تعطيل الفحص صراحةً تبقى Validation/Authorization/SHA-256/duplicate safeguards إلزامية، ويخزن الملف مباشرة في permanent private storage.
4. لا يوجد أكثر من Process أو Model ثقيل فعال في الوقت نفسه عندما تكون المراحل المحلية الثقيلة مستخدمة.
5. لا نبني ذاكرة محادثة مستخرجة أو طويلة الأمد في v1.
6. لا ننفذ Streaming حقيقياً ولا NDJSON ولا Redis Stream ولا Replay في v1.
7. نحافظ على الشكل الجمالي بواسطة حالة `جاري التفكير` النابضة ثم Progressive reveal محلي لإجابة مكتملة ومحفوظة.

### 174.20.2 Security routing والفحص عند الطلب

الإعداد المرجعي:

```text
DOCUMENT_SECURITY_SCAN_ENABLED=true
```

#### المسار الافتراضي — Enabled

```text
Upload
  → Validation
  → Private quarantine
  → security-scan queue (concurrency=1)
  → clamscan process
  → clean ? promote private file : infected/fail-closed
  → process exits and RAM is returned to the OS
  → AI processing may start only after clean result
```

التنفيذ المعتمد عند التفعيل:

- يوجد Security Scan Worker مخصص يملك ClamAV CLI ويستطيع قراءة Quarantine path المصرح فقط.
- يستدعي العامل `clamscan` بوسائط Array آمنة عبر Process API؛ يمنع Shell interpolation وبناء command string من اسم الملف.
- Process الفحص قصيرة العمر وتنتهي بعد كل ملف. لا يوجد `clamd` دائم ولا TCP port 3310 في Baseline.
- تبقى قاعدة التواقيع في Volume دائم وتحدثها `UpdateClamAvSignaturesJob` مجدولة على Queue `security-scan` نفسها.
- Exit code للملف النظيف فقط يسمح بالـpromotion. Infected أو Timeout أو Process failure أو missing/stale signatures كلها Fail-Closed.
- لا يبدأ Processing/AI قبل تثبيت نتيجة Clean وانتهاء Process الفحص.
- إذا وصلت ملفات متعددة، تعالجها Queue بالتسلسل.
- في Local Demo يكتسب Scan أو Signature Update القفل العالمي `rag:local:heavy-resource` المشترك مع `ai-local`.

#### المسار الاختياري — Disabled explicitly

```text
DOCUMENT_SECURITY_SCAN_ENABLED=false

Upload
  → Validation
  → permanent private storage
  → subsequent processing orchestration
```

القواعد:

- تعطيل الفحص قرار Operator/Deployment موثوق فقط، وليس اختياراً يرسله Browser لكل Upload.
- لا يستخدم النظام Quarantine أو Security Scan Job كمرحلة لازمة للملف الجديد في هذا المسار.
- Validation تبقى إلزامية، لكنها لا تعتبر Antivirus ولا تزعم كشف malware.
- لا يُسمح لفشل `clamscan` أو غياب التواقيع أو Timeout بتغيير المسار إلى Disabled؛ إذا كانت القيمة `true` يبقى Fail-Closed.
- `DocumentUploadService` يملك قرار routing، بينما `DocumentStorageService` يوفر primitives مثل `storeQuarantined()`, `storePermanent()`, `promoteQuarantined()` ولا يقرأ Feature flag ليقرر السياسة.
- الاسم `storePermanent()` هو العقد المفضل للتخزين المباشر بدلاً من `store()` الغامض بعد التحقق من جميع callers في C6.
- Oracle/reference deployment يبقى Security Scan فيه مفعلاً افتراضياً، ويمكن تعطيله فقط إذا اختار المشغّل ذلك صراحةً مع قبول الـTrade-off الأمني.

إعدادات ClamAV عند التفعيل:

```text
CLAMAV_SCAN_MODE=on_demand_cli
CLAMAV_SCAN_QUEUE=security-scan
CLAMAV_SCAN_CONCURRENCY=1
CLAMAV_SCAN_TIMEOUT=300
CLAMAV_SIGNATURE_DIR=/var/lib/clamav
CLAMAV_FAIL_CLOSED=true
LOCAL_HEAVY_RESOURCE_LOCK_ENABLED=true   # Local Demo only
LOCAL_HEAVY_RESOURCE_LOCK_KEY=rag:local:heavy-resource
```

### 174.20.3 دورة حياة Local AI المبسطة

لا نستخدم LRU/TTL cache متعددة النماذج في Baseline. توجد بوابة واحدة و`active_model` واحد فقط داخل FastAPI worker الوحيد.

```text
Parse result ready
  → load BGE-M3 lazily
  → embedding/query embedding
  → release BGE-M3 + GC/backend cleanup
  → load BGE reranker lazily
  → rerank
  → release reranker + GC/backend cleanup
  → request Ollama generation
  → keep_alive=0 after completed generation
```

القواعد:

- `LOCAL_AI_MAX_CONCURRENCY=1` وQueue `ai-local` ذات Worker واحد.
- يكتسب Laravel `ai-local` worker قفل Redis العالمي قبل استدعاء FastAPI ويمسكه حتى اكتمال جميع المراحل المحلية أو فشلها، مع Owner token وTTL/heartbeat وatomic compare-and-delete عند التحرير. لا يحتاج Host FastAPI إلى Redis؛ تبقى Semaphore الداخلية دفاعاً إضافياً.
- يملك الاستخدام الحالي Lease بسيطة تمنع التحرير أثناء Stage فعالة؛ لا توجد Cache entries متعددة أو Reaper دوري.
- قبل Load يقاس available memory. إذا لم يتحقق الحد الأدنى، يفشل الطلب مبكراً بـ`local_resource_exhausted`.
- بعد Stage تزال references، ثم `gc.collect()` وBackend cleanup المناسب، ثم يعاد قياس RAM.
- لا يبدأ Model ثقيل جديد قبل تحرير السابق والتحقق من الذاكرة.
- إذا أثبت Q8 أن Python process لا يعيد الذاكرة بصورة كافية، ينقل Model runner إلى subprocess قصيرة العمر بقرار تحسين محدود؛ لا يدخل ذلك Baseline قبل القياس.
- Compare يعيد استخدام Parse المشترك وينفذ مراحل Local بالتتابع، لذلك يزيد الزمن ولا يضاعف عدد النماذج المقيمة.
- لا يوجد Fallback صامت إلى CPU أو Cloud أو Model آخر.

الإعداد المرجعي:

```text
# FastAPI Host
AI_SERVICE_WORKERS=1
LOCAL_MODEL_LIFECYCLE=single_active
LOCAL_RELEASE_MODEL_AFTER_STAGE=true
LOCAL_MIN_AVAILABLE_MEMORY_RATIO=0.15
LOCAL_AI_MAX_CONCURRENCY=1

# Laravel/Redis داخل Docker
LOCAL_AI_QUEUE=ai-local
LOCAL_AI_QUEUE_CONCURRENCY=1
LOCAL_HEAVY_RESOURCE_LOCK_ENABLED=true
LOCAL_HEAVY_RESOURCE_LOCK_KEY=rag:local:heavy-resource
LOCAL_HEAVY_RESOURCE_LOCK_TIMEOUT=600

# Ollama Host
OLLAMA_MAX_LOADED_MODELS=1
OLLAMA_NUM_PARALLEL=1
OLLAMA_KEEP_ALIVE=0
```

### 174.20.4 سياق المحادثة البسيط

لا تنشأ الجداول أو المكونات التالية في v1:

```text
conversation_memory_snapshots
memory extractor
memory reducer
memory provenance graph
long-term user memory
```

البديل المحدود:

- تحفظ المحادثات والرسائل العادية في MySQL كما هو مخطط.
- عند السؤال يجوز لـLaravel إرسال آخر تبادلين مكتملين فقط من Conversation نفسها ضمن `recent_completed_turns`.
- لا تدخل Pending أو Failed أو رسالة Assistant غير مكتملة.
- يستخدم السياق الحديث لفهم الضمائر والإحالات مثل «اشرح النقطة السابقة» فقط.
- Retrieved document chunks تبقى مصدر الحقائق؛ Prompt يمنع الاستشهاد بإجابة سابقة كدليل.
- إذا تجاوز السياق الحد المضبوط، يحذف الأقدم أولاً. لا يولد Summary ولا Snapshot بديل.
- تغيير الوثائق المختارة يؤثر على السؤال الجديد، بينما يبقى `document_ids_snapshot` لكل رسالة لأغراض Audit وليس كذاكرة توليد.

هذا الحل لا يضيف Migration أو Queue أو Model أو عملية استخراج جديدة.

### 174.20.5 لا Streaming حقيقي في v1

المكونات التالية خارج النطاق:

```text
Provider token streaming
FastAPI NDJSON protocol
Redis Stream relay/replay
Laravel streaming proxy
SSE/WebSocket
Nginx no-buffering for chat deltas
partial assistant message persistence
```

يبقى `AskConversationJob` عملاً خلفياً عادياً:

1. تحفظ رسالة المستخدم وAssistant placeholder بحالة Pending.
2. تعرض الواجهة `جاري التفكير` بنبض بصري هادئ أثناء Pending/Processing.
3. ينفذ Job السؤال ويحفظ Answer وSources وTimings كاملة داخل Transaction مناسبة.
4. تكتشف Livewire/Polling الحالة Completed بالطريقة العادية.
5. يكشف Frontend النص المكتمل تدريجياً محلياً لإعطاء طابع محادثة حديث.

### 174.20.6 عقد Progressive reveal البصري

- النص الذي يكشف تدريجياً هو النص النهائي المحفوظ نفسه؛ لا ينشئ Frontend كلمات أو يجزئ جواباً قادماً من الشبكة.
- لا يبدأ Reveal قبل حالة Completed.
- `جاري التفكير` ليست Streaming indicator ولا تدعي وصول Tokens.
- عند Reload أو فتح تاريخ المحادثة تظهر الرسائل القديمة كاملة فوراً ولا يعاد تحريكها.
- يوجد زر/إجراء `إظهار الإجابة كاملة` أثناء Reveal.
- Copy ينسخ النص النهائي الكامل، لا الجزء المرئي فقط.
- عند `prefers-reduced-motion: reduce` لا يوجد نبض أو Reveal؛ تظهر الإجابة كاملة فوراً.
- تستخدم حالة واحدة مفهومة لقارئ الشاشة، ولا تعاد عبارة `جاري التفكير` مع كل نبضة.
- فشل Job يوقف النبض ويعرض Error/Retry؛ لا يظهر نص جزئي على أنه جواب مكتمل.

هذا التأثير Frontend-only واستهلاكه من RAM مهمل مقارنة بالنماذج، لكنه لا يخفض زمن التوليد الحقيقي؛ المستخدم يرى `جاري التفكير` حتى اكتمال Backend ثم يبدأ العرض التدريجي.

### 174.20.7 أثر القرار على خريطة المهام

التعديلات الملزمة:

| المهمة | النطاق بعد التبسيط |
|---|---|
| `C1` | Security Scan Worker + ClamAV CLI + signature volume، بلا `clamd` دائم |
| `C2` | DocumentSecurityService يشغّل Process آمنة ويفسر exit codes بشكل Fail-closed |
| `C3–C5` | Quarantine/clean/infected paths عند تفعيل Security Scan |
| `C6` | Configurable security routing: default enabled، direct permanent storage عند disabled الصريح، بلا fallback تلقائي، و`storePermanent()` API واضح |
| `C7` | Aggregate status transitions للمسارين enabled/disabled |
| `C8` | Security tests لكلا المسارين وتحقق default/fail-closed/no-auto-bypass |
| `D8–D10` | Capabilities/validation/dependency split: Oracle يسجل Cloud فقط وCloud image بلا Local AI packages أو weights |
| `D11` | Device resolver وmemory probes من دون Model load عند Startup |
| `G11` | Single-active-model coordinator بدلاً من LRU/TTL registry متعددة entries |
| `I11` | Queue `ai-local` ذات Worker واحد وقفل Redis عالمي يمنع تداخل أي Security scan مع أي Local AI Stage عبر الوثائق |
| `M1` | Retrieved context + آخر تبادلين مكتملين فقط، بلا ذاكرة مستخرجة |
| `M3` | Provider Registry يختار من trusted Processing profile وCapabilities، بلا global `LLM_PROVIDER` switch |
| `M6` | رفض Profile/Provider غير المتاح ومنع Fallback الصامت أو قيمة Provider قادمة مباشرة من Browser |
| `M9` | Ollama `keep_alive=0` وتنسيق التحرير بين مراحل BGE وGeneration |
| `M10` | اختبارات load/use/release/no-leak/concurrency على الجهازين |
| `N8` | Pending/failure/retry + `جاري التفكير` نابضة + Reveal بعد Completed |
| `N9` | E2E للمحادثة والسياق المحدود وAccessibility وعدم إعادة Reveal |
| `Q8` | قياس Peak لكل مرحلة وإثبات عدم تداخل ClamAV/BGE/Reranker/Ollama |
| `Q10` | Backend الفعلي وloaded-model count و`keep_alive=0` وتحرير الذاكرة |
| `Q12` | توثيق صريح أن العرض التدريجي بصري وليس Streaming |
| `DPL-1–DPL-23` | Oracle Cloud-only deployment واختبار Security/Images/Provider/Backup/Restart |
| `DPL-24–DPL-25` | Local topology وCompare على Mac/ASUS فقط، خارج Oracle |

لا توجد المهام `K9/M11/M12/M13/N10/N11/N12/Q13/Q14/DPL-26` في الخطة المعتمدة؛ أي ذكر سابق لها ملغى بهذا القرار.

### 174.20.8 معايير القبول

#### Security

- `DOCUMENT_SECURITY_SCAN_ENABLED=true` هو Default.
- عند التفعيل لا يصل ملف إلى FastAPI قبل Clean result، وأي فشل ClamAV/تواقيع/Timeout يبقى Fail-Closed.
- عند التعطيل الصريح يصل الملف إلى المعالجة فقط بعد Validation والتخزين الدائم، ولا ينشأ bypass من Request المستخدم أو من failure تلقائي.
- Validation لا توصف بأنها بديل عن Antivirus.
- بعد انتهاء `clamscan` لا تبقى Process فحص أو محرك تواقيع مقيم في RAM.
- لا Docker socket داخل Laravel أو Queue container.

#### Memory

- لا تتداخل أي ClamAV scan مع أي Local AI Stage حتى عند اختلاف الوثيقة؛ يثبت الاختبار انتظار القفل العالمي وتحريره الآمن بعد النجاح والفشل.
- لا يوجد أكثر من BGE-M3 أو Reranker أو Qwen محملاً/فعالاً في الوقت نفسه.
- بعد كل Stage تسجل الذاكرة قبل Load وعند Peak وبعد Release.
- ثلاث دورات كاملة لا تظهر نمواً تراكمياً غير مفسر يتجاوز الأكبر من 256 MiB أو 10% من Idle envelope.
- فشل تحرير الذاكرة لا يمر بصمت؛ يظهر في Q8 ويمنع وصف الجهاز بأنه Local-ready حتى اتخاذ قرار subprocess أو تحسين آخر.

#### Conversation simplicity

- لا يوجد جدول memory snapshots ولا Job استخراج ذاكرة.
- لا يرسل أكثر من آخر تبادلين مكتملين.
- لا تستخدم رسائل سابقة كمصدر Facts أو Citation.
- سؤال مستقل يعمل من دون أي History.

#### UI

- تظهر `جاري التفكير` أثناء Pending/Processing فقط.
- تبدأ الإجابة التدريجية بعد اكتمال وحفظ النص النهائي.
- الرسائل القديمة وReload لا يعيدان الحركة.
- reduced-motion يعرض النص فوراً.
- لا NDJSON أو Redis relay أو long-lived chat response.

### 174.20.9 Resource envelope المستهدف

هذه أرقام هندسية أولية وليست ضماناً، وتثبت أو تعدل حصراً عبر Q8 على الجهازين:

| المرحلة | إجمالي RAM المستهدف على جهاز 16 GB |
|---|---:|
| Idle infrastructure بلا Scan أو Model | 3–6 GB |
| `clamscan` فعالة | 6–10 GB |
| BGE-M3 embedding/query embedding | 7–11 GB |
| Local BGE reranking | 7–11 GB |
| Ollama `qwen3.5:4b` generation | 8–13 GB |

- الهدف التشغيلي أن يبقى Peak الطبيعي أقل من 14 GB وألا يستخدم النظام Swap كثيفاً.
- لا تجمع الأرقام لأنها مراحل متبادلة وليست خدمات ثقيلة متزامنة.
- إذا تجاوزت مرحلة واحدة الحد أو لم تعد الذاكرة بعد Release، يفشل اعتماد Local readiness حتى معالجة السبب.
- Cloud mode لا يحمل أي أوزان Local AI ويظل المسار الافتراضي الأكثر راحة للنشر Online.

### 174.20.10 قرارات غير قابلة للتفاوض

1. ClamAV لا يُحذف من المشروع، وSecurity Scan يكون مفعلاً افتراضياً ويعمل On-demand؛ يمكن تعطيله فقط بإعداد تشغيلي صريح.
2. عندما يكون الفحص مفعلاً تبقى النتيجة Fail-Closed ولا يبدأ AI قبل Clean موثق؛ فشل الفاحص أو التواقيع أو Timeout لا يفعّل direct-storage bypass.
3. عندما يكون الفحص معطلاً صراحةً تبقى Validation/Authorization/SHA-256/duplicate safeguards إلزامية ويستخدم permanent private storage مباشرة.
4. قرار Enabled/Disabled يأتي من Server-side trusted configuration وليس من Browser لكل Upload.
5. لا Docker socket داخل التطبيق.
6. عملية AI ثقيلة واحدة فقط، ونموذج ثقيل واحد فقط في الذاكرة.
7. كل Model محلي يحمل Lazy ويحرر بعد مرحلته؛ Ollama يستخدم `keep_alive=0`.
8. لا ذاكرة محادثة مستخرجة أو طويلة الأمد في v1.
9. آخر تبادلين مكتملين حد أقصى للسياق الحواري البسيط.
10. وثائق RAG المسترجعة هي مصدر الحقائق الوحيد.
11. لا Streaming حقيقي أو NDJSON أو Redis Stream أو Replay في v1.
12. `جاري التفكير` وProgressive reveal تأثيران بصريان فقط ولا يغيران عقد FastAPI المتزامن أو محتوى الرسالة المحفوظ.
13. Oracle Online يشغّل `cloud` فقط ولا يحتوي أي Local AI dependencies أو weights؛ Local/Compare يعملان على Mac/ASUS فقط.
14. في Local Demo، عندما يكون Security Scan مفعلاً، يمنع قفل Redis عالمي تداخل `clamscan` مع أي BGE/Reranker/Qwen Stage، وليس فقط للوثيقة نفسها.