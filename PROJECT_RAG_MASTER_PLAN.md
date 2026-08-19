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
> **المراجع التقنية لمساري RAG:** الملفان `cloud_first_rag_colab_fixed_interactive.ipynb` و`hybrid_cloud_parse_local_rag_simple_colab(1).ipynb`
> **إستراتيجية المعالجة:** Cloud أو Hybrid Local أو Compare في البيئة المحلية، وCloud فقط في النشر Online
> **إستراتيجية LLM:** `Qwen/Qwen3.5-9B` عبر Hugging Face Router في Cloud، و`qwen3.5:4b` عبر Ollama محلياً
> **خطة النشر المرجعية:** Oracle Cloud Always Free + Docker Compose
> **آخر تحديث للخطة:** 2026-08-19

> [!IMPORTANT]
> القسم **174 — التعديل المعماري المعتمد** هو المرجع الأحدث والملزم لكل ما يخص مسارات المعالجة، قواعد البيانات، Qdrant، المحادثة، صفحات Blade، Filament والنشر. عند وجود تعارض مع قسم أقدم، تكون الأولوية للقسم 174. أبقينا الأقسام السابقة لحفظ سياق القرارات وعدم فقدان أي معلومة تاريخية.

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
3. فحص الملفات أمنياً باستخدام ClamAV قبل معالجتها.
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
- فحص الملفات عبر ClamAV.
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

## 4.2 إضافة ClamAV

أي ملف يرفعه المستخدم يجب ألا يصل إلى FastAPI قبل اجتياز الفحص الأمني.

المسار:

```text
Upload
  ↓
Laravel Validation
  ↓
Temporary Private Storage
  ↓
ClamAV Scan
  ↓
Clean?
  ├── Yes → متابعة
  └── No  → حذف + رفض
```

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
│ ClamAV                                                       │
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
       │ Qdrant Local   │   │ External AI APIs   │
       │ Dense + BM25   │   │ Llama/Jina/HF     │
       └────────────────┘   └────────────────────┘
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
- ClamAV.
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
│   │   │   ├── DocumentUploadService.php
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

جدول Laravel القياسي مع:

- الاسم.
- البريد.
- كلمة المرور.
- email verification.
- timestamps.

---

# 10. جدول documents

يقترح أن يحتوي:

```text
id
user_id
original_name
stored_name
title
file_path
file_type
mime_type
file_size
sha256

status
failure_reason

total_pages
total_chunks

qdrant_collection
processed_at

created_at
updated_at
```

## 10.1 file_type

القيم المسموحة:

```text
pdf
docx
txt
```

## 10.2 status

المقترح:

```text
pending
scanning
queued
processing
ready
failed
infected
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
- حذف النسخة المؤقتة.
- تسجيل سبب الرفض.

#### queued

الملف آمن وتم إرسال Job لمعالجته.

#### processing

FastAPI بدأ معالجة الوثيقة.

#### ready

انتهت المعالجة بنجاح وأصبح الملف قابلاً للاستخدام في المحادثات.

#### failed

حدث خطأ أثناء Parsing أو Embedding أو التخزين أو الاتصال بالخدمات.

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
 ├── belongsToMany Conversations
 └── hasMany MessageSources

Message
 ├── belongsTo Conversation
 └── hasMany MessageSources
```

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
8. ClamAV.

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
5b1a93a8-77e1-4c06-b066-7c82b24d624c.pdf
```

ولا يتم استخدام الاسم الأصلي كاسم فعلي في التخزين.

---

# 18. ClamAV Security Flow

المسار الكامل:

```text
User
  ↓
Upload
  ↓
Laravel request validation
  ↓
Temporary private file
  ↓
ClamAV
  ↓
┌───────────────────────┐
│ Is file clean?        │
└──────────┬────────────┘
           │
       ┌───┴────┐
       │        │
      Yes      No
       │        │
       ▼        ▼
Permanent     Delete
Private       Temp File
Storage         │
       │        ▼
       │     infected
       │
       ▼
Create / Update Document
       │
       ▼
Dispatch Queue
```

قاعدة مهمة:

> FastAPI يجب ألا يستقبل أي ملف لم يجتز ClamAV.

---

# 19. DocumentUploadService

المسؤوليات:

- استقبال UploadedFile.
- حساب SHA-256.
- تخزين مؤقت.
- استدعاء `DocumentSecurityService`.
- رفض الملف إذا كان مصاباً.
- نقل الملف إلى Private Storage الدائم.
- إنشاء Document record.
- Dispatch `ProcessDocumentJob`.

يجب ألا يقوم هذا Service بـParsing أو Embedding.

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
DocumentProcessingService
  ↓
AiServiceClient
  ↓
POST FastAPI /documents/process
  ↓
Receive result
  ↓
Update:
  total_pages
  total_chunks
  processed_at
  status = ready
```

في حال Exception:

```text
status = failed
failure_reason = ...
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
file_type
file
```

النتيجة:

