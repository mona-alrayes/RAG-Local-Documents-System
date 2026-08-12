# PROJECT RAG — GitHub Review Protocol

> **Project:** Cloud / Local RAG System  
> **Purpose:** بروتوكول ثابت لتنظيم العمل على GitHub ومراجعة كل مهمة قبل اعتمادها كمنجزة.  
> **Applies to:** جميع مراحل المشروع من `A1` حتى `Q` ثم مراحل النشر `DPL-*`.  
> **Primary references:**
> - `PROJECT_RAG_MASTER_PLAN.md`
> - `PROJECT_RAG_EXECUTION_PROGRESS.md`
> - `PROJECT_RAG_GITHUB_REVIEW_PROTOCOL.md`

---

# 1. الهدف من هذا الملف

هذا الملف هو المرجع الرسمي الذي يجب اتباعه عند:

- إنشاء Branch جديد لأي مهمة.
- كتابة Commits.
- فتح Pull Request.
- مراجعة الكود.
- تقييم جودة التنفيذ.
- التحقق من الاختبارات.
- تحديد هل المهمة تحتاج تعديلات أم أصبحت `DONE`.
- تحديث `PROJECT_RAG_EXECUTION_PROGRESS.md`.
- الانتقال إلى المهمة التالية.

المبدأ الأساسي:

> **لا تعتبر أي مهمة منجزة لمجرد وجود الكود.  
> المهمة تصبح `DONE` فقط بعد مراجعة التنفيذ والتحقق من معيار القبول والاختبارات المناسبة.**

---

# 2. مصادر الحقيقة في المشروع

## 2.1 `PROJECT_RAG_MASTER_PLAN.md`

يمثل:

```text
What should be built?
```

ويحتوي على:

- المعمارية.
- المراحل.
- المسؤوليات.
- متطلبات الأمان.
- معايير انتهاء كل مرحلة.
- قرارات التصميم الرئيسية.

لا يتم تعديله بسبب تقدم التنفيذ اليومي إلا عند وجود قرار معماري فعلي يغيّر الخطة.

---

## 2.2 `PROJECT_RAG_EXECUTION_PROGRESS.md`

يمثل:

```text
What has actually been completed?
```

ويحتوي على:

- آخر مهمة منجزة.
- المهمة الحالية.
- المهام `TODO`.
- المهام `DONE`.
- المشاكل المفتوحة.
- نتائج الاختبارات.
- القرارات التنفيذية.
- المهمة التالية.

هذا الملف هو:

> **Single Source of Truth لحالة تنفيذ المشروع.**

---

## 2.3 `PROJECT_RAG_GITHUB_REVIEW_PROTOCOL.md`

يمثل:

```text
How should every task be reviewed?
```

ويحدد:

- Branch naming.
- Commit naming.
- PR naming.
- PR structure.
- Review workflow.
- Review severity.
- Definition of approval.
- قواعد تحديث ملف Progress.

---

# 3. الاستراتيجية العامة للعمل على GitHub

لكل مهمة:

```text
Task
  ↓
Create Branch
  ↓
Implementation
  ↓
Local Tests
  ↓
Commit(s)
  ↓
Push
  ↓
Pull Request
  ↓
Code Review
  ↓
Required Fixes
  ↓
Re-review
  ↓
Acceptance Criteria Verification
  ↓
Task DONE
  ↓
Update PROJECT_RAG_EXECUTION_PROGRESS.md
  ↓
Merge
  ↓
Next Task
```

---

# 4. قاعدة أساسية: مهمة واحدة لكل Branch

يجب أن يمثل كل Branch مهمة واحدة فقط قدر الإمكان.

أمثلة صحيحة:

```text
task/A1-laravel-foundation
task/A2-authentication
task/B1-documents-migration
task/B6-upload-validation
task/C2-document-security-service
task/K2-qdrant-security-filter
```

مثال غير مرغوب:

```text
task/laravel-fastapi-chat-all
```

لأنه يجمع عدة مسؤوليات ومراحل في Branch واحد.

