# سجل تنفيذ مشروع RAG

> **المرجع:** `PROJECT_RAG_MASTER_PLAN.md`  
> **الغرض:** توثيق التنفيذ الفعلي خطوة بخطوة دون تعديل الخطة المعمارية الأصلية.  
> **آخر تحديث:** 2026-08-12  
> **الحالة العامة:** قيد التنفيذ

---

# CURRENT HANDOFF — نقطة الاستلام للمحادثة الجديدة

> **هذا القسم هو أول شيء يجب قراءته في أي Chat جديد.**
> يكفي رفع آخر نسخة من هذا الملف وطلب: **«نكمل مشروع RAG حسب ملف التقدم المرفق»**.

```text
Project Mode: Start From Scratch
Last Completed Task: لا يوجد حتى الآن
Current Task: A1 — إنشاء Laravel Application
Current Task Status: TODO
Next Task After Completion: A2 — إعداد Authentication
Open Blockers: لا يوجد
Required Context: هذا الملف + الخطة الرئيسية عند الحاجة فقط
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
| A1 إنشاء Laravel application | TODO |
| A2 إعداد Authentication | TODO |
| A3 إعداد MySQL | TODO |
| A4 إعداد Redis | TODO |
| A5 إعداد Queue | TODO |

**معيار انتهاء المرحلة:** المستخدم يستطيع التسجيل والدخول، والـQueue تعمل بنجاح.

---

# 5. المرحلة B — Documents Domain

| المهمة | الحالة |
|---|---|
| B1 إنشاء documents migration | TODO |
| B2 Document model | TODO |
| B3 DocumentStatus enum | TODO |
| B4 DocumentPolicy | TODO |
| B5 صفحة Documents | TODO |
| B6 Upload validation | TODO |
| B7 Private storage | TODO |
| B8 SHA-256 | TODO |

**معيار انتهاء المرحلة:** المستخدم يستطيع رفع ملف صالح ويظهر في قائمة ملفاته بدون معالجة AI بعد.

---

# 6. المرحلة C — ClamAV

| المهمة | الحالة |
|---|---|
| C1 إضافة ClamAV infrastructure | TODO |
| C2 DocumentSecurityService | TODO |
| C3 Temporary upload flow | TODO |
| C4 Scan clean files | TODO |
| C5 Reject infected files | TODO |
| C6 Status transitions | TODO |
| C7 Tests | TODO |

**معيار انتهاء المرحلة:** لا يمكن لملف لم يجتز ClamAV الوصول إلى Queue الخاصة بمعالجة AI.

---

# 7. المرحلة D — FastAPI Foundation

| المهمة | الحالة |
|---|---|
| D1 إنشاء مشروع FastAPI | TODO |
| D2 Config | TODO |
| D3 Logging | TODO |
| D4 Internal API security | TODO |
| D5 Health endpoint | TODO |
| D6 Schemas | TODO |
| D7 Structured exceptions | TODO |

**معيار انتهاء المرحلة:** Laravel يستطيع الاتصال بـFastAPI داخلياً والحصول على Health response.

---

# 8. المرحلة E — Qdrant Local

| المهمة | الحالة |
|---|---|
| E1 تشغيل Qdrant محلياً | TODO |
| E2 إضافة Docker volume | TODO |
| E3 إنشاء `rag_documents` | TODO |
| E4 Dense vector config | TODO |
| E5 Sparse vector config | TODO |
| E6 Payload indexes | TODO |
| E7 اختبار الاتصال | TODO |

**معيار انتهاء المرحلة:** FastAPI يستطيع إدخال واسترجاع وحذف Points من Qdrant المحلي.

---

# 9. المرحلة F — Document Loaders

| المهمة | الحالة |
|---|---|
| F1 Base loader interface | TODO |
| F2 PDF Loader | TODO |
| F3 DOCX Loader | TODO |
| F4 TXT Loader | TODO |
| F5 Normalized document schema | TODO |
| F6 Loader tests | TODO |

**معيار انتهاء المرحلة:** PDF/DOCX/TXT تتحول إلى Documents موحدة قابلة للـChunking.

---

# 10. المرحلة G — Chunking + Embeddings

| المهمة | الحالة |
|---|---|
| G1 ChunkingService | TODO |
| G2 EmbeddingService | TODO |
| G3 Passage/query separation | TODO |
| G4 Batch processing | TODO |
| G5 Retry/rate limit handling | TODO |
| G6 Metadata propagation | TODO |

**معيار انتهاء المرحلة:** كل Document ينتج chunks لها vectors صحيحة وmetadata كاملة.

---

# 11. المرحلة H — Qdrant Ingestion

| المهمة | الحالة |
|---|---|
| H1 Point builder | TODO |
| H2 UUID generation | TODO |
| H3 Dense vectors | TODO |
| H4 BM25 sparse representation | TODO |
| H5 Upload batches | TODO |
| H6 Count verification | TODO |
| H7 Delete by user/document | TODO |
| H8 Reprocessing without duplicates | TODO |

**معيار انتهاء المرحلة:** يمكن معالجة وثيقة كاملة ورؤية جميع chunks الخاصة بها داخل Qdrant.

---

# 12. المرحلة I — Laravel ↔ FastAPI Document Processing

| المهمة | الحالة |
|---|---|
| I1 AiServiceClient | TODO |
| I2 `POST /documents/process` | TODO |
| I3 ProcessDocumentJob | TODO |
| I4 Status handling | TODO |
| I5 total_chunks | TODO |
| I6 total_pages | TODO |
| I7 failure_reason | TODO |
| I8 UI status refresh | TODO |

**معيار انتهاء المرحلة:** المستخدم يرفع PDF/DOCX/TXT، يمر بـClamAV ثم Queue ثم FastAPI ويصبح Ready.

---

# 13. المرحلة J — Conversations

| المهمة | الحالة |
|---|---|
| J1 Conversations migration | TODO |
| J2 conversation_document | TODO |
| J3 messages | TODO |
| J4 message_sources | TODO |
| J5 ConversationPolicy | TODO |
| J6 Create conversation | TODO |
| J7 List conversations | TODO |
| J8 Select documents | TODO |
| J9 Only ready documents | TODO |

**معيار انتهاء المرحلة:** المستخدم يستطيع إنشاء محادثة واختيار عدة وثائق يملكها.

---

# 14. المرحلة K — Retrieval

| المهمة | الحالة |
|---|---|
| K1 Query embedding | TODO |
| K2 Security filter (`user_id`, `document_ids`) | TODO |
| K3 Dense retrieval | TODO |
| K4 BM25 retrieval | TODO |
| K5 RRF | TODO |
| K6 Top candidates | TODO |
| K7 Development debug helper | TODO |

**معيار انتهاء المرحلة:** السؤال يسترجع المقاطع ذات الصلة فقط من وثائق المستخدم المختارة.

---

# 15. المرحلة L — Reranking

| المهمة | الحالة |
|---|---|
| L1 Jina Reranker client | TODO |
| L2 Convert Qdrant points to reranker nodes | TODO |
| L3 Rerank | TODO |
| L4 Top 5 | TODO |
| L5 Preserve metadata | TODO |

**معيار انتهاء المرحلة:** أفضل chunks بعد reranking جاهزة لبناء السياق.

---

# 16. المرحلة M — Generation

| المهمة | الحالة |
|---|---|
| M1 ContextService | TODO |
| M2 Prompt file | TODO |
| M3 GenerationService | TODO |
| M4 LLM Provider abstraction | TODO |
| M5 Cloud provider | TODO |
| M6 Local provider | TODO |
| M7 Sources array | TODO |
| M8 Insufficient-information behavior | TODO |

**معيار انتهاء المرحلة:** FastAPI يعيد Answer + Sources بعقد ثابت ومنظم.

---

# 17. المرحلة N — Laravel Chat Flow

| المهمة | الحالة |
|---|---|
| N1 إرسال سؤال | TODO |
| N2 Save user message | TODO |
| N3 Assistant placeholder | TODO |
| N4 AskConversationJob | TODO |
| N5 FastAPI request | TODO |
| N6 Save answer | TODO |
| N7 Save sources | TODO |
| N8 Livewire refresh | TODO |
| N9 Error display | TODO |

**معيار انتهاء المرحلة:** المستخدم يجري محادثة كاملة مع عدة وثائق ويشاهد المصادر.

---

# 18. المرحلة O — Filament

| المهمة | الحالة |
|---|---|
| O1 UserResource | TODO |
| O2 DocumentResource | TODO |
| O3 ConversationResource | TODO |
| O4 Dashboard widgets | TODO |
| O5 Failed documents filters | TODO |
| O6 Infected uploads monitoring | TODO |
| O7 Retry action | TODO |

**معيار انتهاء المرحلة:** المشرف يستطيع مراقبة حالة النظام من لوحة Filament.

---

# 19. المرحلة P — الاختبارات الأمنية

| المهمة | الحالة |
|---|---|
| P1 Ownership tests | TODO |
| P2 IDOR attempts | TODO |
| P3 Qdrant leakage tests | TODO |
| P4 ClamAV failures | TODO |
| P5 MIME spoofing | TODO |
| P6 Invalid extensions | TODO |
| P7 File size limits | TODO |
| P8 FastAPI API key | TODO |
| P9 Private download authorization | TODO |

**معيار انتهاء المرحلة:** لا يستطيع مستخدم الوصول أو البحث أو التنزيل من وثائق مستخدم آخر.

---

# 20. المرحلة Q — الاختبارات النهائية

| المهمة | الحالة |
|---|---|
| Q1 PDF scenarios | TODO |
| Q2 DOCX scenarios | TODO |
| Q3 TXT scenarios | TODO |
| Q4 RAG questions | TODO |
| Q5 Queue failures | TODO |
| Q6 Service restarts | TODO |
| Q7 Qdrant persistence | TODO |

**معيار انتهاء المرحلة:** النظام يعمل End-to-End بعد إعادة تشغيل الخدمات ولا تضيع البيانات.

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
| DPL-18 Cloud LLM Test | TODO |
| DPL-19 Local LLM Test | TODO |
| DPL-20 Security Test | TODO |
| DPL-21 Backup Test | TODO |
| DPL-22 Restart Test | TODO |

---

# 22. سجل الإنجاز

## 2026-08-12 — إنشاء سجل التنفيذ واعتماد البدء من الصفر

- تم اعتماد ملف الخطة الرئيسية كمرجع معماري ثابت.
- تم اعتماد هذا الملف كسجل الحالة التنفيذية ومرجع الـhandoff بين المحادثات.
- تم اعتماد سياسة: **Task واحدة = Chat مستقل**.
- تم إلغاء `P0 Baseline Audit` لأن المشروع سيبدأ من الصفر.
- لم يتم وضع أي مهمة تقنية على أنها `DONE` حتى الآن.
- المهمة الحالية: **A1 — إنشاء Laravel Application**.

---

# 23. القرارات المعمارية

| التاريخ | القرار | السبب |
|---|---|---|
| 2026-08-12 | إبقاء الخطة الأصلية ثابتة وإنشاء سجل تنفيذ منفصل | فصل Architecture Plan عن Execution State ومنع تشويه المرجع الأساسي |
| 2026-08-12 | بدء المشروع من الصفر وإلغاء P0 | لا يوجد Codebase سابق يحتاج إلى Baseline Audit |
| 2026-08-12 | Task واحدة لكل Chat | تقليل استهلاك نافذة السياق وتحسين التنظيم والتتبع |
| 2026-08-12 | اعتماد `CURRENT HANDOFF` في أعلى ملف التقدم | تمكين أي محادثة جديدة من معرفة نقطة الاستكمال فوراً |

---

# 24. العوائق والملاحظات

لا توجد عوائق مسجلة حتى الآن.

---

# 25. المهمة الحالية

```text
A1 — إنشاء Laravel Application
Status: TODO
```

## الهدف

إنشاء أساس مشروع Laravel من الصفر وفق الخطة الرئيسية، وتشغيله بنجاح قبل الانتقال إلى Authentication.

## لا تعتبر A1 منجزة حتى

- إنشاء مشروع Laravel داخل البنية المعتمدة.
- التأكد من أن التطبيق يعمل محلياً دون أخطاء.
- التحقق من الإصدارات والاعتمادات الأساسية.
- تسجيل الملفات/الأوامر/الاختبارات في هذا الملف.
- تحديث `CURRENT HANDOFF` بحيث تصبح المهمة التالية `A2 — إعداد Authentication`.

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