```json
{
  "document_id": 152,
  "status": "processed",
  "total_pages": 33,
  "total_chunks": 184
}
```

بالنسبة إلى TXT أو DOCX:

`total_pages` يمكن أن يكون `null` إذا لم توجد دلالة موثوقة للصفحات.

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

مبدئياً نستخدم Collection واحدة:

```text
rag_documents
```

بدلاً من Collection لكل User أو لكل Document.

الأسباب:

- إدارة أسهل.
- scalability أفضل.
- filtering موحد.
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
```

مثال:

```text
user_id = 7
AND
document_id IN [12, 17, 33]
```

هذه القاعدة يجب تطبيقها على:

- Dense prefetch.
- Sparse prefetch.
- deletion.
- أي query آخر.

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

# 49. LLM Generation — تصميم مزدوج Cloud / Local

الـNotebook الأصلي يستخدم:

```text
Qwen/Qwen3.5-9B
```

عبر Hugging Face Router.

في التطبيق النهائي **لا يتم ربط FastAPI مباشرة بمزوّد واحد**. بدلاً من ذلك نعرّف طبقة Provider مستقلة تسمح بتشغيل أحد مسارين:

```text
LLM_PROVIDER=cloud
```

أو:

```text
LLM_PROVIDER=local
```

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

## 49.2 المسار الثاني: Self-hosted Local LLM

المسار الثاني يشغل النموذج تحت إدارتنا:

```text
FastAPI
   ↓
LocalLLMProvider
   ↓
OpenAI-compatible Local Endpoint
   ↓
llama.cpp / Ollama / compatible runtime
   ↓
Quantized Qwen model
```

في النشر المجاني على CPU، النموذج المقترح كبداية:

```text
Qwen/Qwen3-4B-GGUF
Q4_K_M
```

حجم ملف Q4_K_M الرسمي يقارب:

```text
2.5 GB
```

ويتم تشغيله مثلاً بواسطة `llama.cpp` كـOpenAI-compatible server.

هذا المسار مناسب لـ:

- Demo.
- الاختبارات.
- إثبات إمكانية العمل بدون LLM API خارجي.
- تقليل تكلفة كل Request.
- الحفاظ على خصوصية السياق المرسل للـLLM داخل بنيتنا.

لكنه أبطأ بكثير على Oracle Always Free لأن الخطة المجانية تعتمد على CPU محدود.

## 49.3 لا نستخدم Qwen3.5-9B محلياً على Free VM

رغم إمكانية تشغيل نماذج Quantized أكبر نظرياً، لا تعتمد الخطة المجانية على تشغيل:

```text
Qwen3.5-9B
```

محلياً على نفس VM التي تشغّل:

```text
Laravel
FastAPI
MySQL
Redis
Qdrant
ClamAV
Queue Worker
Nginx
```

لأن ذلك سيؤدي إلى ضغط كبير على الذاكرة والـCPU.

لذلك:

```text
Cloud mode  → Qwen3.5-9B
Local mode  → Small quantized model مثل Qwen3-4B Q4_K_M
```

مع إمكانية تغيير النموذج لاحقاً من الإعدادات.

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
LLMProviderFactory
   ↓
read LLM_PROVIDER
   ↓
cloud ? CloudLLMProvider
local ? LocalLLMProvider
```

وبذلك يتم تغيير المسار من `.env` فقط.

## 49.6 إعدادات Cloud LLM

```text
LLM_PROVIDER=cloud

CLOUD_LLM_BASE_URL=https://router.huggingface.co/v1
CLOUD_LLM_MODEL=Qwen/Qwen3.5-9B
HF_TOKEN=...
```

## 49.7 إعدادات Local LLM

```text
LLM_PROVIDER=local

LOCAL_LLM_BASE_URL=http://local-llm:8080/v1
LOCAL_LLM_MODEL=qwen-local
LOCAL_LLM_API_KEY=none
```

## 49.8 توحيد OpenAI-compatible API

يفضل استخدام بروتوكول Chat Completions متوافق مع OpenAI في كلا المسارين.

هذا يسمح لـFastAPI باستخدام Client abstraction واحدة تقريباً:

```text
Cloud:
https://router.huggingface.co/v1/chat/completions

Local:
http://local-llm:8080/v1/chat/completions
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
ready
```

---

# 55. تغيير الوثائق المرتبطة بمحادثة

عند إضافة Document إلى Conversation:

1. Verify owner.
2. Verify status = ready.
3. attach pivot.

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
Delete physical private file
  ↓
Delete pivot references
  ↓
Delete database record
```

يفضل تنفيذ حذف Qdrant عبر Job مع معالجة حالات الفشل.

---

# 58. Reprocessing

عند إعادة معالجة وثيقة:

1. حذف vectors القديمة الخاصة بالوثيقة.
2. status → queued.
3. إرسال ProcessDocumentJob جديد.
4. استخراج chunks جديدة.
5. إدخال points جديدة.
6. status → ready.

يجب ألا تتراكم duplicates لنفس الوثيقة.

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
- ClamAV.
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
mysql
redis
fastapi
qdrant
clamav
```