---

# 5. Branch Naming Convention

الصيغة الرسمية:

```text
<type>/<task-id>-<short-description>
```

## الأنواع المعتمدة

### Task

للمهام الأساسية المخططة:

```text
task/A1-laravel-foundation
task/B3-document-status-enum
task/E3-create-rag-collection
task/N4-ask-conversation-job
```

### Fix

لإصلاح مشكلة:

```text
fix/B6-mime-validation
fix/K2-user-filter-leak
```

### Refactor

لإعادة هيكلة بدون تغيير السلوك:

```text
refactor/I1-ai-service-client
```

### Test

للاختبارات فقط:

```text
test/P3-qdrant-leakage
```

### Docs

للتوثيق:

```text
docs/project-progress
docs/rag-architecture
```

### Chore

للأعمال التقنية المساندة:

```text
chore/docker-compose
chore/ci-setup
```

---

# 6. قواعد أسماء الـBranches

- استخدام الأحرف الإنجليزية في الوصف.
- استخدام `-` بين الكلمات.
- عدم استخدام spaces.
- الحفاظ على Task ID كما هو في الخطة:
  - `A1`
  - `B6`
  - `K2`
  - `DPL-5`
- الوصف قصير وواضح.
- لا يوضع اسم المطور في اسم الـBranch.

مثال:

```text
task/DPL-5-install-docker
```

---

# 7. الـDefault Branch

الـDefault Branch:

```text
main
```

ويعامل كفرع مستقر.

القاعدة:

> لا يتم تطوير المهام مباشرة على `main`.

كل Task تبدأ من أحدث نسخة مستقرة من `main`.

---

# 8. Commit Message Convention

الصيغة:

```text
<type>(<task-id>): <short description>
```

الأنواع المقترحة:

```text
feat
fix
refactor
test
docs
chore
```

أمثلة:

```text
feat(A1): create laravel application foundation
feat(B1): add documents migration
fix(B6): reject invalid mime types
test(C7): add clamav failure scenarios
refactor(I1): isolate ai service response mapping
docs(A1): update execution progress
```

القواعد:

- كل Commit يجب أن يكون مفهوماً لوحده.
- تجنب الرسائل العامة مثل:
  - `update`
  - `changes`
  - `fix stuff`
- لا تجمع تغييرات غير مرتبطة في Commit واحد.

---

# 9. Pull Request Naming Convention

الصيغة:

```text
[<task-id>] <task title>
```

أمثلة:

```text
[A1] Laravel Application Foundation
[A2] Authentication Setup
[B1] Documents Migration
[K2] Qdrant User and Document Security Filters
```

---

# 10. قالب Pull Request

كل PR يجب أن يوضح:

## Task

```text
Task ID:
A1
```

## Goal

ما المطلوب تحقيقه في هذه المهمة؟

## Changes

ما الملفات أو المكونات التي تغيرت؟

## Tests

ما الاختبارات التي تم تشغيلها؟

## Acceptance Criteria

ما شروط اعتبار المهمة ناجحة؟

## Notes

أي قرار أو trade-off أو مشكلة معروفة.

---

# 11. نطاق المراجعة

عند مراجعة أي PR يجب فحص المحاور التالية حسب طبيعة المهمة.

## 11.1 Correctness

- هل التنفيذ يحقق المطلوب؟
- هل توجد أخطاء منطقية؟
- هل حالات الفشل معالجة؟
- هل السلوك مطابق للخطة؟

## 11.2 Architecture

- هل المسؤوليات موزعة في الطبقة الصحيحة؟
- هل Laravel يتجنب منطق AI؟
- هل FastAPI يتجنب إدارة application state الخاصة بـLaravel؟
- هل يوجد coupling غير ضروري؟
- هل يوجد violation لـSeparation of Concerns؟

## 11.3 Clean Code

- Single Responsibility.
- أسماء واضحة.
- تجنب الدوال الضخمة.
- تجنب duplication.
- تجنب magic values.
- configuration بدلاً من hardcoding.
- dependency direction صحيحة.

