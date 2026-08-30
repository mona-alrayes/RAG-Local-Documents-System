# PROJECT RAG — GitHub Review Protocol

> **Project:** Cloud / Local RAG System  
> **Purpose:** بروتوكول ثابت لتنظيم العمل على GitHub ومراجعة كل مهمة قبل اعتمادها كمنجزة.  
> **Applies to:** جميع مراحل المشروع النشطة من `A1` حتى `P11` ثم مراحل النشر `DPL-*`.  
> **Roadmap baseline:** Profile واحد لكل `ProcessingRun`، فهرسة Qdrant دائمة مباشرة، و`active_processing_run_id` للـRun الحالية المفهرسة.  
> **Primary references:**
> - `PROJECT_RAG_MASTER_PLAN.md`
> - `PROJECT_RAG_EXECUTION_PROGRESS.md`
> - `PROJECT_RAG_GITHUB_REVIEW_PROTOCOL.md`
> - `PROJECT_RAG_MENTOR_WORKFLOW.md`

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
- المراحل والـTask IDs النشطة.
- المسؤوليات.
- متطلبات الأمان.
- معايير انتهاء كل مرحلة.
- قرارات التصميم الرئيسية.

لا يتم تعديله بسبب تقدم التنفيذ اليومي إلا عند وجود قرار معماري فعلي يغير الخطة.

إذا تغيرت الخريطة المعمارية أو أعيد ترقيم المهام، فالـMaster Plan هو المرجع الأعلى للترقيم الجديد.

---

## 2.2 `PROJECT_RAG_EXECUTION_PROGRESS.md`

يمثل:

```text
What has actually been completed?
Where should execution resume?
```

ويحتوي على:

- آخر مهمة أو مبادرة منجزة.
- المهمة الحالية.
- المهام `TODO`.
- المهام `DONE`.
- المشاكل المفتوحة.
- نتائج الاختبارات.
- القرارات التنفيذية.
- المهمة التالية.

هذا الملف هو:

> **Single Source of Truth لحالة تنفيذ المشروع ونقطة الاستلام.**

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

## 2.4 `PROJECT_RAG_MENTOR_WORKFLOW.md`

يمثل:

```text
How should the technical mentor guide execution?
```

ويحدد:

- أسلوب العمل خطوة بخطوة.
- الفصل بين التنفيذ والمراجعة.
- قواعد الانتقال بين المهام.
- مستوى الدليل المطلوب قبل اعتبار المهمة منجزة.

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
Local Verification
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
APPROVED
  ↓
Update PROJECT_RAG_EXECUTION_PROGRESS.md
  ↓
Merge
  ↓
Next Task
```

لا يعتبر وجود PR أو CI أخضر وحده دليلاً كافياً على صحة المهمة.

---

# 4. قاعدة أساسية: مهمة واحدة لكل Branch

يجب أن يمثل كل Branch مهمة واحدة فقط قدر الإمكان.

أمثلة صحيحة وفق الـroadmap الحالي:

```text
task/A1-laravel-foundation
task/B6-upload-validation
task/C2-document-security-service
task/E9-direct-document-indexer
task/H1-ai-service-client
task/M4-ask-conversation-job
task/O2-qdrant-leakage
```

مثال غير مرغوب:

```text
task/laravel-fastapi-chat-all
```

لأنه يجمع عدة مسؤوليات ومراحل.

## 4.1 استثناء المبادرات المعمارية الموثقة

يجوز لقرار معماري Cross-cutting أن ينفذ في Branch واحد إذا كان موثقاً صراحةً في الـMaster Plan والـExecution Progress ويحتاج Cleanup متماسكاً عبر أكثر من طبقة.

مثال:

```text
task/remove-compare-winner-flow
```

في هذه الحالة يجب:

- تحديد Scope المبادرة قبل التنفيذ.
- إجراء Impact/Reference Audit.
- منع Scope creep خارج القرار.
- مراجعة جميع الطبقات المتأثرة.
- تشغيل regression/full verification قبل الدمج.
- تحديث الوثائق النهائية قبل إغلاق المبادرة.

---

# 5. Branch Naming Convention

الصيغة الرسمية للمهام المخططة:

```text
<type>/<task-id>-<short-description>
```

## الأنواع المعتمدة

### Task

للمهام الأساسية المخططة:

```text
task/A1-laravel-foundation
task/B3-document-status-enum
task/E9-direct-document-indexer
task/H1-ai-service-client
task/M4-ask-conversation-job
```

### Fix

لإصلاح مشكلة:

```text
fix/B6-mime-validation
fix/O2-user-filter-leak
```

### Refactor

لإعادة هيكلة بدون توسيع السلوك:

```text
refactor/H1-ai-service-client-boundary
```

### Test

للاختبارات فقط:

```text
test/O2-qdrant-leakage
test/P4-mixed-profile-chat-e2e
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
- الحفاظ على Task ID كما هو في الخطة النشطة:
  - `A1`
  - `B6`
  - `H1`
  - `O2`
  - `P4`
  - `DPL-5`