لا حاجة لتعريض:

```text
3306
6379
6333
3310
```

للإنترنت في production.

---

# 72. Environment Variables في Laravel

مثال:

```text
AI_SERVICE_BASE_URL=http://fastapi:8000
AI_SERVICE_API_KEY=...
AI_SERVICE_TIMEOUT=600

QUEUE_CONNECTION=redis

CLAMAV_HOST=clamav
CLAMAV_PORT=3310
```

---

# 73. Environment Variables في FastAPI

مثال:

```text
QDRANT_URL=http://qdrant:6333
QDRANT_COLLECTION=rag_documents

JINAAI_API_KEY=...
LLAMA_CLOUD_API_KEY=...
HF_TOKEN=...

EMBED_MODEL_NAME=jina-embeddings-v3
EMBED_DIM=1024

RERANK_MODEL_NAME=jina-reranker-v2-base-multilingual
LLM_MODEL_NAME=Qwen/Qwen3.5-9B

CHUNK_SIZE=800
CHUNK_OVERLAP=80

DENSE_CANDIDATES=12
SPARSE_CANDIDATES=12
RRF_TOP_K=12
RERANK_TOP_N=5
```

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

# 79. ClamAV Tests

يجب اختبار:

1. Clean file.
2. Infected test file.
3. ClamAV unavailable.
4. Timeout.
5. Invalid response.

في حالة ClamAV unavailable:

> لا نرسل الملف إلى FastAPI.

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

# 86. مراحل التنفيذ المقترحة

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

## C1. إضافة ClamAV infrastructure

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

لا يمكن لملف لم يجتز ClamAV الوصول إلى Queue الخاصة بمعالجة AI.

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

## E3. إنشاء `rag_documents`

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

## I5. total_chunks

## I6. total_pages

## I7. failure_reason

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

# 87. ترتيب التنفيذ المختصر

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
- اجتازت ClamAV.
- موجودة في Private Storage.
- FastAPI استطاع قراءتها.
- تم استخراج محتواها.
- تم Chunking.
- تم Embedding.
- تم رفع Dense + Sparse representations.
- تحمل جميع نقاط Qdrant `user_id` و`document_id`.
- total_chunks > 0.
- status = ready.

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

لا يصل ملف إلى FastAPI قبل ClamAV.

## قاعدة 2

لا يخزن ملف خاص داخل public storage.

## قاعدة 3

لا يجري Qdrant query بدون `user_id`.

## قاعدة 4

عند السؤال يجب أيضاً تحديد `document_ids`.

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
             Temporary Storage
                     │
                     ▼
                  ClamAV
                     │
            ┌────────┴────────┐
            │                 │
          Clean            Infected
            │                 │
            ▼                 ▼
     Private Storage        Reject
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
    Passage Embeddings
            │
            ▼
 Dense + BM25 representation
            │
            ▼
       Qdrant Local
            │
            ▼
          Ready
            │
            ▼
          Laravel
```

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
        Selected Ready Documents
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
              Query Embedding
                      │
                      ▼
              Qdrant Local
                      │
          user_id + document_ids
                      │
          ┌───────────┴───────────┐
          ▼                       ▼
    Dense Search              BM25 Search
          │                       │
          └───────────┬───────────┘
                      ▼
                    RRF
                      │
                      ▼
               12 Candidates
                      │
                      ▼
               Jina Reranker
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
              Qwen 3.5 / HF
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
MySQL + Redis + ClamAV + Qdrant Local + Private Storage
```

مع استمرار استخدام الخدمات الموجودة في الـNotebook للنسخة الأولى:

```text
LlamaParse
Jina Embeddings
Jina Reranker
Hugging Face / Qwen
```

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
ClamAV قبل FastAPI
```

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

بعد التعديلات الحالية، **Qdrant أصبح محلياً**، والملفات مخزنة محلياً، وLaravel/FastAPI/MySQL/Redis/ClamAV يمكن تشغيلها محلياً.

لكن Pipeline المأخوذ من الـNotebook لا يزال يستخدم خدمات خارجية في النسخة الحالية:

```text
LlamaParse Cloud
Jina API
Hugging Face Router
```

لذلك هذه النسخة ليست Offline بالكامل.

إذا أصبح الهدف لاحقاً أن يكون النظام Local/Offline بشكل كامل، يمكن استبدال هذه الخدمات تدريجياً بنماذج محلية مع الحفاظ على نفس Interfaces والـServices، بدون إعادة تصميم Laravel أو قواعد البيانات.

---

# 97. الخلاصة التنفيذية

النسخة المقترحة ليست إعادة كتابة للـNotebook، بل تحويل منظم له من Prototype إلى Application Architecture.

يتم الاحتفاظ بقلب الـRAG:

```text
Parsing
→ Chunking
→ Jina Embeddings
→ Dense + BM25
→ Qdrant
→ Hybrid Search
→ RRF
→ Jina Reranker
→ Context
→ Qwen
→ Answer + Sources
```

مع إضافة متطلبات التطبيق الحقيقي:

```text
Laravel
Authentication
Authorization
PDF/DOCX/TXT
ClamAV
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
                           LLMProviderFactory
                                  │
                    ┌─────────────┴─────────────┐
                    │                           │
                    ▼                           ▼
              CloudLLMProvider            LocalLLMProvider
                    │                           │
                    ▼                           ▼
            Hugging Face Router           llama.cpp / Ollama
                    │                           │
                    ▼                           ▼
             Qwen3.5-9B                  Quantized Qwen