## 11.4 Security

يتم التشدد خصوصاً في:

- Authentication.
- Authorization.
- Ownership.
- IDOR.
- Private storage.
- MIME validation.
- ClamAV.
- FastAPI internal authentication.
- Qdrant filters.
- secrets.
- logging.
- user isolation.

## 11.5 Data Integrity

- العلاقات صحيحة.
- القيود والفهارس مناسبة.
- status transitions صحيحة.
- لا توجد duplicates غير مقصودة.
- الحذف وإعادة المعالجة يحافظان على الاتساق.

## 11.6 Error Handling

- Exceptions واضحة.
- الأخطاء المؤقتة والدائمة مميزة.
- رسائل المستخدم لا تكشف تفاصيل حساسة.
- Logs مفيدة للتشخيص.

## 11.7 Tests

- هل توجد اختبارات مناسبة للمهمة؟
- هل تغطي happy path؟
- هل تغطي failure path؟
- هل تغطي security boundaries عند الحاجة؟
- هل الاختبارات مستقلة وقابلة للتكرار؟

## 11.8 Maintainability

- هل الكود سهل القراءة؟
- هل يمكن توسيعه لاحقاً؟
- هل API contract واضح؟
- هل التعديلات المستقبلية محصورة في طبقات مناسبة؟

## 11.9 Performance

يتم تقييمها فقط عندما تكون مهمة فعلاً للمهمة الحالية، مثل:

- N+1 queries.
- تحميل ملفات ضخمة في الذاكرة.
- batching.
- Qdrant query limits.
- retries.
- timeouts.
- concurrent workloads.

---

# 12. مستويات ملاحظات المراجعة

## BLOCKER

مشكلة تمنع اعتماد المهمة.

أمثلة:

- تسريب بيانات مستخدم.
- كسر معماري واضح.
- فقدان بيانات.
- اختبارات أساسية فاشلة.
- API contract غير صحيح.
- تنفيذ لا يحقق Acceptance Criteria.

النتيجة:

```text
NEEDS CHANGES
```

---

## MAJOR

مشكلة مهمة يجب إصلاحها قبل `DONE`.

أمثلة:

- validation ناقصة.
- error handling غير كافٍ.
- منطق أعمال داخل Controller بشكل كبير.
- coupling مرتفع.
- failure scenario أساسي غير مغطى.

النتيجة غالباً:

```text
NEEDS CHANGES
```

---

## MINOR

تحسين مهم لكن لا يمنع بالضرورة اعتماد المهمة.

أمثلة:

- naming.
- تبسيط كود.
- تحسين documentation.
- refactor صغير.

يمكن اعتماد المهمة إذا لم تؤثر على الصحة أو الأمان أو معيار القبول.

---

## NIT

ملاحظة شكلية اختيارية:

- formatting.
- wording.
- ترتيب بسيط.

لا تمنع `DONE`.

---

# 13. نتائج المراجعة الممكنة

بعد كل Review تكون النتيجة واحدة فقط من الحالات التالية.

## `NEEDS CHANGES`

تستخدم عندما يوجد:

- BLOCKER.
- MAJOR.
- Acceptance Criteria غير مكتملة.
- اختبارات مطلوبة غير ناجحة.

لا يتم تحديث المهمة إلى `DONE`.

---

## `VERIFY`

التنفيذ يبدو صحيحاً لكن يوجد شيء يحتاج تحقق خارجي أو تشغيل فعلي.

مثال:

- خدمة ClamAV غير متاحة في بيئة المراجعة.
- Integration يحتاج Docker runtime.
- Provider خارجي يحتاج secret غير متاح.

تبقى المهمة:

```text
VERIFY
```

ولا تصبح `DONE` حتى يكتمل الدليل.

---

## `APPROVED`

تستخدم عندما:

- لا توجد Blockers.
- لا توجد Major issues مفتوحة.
- Acceptance Criteria متحققة.
- الاختبارات المطلوبة ناجحة.
- التنفيذ مطابق للخطة.
- لا يوجد خرق أمني معروف.