- الوصف قصير وواضح.
- لا يوضع اسم المطور في اسم الـBranch.
- لا يعاد استخدام Task ID قديم أزيل من الـroadmap لمهمة جديدة بدون قرار معماري صريح.

مثال:

```text
task/DPL-5-docker-buildx-compose
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

قبل بدء المهمة أو قبل Push/PR بعد عمل طويل، يجب التأكد من أن الـbaseline المحلي لم يتأخر عن `main` بصورة قد تسبب conflict أو مراجعة على أساس قديم.

---

# 8. Commit Message Convention

الصيغة المفضلة للمهام المخططة:

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
test(C8): cover security pipeline failures
feat(E9): add direct qdrant document indexer
refactor(H1): isolate ai service client boundary
docs(H1): update execution progress
```

## 8.1 المبادرات المعمارية Cross-cutting

إذا كان التغيير مبادرة معمارية موثقة وليس Task عادية، يجوز:

```text
refactor(ARC-1): remove obsolete lifecycle
```

أو Conventional Commit بدون Task ID عندما يكون اسم الـBranch والـPR والوثائق يحدد Scope المبادرة بوضوح:

```text
refactor: remove obsolete lifecycle
```

القواعد:

- كل Commit يجب أن يكون مفهوماً لوحده.
- تجنب الرسائل العامة مثل:
  - `update`
  - `changes`
  - `fix stuff`
- لا تجمع تغييرات غير مرتبطة في Commit واحد.
- لا تستخدم Task ID لم يعد موجوداً في الخطة النشطة.

---

# 9. Pull Request Naming Convention

للمهام العادية:

```text
[<task-id>] <task title>
```

أمثلة:

```text
[A1] Laravel Application Foundation
[B1] Documents Migration
[H1] AI Service Client
[O2] Qdrant Leakage Verification
```

للمبادرات المعمارية الموثقة يجوز:

```text
[ARC-1] Remove obsolete processing lifecycle
```

أو Conventional PR title واضح:

```text
refactor: remove obsolete processing lifecycle
```

الأهم أن يكون Scope الـPR واضحاً ويمكن ربطه بالـExecution Progress.

---

# 10. قالب Pull Request

كل PR يجب أن يوضح:

## Task

```text
Task ID:
H1
```

أو عند مبادرة معمارية:

```text
Initiative:
ARC-1
```

## Goal

ما المطلوب تحقيقه في هذه المهمة؟

## Changes

ما الملفات أو المكونات التي تغيرت؟

## Tests

ما الاختبارات التي تم تشغيلها فعلياً؟

## Acceptance Criteria

ما شروط اعتبار المهمة ناجحة؟

## Notes

أي قرار أو trade-off أو مشكلة معروفة.

عند وجود Schema أو Contract change يجب توضيحه صراحةً.

---

# 11. نطاق المراجعة

عند مراجعة أي PR يجب فحص المحاور التالية حسب طبيعة المهمة.

## 11.1 Correctness