```

الهدف ليس تشغيل المسارين معاً في كل سؤال، بل اختيار أحدهما حسب Environment.

مثلاً:

```text
Development:
LLM_PROVIDER=local

Cloud Demo:
LLM_PROVIDER=cloud

Privacy Demo:
LLM_PROVIDER=local
```

ويمكن لاحقاً إضافة Providers أخرى بدون تعديل منطق الـRAG.

---

# 99. سياسة Fallback بين Local وCloud

في النسخة الأولى لا يفعّل fallback تلقائي بدون تحكم، لأن ذلك قد يرسل سياقاً كان مفترضاً أن يبقى محلياً إلى Cloud Provider.

الوضع الافتراضي:

```text
ALLOW_CLOUD_FALLBACK=false
```

إذا كان:

```text
LLM_PROVIDER=local
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
| مناسب لـOracle Free | ممتاز | ممكن مع نموذج صغير Quantized |
| Qwen3.5-9B | مناسب عبر Provider | غير موصى به على Free VM |

---

# 101. الهدف من خطة النشر المجاني

الهدف هو نشر نسخة:

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

وتشغيل:

```text
Nginx
Laravel
Laravel Queue Worker
FastAPI
MySQL
Redis
Qdrant Local
ClamAV
```

مع مسارين للـLLM:

```text
A. Cloud LLM
B. Local Quantized LLM
```

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
llm_models        # فقط في Local LLM mode
```

---

# 106. المكونات التي تعمل داخل Docker

```text
┌──────────────── Oracle VM ────────────────┐
│                                          │
│  nginx                                   │
│  laravel                                 │
│  queue-worker                            │
│  fastapi                                 │
│  mysql                                   │
│  redis                                   │
│  qdrant                                  │
│  clamav                                  │
│                                          │
│  optional:                               │
│  local-llm                               │
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
fastapi
mysql
redis
qdrant
clamav
local-llm
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
3310 ClamAV
8000 FastAPI
8080 Local LLM
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
   ├── ClamAV
   │
   └── FastAPI
          │
          ├── Qdrant
          ├── LlamaParse
          ├── Jina
          │
          └── LLM Provider
                ├── Cloud
                └── Local
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

  clamav:
    volumes:
      - clamav_db:/var/lib/clamav

  local-llm:
    profiles:
      - local-llm
```

`local-llm` لا يعمل عندما نستخدم Cloud Provider.

---

# 112. Profile A — Deployment مجاني مع Cloud LLM

هذا هو الخيار الموصى به للنسخة المنشورة على Oracle Always Free.

تشغيل:

```text
Nginx
Laravel
Queue
FastAPI
MySQL
Redis
Qdrant
ClamAV
```

ولا نشغّل:

```text
local-llm
```

إعداد FastAPI:

```text
LLM_PROVIDER=cloud
```

ثم:

```text
FastAPI
  ↓
Hugging Face Router
  ↓
Qwen/Qwen3.5-9B
```

---

# 113. لماذا Cloud LLM Profile هو الأفضل على Oracle Free؟

لأن موارد Oracle تبقى مخصصة إلى:

```text
ClamAV
Qdrant
MySQL
Laravel
FastAPI
Queue
Redis
```

بدلاً من استهلاك عدة GB إضافية في LLM.

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

# 115. Profile B — Deployment مجاني مع Local LLM

هذا الـProfile يشغّل LLM على نفس VM:

```text
Oracle VM
│
├── Laravel
├── Queue
├── FastAPI
├── Qdrant
├── MySQL
├── Redis
├── ClamAV
└── llama.cpp
     ↓
   Qwen Quantized