بعدها يمكن اعتبار المهمة:

```text
DONE
```

---

# 14. Definition of Review Done

لا تعتبر المراجعة منتهية حتى يتم فحص:

- PR metadata.
- changed files.
- diff.
- الملفات المهمة كاملة عند الحاجة.
- tests / CI.
- العلاقات مع الكود المحيط.
- Acceptance Criteria.
- security implications.
- open review threads إن وجدت.

---

# 15. أسلوب مراجعة الـDiff

لا تعتمد المراجعة على diff فقط عندما يكون فهم السياق ضرورياً.

يجب عند الحاجة قراءة:

- Interfaces.
- Models.
- Services.
- Config.
- Tests.
- Migrations.
- Policies.
- routes.
- API schemas.
- الملفات التي يعتمد عليها الكود الجديد.

الهدف:

> مراجعة السلوك الفعلي، لا مجرد الأسطر الجديدة.

---

# 16. مراجعة CI / GitHub Actions

إذا كان CI موجوداً:

يتم فحص:

```text
lint
unit tests
feature tests
integration tests
static analysis
build
```

حسب المشروع.

قاعدة:

> CI الأخضر ليس دليلاً كافياً وحده على صحة المهمة.

المراجعة اليدوية للمعمارية والأمان تبقى مطلوبة.

---

# 17. حالات لا تعتبر فيها المهمة DONE

لا تعتمد المهمة إذا كان أي مما يلي صحيحاً:

- الكود غير مختبر عندما تكون الاختبارات ضرورية.
- يوجد failing CI.
- يوجد security issue معروف.
- يوجد TODO أساسي داخل نفس Scope.
- Acceptance Criteria غير مكتملة.
- تم تنفيذ جزء فقط من Task.
- يوجد workaround مؤقت غير موثق.
- توجد migrations غير آمنة أو غير قابلة للتطبيق.
- يوجد hardcoded secret.
- توجد بيانات مستخدم بدون ownership filtering.
- Qdrant query بدون `user_id`.
- FastAPI endpoint عام بدون الحماية المطلوبة.
- الملفات الخاصة مخزنة public.
- ClamAV تم تجاوزه في مسار upload حيث هو مطلوب.

---

# 18. تحديث `PROJECT_RAG_EXECUTION_PROGRESS.md`

بعد نجاح المراجعة فقط، يتم تحديث المهمة.

مثال:

```text
A1 — DONE
```

ويجب تسجيل:

```text
Task:
A1

Status:
DONE

Branch:
task/A1-laravel-foundation

Pull Request:
#<number>

Reviewed Commit:
<sha>

Completed:
- ...

Tests:
- ...

Review Result:
APPROVED

Open Issues:
- None

Next Task:
A2 — Authentication
```

---

# 19. Evidence لكل مهمة

يجب الاحتفاظ بدليل قابل للتتبع.

الحد الأدنى:

- Task ID.
- Branch.
- PR number.
- reviewed commit SHA.
- test result.
- review status.

هذا يجعل ملف Progress مفيداً لاحقاً في:

- debugging.
- project history.
- تقرير الماجستير.
- توثيق مراحل التطوير.

---

# 20. قاعدة تحديث الخطة الرئيسية

`PROJECT_RAG_MASTER_PLAN.md` لا يتغير بسبب إصلاحات تنفيذية عادية.

يتم تعديله فقط إذا حدث:

```text
Architecture Decision
Technology Change
Scope Change
Security Policy Change
API Contract Strategy Change
Deployment Strategy Change
```

وعندها يجب توثيق القرار أيضاً في Progress.

---

# 21. التعامل مع تعديلات المراجعة

إذا كانت النتيجة:

```text
NEEDS CHANGES
```

يتم:

```text
Review
  ↓
Fixes on same task branch
  ↓
Commit
  ↓
Push
  ↓
Re-review
```

لا يتم إنشاء Branch جديد لنفس ملاحظات PR إلا إذا كان هناك سبب واضح.

---