- هل التنفيذ يحقق المطلوب؟
- هل توجد أخطاء منطقية؟
- هل حالات الفشل معالجة؟
- هل السلوك مطابق للـMaster Plan؟
- هل التطبيق يستخدم الـactive/current contracts وليس semantics تاريخية ألغيت؟

## 11.2 Architecture

- هل المسؤوليات موزعة في الطبقة الصحيحة؟
- هل Laravel يتجنب منطق AI؟
- هل FastAPI يتجنب إدارة application state الخاصة بـLaravel؟
- هل Qdrant infrastructure منفصلة عن Laravel business state؟
- هل يوجد coupling غير ضروري؟
- هل يوجد violation لـSeparation of Concerns؟
- هل تم تجنب abstraction لا حاجة لها؟

## 11.3 Clean Code

- Single Responsibility.
- أسماء واضحة.
- تجنب الدوال الضخمة.
- تجنب duplication.
- تجنب magic values.
- configuration بدلاً من hardcoding.
- dependency direction صحيحة.
- contracts صريحة وقابلة للاختبار.

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
- trusted profile/run/collection routing.

## 11.5 Data Integrity

- العلاقات صحيحة.
- القيود والفهارس مناسبة.
- status transitions صحيحة.
- `active_processing_run_id` لا يشير إلا إلى Run تخص نفس الوثيقة وحالتها `indexed`.
- لا توجد duplicates غير مقصودة.
- الحذف وإعادة المعالجة يحافظان على الاتساق.
- لا يتم حذف الـactive run القديمة قبل نجاح البديل في reprocessing.

## 11.6 Error Handling

- Exceptions واضحة.
- الأخطاء المؤقتة والدائمة مميزة عند الحاجة.
- رسائل المستخدم لا تكشف تفاصيل حساسة.
- Logs مفيدة للتشخيص.
- الفشل لا يؤدي إلى unsafe fallback.

## 11.7 Tests

- هل توجد اختبارات مناسبة للمهمة؟
- هل تغطي happy path؟
- هل تغطي failure path؟
- هل تغطي security boundaries عند الحاجة؟
- هل الاختبارات مستقلة وقابلة للتكرار؟
- هل test isolation صحيح بالنسبة لـ`.env` والـruntime configuration؟
- هل regression tests تحمي العقود التي تم حذفها أو تبسيطها عند الحاجة؟

## 11.8 Maintainability

- هل الكود سهل القراءة؟
- هل يمكن توسيعه لاحقاً؟
- هل API contract واضح؟
- هل التعديلات المستقبلية محصورة في طبقات مناسبة؟
- هل يوجد dead code أو abstraction غير مستخدمة؟
- هل أسماء الحقول والعلاقات تعكس semantics الحالية؟

## 11.9 Performance

يتم تقييمها فقط عندما تكون مهمة فعلاً للمهمة الحالية، مثل:

- N+1 queries.
- تحميل ملفات ضخمة في الذاكرة.
- batching.
- Qdrant query limits.
- retries.
- timeouts.
- concurrent workloads.
- local heavy-model lifecycle.
- worker concurrency.
- memory release after local AI stages.

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
- تغيير schema يترك التطبيق في حالة غير قابلة للإقلاع.
- bypass أمني غير مقصود.

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
- status transition غير صحيح.
- Qdrant scope ناقص.
- contract لا يطابق الخطة الحالية.

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
- Integration يحتاج Docker/runtime فعلي.
- Provider خارجي يحتاج secret غير متاح.
- Local AI يحتاج جهازاً فعلياً لإثبات load/release behavior.

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
- الـbranch مبني على baseline صالح للمراجعة.

بعدها يمكن اعتبار المهمة:

```text
DONE
```

حسب قواعد تحديث الـExecution Progress.

---

# 14. Definition of Review Done

لا تعتبر المراجعة منتهية حتى يتم فحص ما ينطبق من:

- PR metadata.
- base/head refs.
- changed files.
- diff أو patches.
- الملفات المهمة كاملة عند الحاجة.
- tests / CI.
- migrations/schema عند وجود تغيير بيانات.
- العلاقات مع الكود المحيط.
- API/DTO contracts.
- Acceptance Criteria.
- security implications.
- open review threads إن وجدت.
- documentation consistency عندما يغير PR المعمارية أو Task IDs.

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
- infrastructure boundaries.
- الملفات التي يعتمد عليها الكود الجديد.
- Git history للمكونات عندما يكون أصلها مهم لفهم هل هي legacy أم لازمة.

الهدف:

> مراجعة السلوك الفعلي، لا مجرد الأسطر الجديدة.

وفي Cleanup/Refactor معماري يجب مراجعة **ما بقي** بعد الحذف، وليس فقط الملفات المحذوفة.

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

إذا لم يوجد CI يغطي المهمة، تستخدم نتائج الاختبارات المحلية التي أرسل المستخدم ناتجها صراحةً، ولا يفترض نجاحها بدون دليل.

---

# 17. حالات لا تعتبر فيها المهمة DONE

لا تعتمد المهمة إذا كان أي مما يلي صحيحاً:

- الكود غير مختبر عندما تكون الاختبارات ضرورية.
- يوجد failing CI مطلوب.
- يوجد security issue معروف.
- يوجد TODO أساسي داخل نفس Scope.
- Acceptance Criteria غير مكتملة.
- تم تنفيذ جزء فقط من Task.
- يوجد workaround مؤقت غير موثق.
- توجد migrations غير قابلة للتطبيق على الـbaseline المعتمد.
- يوجد hardcoded secret.
- توجد بيانات مستخدم بدون ownership filtering.
- Qdrant operation متعددة المستخدمين بدون scope موثوق.
- FastAPI endpoint عام بدون الحماية المطلوبة.
- الملفات الخاصة مخزنة public.
- ClamAV تم تجاوزه في مسار upload حيث هو مطلوب.
- تم تغيير active run قبل اكتمال الفهرسة والتحقق.
- يوجد silent Cloud/CPU/provider fallback يخالف السياسة.

---

# 18. تحديث `PROJECT_RAG_EXECUTION_PROGRESS.md`

بعد نجاح المراجعة فقط، يتم تحديث المهمة.

مثال:

```text
H1 — DONE
```

ويجب أن يحفظ ملف Progress ما يلزم لنقطة الاستلام، مثل:

```text
Task:
H1

Status:
DONE

Branch:
task/H1-ai-service-client

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
H2 — Processing DTOs and contract alignment
```

لا يجب نسخ تاريخ ضخم إلى Progress إذا كان محفوظاً في Git؛ الملف يجب أن يبقى مفيداً كنقطة استلام تنفيذية.

---

# 19. Evidence لكل مهمة

يجب الاحتفاظ بدليل قابل للتتبع.

الحد الأدنى حسب نوع المهمة:

- Task ID أو Initiative ID.
- Branch.
- PR number عند فتح PR.
- reviewed commit SHA.
- test result.
- review status.
- schema/migration verification عند الحاجة.

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
Roadmap/Task Renumbering
```

وعندها يجب:

1. تحديث الـMaster Plan.
2. تحديث الـExecution Progress.
3. التأكد أن Task IDs والأسماء متطابقة بينهما.
4. مراجعة أي بروتوكولات أو docs أخرى تحتوي أمثلة IDs قديمة.
5. عدم إبقاء مهام أزيلت من الـTarget Architecture كمهام نشطة تحت أسماء `CANCELLED` أو مشابه إذا كان القرار هو تنظيف الـroadmap.

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
H1 AiServiceClient
```

وأثناءها ظهر تحسين كبير غير ضروري الآن في Retrieval.

لا يتم إدخاله داخل نفس PR.

يسجل كـ:

```text
Follow-up Task
```

ويؤجل إلى مرحلته المناسبة.

إذا كانت المشكلة BLOCKER لصحة المهمة الحالية، تعالج فقط بالحد اللازم لرفع الـBlocker وتوثق بوضوح.