```

الإعداد:

```text
LLM_PROVIDER=local
```

ثم:

```text
LOCAL_LLM_BASE_URL=http://local-llm:8080/v1
```

---

# 116. النموذج المقترح للـLocal Free Profile

نستخدم كبداية:

```text
Qwen/Qwen3-4B-GGUF
```

والـquantization:

```text
Q4_K_M
```

ملف Q4_K_M الرسمي حجمه يقارب:

```text
2.5 GB
```

سبب اختيار 4B Quantized بدلاً من 9B:

- RAM أقل.
- Disk أقل.
- CPU inference أخف.
- مناسب أكثر للـDemo.
- متوفر بصيغة GGUF رسمية.
- يعمل مع llama.cpp.

---

# 117. تشغيل Local LLM بواسطة llama.cpp

يمكن تشغيل OpenAI-compatible endpoint مثل:

```bash
llama serve   -hf Qwen/Qwen3-4B-GGUF:Q4_K_M   --host 0.0.0.0   --port 8080
```

داخل Docker يبقى Endpoint على الشبكة الداخلية فقط.

FastAPI يتصل به:

```text
http://local-llm:8080/v1
```

---

# 118. إعداد Context للـLocal LLM

على Free CPU VM لا نستخدم Context ضخماً بلا حاجة.

RAG يرسل فقط أفضل chunks.

بداية مناسبة للاختبار:

```text
LOCAL_LLM_CONTEXT_SIZE=8192
RERANK_TOP_N=5
MAX_GENERATION_TOKENS=700
```

ثم يتم القياس والتعديل.

الهدف تقليل:

- KV cache.
- RAM.
- latency.

---

# 119. Concurrency للـLocal LLM

على:

```text
2 OCPU
```

يجب اعتبار Local LLM خدمة Low-concurrency.

الإعداد المبدئي:

```text
LLM_MAX_CONCURRENT_REQUESTS=1
```

وتستخدم Queue لمنع عدة Generations ثقيلة بالتوازي.

---

# 120. إدارة الموارد في Local Profile

ClamAV نفسه يحتاج RAM كبيرة.

التوثيق الرسمي يذكر تقريباً:

```text
Minimum: 3 GiB
Preferred: 4 GiB
```

لذلك تشغيل:

```text
ClamAV + Qdrant + Local LLM + MySQL + Laravel + FastAPI
```

ضمن 12 GB يحتاج ضبطاً جيداً.

هذا الـProfile:

```text
مناسب للـDemo
ليس مناسباً لحمل Production متعدد المستخدمين
```

---

# 121. Resource Budget تقريبي — Cloud LLM Profile

هذه ليست أرقام ضمان، بل Budget مبدئي:

```text
ClamAV        3–4 GB
Qdrant        1–2 GB
MySQL         0.5–1 GB
Laravel       0.3–0.7 GB
Queue Worker  0.2–0.5 GB
FastAPI       0.3–0.7 GB
Redis         0.1–0.3 GB
Nginx         <0.1 GB
OS/Docker     remaining
```

هذا الـProfile أكثر راحة على 12 GB.

---

# 122. Resource Budget تقريبي — Local LLM Profile

تقريبياً:

```text
ClamAV           3–4 GB
Local LLM        3–4+ GB حسب context/runtime
Qdrant           1–2 GB
MySQL            ~0.5 GB
Laravel/FastAPI  0.8–1.5 GB
Redis/Nginx      0.2–0.4 GB
OS/Docker        remaining
```

هذا قريب جداً من الحد، لذلك يجب:

- Concurrency = 1.
- small context.
- small model.
- on-disk Qdrant عند الحاجة.
- مراقبة RAM.
- Swap محدود كشبكة أمان وليس كحل أداء.

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

ClamAV يعمل كخدمة داخلية:

```text
Laravel
  ↓
ClamAV
  ↓
Clean?
```

فقط الملفات النظيفة تنتقل إلى:

```text
Queue → FastAPI
```

قاعدة تواقيع ClamAV تحفظ في Volume:

```text
clamav_db
```

---

# 128. Failure Policy لـClamAV

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
3310
8000
8080
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
health checks
```

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

كل Container مهم يملك Health Check حيثما أمكن:

```text
mysql
redis
qdrant
clamav
fastapi
local-llm
```

---

# 145. Cloud LLM Health

لا نرسل Generation كاملة في كل Health Check حتى لا نستهلك الرصيد المجاني.

يتم اختبار Provider عند الحاجة أو بفحص دوري منخفض التكرار.

---

# 146. Local LLM Health

يمكن فحص endpoint مناسب مثل قائمة النماذج أو endpoint readiness حسب runtime المستخدم.

FastAPI في Local Mode يجب أن يكتشف غياب LLM ويعيد خطأ منضبطاً.

---

# 147. Deployment Command — Cloud Mode

```text
LLM_PROVIDER=cloud
```

ثم:

```bash
docker compose up -d
```

ولا يتم تشغيل Local LLM service.

---

# 148. Deployment Command — Local Mode

```text
LLM_PROVIDER=local
```

ثم:

```bash
docker compose --profile local-llm up -d
```

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
Local llama.cpp
  ↓
Qwen Quantized
  ↓