# 22. Scope Creep

إذا ظهرت أثناء المهمة مشكلة خارج نطاقها:

مثال:

```text
A2 Authentication
```

وأثناءها ظهر تحسين كبير في Qdrant.

لا يتم إدخاله داخل نفس PR.

يسجل كـ:

```text
Follow-up Task
```

ويؤجل إلى مرحلته المناسبة.

---

# 23. حجم Pull Request

يفضل أن يكون PR:

- صغيراً.
- له هدف واحد.
- قابل للمراجعة.
- لا يحتوي تغييرات unrelated.

إذا أصبحت Task الأصلية كبيرة جداً، يجوز تقسيم التنفيذ إلى Subtasks بشرط توثيق ذلك في Progress وعدم تغيير الهدف الأصلي بدون قرار واضح.

---

# 24. سياسة Commits أثناء المراجعة

يفضل الاحتفاظ بالـCommits مفهومة أثناء دورة المراجعة.

قبل الدمج يمكن استخدام:

```text
Squash Merge
```

إذا كان ذلك أنسب لسجل `main`.

القرار النهائي لطريقة الدمج يحدد لاحقاً عند إعداد قواعد الـRepository.

---

# 25. حماية `main`

عند إعداد GitHub Repository يفضل تفعيل Branch Protection بحيث:

- لا push مباشر إلى `main`.
- الدمج يتم عبر Pull Request.
- CI المطلوب يجب أن ينجح.
- conversations المطلوبة يجب حلها.
- force push ممنوع.
- deletion ممنوع.

---

# 26. الأسرار

ممنوع Commit لأي من:

```text
.env
APP_KEY
DB_PASSWORD
HF_TOKEN
JINAAI_API_KEY
LLAMA_CLOUD_API_KEY
AI_SERVICE_API_KEY
QDRANT_API_KEY
private keys
```

يجب توفير:

```text
.env.example
```

بدون قيم سرية.

---

# 27. الملفات المولدة

لا يتم Commit لملفات build/cache/dependencies غير المطلوبة.

أمثلة شائعة:

```text
vendor/
node_modules/
.env
storage/logs/*
__pycache__/
.pytest_cache/
.venv/
```

ويجب ضبط `.gitignore`.

---

# 28. قاعدة مراجعة Laravel

في Laravel تتم مراجعة:

- migrations.
- Models.
- casts.
- Enums.
- Form Requests.
- Policies.
- Services.
- Jobs.
- Livewire components.
- Controllers.
- Storage.
- Queue behavior.
- tests.

ويجب إبقاء Controllers وLivewire Components خفيفة قدر الإمكان.

---

# 29. قاعدة مراجعة FastAPI

في FastAPI تتم مراجعة:

- API schemas.
- validation.
- configuration.
- clients.
- loaders.
- services.
- exceptions.
- logging.
- async/sync boundaries عند الحاجة.
- tests.

Endpoints يجب ألا تحمل منطق RAG كبيراً مباشرة.

---

# 30. قاعدة مراجعة RAG

يجب التحقق من:

- document normalization.
- chunk metadata.
- passage/query embedding separation.
- dense retrieval.
- sparse retrieval.
- RRF.
- reranker.
- context building.
- prompt behavior.
- source preservation.
- insufficient-context behavior.

---

# 31. قاعدة مراجعة Qdrant

لا يمر أي retrieval في التطبيق متعدد المستخدمين بدون:

```text
user_id
```

وعند السؤال يجب أيضاً تقييد:

```text
document_ids
```

ويتم التحقق من ذلك في:

- dense search.
- sparse search.
- hybrid search.
- delete.
- reprocess.
- maintenance queries ذات العلاقة بالمستخدم.

---

# 32. قاعدة مراجعة ClamAV

يجب التأكد أن:

```text
Upload
→ Temporary private storage
→ Scan
→ Clean
→ Processing
```

وليس:

```text
Upload
→ FastAPI
→ Scan later
```

وعند تعطل ClamAV:

```text
Fail Closed
```

---