---

# 23. حجم Pull Request

يفضل أن يكون PR:

- صغيراً قدر الإمكان.
- له هدف واحد.
- قابل للمراجعة.
- لا يحتوي تغييرات unrelated.

إذا أصبحت Task الأصلية كبيرة جداً، يجوز تقسيم التنفيذ إلى Subtasks بشرط توثيق ذلك في Progress وعدم تغيير الهدف الأصلي بدون قرار واضح.

المبادرات المعمارية Cross-cutting قد تكون أكبر، لكن يجب تعويض ذلك بـ:

- Impact Audit.
- Scope واضح.
- مراجعة طبقية.
- regression/full verification.
- توثيق نهائي متسق.

---

# 24. سياسة Commits أثناء المراجعة

يفضل الاحتفاظ بالـCommits مفهومة أثناء دورة المراجعة.

قبل الدمج يمكن استخدام:

```text
Squash Merge
```

إذا كان ذلك أنسب لسجل `main`.

لا يتم إعادة كتابة history أو force-push أثناء المراجعة بدون حاجة واضحة ومعرفة أثر ذلك على PR.

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
INTERNAL_API_KEY
AI_SERVICE_API_KEY
QDRANT_API_KEY
private keys
```

يجب توفير:

```text
.env.example
```

بدون قيم سرية حقيقية.

---

# 27. الملفات المولدة والمؤقتة

لا يتم Commit لملفات build/cache/dependencies أو audit outputs غير المطلوبة.

أمثلة شائعة:

```text
vendor/
node_modules/
.env
storage/logs/*
__pycache__/
.pytest_cache/
.venv/
*.tmp
local audit output files
```

ويجب ضبط `.gitignore` حيث يكون ذلك مناسباً.

قبل Commit كبير، تتم مراجعة `git status --short` للتأكد أن قائمة الملفات ضمن Scope.

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

في processing orchestration يجب أن يبقى Laravel مصدر الحقيقة لـ:

```text
ownership
document state
processing run state
active_processing_run_id
transactions
queue orchestration
```

ولا ينفذ Parsing/Embeddings/Retrieval داخله.

---

# 29. قاعدة مراجعة FastAPI

في FastAPI تتم مراجعة:

- API schemas.
- validation.
- configuration.
- clients.
- parsing/loaders.
- processing services.
- Qdrant infrastructure.
- exceptions.
- logging.
- runtime/resource lifecycle.
- async/sync boundaries عند الحاجة.
- tests.

Endpoints يجب ألا تحمل منطق RAG كبيراً مباشرة.

FastAPI لا يصبح مصدر الحقيقة لـLaravel business state.

---

# 30. قاعدة مراجعة RAG

يجب التحقق من:

- document normalization.
- chunk metadata.
- passage/query embedding separation عند الحاجة.
- dense retrieval.
- sparse retrieval.
- RRF.
- reranker.
- context building.
- prompt behavior.
- source preservation.
- insufficient-context behavior.
- profile-specific provider routing.
- عدم مقارنة raw scores عبر embedding spaces مختلفة.

Cloud وHybrid Local مساران مستقلان، وجودهما معاً في محادثة يعني mixed-profile retrieval لDocuments مفهرسة مسبقاً، وليس إنشاء workflow اختيار بين نتيجتي معالجة للـUpload.

---

# 31. قاعدة مراجعة Qdrant

## 31.1 الكتابة والفهرسة

كل Point يجب أن تحمل metadata اللازمة لعزلها وتتبعها:

```text
user_id
document_id
processing_run_id
processing_profile
```

الفهرسة الدائمة تستخدم:

- deterministic Point IDs.
- idempotent upsert.
- Collection مشتقة Server-side من Processing Profile موثوقة.
- count verification ضمن نطاق الـRun قبل اعتبارها indexed.

## 31.2 Retrieval

لا يمر أي Retrieval متعدد المستخدمين بدون filters موثوقة:

```text
user_id = current user
AND
document_id = authorized document
AND
processing_run_id = active indexed run
```

وعند عدة وثائق تبنى الـtargets Server-side من MySQL بعد التحقق من ownership والحالة.

## 31.3 Delete / Reprocess / Admin

يطبق العزل نفسه على:

- delete.
- reprocess cleanup.
- admin chunk browsing.
- maintenance queries المرتبطة بالمستخدم.

Browser لا يختار Collection أو Run موثوقة بمفرده.

---

# 32. قاعدة مراجعة ClamAV

الـflow الافتراضي عند تفعيل الفحص:

```text
Upload
→ Private security quarantine
→ Security Scan
→ Clean
→ Permanent private storage
→ Processing
```

وليس:

```text
Upload
→ FastAPI processing
→ Scan later
```

عند اكتشاف ملف مصاب:

```text
infected
→ no processing
```

وعند تعطل ClamAV بينما الفحص مفعّل:

```text
Fail Closed
```

يجوز تعطيل الفحص فقط عبر trusted server-side configuration صريحة حسب الخطة.

الـquarantine الأمنية ليست processing artifact ولا vector staging layer.

---

# 33. قاعدة مراجعة المصادر

المصدر المعروض يجب أن يكون قابلاً للتتبع إلى ما ينطبق من:

```text
document_id
processing_run_id
processing_profile
page / section
chunk_index
qdrant_point_id
source_number
```

بحسب نوع الملف والعقد النهائي للرسائل.

لا يتم اختراع page لـDOCX/TXT عندما لا توجد page metadata موثوقة.

---

# 34. قاعدة مراجعة LLM Providers

Routing لا يعتمد على Browser أو global provider switch غير موثوق.

المطلوب مفاهيمياً:

```text
Authorized document targets
  ↓
trusted active ProcessingRun/profile
  ↓
LLMProvider registry/routing
  ↓
Cloud or Local provider
```

Cloud active run:

```text
Hugging Face Router
→ Qwen/Qwen3.5-9B
```

Hybrid Local active run:

```text
Ollama
→ qwen3.5:4b
```

لا يسمح بتسريب تفاصيل مزود واحد عبر باقي النظام.

---

# 35. قاعدة Fallback والـLocal Resources

لا يوجد silent fallback من Local إلى Cloud عند الفشل.

ولا يوجد silent device/provider fallback يخالف configuration الموثوقة.

للمسار المحلي يجب احترام:

```text
single active heavy model
worker concurrency = 1
lazy load
release after stage
OLLAMA_KEEP_ALIVE=0
```

وعند مشاركة الموارد مع ClamAV يجب احترام global heavy-resource lock حسب الخطة.

إذا فشل Local provider، يعاد خطأ واضح بدلاً من إرسال المحتوى إلى Cloud تلقائياً.

---

# 36. GitHub Review Handoff بين المحادثات

عند بدء Chat جديد للمراجعة يكفي تقديم:

```text
Repository:
owner/repository

Pull Request:
#N

Task or Initiative:
H1
```

ثم تتم قراءة:

```text
PROJECT_RAG_MASTER_PLAN.md
PROJECT_RAG_EXECUTION_PROGRESS.md
PROJECT_RAG_GITHUB_REVIEW_PROTOCOL.md
```

من GitHub عند الحاجة.

لا يفترض Task ID أو next task من أمثلة ثابتة داخل هذا البروتوكول؛ تؤخذ الحالة الحالية دائماً من `PROJECT_RAG_EXECUTION_PROGRESS.md`.

---

# 37. طلب المراجعة القياسي

يمكن استخدام الطلب المختصر:

```text
راجع PR رقم <N> لمهمة <TASK-ID> حسب GitHub Review Protocol.
تحقق من الكود والاختبارات ومعيار الإنجاز.
إذا كانت هناك مشاكل أعطني NEEDS CHANGES مع التفاصيل.
إذا نجحت المهمة أعطني APPROVED وحدد تحديث Progress المطلوب.
```

تحديث GitHub نفسه يتم فقط بعد وجود صلاحية وموافقة صريحة على عملية الكتابة المطلوبة.

---

# 38. التقرير النهائي لكل Review

يجب أن يحتوي ملخص المراجعة على:

```text
Task / Initiative
PR
Base / Head
Review Result
Critical Findings
Major Findings
Minor Findings
Tests / CI
Schema / Contract Verification عند الحاجة
Acceptance Criteria
Final Decision
Progress Update
Next Task
```

يجب فصل:

- ما تم التحقق منه فعلياً.
- ما تم استنتاجه.
- ما لم يمكن التحقق منه في البيئة الحالية.

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

# 41. الحالة التنفيذية الحالية

هذا البروتوكول **لا يخزن Current Task ثابتة** حتى لا يصبح قديماً مع تقدم المشروع.

الحالة الحالية دائماً تقرأ من:

```text
PROJECT_RAG_EXECUTION_PROGRESS.md
```

والـroadmap النشط الحالي يغطي:

```text
A — Foundation
B — Documents Foundation
C — Security Pipeline
D — FastAPI Foundation
E — Qdrant
F — Parsing and Normalization
G — Profile Processing
H — Processing Orchestration
I — Blade Documents Experience
J — Conversations Database
K — Retrieval and Reranking
L — Generation
M — Chat Experience
N — Filament
O — Security and Operations
P — Final Validation
DPL-* — Deployment
```

عند baseline توثيق هذا البروتوكول، المبادرة المعمارية `ARC-1` أنهت إزالة الـprocessing lifecycle القديم محلياً على Branch مخصص، والمهمة المخططة التالية بعد دمجها هي:

```text
H1 — AiServiceClient
```

لكن بعد أي Merge أو Progress update لا يعتمد هذا السطر كبديل عن `PROJECT_RAG_EXECUTION_PROGRESS.md`.

---

# 42. فحص الاتساق قبل دمج تغيير معماري

إذا كان PR يغير Architecture أو roadmap أو naming/domain semantics، يجب قبل الدمج التحقق من:

```text
Master Plan
Execution Progress
GitHub Review Protocol
Mentor Workflow عند تأثره
code
schema
tests
environment examples
```

ويجب التأكد من:

- عدم وجود Task IDs قديمة توجه التنفيذ.
- عدم وجود status/field/endpoint قديم ما زال Active بالخطأ.
- عدم وجود dead code ناتج عن الـcleanup.
- عدم وجود config/env values لميزة أزيلت.
- عدم وجود test يختبر workflow أزيل، إلا إذا كان Negative Regression Guard واضحاً.
- أن Git history وحده هو مكان التفاصيل التاريخية التي لم تعد جزءاً من الـTarget Architecture.

---

# 43. قاعدة Clean Baseline

عند إعادة بناء baseline أثناء مرحلة تطوير مبكرة، يجوز Consolidate لمigrations أو schema فقط إذا:

- لا توجد بيانات يجب الحفاظ عليها.
- القرار موثق.
- تم التحقق من `migrate:fresh` أو ما يعادله.
- تم تشغيل regression المناسب.

بعد وجود بيانات يجب الحفاظ عليها أو اعتماد release baseline، تستخدم Forward Migrations بدلاً من إعادة كتابة history التنفيذي المعتمد.

هذه القاعدة لا تعني حذف Git history؛ Git يبقى المرجع التاريخي للتغييرات السابقة.

---

# 44. الخلاصة

البروتوكول يهدف إلى أن تكون كل مهمة:

```text
Scoped
→ Implemented
→ Verified
→ Reviewed
→ Traceable
→ Documented
→ Mergeable
```

مع المحافظة على:

```text
Clean Code
Separation of Concerns
Security
User Isolation
Data Integrity
Explicit Contracts
Maintainability
Evidence-based Review
```

وأي قرار معماري جديد يجب أن يظهر أولاً في الـMaster Plan والـExecution Progress، ثم ينعكس على التنفيذ والمراجعة بدون إبقاء توجيهات قديمة في الوثائق النشطة.