Answer + Sources
```

---

# 151. اختبار التبديل بين المسارين

يجب أن نتمكن من تغيير:

```text
LLM_PROVIDER
```

بدون:

- تعديل Laravel.
- إعادة Embedding.
- إعادة Parsing.
- حذف Qdrant.
- إعادة إنشاء المحادثات.

فقط FastAPI Generation Layer يتغير.

---

# 152. Backup Strategy

يجب عمل Backup على الأقل لـ:

```text
MySQL
Laravel private documents
Qdrant
configuration
```

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

---

# 158. تحديث Qdrant

قبل تغيير Version:

- Snapshot/Backup.
- مراجعة compatibility.
- Pin version بدلاً من `latest` في Production-like demo.

---

# 159. تحديث ClamAV

يجب تحديث:

- Container version.
- virus signatures.

ويفضل Stable release مدروسة.

---

# 160. Free Deployment مع Cloud LLM — التقييم

```text
Oracle Infrastructure  → Free ضمن الحدود
Qdrant                  → Local / Free
ClamAV                  → Local / Free
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

إذا شغّلنا Small Quantized LLM على Oracle VM:

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

وهذا مناسب لإثبات Self-hosted LLM path في مشروع الماجستير.

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
Cloud LLM
```

للـDemo الأسرع والأكثر استقراراً.

## الوضع الثاني

```text
Oracle Always Free
+
Local Quantized LLM
```

لإظهار أن المعمارية تدعم Self-hosted generation.

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

Docker Engine + Compose plugin.

## DPL-6 — Clone المشروع

```bash
git clone ...
```

## DPL-7 — إعداد `.env`

Laravel + FastAPI + infrastructure.

## DPL-8 — إنشاء Volumes

MySQL/Qdrant/ClamAV/documents.

## DPL-9 — تشغيل Infrastructure

```text
mysql
redis
qdrant
clamav
```

## DPL-10 — تشغيل FastAPI

ثم Health Check.

## DPL-11 — تشغيل Laravel

ثم migrations.

## DPL-12 — تشغيل Queue Worker

ثم اختبار Job.

## DPL-13 — تشغيل Nginx

ربط Laravel.

## DPL-14 — HTTPS

Let's Encrypt.

## DPL-15 — اختبار PDF

Validation + ClamAV + Queue + FastAPI + Qdrant.

## DPL-16 — اختبار DOCX

## DPL-17 — اختبار TXT

## DPL-18 — Cloud LLM Test

```text
LLM_PROVIDER=cloud
```

## DPL-19 — Local LLM Test

```text
LLM_PROVIDER=local
```

## DPL-20 — Security Test

محاولة User A الوصول إلى Document User B.

## DPL-21 — Backup Test

Backup + restore.

## DPL-22 — Restart Test

```bash
docker compose restart
```

والتأكد أن MySQL وQdrant والملفات بقيت محفوظة.

---

# 166. Acceptance Criteria للنشر المجاني

يعتبر Deployment ناجحاً عندما:

- الموقع يعمل عبر HTTPS.
- Authentication يعمل.
- PDF/DOCX/TXT ترفع.
- ClamAV يفحص الملفات.
- الملفات الخاصة غير عامة.
- Queue تعمل.
- FastAPI داخلي.
- Qdrant داخلي ومحلي.
- Qdrant data persistent.
- User isolation يعمل.
- Cloud LLM mode يعمل.
- Local LLM mode يعمل.
- التبديل بينهما عبر Environment فقط.
- Filament يعمل.
- Restart لا يحذف البيانات.
- Backup قابل للاسترجاع.

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
Local llama.cpp → GPU vLLM server
Cloud HF Router → Different Provider
```

بسبب فصل Interfaces.

---

# 169. الشكل النهائي للـDeployment مع Cloud LLM

```text
                         Internet
                            │
                            ▼
                       HTTPS / Nginx
                            │
                            ▼
                         Laravel
                     ┌──────┼──────┐
                     │      │      │
                     ▼      ▼      ▼
                   MySQL   Redis  ClamAV
                             │
                             ▼
                         Queue Worker
                             │
                             ▼
                           FastAPI
                          ┌──┴───┐
                          │      │
                          ▼      ▼
                       Qdrant  External AI
                               │
                    ┌──────────┼──────────┐
                    ▼          ▼          ▼
                LlamaParse    Jina     HF Router
                                          │
                                          ▼
                                     Qwen3.5-9B
```

---

# 170. الشكل النهائي للـDeployment مع Local LLM

```text
                         Internet
                            │
                            ▼
                       HTTPS / Nginx
                            │
                            ▼
                         Laravel
                     ┌──────┼──────┐
                     │      │      │
                     ▼      ▼      ▼
                   MySQL   Redis  ClamAV
                             │
                             ▼
                         Queue Worker
                             │
                             ▼
                           FastAPI
                          ┌──┴──────────────┐
                          │                 │
                          ▼                 ▼
                       Qdrant          Local LLM
                                      llama.cpp
                                          │
                                          ▼
                                  Qwen3-4B Q4_K_M
```

في هذه النسخة تبقى:

```text
LlamaParse
Jina Embedding
Jina Reranker
```

خدمات خارجية ما لم نستبدلها أيضاً لاحقاً.

---