# 33. قاعدة مراجعة المصادر

المصدر المعروض يجب أن يكون قابلاً للتتبع إلى:

```text
document_id
page / section
chunk_index
qdrant_point_id
source_number
```

بحسب نوع الملف.

لا يتم اختراع page لـDOCX/TXT.

---

# 34. قاعدة مراجعة LLM Providers

يجب ألا يعرف بقية RAG أي Provider مستخدم.

المطلوب:

```text
GenerationService
  ↓
LLMProvider abstraction
  ↓
Cloud / Local
```

لا يسمح بتسريب تفاصيل مزود واحد عبر باقي النظام.

---

# 35. قاعدة Fallback

إذا كان:

```text
LLM_PROVIDER=local
```

فلا يتم إرسال السياق إلى Cloud تلقائياً عند الفشل.

الوضع الافتراضي:

```text
ALLOW_CLOUD_FALLBACK=false
```

---

# 36. GitHub Review Handoff بين المحادثات

عند بدء Chat جديد للمراجعة يكفي تقديم:

```text
Repository:
owner/repository

Pull Request:
#N

Task:
A1
```

ثم تتم قراءة:

```text
PROJECT_RAG_MASTER_PLAN.md
PROJECT_RAG_EXECUTION_PROGRESS.md
PROJECT_RAG_GITHUB_REVIEW_PROTOCOL.md
```

من GitHub عند الحاجة.

بعد ذلك يتم تنفيذ المراجعة وفق هذا الملف.

---

# 37. طلب المراجعة القياسي

يمكن استخدام الطلب المختصر:

```text
راجع PR رقم <N> لمهمة <TASK-ID> حسب GitHub Review Protocol.
تحقق من الكود والاختبارات ومعيار الإنجاز.
إذا كانت هناك مشاكل أعطني NEEDS CHANGES مع التفاصيل.
إذا نجحت المهمة اعتبرها DONE وحدّث PROJECT_RAG_EXECUTION_PROGRESS.md.
```

تحديث GitHub نفسه يتم فقط بعد وجود صلاحية وموافقة صريحة على عملية الكتابة المطلوبة.

---

# 38. التقرير النهائي لكل Review

يجب أن يحتوي ملخص المراجعة على:

```text
Task
PR
Review Result
Critical Findings
Major Findings
Minor Findings
Tests / CI
Acceptance Criteria
Final Decision
Progress Update
Next Task
```

---

# 39. ترتيب أولوية المراجعة

الأولوية:

```text
1. Security
2. Correctness
3. Data isolation
4. Acceptance Criteria
5. Architecture
6. Data integrity
7. Error handling
8. Tests
9. Maintainability
10. Performance
11. Style
```

لا يتم قبول تحسين شكلي على حساب مشكلة أمنية أو منطقية.

---

# 40. القاعدة النهائية

لكل Task:

```text
No review → No DONE
No evidence → No DONE
Failed required tests → No DONE
Open blocker/major issue → No DONE
```

والمهمة تصبح:

```text
DONE
```

فقط عندما يكون لدينا دليل واضح أن التنفيذ يحقق متطلبات الخطة ومعيار القبول.

---

# 41. الحالة الحالية

تم إنشاء GitHub Repository الرسمي للمشروع وتهيئته:

```text
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Initialized
Current Task: A1 — إنشاء Laravel Application
Current Task Status: TODO
```

الملفات المرجعية الموجودة في root:

- `PROJECT_RAG_MASTER_PLAN.md`
- `PROJECT_RAG_EXECUTION_PROGRESS.md`
- `PROJECT_RAG_GITHUB_REVIEW_PROTOCOL.md`
- `PROJECT_RAG_MENTOR_WORKFLOW.md`
- `cloud_first_rag_colab_fixed_interactive.ipynb`

أول Branch تنفيذي مخطط:

```text
task/A1-laravel-foundation
```

ولا يتم الانتقال إلى `A2` قبل اكتمال تنفيذ ومراجعة واعتماد `A1` وفق هذا البروتوكول.