# 171. ملاحظة أكاديمية مهمة حول مصطلح Local

لدينا ثلاثة مستويات:

## المستوى 1 — Local Vector Database

```text
Qdrant Local
```

## المستوى 2 — Self-hosted LLM

```text
Qdrant Local
+
Local LLM
```

لكن Parser وEmbedding قد يبقيان Cloud.

## المستوى 3 — Fully Local RAG

```text
Local Parser/OCR
Local Embeddings
Local Reranker
Local Qdrant
Local LLM
```

الخطة الحالية تحقق المستوى الأول دائماً وتتيح المستوى الثاني اختيارياً.

أما Fully Local RAG فهو توسع مستقبلي، ولا ندعي تحقيقه ما دام LlamaParse وJina يعملان عبر Cloud APIs.

---

# 172. المصادر الرسمية لخطة Deployment

تم التحقق من المعلومات بتاريخ:

```text
2026-08-12
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

- Qwen/Qwen3-4B-GGUF  
  https://huggingface.co/Qwen/Qwen3-4B-GGUF

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
Local Qdrant
+
ClamAV
+
MySQL
+
Redis
+
Laravel Queue
```

وتجعل طبقة الـLLM قابلة للتبديل:

```text
LLM_PROVIDER=cloud
```

أو:

```text
LLM_PROVIDER=local
```

### Cloud mode

```text
Qwen/Qwen3.5-9B
via Hugging Face Router
```

### Local mode

للـFree CPU Demo:

```text
Qwen/Qwen3-4B-GGUF
Q4_K_M
via llama.cpp
```

ولا يتغير أي جزء آخر من الـRAG أو Laravel عند التبديل بين المسارين.

هذه هي البنية التي يجب اعتمادها في تنفيذ المشروع من الآن فصاعداً.

---

# 174. التعديل المعماري المعتمد — Cloud / Hybrid Local / Compare

## 174.1 حالة القرار ونطاقه

هذا القسم قرار معماري نهائي معتمد بتاريخ 2026-08-15، وليس فكرة مؤجلة. وهو يوسّع الخطة الأصلية دون تغيير نقطة التنفيذ الحالية:

```text
Last Completed Task: A5 — إعداد Queue
Current Task: B1 — إنشاء documents migration
Current Task Status: TODO
Expected Task Branch: task/B1-documents-migration
```

لا تُنفّذ في B1 مسؤوليات B2 أو المعالجة أو الواجهة أو FastAPI. لكن يجب أن يكون Schema الذي تنشئه B1 متوافقاً مع هذه المعمارية حتى لا نضطر إلى إعادة تصميم جدول `documents` لاحقاً.

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
LLM_PROVIDER=cloud
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
LLM_PROVIDER=local
LOCAL_LLM_BASE_URL=http://ollama:11434/v1
LOCAL_LLM_MODEL=qwen3.5:4b
```

- تظهر الخيارات `cloud` و`hybrid_local` و`compare`.
- يحتاج Cloud وCompare مفاتيح LlamaParse وJina وHugging Face حسب المرحلة.
- يستخدم Hybrid Local النموذج المثبت فعلياً في Ollama: `qwen3.5:4b`.
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

ينشأ FastAPI بملفات Dependencies أو Docker stages منفصلة:

```text
base/cloud  = FastAPI + HTTP clients + Qdrant client + parsing utilities
local       = base + local embedding/reranker dependencies
ollama      = خدمة مستقلة ولا تدخل داخل FastAPI image
```

الهدف أن تبقى النسخة Online خفيفة وقابلة للنشر ضمن Free Tier قدر الإمكان. الخدمات الخارجية نفسها قد تخضع لحصص أو Credits، لذلك لا نعد بأن سلسلة AI مجانية بلا حدود.

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
CLAMAV_HOST=clamav
CLAMAV_PORT=3310
```

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
LOCAL_LLM_BASE_URL=http://ollama:11434/v1
LOCAL_LLM_MODEL=qwen3.5:4b
```

لا توجد مفاتيح حقيقية داخل Git. يقرأ Frontend القدرات من Laravel configuration/Capabilities service، لا من Environment مباشرة.

## 174.4 تجربة رفع الوثيقة

- يرفع المستخدم ملفاً واحداً في كل عملية Upload.
- الأنواع: PDF وDOCX وTXT.
- يمر الملف دائماً بـValidation ثم Private Temporary Storage ثم ClamAV.
- لا يصل ملف غير نظيف إلى FastAPI.
- بعد نجاح الفحص يختار المستخدم مسار المعالجة من القدرات المتاحة في البيئة.
- لا يرتبط اختيار مسار المعالجة باختيار وثائق المحادثة لاحقاً.

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
- ClamAV orchestration.
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

### 174.7.1 جدول `documents` — نطاق B1

ينشئ B1 الأعمدة التالية فقط:

| العمود | النوع المقترح | Null | الملاحظة |
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
INDEX documents_user_status_index (user_id, status)
INDEX documents_user_sha256_index (user_id, sha256)
INDEX documents_user_created_index (user_id, created_at)
```

لا يفرض B1 `UNIQUE(user_id, sha256)`؛ سياسة منع تكرار الملف قرار Application في B8 ولا يجب أن تمنع Re-upload أو حالات الاختبار من مستوى قاعدة البيانات مبكراً.

لا يضع B1 الأعمدة التالية داخل `documents`:

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

سيضاف `selected_processing_run_id` في Migration لاحقة بعد إنشاء جدول Runs، وليس في B1، لتجنب Foreign Key إلى جدول غير موجود.

سياسة الحذف:

- لا نعتمد Cascade مخفياً من user إلى documents لأن للوثيقة ملفاً خاصاً وPoints خارج MySQL.
- `restrictOnDelete` يجبر التطبيق على تنفيذ DocumentDeletionService أولاً.
- Service يحذف Qdrant points وtemporary artifacts والملف الخاص والعلاقات ثم السجلات.
- لا يطبق B1 Soft Deletes دون Task وسياسة Cleanup صريحة.

### 174.7.2 جدول `document_processing_runs`

ينشأ في Task مستقلة بعد B8:

| العمود | الغرض |
|---|---|
| `id` | Run ID |
| `document_id` | FK مع `restrictOnDelete` |
| `profile` | `cloud` أو `hybrid_local` |
| `status` | حالة Run |
| `profile_snapshot` JSON | Providers/models/config الفعلي وقت التشغيل |
| `total_pages` nullable | عدد الصفحات الموثوق |
| `total_chunks` | عدد chunks |
| `vector_count` | عدد vectors |
| `vector_dimension` nullable | الأبعاد |
| `stage_timings_ms` JSON | أزمنة المراحل |
| `warnings` JSON nullable | تحذيرات منظمة |
| `error_code` nullable | رمز ثابت |
| `failure_reason` TEXT nullable | رسالة منقحة لا تحتوي Secrets |
| `comparison_report` JSON nullable | العينات والنتيجة المضغوطة، بلا vectors |
| `temporary_artifact_ref` nullable | Opaque token |
| `temporary_expires_at` nullable | TTL |
| `qdrant_collection` nullable | Collection الفعلية |
| `indexed_at` nullable | وقت اكتمال الفهرسة |
| `selected_at` nullable | وقت الاعتماد |
| `discarded_at` nullable | وقت الاستبعاد |
| `expired_at` nullable | وقت الانتهاء |
| timestamps | Audit |

الفهارس:

```text
(document_id, status)
(document_id, profile, created_at)
(status, temporary_expires_at)
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

### 174.7.3 ربط selected run

بعد إنشاء Runs، تضيف Migration مستقلة:

```text
documents.selected_processing_run_id NULL
FK → document_processing_runs.id
restrictOnDelete
INDEX
```

يتحقق Domain Service أن الـRun يعود إلى الوثيقة نفسها وأن حالته `indexed`. تغيير هذا الحقل وعلامات Run يتم داخل Transaction في Laravel بعد تأكيد FastAPI نجاح Qdrant.

### 174.7.4 جدول `document_processing_comparisons`

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
  "question": "ما أهم النتائج؟"
}
```

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

### Compare

- ينشأ Runان فقط للوثيقة نفسها.
- التقرير لا يحتوي vector values ولا cost.
- سؤال الاختبار يعيد مصادر صحيحة لكل مسار.
- اختيار الفائز idempotent.
- لا يُحذف الخاسر قبل نجاح الفائز.
- Cleanup يعلّم expired ويحذف artifacts بعد TTL.

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

- `C1` ClamAV infrastructure.
- `C2` DocumentSecurityService.
- `C3` Temporary upload flow.
- `C4` clean path.
- `C5` infected/fail-closed path.
- `C6` aggregate status transitions.
- `C7` security tests.

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

- `M1` ContextService.
- `M2` prompt/insufficient-context behavior.
- `M3` LLMProvider interface/factory.
- `M4` HF Router `Qwen/Qwen3.5-9B`.
- `M5` Ollama `qwen3.5:4b`.
- `M6` no-fallback/provider validation.
- `M7` answer/sources/timings response.
- `M8` provider contract tests.

### المرحلة N — Chat Experience

- `N1` chat layout/list.
- `N2` top document multi-selector.
- `N3` selected chips and authorization.
- `N4` AskConversationJob.
- `N5` save snapshots/answer/metrics.
- `N6` sources drawer with relevance score.
- `N7` timings display.
- `N8` pending/failure/retry.
- `N9` mixed-profile end-to-end chat tests.

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
- `Q8` performance report.
- `Q9` Cloud-only lightweight image verification.
- `Q10` Local Ollama profile verification.
- `Q11` backup/restore.
- `Q12` final documentation.

## 174.17 Definition of Done الجديدة

### Document ready

- الملف مملوك للمستخدم واجتاز Validation وClamAV.
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
