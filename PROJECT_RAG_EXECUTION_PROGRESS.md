# سجل تنفيذ مشروع RAG

> **المرجع المعماري:** `PROJECT_RAG_MASTER_PLAN.md`  
> **الغرض:** حفظ الحالة التنفيذية الفعلية ونقطة الاستلام بين المحادثات  
> **آخر تحديث:** 2026-09-05
> **الحالة العامة:** قيد التنفيذ — K6 مكتملة ومدموجة في PR #113؛ أضيف Jina reranking لمسار `cloud` بعد Dense + BM25 + RRF مع الحفاظ على trusted RetrievalScope، والمهمة الحالية هي K7 — Local BGE reranker وفق الـMaster Plan

---

# CURRENT HANDOFF — نقطة الاستلام

> **هذا هو أول قسم يُقرأ في أي Chat جديد.**

```text
Project Mode: Start From Scratch
Repository: mona-alrayes/RAG-Local-Documents-System
Default Branch: main
Repository Status: Active Development

Verified Main Commit: 9b2301875077c1a6a0dc1999a7ed3101bd49eaa7
Last Merged Feature PR on main: #113 — K6 Cloud Jina Reranker
Latest Task PR: #113 — K6 Cloud Jina Reranker
Verified K1 Feature Commit:
- 28759b3fd2df80283fd8d3fba831aaadee569433 — K1 Trusted document_targets
Verified K1 Merge Commit: 3fc41d4490c46012d314f4a58bf446e3e1946fad
Verified K2 Feature Commit:
- 608b50955616c96f898c51f5080ceb98beb503d2 — K2 Cloud Query Embedding / Retrieval
Verified K2 Merge Commit: d512feb1100c7d1fa791340bcfb9eac92b3cbb84
Verified K3 Feature Commit:
- 21ea644202f03d0430682d4e37c4464d4205b8fa — K3 Hybrid Local Query Embedding / Retrieval
Verified K3 Merge Commit: c2e488a8d45e07b86bb6d25f01cb78a9f70edf8b
Verified K4 Feature Commit:
- a69fd712c61e73b4100d95be542a7a4942bef9b0 — K4 Trusted User / Document / Run Retrieval Filters
Verified K4 Merge Commit: 3c7be519fac934de2ce1f9264be202623c69f757
Verified K5 Feature Commit:
- 45470543615ab6c9d2e5edf7d7ee2c055af0c721 — K5 Per-Profile Dense + BM25 + RRF Retrieval
Verified K5 Merge Commit: cc5dcc4f951ffac00e07db592a1d32091bf9a602
Verified K6 Feature Commit:
- a2fd18f6535f99099e69a7ed764158136334da9c — K6 Cloud Jina Reranker
Verified K6 Merge Commit: 9b2301875077c1a6a0dc1999a7ed3101bd49eaa7
Documentation Baseline: تم تسجيل اكتمال K6 وتسليم K7 — Local BGE reranker كمهمة حالية. أضاف K6 إعادة ترتيب نتائج `cloud` باستخدام `jina-reranker-v2-base-multilingual` بعد Dense + BM25 + RRF، مع توسيع candidate pool قبل reranking وتطبيق final limit بعده. يعاد ترتيب نفس trusted candidates بالاعتماد على indexes التي يعيدها Jina دون إعادة بناء النتائج من محتوى المزود، مع Fail-Closed validation للاستجابات malformed والـduplicate/out-of-range indexes والـinvalid scores، وإعادة استخدام Jina retry/error handling. بقي trusted scope على `user_id`, `document_id`, `processing_run_id`, و`processing_profile` محفوظاً، ولم يتغير Hybrid Local أو Qdrant schema/indexing أو generation.

Current Working Branch: main

Latest Completed Architectural Initiative:
ARC-1 — Remove Compare/Winner lifecycle

Latest Completed Task:
K6 — Cloud Jina Reranker

Current Task:
K7 — Local BGE reranker

Current Phase:
K — Retrieval and Reranking

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
- حولت I3 `/documents` إلى صفحة إدارة وثائق فعلية داخل App Shell الحالي، واستخدمت `DocumentReadService` وH12 Presentation DTOs بدل query مباشر داخل Controller/Blade.
- تدعم I3 pagination والبحث وفلتر Document status وفلتر file type عبر query string، وتحافظ على قيم الفلاتر أثناء pagination.
- تستخدم I3 document cards responsive بدل جدول إداري ثقيل، وتعرض title/original filename/type/file size/status/timestamp.
- تعيد I3 استخدام `documents.status-indicator` و`documents.actions-menu` وتحترم `canDownload`, `canReprocess`, `canDelete`، وتعرض reprocessing indicator عند الحاجة.
- لا تعرض I3 raw `failure_reason`؛ تستخدم safe failure presentation، وتفصل بين no documents empty state وfiltered no-results empty state.
- بقيت Queries user-scoped مع eager loading للـactive/latest processing runs لمنع N+1.
- أضيف `DocumentReadService::hasAnyForUser()` لدعم empty-state distinction، وأصلح بناء user-scoped query باستخدام relation `getQuery()` ليتوافق مع typed Builder المستخدم في filter/search methods.
- أضافت I4 نموذج رفع وثيقة واحدة فقط بصيغ PDF / DOCX / TXT داخل صفحة `/documents` الحالية.
- تعرض I4 خياري `cloud` و`hybrid_local` وفق Capabilities الفعلية، وتمنع اختيار الـProfile غير المتاحة دون أي automatic fallback.
- تبقى Server-side capability verification داخل orchestration مصدر الحقيقة النهائي حتى لو كانت الواجهة قد عرضت Profile متاحة سابقًا.
- تفشل واجهة I4 مغلقًا عند تعطل خدمة Capabilities أو فساد response، وتُعطّل الرفع عندما لا توجد أي Profile متاحة.
- إذا أصبحت الـProfile غير متاحة بين عرض الصفحة وتنفيذ الرفع، يعاد المستخدم بأمان إلى صفحة الوثائق برسالة localized دون إنشاء ProcessingRun أو Queue job بديلة.
- حافظت I4 على redirect/flash contract المثبت في H13 ولم تنشئ مسار Upload موازياً.
- أصبحت ملفات ترجمة Documents في المسار القياسي `lang/ar` و`lang/en` مع رسائل I4 الجديدة.
- أضيفت اختبارات I4 لحالات one-file upload، capability-aware UI، unavailable profile، fail-closed، وغياب fallback.
- أعادت I5 بناء صفحة تفاصيل الوثيقة باستخدام `DocumentReadService` وpresentation DTOs الحالية بدل تمرير Eloquent مباشرة إلى Blade.
- تعرض I5 `active_run` و`latest_attempt` منفصلتين صراحة، وتحافظ على النسخة السابقة فعالة أثناء reprocessing، كما تبقي الوثيقة Ready عند فشل آخر reprocessing إذا بقيت active run السابقة صالحة.
- يعرض processing timeline في I5 فقط timestamps المسجلة فعليًا للـQueued/Processing/Indexing/Completed/Failed، مع pages/chunks/stage timings والتحذيرات الآمنة.
- لا تعرض I5 raw `failure_reason` أو Qdrant/profile internals؛ وتستخدم safe failure presentation للمستخدم.
- تحترم I5 صلاحيات Download/Reprocess/Delete، وتمنع Reprocess في الواجهة إذا كانت Processing Profile الفعالة غير متاحة ضمن Capabilities الحالية.
- أضافت I5 private browser preview محميًا بنفس authorization الخاصة بمحتوى الوثيقة: PDF وTXT inline preview، بينما DOCX يبقى Download فقط، دون كشف public storage URL.
- حسنت I6 Accessibility لرسائل الحالة والتحقق من النماذج والإجراءات والنوافذ التأكيدية، مع الحفاظ على تجربة RTL قابلة للاستخدام بلوحة المفاتيح وتقنيات المساعدة.
- حسنت I6 Responsive لواجهات Workspace وDocuments list وDocument details والإجراءات والنوافذ على الشاشات الصغيرة 320–375px والتابلت والديسكتوب، مع معالجة النصوص وأسماء الملفات الطويلة والـoverflow.
- وحّدت I6 حالات النجاح والتحذير والخطأ برسائل localized وآمنة، ومنعت كشف raw `failure_reason` أو تفاصيل داخلية حساسة للمستخدم.
- حافظت I6 على الفصل بين `Document.status` وجاهزية الوثيقة وبين `ProcessingRun.status` وتقدم المحاولة، ولم تنشئ Business State Machine داخل JavaScript.
- أضافت I6 polling endpoint في Laravel لطبقة العرض، لكن J8 استبدلت آلية التحديث القديمة المعتمدة على JavaScript fetch + `window.location.reload()` بـtargeted conditional Livewire polling وLivewire navigation عند تغير snapshot.
- أصبحت Workspace/Documents/Sidebar/Document Details تتحدث تلقائيًا اعتمادًا على Laravel/MySQL وPresentation Layer، مع تشغيل polling فقط عند `poll_required` أو hint موثوقة مكافئة وإيقافه عند stable/terminal state.
- لا يوجد Browser polling مباشر إلى FastAPI أو Qdrant، ولا يعاد تفسير lifecycle داخل JavaScript.
- حافظت I6 على private preview/download behavior وصلاحيات الوصول الحالية دون تحويل الملفات إلى Public URLs.
- أضافت J1 جدول `conversations` في Laravel/MySQL، وكل Conversation مملوكة لمستخدم عبر `user_id` مع FK يستخدم `restrictOnDelete()` بما يتوافق مع النمط الحالي.
- أضافت J1 حقل `title` nullable لدعم توليد اسم افتراضي للمحادثة لاحقًا من محتواها وإتاحة إعادة التسمية لاحقًا، دون تنفيذ منطق التوليد أو UI/API لإعادة التسمية في هذه المهمة.
- أضافت J1 `Conversation` Eloquent model وعلاقتي `Conversation -> belongsTo(User)` و`User -> hasMany(Conversation)`.
- لم تضف J1 document selection أو Messages أو Message sources أو Policies أو Conversation UI/controllers أو RAG/Retrieval/FastAPI/Qdrant/streaming/conversation memory.
- أضافت J2 جدول pivot باسم `conversation_document` وعلاقة Many-to-Many بين `Conversation` و`Document` مع علاقات Eloquent من الجهتين.
- تمنع J2 تكرار نفس `conversation_id/document_id` عبر unique constraint، وتستخدم Foreign Keys إلى `conversations` و`documents` مع `cascadeOnDelete()` لأن سجلات العلاقة ليست Business Entity مستقلة.
- لا يوجد Pivot Model مستقل لأن الجدول يمثل العلاقة فقط ولا يحمل business state، وتبقى Laravel/MySQL مصدر الحقيقة لهذه العلاقة.
- لم تضف J2 document selection flow أو authorization أو UI أو RAG، كما لم تحاول حل cross-user document selection عبر schema معقد أو trigger؛ يفرض ذلك لاحقًا في application layer عند تنفيذ document selection flow.
- أضافت J3 جدول `messages` و`Message` model وربط `Conversation -> Messages`، مع `MessageRole` (`user`, `assistant`) و`MessageStatus` (`pending`, `completed`, `failed`).
- تخزن J3 `content` و`execution_snapshot` و`metrics`، مع casts مناسبة للـenums وحقول JSON.
- أسست J3 مبكرًا جدول `message_sources` و`MessageSource` model وربط `Message -> MessageSources` و`MessageSource -> ProcessingRun`، مع حفظ `processing_run_id`, `qdrant_point_id`, `chunk_index`, `source_snapshot`, و`relevance_score`.
- أكملت J4 provenance الخاصة بـ`message_sources` بالاعتماد على `MessageSource.processing_run_id -> ProcessingRun` كمصدر موثوق للوصول إلى `document_id` وProcessing Profile و`qdrant_collection` دون تخزينها مرة ثانية داخل `message_sources`.
- غيرت J4 FK الخاصة بـ`message_sources.processing_run_id` إلى `CASCADE ON DELETE`؛ حذف ProcessingRun يحذف MessageSources التابعة لها فقط بينما تبقى Message نفسها.
- دمجت J4 أعمدة `kind`, `started_at`, `indexing_started_at`, و`failed_at` داخل migration الإنشاء الأصلية لـ`document_processing_runs`، وحذفت migration الإضافية `2026_09_03_060000_add_progress_fields_to_document_processing_runs_table.php` لأن قاعدة التطوير تبنى من الصفر.
- حدثت J4 اختبارات schema/provenance لتتوافق مع الـbaseline النهائي، بما في ذلك default/casts وحذف backfill test القديم المرتبط بالـmigration المحذوفة.
- أضافت J5 `ConversationPolicy` باستخدام Laravel Gate / Policy authorization.
- تسمح J5 للمستخدم المسجل بـ`viewAny` و`create`.
- تقصر J5 صلاحيات `view` و`update` و`delete` على مالك المحادثة وفق `conversation.user_id === user.id`، وتمنع مستخدمًا آخر من إدارة Conversation لا يملكها.
- لم تضف J5 Routes أو Controllers أو UI أو workflow جديد للمحادثات.
- أضافت J6 workflow لإنشاء Conversation جديدة وصفحة لعرض Conversations الخاصة بالمستخدم الحالي فقط.
- تستخدم J6 `ConversationPolicy` من J5 عبر `viewAny` للقائمة و`create` للإنشاء.
- Query القائمة user-scoped عبر علاقة المستخدم authenticated لمنع cross-user Conversation leakage.
- إنشاء Conversation يتم عبر علاقة المستخدم Server-side ولا يثق بـ`user_id` من client، لذلك لا يمكن تزوير ownership.
- بقي `title` nullable/اختياري وفق schema الحالية، وأضيفت Conversations إلى App Shell / Sidebar.
- لم تنفذ J6 اختيار Documents أو Messages أو Retrieval أو Chat workflow.
- أضافت J7 workflow لاختيار وثيقة واحدة أو عدة وثائق وربطها بمحادثة يملكها المستخدم، مع تغيير الاختيار أو إزالته بالكامل لاحقًا.
- تستخدم J7 علاقة `Conversation::documents()` و`sync()` لمزامنة الاختيار الحالي ومنع duplicate pivot rows، وتعرض فقط وثائق المستخدم الحالي.
- تحمي J7 الـConversation عبر `ConversationPolicy` وتتحقق Server-side من أن كل `document_id` يعود للمستخدم الحالي، وتغطي owner / other user / guest وترفض cross-user document linking.
- J8 فصلت `selected/attached` عن `runtime-capable`: الوثيقة غير الجاهزة تبقى مرتبطة بالمحادثة لكن تستبعد من runtime set حتى تصبح جاهزة.
- أضيف `ConversationRuntimeDocumentService` لحل الوثائق القابلة للاستخدام فعلياً في RAG للمحادثة، مع owner scoping وإعادة استخدام `DocumentAvailabilityResolver` والفشل المغلق عند active run غير صالحة أو cross-document.
- تحافظ J8 على Safe Reprocessing invariant: Run A الفعالة والمفهرسة تبقي الوثيقة `Ready` حتى لو كانت Run B الجديدة pending/processing/indexing/failed.
- أصبحت واجهة اختيار وثائق المحادثة Livewire component؛ اختيار المستخدم يتحدث فورياً، وتغير readiness في الخلفية يظهر عبر targeted conditional Livewire polling دون detach/reattach أو Manual Browser Refresh.
- أضيفت Livewire components مستقلة لتحديث حالات Workspace/Documents/Sidebar/Document Details بصورة تفاعلية اعتمادًا على Presentation Layer الحالية.
- يستمر polling فقط عندما يقرر السيرفر `poll_required`، ويتوقف عند stable/terminal state؛ لا polling دائم ولا Browser polling مباشر إلى FastAPI/Qdrant.
- أزيل اعتماد الواجهة القديمة على `data-document-poll-url`, `data-document-poll-error`, و`window.location.reload()` كمسار التحديث، ويستخدم `Livewire.navigate()` عند تغير snapshot مع الحفاظ على scroll عند الحاجة.
- لم تنفذ J8 Retrieval أو FastAPI query أو Qdrant retrieval أو LLM answer generation أو Messages/Chat execution.
- أضافت K1 `TrustedDocumentTarget` كـ`final readonly` typed DTO يحمل `documentId`, `processingRunId`, و`processingProfile` فقط.
- أضافت K1 `ConversationDocumentTargetService` التي تعيد استخدام `ConversationRuntimeDocumentService` من J8 وتحوّل الوثائق runtime-capable إلى targets موثوقة دون تكرار readiness logic.
- تستمد K1 `processingRunId` و`processingProfile` من `activeProcessingRun` الموثوقة Server-side ولا تثق بـruntime identifiers قادمة من Browser.
- تدعم K1 عدة وثائق وmixed profiles (`cloud`, `hybrid_local`) وتحافظ على Safe Reprocessing باستخدام active indexed Run القديمة أثناء replacement processing/failed.
- تفشل K1 مغلقًا عند corrupted foreign-document pivot أو cross-document active run، وتبقي selected/unready documents مختارة دون إنتاج target.
- K1 resolution read-only بالنسبة إلى `conversation_document`, `Document`, و`ProcessingRun`.
- لم تنفذ K1 query embeddings أو Qdrant retrieval أو RRF أو reranking أو FastAPI query/retrieval endpoint أو LLM generation أو context building أو citations أو streaming أو Chat execution.
- أضافت K2 `CloudJinaQueryEmbedder` لعمل query embedding باستخدام Jina `retrieval.query` ببُعد `1024`، مع رفض blank query قبل provider call وإعادة استخدام retry semantics الحالية.
- تفشل K2 مغلقًا إذا أعاد provider payload malformed أو أكثر/أقل من vector واحدة أو dimension غير `1024` أو قيماً غير قابلة للتحويل إلى float.
- أضافت K2 `CloudRetrievalService` و`QdrantCloudDenseRetriever` مع Server-side `resolve_qdrant_collection()` واستخدام named vector `dense_vector`.
- تقيد K2 كل Qdrant query بـ`user_id`, `document_id`, `processing_run_id`, و`processing_profile = cloud`، ثم تعيد التحقق دفاعياً من payload كل result قبل تحويله.
- لا تعيد K2 raw vectors (`with_vectors=False`) وتعيد typed `CloudRetrievalResult` فقط.
- لا يوجد unfiltered retrieval ولا fallback من Cloud إلى Hybrid Local، ولا LLM generation أو context building أو citations أو Chat execution ضمن K2.
- أضافت K3 Hybrid Local query embedding باستخدام نفس `BAAI/bge-m3` ونفس Local embedding semantics المستخدمة في document indexing وبُعد `1024`.
- تعيد K3 استخدام `LocalModelCoordinator` نفسه دون query-specific model lifecycle.
- أضافت K3 `HybridLocalRetrievalService` و`QdrantHybridLocalDenseRetriever` مع Hybrid Local collection محلولة Server-side وnamed vector `dense_vector`.
- تقيد K3 retrieval بـ`user_id`, `document_id`, `processing_run_id`, و`processing_profile = hybrid_local`، وتتحقق دفاعياً من scope النتائج.
- تستخدم K3 `with_vectors=False` وتعيد typed retrieval results فقط، وتفشل مغلقاً عند runtime/model/inference/scope failures دون Local → Cloud fallback.
- لم تدخل K3 في K4/K5 أو BM25/RRF أو reranking أو generation.
- أضافت K4 abstraction مشتركة صغيرة باسم `RetrievalScope` لقيم `user_id`, `document_id`, `processing_run_id`, و`processing_profile` الموثوقة فقط.
- أصبح نفس `RetrievalScope` المصدر الموحد لبناء exact Qdrant retrieval filter والتحقق الدفاعي post-query من payload كل result.
- بقي Cloud retrieval يفرض `ProcessingProfile.CLOUD` وبقي Hybrid Local retrieval يفرض `ProcessingProfile.HYBRID_LOCAL`، مع server-side collection resolution عبر `resolve_qdrant_collection()`.
- بقي `DENSE_VECTOR_NAME` و`with_vectors=False` وtyped retrieval results كما هي، ولا يوجد unfiltered retrieval path.
- لا يوجد cross-user أو cross-document أو cross-run أو cross-profile leakage؛ أي Qdrant result خارج trusted scope يفشل Fail-Closed.
- لم تعدل K4 Document Processing أو Laravel ولم تدخل في K5 أو BM25/RRF/reranking/generation/citations.
- أكملت K5 Hybrid Retrieval داخل كل Profile بصورة مستقلة: Cloud = Jina Dense + Cloud BM25 ثم RRF، وHybrid Local = BGE-M3 Dense + Local BM25 ثم RRF.
- يستخدم K5 Qdrant-native Dense/Sparse prefetch ثم `Fusion.RRF`، مع candidate expansion قبل الدمج وfinal limit مستقل.
- بقيت `RetrievalScope` الخاصة بـK4 المصدر الموحد للـexact trusted filters، ويطبق نفس filter على Dense prefetch وSparse/BM25 prefetch والـfinal fused query.
- تخضع final fused results للتحقق الدفاعي نفسه وتفشل Fail-Closed عند أي user/document/run/profile mismatch.
- بقي `with_vectors=False`، وserver-resolved Qdrant collections، وtyped retrieval results كما هي.
- لا يوجد cross-user/document/run/profile leakage، ولا cross-profile fusion، ولا Local → Cloud fallback ضمن K5.
- لم تغير K5 Qdrant schema أو indexing semantics، ولا تتضمن reranking؛ K6 هي Cloud Jina Reranker.
- أضافت K6 Jina reranking لمسار `cloud` باستخدام `jina-reranker-v2-base-multilingual` بعد Dense + BM25 + RRF.
- توسع K6 candidate pool قبل reranking ثم تطبق final limit بعد إعادة الترتيب.
- تعيد K6 ترتيب نفس trusted candidates باستخدام provider result indexes دون إعادة بناء النتائج من محتوى Jina.
- تفشل K6 مغلقًا عند malformed responses أو duplicate/out-of-range indexes أو invalid/non-finite scores، وتعيد استخدام retry/error handling الخاصة بمزود Jina.
- يبقى trusted scope `user_id/document_id/processing_run_id/processing_profile` محفوظًا بالكامل خلال reranking.
- لم تعدل K6 Hybrid Local أو Qdrant schema/indexing أو generation.

Latest Verification:
PR #113 merged on GitHub: PASS
PR title: feat(K6): add cloud Jina reranker
PR head commit: a2fd18f6535f99099e69a7ed764158136334da9c
PR merge commit: 9b2301875077c1a6a0dc1999a7ed3101bd49eaa7
main verified at K6 merge commit 9b2301875077c1a6a0dc1999a7ed3101bd49eaa7 before this documentation update: PASS
Focused regression suite: 90 passed
Full FastAPI suite: 218 passed

Current Task:
K7 — Local BGE reranker

K1 Completion:
- أضيف `TrustedDocumentTarget` كـimmutable typed DTO يحمل `documentId`, `processingRunId`, و`processingProfile`.
- أضيف `ConversationDocumentTargetService` وأعيد استخدام `ConversationRuntimeDocumentService` من J8 بدل تكرار runtime readiness logic.
- تستمد targets من `activeProcessingRun` الموثوقة Server-side ولا تثق بـ`processing_run_id` أو Processing Profile أو runtime identifiers قادمة من Browser.
- يدعم resolution عدة وثائق وmixed profiles: `cloud` و`hybrid_local`.
- Safe Reprocessing يستخدم active indexed Run القديمة أثناء replacement run الأحدث إذا كانت Processing أو Failed.
- corrupted foreign-document pivot لا يسرّب target، وcross-document active run لا ينتج target.
- الوثيقة selected وغير الجاهزة تبقى selected ولا تنتج runtime target.
- resolution لا يعدل `conversation_document` أو `Document` أو `ProcessingRun`.
- لم تنفذ K1 query embeddings أو Qdrant retrieval أو RRF أو reranking أو FastAPI query/retrieval endpoint أو LLM generation أو context building أو citations أو streaming أو Chat execution.

K2 Completion:
- يحول `CloudJinaQueryEmbedder` السؤال إلى Jina embedding باستخدام `task = retrieval.query` وdimension = `1024`.
- يرفض blank query قبل أي provider call، ويحافظ على retry semantics الموجودة لـJina.
- malformed أو wrong-dimension provider results تفشل Fail-Closed.
- تستخدم `CloudRetrievalService` `resolve_qdrant_collection()` لتحديد Cloud collection Server-side.
- يستخدم Qdrant named vector `dense_vector` ولا يعيد raw vectors.
- retrieval مقيد دائماً بالـtrusted scope: `user_id`, `document_id`, `processing_run_id`, و`processing_profile = cloud`.
- توجد defensive validation لكل Qdrant result للتأكد أنه ينتمي لنفس trusted scope قبل إنشاء typed result.
- لا يوجد unfiltered retrieval ولا fallback إلى Hybrid Local.
- تعيد K2 typed retrieval results فقط ولا تولد جواب LLM.
- Out of Scope: K3 Hybrid Local query embedding / retrieval، generalized K4 filtering، BM25 / RRF، reranking، cross-profile fusion، LLM generation، context building، citations، Chat execution، streaming، UI / Livewire، Laravel Chat orchestration، migrations.
- المهمة التالية وفق `PROJECT_RAG_MASTER_PLAN.md` كانت K3 — Hybrid Local query embedding / retrieval.

K3 Completion:
- يستخدم Hybrid Local query embedding نفس `BAAI/bge-m3` المستخدم لفهرسة الوثائق وبُعد `1024`.
- يعاد استخدام نفس Local embedding semantics ونفس `LocalModelCoordinator`.
- تُحل Hybrid Local Qdrant collection Server-side ويستخدم dense retrieval `dense_vector`.
- trusted scope: `user_id`, `document_id`, `processing_run_id`, و`processing_profile = hybrid_local`.
- `with_vectors=False`؛ typed retrieval results فقط.
- runtime/model/inference/scope failures تفشل Fail-Closed، ولا يوجد Local → Cloud fallback.
- Out of Scope: K4 generalized filters، K5 Dense + BM25 + RRF، BM25/RRF، reranking، generation.
- المهمة التالية وفق `PROJECT_RAG_MASTER_PLAN.md` كانت K4 — user / document / run filters.

K4 Completion:
- أضيف `RetrievalScope` كـshared immutable retrieval-scope abstraction صغيرة تحمل فقط `user_id`, `document_id`, `processing_run_id`, و`processing_profile`.
- يستخدم نفس الـscope لبناء exact Qdrant filter والتحقق الدفاعي من results بعد الاستعلام، بدل تكرار قيم security boundary بين Cloud وHybrid Local.
- Cloud retrieval يبقى مقيداً بـ`ProcessingProfile.CLOUD` وHybrid Local بـ`ProcessingProfile.HYBRID_LOCAL`.
- اختيار Qdrant collection يبقى Server-side عبر `resolve_qdrant_collection()`، مع بقاء `DENSE_VECTOR_NAME`, `with_vectors=False`, وtyped results.
- لا يوجد unfiltered retrieval path، ولا cross-user/document/run/profile leakage؛ أي mismatch في payload يفشل Fail-Closed.
- security boundary أصبحت موحدة وقابلة لإعادة الاستخدام للمسارين دون دمجهما في provider abstraction واحدة.
- Out of Scope: Document Processing، Laravel، K5 Dense + BM25 + RRF، BM25 query retrieval، sparse query generation، RRF، reranking، Jina reranker، BGE reranker، cross-profile fusion، generation، citations.
- المهمة التالية وفق `PROJECT_RAG_MASTER_PLAN.md` كانت K5 — Per-profile Dense + BM25 + RRF.

K5 Completion:
- أصبح Cloud retrieval يجمع Jina dense query embedding مع Cloud BM25 query representation باستخدام `Qdrant/bm25` و`multilingual` tokenizer ثم يطبق Qdrant-native RRF.
- أصبح Hybrid Local retrieval يجمع BGE-M3 dense query embedding مع Local BM25 query representation باستخدام نفس primitive المستخدمة في document indexing ثم يطبق Qdrant-native RRF.
- كل Profile يعمل بصورة مستقلة؛ لا يوجد cross-profile fusion ضمن K5.
- يستخدم المساران Dense prefetch وSparse/BM25 prefetch مع `RRF_CANDIDATE_MULTIPLIER = 2` قبل fusion وfinal `limit` مستقل.
- `RetrievalScope` من K4 هي المصدر الموحد للـexact filter المطبق على كل candidate path وعلى الـfinal fused query.
- final results تخضع defensive scope validation وتفشل Fail-Closed عند أي mismatch.
- بقي `with_vectors=False`، وserver-side collection resolution، وtyped retrieval results دون تغيير.
- لا يوجد Local → Cloud fallback ولا unfiltered retrieval path.
- لم تتغير Qdrant schema أو indexing semantics.
- Out of Scope: K6 Cloud Jina reranker، K7 Local BGE reranker، K8 cross-profile rank fusion، generation، citations.
- المهمة التالية وفق `PROJECT_RAG_MASTER_PLAN.md` هي K6 — Cloud Jina Reranker.

K6 Completion:
- أضيف Jina reranking لمسار `cloud` باستخدام `jina-reranker-v2-base-multilingual`.
- تنفذ مرحلة reranking بعد Dense + BM25 + RRF، مع توسيع candidate pool قبلها وتطبيق final limit بعدها.
- يعاد ترتيب نفس trusted candidates اعتمادًا على indexes العائدة من Jina و`return_documents=false`، دون إعادة بناء النتائج من محتوى المزود.
- malformed responses والـduplicate/out-of-range indexes والـinvalid/non-finite relevance scores تفشل Fail-Closed.
- يعاد استخدام retry/error handling الخاصة بمزود Jina.
- يبقى trusted scope على `user_id`, `document_id`, `processing_run_id`, و`processing_profile` دون تغيير.
- لم تعدل K6 Hybrid Local أو Qdrant schema/indexing أو generation.
- Verification: focused regression suite = `90 passed`، وFull FastAPI suite = `218 passed`.
- المهمة التالية وفق `PROJECT_RAG_MASTER_PLAN.md` هي K7 — Local BGE reranker.

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
12. Livewire يبقى Presentation/Interaction layer؛ أي state متغيرة في الخلفية تقرأ من Laravel/MySQL عبر العقود الحالية وتحدث عبر targeted conditional polling فقط عند الحاجة، بينما Chat token output يستخدم streaming لا polling.

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
| I3 Documents list / cards / filters | DONE |
| I4 One-file upload + capability-aware Cloud/Hybrid Local choice | DONE |
| I5 Document details / processing timeline | DONE |
| I6 Accessibility / responsive / error states | DONE |

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
- بعد J8 تتحدث counters/status summaries والـrecent documents والـSidebar تلقائياً عند تغيّر الحالة في الخلفية عبر targeted Livewire polling فقط عندما تكون `poll_required` فعالة.

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

### I3 — Documents List / Cards / Filters

**الحالة:** `DONE` ومتحقق منها في PR #96 والمدمجة في `main`.

تم تنفيذ:

- تحويل `/documents` إلى صفحة إدارة وثائق فعلية داخل App Shell الحالي.
- استخدام `DocumentReadService` وH12 Presentation DTOs بدل query مباشر داخل Controller/Blade.
- دعم pagination والبحث وفلتر Document status وفلتر file type.
- تشغيل الفلاتر عبر query string والحفاظ على قيمها أثناء pagination.
- استخدام document cards responsive بدل جدول إداري ثقيل.
- عرض title/original filename/type/file size/status/timestamp.
- إعادة استخدام `documents.status-indicator` و`documents.actions-menu`.
- احترام `canDownload`, `canReprocess`, `canDelete` وعدم إعادة تعريف صلاحيات الإجراءات في Blade.
- إظهار reprocessing indicator عند الحاجة.
- عدم عرض raw `failure_reason` للمستخدم، واستخدام safe failure presentation.
- التمييز بين no documents empty state وfiltered no-results empty state.
- الحفاظ على user scoping في كل القراءة.
- eager loading للـactive/latest processing runs ومنع N+1.
- إضافة `hasAnyForUser()` إلى `DocumentReadService` لدعم empty-state distinction.
- إصلاح بناء user-scoped query باستخدام relation `getQuery()` بما يتوافق مع typed Builder المستخدم في filter/search methods.
- إصلاح عرض status filter بحيث يعرض القيم الفعلية مثل `pending`, `processing`, `ready`, `failed` بدل ظهور translation keys مثل `documents.availability.pending`.
- بعد J8 تتحدث document cards/statuses تلقائياً عندما تتغير processing state في الخلفية عبر targeted conditional Livewire polling، دون إعادة تفسير lifecycle داخل الواجهة ودون polling دائم.

Verification المثبت لـI3:

```text
PR #96 merged on GitHub: PASS
Feature commit:
- e5e6c0b119578eb1753f2072a50afb2ab8448fcd
Merge commit:
- 356a897b20dfee543e20791664baa2dec7b45038

DocumentPagesTest:
8 passed (21 assertions)

I1/I2 regression — AppShellTest + WorkspaceDashboardTest:
4 passed (36 assertions)

Laravel full regression:
149 passed (728 assertions)

Laravel Pint on I3 files:
PASS

Frontend:
npm run build — PASS

Manual browser verification:
PASS
- `/documents`
- cards
- search
- status filter
- file type filter
- empty states

FastAPI:
لم تُشغّل الاختبارات لأن I3 لم تغيّر FastAPI.
```

### I4 — One-file upload + capability-aware Cloud/Hybrid Local choice

**الحالة:** `DONE` ومتحقق منها في PR #97 والمدمجة في `main` بتاريخ 2026-09-04.

تم تنفيذ:

- إضافة نموذج رفع وثيقة واحدة فقط داخل صفحة `/documents` بصيغ PDF / DOCX / TXT.
- استهلاك `ProcessingCapabilityService` لقراءة `available_profiles` الفعلية وعرض خياري `cloud` و`hybrid_local` مع تعطيل الـProfile غير المتاحة.
- عدم تنفيذ أي automatic fallback من Profile يختارها المستخدم إلى Profile أخرى.
- إبقاء Server-side capability verification في `DocumentProcessingDispatcher` مصدر الحقيقة قبل إنشاء Initial ProcessingRun أو dispatch أي Queue job.
- fail-closed عند تعطل capability lookup أو فساد response؛ عند عدم توفر أي Profile تُعطل أدوات الرفع في الواجهة.
- معالجة آمنة لحالة race عندما تصبح الـProfile غير متاحة بين عرض الصفحة وتنفيذ POST؛ يعاد المستخدم إلى صفحة الوثائق برسالة localized دون إنشاء ProcessingRun أو Queue job بديلة.
- الحفاظ على redirect/flash contract المثبت في H13، بما في ذلك success/duplicate/user-safe error behavior.
- اعتماد ملفات ترجمة Documents في `lang/ar` و`lang/en` وإضافة رسائل `profile_unavailable` و`service_unavailable` الخاصة بالرفع.
- إضافة اختبارات I4 للـcapability-aware UI، تعطيل Profile غير المتاحة، fail-closed، عدم وجود fallback، one-file input، وحالة تغير الـCapability وقت التنفيذ.

Verification المثبت لـI4:

```text
PR #97 merged on GitHub: PASS
Merged at: 2026-09-04T06:12:53Z
Feature commit:
- 014a1a91bd9d84746c29d2c35fd04b5488dbb812
Merge commit:
- 0a0adad4ea9e3e71f64d7c6ae84321d6b95d1107

DocumentPagesTest:
11 passed (51 assertions)

DocumentUploadValidationTest:
8 passed (62 assertions)

ProcessingCapabilityServiceTest:
3 passed (6 assertions)

Laravel full regression:
153 passed (765 assertions)

Laravel Pint on I4 files:
PASS

FastAPI:
لم يتم تعديل FastAPI في I4.
```

### I5 — Document Details / Processing Timeline

**الحالة:** `DONE` ومتحقق منها في PR #98 والمدمجة في `main` بتاريخ 2026-09-04.

تم تنفيذ:

- إعادة بناء صفحة `/documents/{document}` باستخدام `DocumentReadService` وpresentation DTOs الحالية بدل الاعتماد المباشر على Eloquent داخل Blade.
- فصل `active_run` عن `latest_attempt` بوضوح وعرض كل منهما بصورة مستقلة.
- عرض processing timeline اعتمادًا على timestamps المسجلة فعليًا فقط: queued، processing، indexing، completed، failed.
- عرض نوع المحاولة `initial | reprocessing` مع pages/chunks وstage timings والتحذيرات الآمنة المتاحة من read contract.
- دعم reprocessing مع إبقاء النسخة السابقة الفعالة متاحة إلى أن تنجح المحاولة الجديدة.
- عند فشل آخر reprocessing مع وجود active indexed run سابقة صالحة، تبقى الوثيقة `Ready` وتظهر رسالة فشل آمنة بدل تحويل الوثيقة إلى `Failed`.
- عدم عرض raw `failure_reason` أو `qdrant_collection` أو `profile_snapshot` أو أي بيانات داخلية حساسة.
- احترام صلاحيات Download / Reprocess / Delete من العقود الحالية وعدم إعادة تعريف authorization داخل الواجهة.
- تعطيل/إخفاء Reprocess في الواجهة عندما تكون Processing Profile الفعالة غير متاحة ضمن Capabilities الحالية، مع بقاء التحقق Server-side مصدر الحقيقة.
- إضافة private browser preview على مسار محمي بالـauthorization نفسه للوصول إلى محتوى الوثيقة، مع إبقاء الملفات في private documents storage:
  - PDF → inline browser preview.
  - TXT → inline browser preview كـUTF-8 plain text.
  - DOCX → Download فقط بدون browser preview.
- بعد J8 تتحدث `document_availability`, `active_run`, `latest_attempt` والـtimeline تلقائياً أثناء processing/reprocessing عبر targeted Livewire polling، مع بقاء Safe Reprocessing invariant أعلى من latest attempt.

Verification المثبت لـI5:

```text
PR #98 merged on GitHub: PASS
Merged at: 2026-09-04T08:44:29Z
Feature commit:
- 94b4e24dbcdd77622909e190e16f16454ee4af42
Merge commit:
- 2844d0f5bba7371b56b2df11c2e489b68a3846ca

DocumentDetailsPageTest:
3 passed (17 assertions)

DocumentPagesTest:
11 passed (51 assertions)

Private preview/download tests:
6 passed (22 assertions)

H13 regression tests:
26 passed (148 assertions)

Documents feature suite:
97 passed (562 assertions)

Full Laravel suite:
160 passed (796 assertions)

Laravel Pint:
PASS

Frontend build:
PASS
```

الواجهة تعرض جاهزية الوثيقة ومحاولة المعالجة الأحدث كحقيقتين منفصلتين، وتحافظ على النسخة السابقة الفعالة أثناء reprocessing أو بعد فشل محاولة replacement، مع private preview للأنواع التي يدعمها المتصفح بأمان.

### I6 — Accessibility / Responsive / Error States

**الحالة:** `DONE` ومتحقق منها في PR #99 والمدمجة في `main` بتاريخ 2026-09-04.

تم تنفيذ:

- تحسين Accessibility لرسائل الحالة والتحقق من النماذج والإجراءات والنوافذ التأكيدية مع الحفاظ على واجهة RTL قابلة للاستخدام بوضوح.
- تحسين Responsive لواجهات Workspace وDocuments list وDocument details والإجراءات والنوافذ على الشاشات الصغيرة 320–375px والتابلت والديسكتوب، ومعالجة النصوص وأسماء الملفات الطويلة والـoverflow.
- تحسين عرض حالات النجاح والتحذير والخطأ برسائل localized وآمنة للمستخدم.
- منع كشف raw `failure_reason` أو أي تفاصيل داخلية حساسة في واجهات الوثائق.
- الحفاظ على الفصل بين Document status وجاهزية الوثيقة وبين ProcessingRun status وتقدم محاولة المعالجة، بما يشمل reprocessing مع بقاء النسخة الفعالة مستقلة عن أحدث محاولة.
- كانت I6 قد أضافت `DocumentPollingController` وpolling endpoint في Laravel مع JavaScript محدود؛ J8 أبقت Laravel/MySQL مصدر الحقيقة لكنها نقلت آلية الواجهة إلى Livewire targeted/conditional polling و`Livewire.navigate()` عند تغير snapshot.
- لا تنشئ Livewire أو JavaScript Business State Machine ولا تستنتج الانتقالات؛ `poll_required` أو presentation hint موثوقة مكافئة تحدد استمرار polling، ويتوقف عند stable/terminal states.
- لا تعتمد الواجهة الحالية على `data-document-poll-url`, `data-document-poll-error`, أو `window.location.reload()` كتصميم polling مستهدف.
- تحسين document details / workspace / documents list / actions / modals دون تغيير Domain أو H12/H13 contracts.
- الحفاظ على private preview/download behavior والـauthorization الحالية دون كشف Public Storage URL.

Verification المثبت لـI6:

```text
PR #99 merged on GitHub: PASS
Merged at: 2026-09-04T10:23:36Z
Feature commit:
- 3464ea1a91c1d62ecfd8bb1ce38fccaeafbcc723
Merge commit:
- a8aa3df94bbc4ee31336133cfef09f0ed263f640

Pint:
PASS

Vite production build:
PASS

Focused Documents + Workspace regression:
108 tests / 602 assertions PASS

Full Laravel suite:
168 tests / 823 assertions PASS
```

تبعيات المرحلة I الملزمة:

```text
I2 ← H12 dashboard summary + J8 targeted reactive polling
I3 ← H12 list/filter/read contract + J8 targeted reactive polling
I4 ← H12 capability availability + H13 Upload command
I5 ← H12 details/timeline + H13 Reprocess/Delete commands + J8 reactive state refresh
I6 ← H8/H9/H10 safe states + J8 Livewire polling/navigation architecture
```

## J — Conversations Database

| المهمة | الحالة |
|---|---|
| J1 Conversations migration / model | DONE |
| J2 conversation_document pivot | DONE |
| J3 Messages + snapshots / metrics | DONE |
| J4 message_sources + processing run / profile provenance | DONE |
| J5 Conversation policies | DONE |
| J6 Create / list conversations | DONE |
| J7 Multi-document selection | DONE |
| J8 Ready / indexed / runtime-capable document filtering | DONE |

> **J4 finalized in PR #103:** يعتمد `message_sources` على `processing_run_id` للوصول إلى الـProcessingRun ثم Document/Profile/Qdrant provenance دون تكرار حقول مشتقة. أصبح FK الخاص بالـProcessingRun يستخدم `cascadeOnDelete()` بحيث يحذف حذف الـRun مصادرها فقط وتبقى Message نفسها، وتم Consolidate أعمدة `kind` وstage timestamps داخل migration إنشاء ProcessingRun الأصلية مع حذف migration الإضافية القديمة وتحديث اختبارات schema/provenance.

> **J5 completed in PR #104:** أضيفت `ConversationPolicy` باستخدام Laravel Gate / Policy authorization؛ يسمح للمستخدم المسجل بـ`viewAny` و`create`، بينما تقصر `view` و`update` و`delete` على مالك المحادثة وفق `conversation.user_id === user.id`. لم تضف J5 Routes أو Controllers أو UI أو workflow جديد للمحادثات.

> **J6 completed in PR #105:** أضيف workflow لإنشاء Conversation وصفحة لعرض Conversations الخاصة بالمستخدم الحالي فقط؛ تستخدم القائمة `viewAny` والإنشاء `create` من `ConversationPolicy`، وتبقى القراءة والإنشاء user-scoped Server-side دون الثقة بـ`user_id` من client. بقي `title` nullable وأضيفت Conversations إلى App Shell / Sidebar، دون تنفيذ document selection أو Messages أو Retrieval أو Chat workflow.

> **J7 completed in PR #106:** أضيف workflow لإدارة اختيار وثيقة واحدة أو عدة وثائق للمحادثة، مع عرض وثائق المستخدم الحالي فقط، وتغيير الاختيار أو إزالته بالكامل عبر `Conversation::documents()->sync()`. تحمي `ConversationPolicy` المحادثة، ويتحقق Server-side من ملكية كل `document_id` ويرفض cross-user linking. `selected/attached` تعني اختيار المستخدم فقط ولا تعني runtime capability.

> **J8 completed in PR #107:** أضيف `ConversationRuntimeDocumentService` لفصل الوثائق المختارة عن الوثائق القابلة للاستخدام فعلياً، مع owner scoping وإعادة استخدام `DocumentAvailabilityResolver` والفشل المغلق عند active run مفقودة/فاسدة/cross-document. الوثيقة المختارة غير الجاهزة تبقى attached وتصبح runtime-capable تلقائياً عند جاهزيتها. كما أصبح selector Livewire reactive، واعتمدت واجهات Workspace/Documents/Sidebar/Details targeted conditional Livewire polling وLivewire navigation بدل reload polling القديم، مع الحفاظ على Safe Reprocessing.

كل Document جاهزة تشير إلى `active_processing_run_id`.

## K — Retrieval and Reranking

| المهمة | الحالة |
|---|---|
| K1 Trusted document_targets | DONE |
| K2 Cloud query embedding / retrieval | DONE |
| K3 Hybrid Local query embedding / retrieval | DONE |
| K4 Trusted User / Document / Run Retrieval Filters | DONE |
| K5 Per-profile Dense + BM25 + RRF | DONE |
| K6 Cloud Jina reranker | DONE |
| K7 Local BGE reranker | TODO |
| K8 Cross-profile rank fusion | TODO |
| K9 Metadata / source preservation | TODO |
| K10 Retrieval quality / security tests | TODO |

> **K1 completed in PR #108:** أضيف `TrustedDocumentTarget` typed/readonly و`ConversationDocumentTargetService` لتحويل ناتج J8 runtime-capable إلى targets موثوقة من active indexed ProcessingRun Server-side. يدعم المسار multi-document وmixed profiles ويحافظ على Safe Reprocessing ويفشل مغلقًا عند corrupted foreign pivot أو cross-document active run، ولا يعدل selection أو Document/ProcessingRun. لم تنفذ K1 query embedding أو Qdrant retrieval أو RRF أو reranking أو FastAPI query/retrieval endpoint أو generation/context/citations/streaming/Chat execution.

> **K2 completed in PR #109:** أضيف Cloud query embedding باستخدام Jina `retrieval.query` ببُعد `1024` ومسار dense retrieval عبر Qdrant مع Server-side collection resolution وnamed vector `dense_vector`. يرفض blank query قبل provider call، ويحافظ على Jina retry semantics، ويفشل مغلقًا عند malformed/wrong-dimension embeddings. يفرض retrieval دائماً `user_id/document_id/processing_run_id/processing_profile=cloud` ويتحقق دفاعياً من كل result، ولا يعيد raw vectors أو يسمح unfiltered retrieval أو fallback إلى Hybrid Local. المخرجات typed retrieval results فقط؛ لا LLM generation أو context/citations/Chat execution ضمن K2.

> **K3 completed in PR #110:** أضيف Hybrid Local query embedding باستخدام نفس `BAAI/bge-m3` ونفس Local embedding semantics المستخدمة لفهرسة الوثائق وبُعد `1024`، مع إعادة استخدام `LocalModelCoordinator` نفسه. يستخدم dense retrieval عبر `dense_vector` داخل Hybrid Local Qdrant collection المحلولة Server-side، ويقيد كل retrieval بالـtrusted scope `user_id/document_id/processing_run_id/processing_profile=hybrid_local` مع defensive scope validation. تستخدم النتائج `with_vectors=False` وتعود typed فقط، ويفشل المسار مغلقاً عند runtime/model/inference/scope failures دون Local → Cloud fallback. لم تدخل K3 في K4/K5 أو BM25/RRF/reranking/generation.

> **K4 completed in PR #111:** أضيفت `RetrievalScope` مشتركة لقيم `user_id/document_id/processing_run_id/processing_profile` الموثوقة، وأصبح نفس الـscope يبني exact Qdrant filter ويعيد التحقق دفاعياً من payload النتائج بعد retrieval لمساري Cloud وHybrid Local. بقيت profile enforcement والـcollection resolution Server-side، وبقي `DENSE_VECTOR_NAME`, `with_vectors=False`, وtyped results. لا يوجد unfiltered path أو cross-user/document/run/profile leakage، وأي mismatch يفشل Fail-Closed. لم تعدل K4 Document Processing أو Laravel ولم تنفذ K5/BM25/RRF/reranking/generation/citations.

> **K5 completed in PR #112:** أصبح كل Profile يستخدم Hybrid Retrieval داخلياً بصورة مستقلة. Cloud يجمع Jina dense query embedding مع `Qdrant/bm25` وmultilingual tokenizer، وHybrid Local يجمع BGE-M3 dense query embedding مع Local BM25 باستخدام نفس primitive الخاصة بفهرسة الوثائق. يستخدم المساران Dense/Sparse prefetch مع candidate expansion ثم Qdrant-native RRF، مع إعادة استخدام `RetrievalScope` نفسها على كل candidate path والـfinal fused query والتحقق الدفاعي Fail-Closed من النتائج. بقي `with_vectors=False` وserver-resolved collections والtyped results، ولا يوجد cross-profile fusion أو Local → Cloud fallback أو reranking ضمن K5، ولم تتغير Qdrant schema أو indexing semantics.

> **K6 completed in PR #113:** أضيف Jina reranking لمسار `cloud` باستخدام `jina-reranker-v2-base-multilingual` بعد Dense + BM25 + RRF، مع candidate expansion قبل reranking وfinal limit بعده. يعاد ترتيب نفس trusted candidates باستخدام result indexes العائدة من Jina دون إعادة بناء النتائج من محتوى المزود، وتفشل الاستجابات malformed والـduplicate/out-of-range indexes والـinvalid scores مغلقًا. أعيد استخدام Jina retry/error handling وبقي trusted scope على `user_id/document_id/processing_run_id/processing_profile` محفوظًا. لم يتغير Hybrid Local أو Qdrant schema/indexing أو generation. التحقق: focused regression `90 passed`، وFull FastAPI `218 passed`.

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
| M8 Pending / failure / retry + streamed answer rendering | TODO |
| M9 Mixed-profile / context / accessibility E2E | TODO |

العقد المعتمد داخل مهام M الحالية:

```text
Livewire
→ chat interaction/state/forms/navigation

Background non-token state
→ targeted polling where needed

LLM answer tokens
→ token-by-token streaming
→ NOT ordinary polling
```

لا توجد Task مستقلة للStreaming أو Livewire؛ تم دمج المتطلبات داخل المهام الحالية فقط، وتبقى M8 مسؤولة عن streamed answer rendering مع pending/failure/retry.

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

الـbaseline الحالي بعد J4:

```text
id
document_id
profile
status
kind default initial
started_at nullable
indexing_started_at nullable
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
failed_at nullable
created_at
updated_at
```

لا توجد حقول خاصة بالمقارنة أو الـtemporary artifacts.

## 8.2.1 H9 progress fields — consolidated in J4

كانت H9 قد أضافت الحقول التالية عبر migration إضافية أثناء التطوير:

```text
kind: initial | reprocessing
started_at nullable
indexing_started_at nullable
failed_at nullable
```

في J4، وبما أن قاعدة التطوير ستُبنى من الصفر، دُمجت هذه الحقول داخل migration الإنشاء الأصلية لـ`document_processing_runs` وحذفت migration الإضافية `2026_09_03_060000_add_progress_fields_to_document_processing_runs_table.php`. يبقى `created_at` ممثلًا لـ`queued_at` و`indexed_at` توقيت النجاح، ولا تستخدم الواجهة `updated_at` لتخمين المرحلة أو نوع المحاولة.

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

## 8.6 messages — J3

الـschema المثبت في PR #102:

```text
id
conversation_id
role
status default pending
content nullable
execution_snapshot nullable JSON
metrics nullable JSON
created_at
updated_at
```

العلاقات والحالات:

```text
Conversation -> hasMany(Message)
MessageRole: user | assistant
MessageStatus: pending | completed | failed
Conversation deletion -> cascade Messages
```

## 8.7 message_sources — J4 finalized provenance baseline

الـschema النهائي المثبت بعد PR #103:

```text
id
message_id
processing_run_id
qdrant_point_id
chunk_index
source_snapshot JSON
relevance_score nullable
created_at
updated_at
```

العقود المثبتة:

- `Message -> hasMany(MessageSource)`.
- `MessageSource -> belongsTo(Message)`.
- `MessageSource -> belongsTo(ProcessingRun)`.
- حذف Message يعمل cascade على MessageSources.
- حذف ProcessingRun يعمل cascade على MessageSources المرتبطة بتلك الـRun، بينما تبقى Message نفسها.
- لا تخزن `document_id`, `processing_profile`, أو `qdrant_collection` داخل `message_sources`؛ تستنتج عبر `ProcessingRun` الموثوقة.
- يوجد unique constraint على `message_id + qdrant_point_id`.
- `processing_run_id` هو provenance anchor المعتمد للوصول إلى Document/Profile/Qdrant provenance دون duplication داخل `message_sources`.

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

## 9.4 K2 Cloud Query Embedding / Dense Retrieval

الموجود بعد PR #109:

```text
CloudJinaQueryEmbedder
CloudRetrievalService
CloudRetrievalTarget
CloudRetrievalResult
QdrantCloudDenseRetriever
```

العقد:

- Query embedding يستخدم Jina `task = retrieval.query` وdimension = `1024`.
- blank query ترفض قبل provider call، مع إعادة استخدام retry semantics الحالية لـJina.
- provider result يجب أن تكون vector واحدة صحيحة ببُعد `1024` وإلا يفشل المسار Fail-Closed.
- Cloud collection تحدد Server-side عبر `resolve_qdrant_collection()`.
- Dense search يستخدم named vector `dense_vector`.
- كل Qdrant query مقيدة بـ`user_id`, `document_id`, `processing_run_id`, و`processing_profile = cloud`.
- كل result تخضع defensive scope validation بعد Qdrant.
- `with_vectors=False`؛ لا تعاد raw vectors.
- المخرجات typed retrieval results فقط.
- لا يوجد unfiltered retrieval أو fallback إلى Hybrid Local.
- BM25/RRF/reranking/cross-profile fusion/generation/context/citations/Chat execution ليست ضمن K2.

## 9.5 K3 Hybrid Local Query Embedding / Dense Retrieval

الموجود بعد PR #110:

```text
LocalBgeM3QueryEmbedder
HybridLocalRetrievalService
HybridLocalRetrievalTarget
HybridLocalRetrievalResult
QdrantHybridLocalDenseRetriever
```

العقد:

- Query embedding يستخدم نفس `BAAI/bge-m3` ونفس Local embedding semantics المستخدمة لفهرسة الوثائق وبُعد `1024`.
- يعاد استخدام `LocalModelCoordinator` نفسه دون lifecycle منفصل للـquery.
- Hybrid Local collection تحدد Server-side عبر `resolve_qdrant_collection()`.
- Dense search يستخدم named vector `dense_vector`.
- كل Qdrant query مقيدة بـ`user_id`, `document_id`, `processing_run_id`, و`processing_profile = hybrid_local`.
- كل result تخضع defensive scope validation بعد Qdrant.
- `with_vectors=False`؛ لا تعاد raw vectors.
- المخرجات typed retrieval results فقط.
- runtime/model/inference/scope failures تفشل Fail-Closed، ولا يوجد Local → Cloud fallback.
- K4/K5 وBM25/RRF/reranking/generation ليست ضمن K3.

## 9.6 K4 Trusted User / Document / Run Retrieval Filters

الموجود بعد PR #111:

```text
Trusted retrieval target
→ shared RetrievalScope
→ exact user/document/run/profile Qdrant filter
→ dense retrieval
→ defensive result scope validation
→ typed safe results
```

العقد:

- `RetrievalScope` abstraction مشتركة صغيرة تحتوي فقط على `user_id`, `document_id`, `processing_run_id`, و`processing_profile`.
- نفس الـscope هو المصدر الموحد لبناء exact Qdrant retrieval filter وللتحقق الدفاعي post-query من payload النتائج.
- Cloud retrieval يبقى مقيداً بـ`ProcessingProfile.CLOUD` وHybrid Local بـ`ProcessingProfile.HYBRID_LOCAL`.
- Qdrant collection تبقى محلولة Server-side عبر `resolve_qdrant_collection()`؛ Browser لا يختار Collection موثوقة.
- يبقى `DENSE_VECTOR_NAME`, `with_vectors=False`, وtyped retrieval results دون تغيير.
- لا يوجد unfiltered retrieval path ولا cross-user/document/run/profile leakage؛ أي result خارج trusted scope يفشل Fail-Closed.
- security boundary موحدة وقابلة لإعادة الاستخدام لمساري Cloud وHybrid Local.
- K4 لم تعدل Document Processing أو Laravel ولم تدخل في K5/BM25/RRF/reranking/generation/citations.

## 9.7 K5 Per-Profile Dense + BM25 + RRF Retrieval

الموجود بعد PR #112:

```text
Cloud:
Jina dense query embedding + Cloud BM25
→ Dense prefetch + Sparse/BM25 prefetch
→ Qdrant RRF

Hybrid Local:
BGE-M3 dense query embedding + Local BM25
→ Dense prefetch + Sparse/BM25 prefetch
→ Qdrant RRF
```

العقد:

- كل Processing Profile ينفذ Hybrid Retrieval داخلياً بصورة مستقلة؛ لا يوجد cross-profile fusion ضمن K5.
- Cloud sparse query تستخدم `CloudSparseRepresenter` نفسها بعقد `Qdrant/bm25` و`multilingual` tokenizer المستخدم للفهرسة.
- Hybrid Local sparse query تستخدم `LocalBm25Representer` نفسها ونفس local BM25 primitive المستخدمة لفهرسة الوثائق.
- يستخدم Qdrant-native prefetch للـ`dense_vector` والـ`bm25_sparse_vector` ثم `Fusion.RRF`.
- candidate expansion قبل fusion يستخدم `RRF_CANDIDATE_MULTIPLIER = 2`، بينما يبقى final `limit` مستقلاً.
- `RetrievalScope` من K4 هي المصدر الموحد للـexact filter على `user_id`, `document_id`, `processing_run_id`, و`processing_profile`.
- نفس trusted filter يطبق على Dense prefetch وSparse/BM25 prefetch والـfinal fused query.
- final fused results تخضع defensive post-result scope validation، وأي mismatch يفشل Fail-Closed.
- بقي `with_vectors=False`، واختيار Qdrant collection Server-side عبر `resolve_qdrant_collection()`، والنتائج typed.
- لا يوجد unfiltered retrieval أو cross-user/document/run/profile leakage.
- لا يوجد Local → Cloud fallback.
- لم تتغير Qdrant schema أو indexing semantics.
- لا يوجد reranking ضمن K5؛ K6 هي Cloud Jina Reranker.

## 9.8 K6 Cloud Jina Reranker

الموجود بعد PR #113:

```text
Cloud Dense + BM25 + RRF
→ expanded trusted candidate pool
→ Jina jina-reranker-v2-base-multilingual
→ map provider result indexes back to the same trusted candidates
→ final limit
```

العقد:

- reranking خاص بمسار `cloud` فقط ويعمل بعد Dense + BM25 + RRF.
- يستخدم `jina-reranker-v2-base-multilingual`.
- يوسع candidate pool قبل reranking ثم يطبق final limit بعد إعادة الترتيب.
- يستخدم `return_documents=false` ويعتمد على result indexes لإعادة ترتيب كائنات `CloudRetrievalResult` الأصلية الموثوقة، دون إعادة بناء النتائج من محتوى Jina.
- malformed responses أو duplicate indexes أو out-of-range indexes أو invalid/non-finite relevance scores تفشل Fail-Closed.
- يعاد استخدام retry/error handling الخاصة بمزود Jina.
- يبقى trusted scope `user_id`, `document_id`, `processing_run_id`, و`processing_profile` كما خرج من Retrieval؛ لا ينشئ reranker نطاقًا جديدًا أو Qdrant query إضافية.
- لم يتغير Hybrid Local أو Qdrant schema/indexing أو generation.
- Verification: focused regression suite `90 passed`، وFull FastAPI suite `218 passed`.

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

Frontend background state delivery:

```text
Laravel/MySQL source of truth
→ existing read/presentation contracts
→ targeted conditional Livewire polling
→ stop at stable/terminal state
```

Chat answer delivery:

```text
LLM answer tokens
→ token-by-token streaming
→ not ordinary polling
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

## المهمة السابقة المكتملة — I2 Workspace Dashboard

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
Feature branch:
- task/I2-workspace-dashboard
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

## المهمة السابقة المكتملة — I3 Documents List / Cards / Filters

**الحالة:** `DONE` ومتحقق منها في PR #96، والمدمجة في `main` عند merge commit `356a897b20dfee543e20791664baa2dec7b45038`.

تم تنفيذ:

- تحويل `/documents` إلى صفحة إدارة وثائق فعلية داخل App Shell الحالي.
- استخدام `DocumentReadService` وH12 Presentation DTOs بدل query مباشر داخل Controller/Blade.
- دعم pagination.
- إضافة البحث.
- إضافة فلتر Document status.
- إضافة فلتر file type.
- الفلاتر تعمل عبر query string وتحافظ على قيمها أثناء pagination.
- استخدام document cards responsive بدل جدول إداري ثقيل.
- عرض title/original filename/type/file size/status/timestamp.
- إعادة استخدام `documents.status-indicator` و`documents.actions-menu`.
- احترام `canDownload`, `canReprocess`, `canDelete`.
- إظهار reprocessing indicator عند الحاجة.
- عدم عرض raw `failure_reason` للمستخدم، واستخدام safe failure presentation.
- التمييز بين no documents empty state وfiltered no-results empty state.
- الحفاظ على user scoping.
- eager loading للـactive/latest processing runs ومنع N+1.
- إضافة `hasAnyForUser()` إلى `DocumentReadService` لدعم empty-state distinction.
- إصلاح بناء user-scoped query باستخدام relation `getQuery()` بما يتوافق مع typed Builder المستخدم في filter/search methods.
- إصلاح عرض status filter بحيث يعرض القيم الفعلية مثل `pending`, `processing`, `ready`, `failed` بدل ظهور translation keys مثل `documents.availability.pending`.

### Verification I3

```text
PR #96 merged on GitHub: PASS
Feature commit:
- e5e6c0b119578eb1753f2072a50afb2ab8448fcd
Merge commit:
- 356a897b20dfee543e20791664baa2dec7b45038

DocumentPagesTest:
8 passed (21 assertions)

I1/I2 regression — AppShellTest + WorkspaceDashboardTest:
4 passed (36 assertions)

Laravel full regression:
149 passed (728 assertions)

Laravel Pint on I3 files:
PASS

Frontend:
npm run build — PASS

Manual browser verification:
PASS
- `/documents`
- cards
- search
- status filter
- file type filter
- empty states

FastAPI:
لم تُشغّل الاختبارات لأن I3 لم تغيّر FastAPI.
```

## المهمة السابقة المكتملة — I4 One-file upload + capability-aware Cloud/Hybrid Local choice

**الحالة:** `DONE` ومتحقق منها في PR #97، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `0a0adad4ea9e3e71f64d7c6ae84321d6b95d1107`.

تم تنفيذ:

- رفع وثيقة واحدة فقط بصيغ PDF / DOCX / TXT ضمن صفحة Documents الحالية.
- اختيار `cloud` أو `hybrid_local` حسب `available_profiles` الفعلية، مع تعطيل الـProfile غير المتاحة.
- لا يوجد automatic fallback؛ اختيار المستخدم لا يتحول تلقائيًا إلى Profile أخرى.
- Server-side capability verification تبقى مصدر الحقيقة النهائي قبل إنشاء ProcessingRun أو dispatch أي Queue job.
- fail-closed عند تعطل capability lookup أو فساد response، مع تعطيل الرفع عندما لا توجد أي Profile متاحة.
- إذا أصبحت الـProfile غير متاحة بين عرض الصفحة وتنفيذ الرفع، يفشل الطلب بأمان ويعود برسالة localized دون إنشاء Run أو Job بديلة.
- الحفاظ على redirect/flash contract المثبت في H13.
- اعتماد ملفات الترجمة في `lang/ar` و`lang/en` وإضافة رسائل الرفع الجديدة.
- إضافة اختبارات I4 اللازمة للـcapability-aware UI، one-file upload، fail-closed، unavailable profile، وعدم وجود fallback.

### Verification I4

```text
PR #97 merged on GitHub: PASS
Merged at: 2026-09-04T06:12:53Z
Feature commit:
- 014a1a91bd9d84746c29d2c35fd04b5488dbb812
Merge commit:
- 0a0adad4ea9e3e71f64d7c6ae84321d6b95d1107

DocumentPagesTest:
11 passed (51 assertions)

DocumentUploadValidationTest:
8 passed (62 assertions)

ProcessingCapabilityServiceTest:
3 passed (6 assertions)

Laravel full regression:
153 passed (765 assertions)

Laravel Pint on I4 files:
PASS

FastAPI:
لم يتم تعديل FastAPI في I4.
```

## المهمة السابقة المكتملة — I5 Document Details / Processing Timeline

**الحالة:** `DONE` ومتحقق منها في PR #98، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `2844d0f5bba7371b56b2df11c2e489b68a3846ca`.

تم تنفيذ:

- بناء صفحة تفاصيل الوثيقة باستخدام `DocumentReadService` وpresentation/read contracts الحالية بدل تمرير Eloquent مباشرة إلى Blade.
- الفصل الصريح بين `active_run` و`latest_attempt` وإظهار جاهزية الوثيقة وتقدم أحدث محاولة بصورة مستقلة.
- عرض processing timeline من timestamps المسجلة فعليًا فقط، مع pages/chunks/stage timings والتحذيرات الآمنة.
- دعم reprocessing مع بقاء النسخة السابقة فعالة أثناء المحاولة الجديدة، وبقاء الوثيقة `Ready` إذا فشلت آخر إعادة معالجة مع وجود active indexed run سابقة صالحة.
- عدم عرض raw `failure_reason` أو بيانات Qdrant/profile الداخلية الحساسة، والاكتفاء برسائل فشل آمنة للمستخدم.
- احترام صلاحيات Download/Reprocess/Delete من العقود الحالية.
- منع Reprocess في الواجهة عندما تكون الـProcessing Profile الفعالة غير متاحة ضمن Capabilities الحالية، مع بقاء التحقق Server-side مصدر الحقيقة.
- إضافة private browser preview محمي على private documents storage: PDF وTXT يدعمان inline preview، بينما DOCX يبقى Download فقط.

### Verification I5

```text
PR #98 merged on GitHub: PASS
Merged at: 2026-09-04T08:44:29Z
Feature commit:
- 94b4e24dbcdd77622909e190e16f16454ee4af42
Merge commit:
- 2844d0f5bba7371b56b2df11c2e489b68a3846ca

DocumentDetailsPageTest:
3 passed (17 assertions)

DocumentPagesTest:
11 passed (51 assertions)

Private preview/download tests:
6 passed (22 assertions)

H13 regression tests:
26 passed (148 assertions)

Documents feature suite:
97 passed (562 assertions)

Full Laravel suite:
160 passed (796 assertions)

Laravel Pint:
PASS

Frontend build:
PASS
```

## المهمة السابقة المكتملة — I6 Accessibility / Responsive / Error States

**الحالة:** `DONE` ومتحقق منها في PR #99، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `a8aa3df94bbc4ee31336133cfef09f0ed263f640`.

تم تنفيذ:

- تحسين Accessibility لرسائل الحالة والتحقق من النماذج والإجراءات والنوافذ التأكيدية.
- تحسين Responsive للشاشات الصغيرة 320–375px والتابلت والديسكتوب، مع معالجة النصوص وأسماء الملفات الطويلة والـoverflow.
- تحسين حالات النجاح والتحذير والخطأ برسائل localized وآمنة للمستخدم.
- منع عرض raw `failure_reason` أو تفاصيل داخلية حساسة.
- الحفاظ على الفصل بين Document status وProcessingRun status وعدم دمجهما في حالة UI واحدة.
- إضافة polling endpoint في Laravel لطبقة العرض، مع اعتماد `poll_required` Server-side لتحديد استمرار التحديث وإيقافه عند terminal states.
- عدم إنشاء Business State Machine في JavaScript أو تخمين انتقالات الحالة محليًا.
- تحسين document details / workspace / documents list / actions / modals ضمن العقود الحالية.
- الحفاظ على private preview/download behavior والـauthorization الحالية.

### Verification I6

```text
PR #99 merged on GitHub: PASS
Merged at: 2026-09-04T10:23:36Z
Feature commit:
- 3464ea1a91c1d62ecfd8bb1ce38fccaeafbcc723
Merge commit:
- a8aa3df94bbc4ee31336133cfef09f0ed263f640

Pint:
PASS

Vite production build:
PASS

Focused Documents + Workspace regression:
108 tests / 602 assertions PASS

Full Laravel suite:
168 tests / 823 assertions PASS
```

> **J8 frontend reactivity update:** أبقت J8 حقيقة أن I6 أسست polling server-side لكنها استبدلت مسار الواجهة القديم بـLivewire components وtargeted conditional polling. لم يعد `window.location.reload()` جزءاً من التصميم المستهدف، وتعمل Workspace/Documents/Sidebar/Document Details بصورة reactive مع إيقاف polling عند الاستقرار.

## المهمة السابقة المكتملة — J1 Conversations migration / model

**الحالة:** `DONE` ومتحقق منها في PR #100، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `e84467a971e42b88ce88349e8d3a39c1f70a861f`.

تم تنفيذ:

- إضافة جدول `conversations` في Laravel/MySQL.
- كل Conversation مملوكة لمستخدم عبر `user_id` مع FK يستخدم `restrictOnDelete()` بما يتوافق مع نمط المشروع الحالي.
- إضافة `title` nullable. الحقل يسمح لاحقًا بتوليد اسم افتراضي للمحادثة من محتواها على نمط تجربة ChatGPT، كما يسمح بإضافة إعادة تسمية من المستخدم لاحقًا؛ لم يُنفذ أي من هذين السلوكين في J1.
- إضافة `Conversation` Eloquent model.
- إضافة `Conversation -> belongsTo(User)` و`User -> hasMany(Conversation)`.

حدود J1:

- لا `conversation_document` pivot.
- لا Messages أو Message sources.
- لا Conversation policies.
- لا Conversation create/list UI أو controllers.
- لا document selection.
- لا RAG / retrieval.
- لا FastAPI أو Qdrant.
- لا streaming.
- لا conversation memory.

### Verification J1

```text
PR #100 merged on GitHub: PASS
Feature commit:
- bdcd0c6c6cbbd39344e068d9d556b611cb58a450
Merge commit:
- e84467a971e42b88ce88349e8d3a39c1f70a861f

Migration:
PASS

Migration rollback + re-run:
PASS

Laravel Pint:
PASS

Focused J1 tests:
2 passed (6 assertions)

Full Laravel suite:
170 passed (829 assertions)
```

## المهمة السابقة المكتملة — J2 conversation_document pivot

**الحالة:** `DONE` ومتحقق منها في PR #101، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `7c5ca49a04908f6cdf4db5897b8dd369e32b1f4a`.

تم تنفيذ:

- إضافة جدول pivot باسم `conversation_document` في Laravel/MySQL.
- أصبحت العلاقة Many-to-Many بين `Conversation` و`Document`.
- إضافة علاقة Eloquent `Conversation -> documents()` وعلاقة `Document -> conversations()`.
- منع duplicate pair لنفس `conversation_id` و`document_id` على مستوى قاعدة البيانات عبر unique constraint.
- استخدام Foreign Keys المناسبة إلى `conversations` و`documents` مع `cascadeOnDelete()`، بما يتوافق مع كون سجلات الـpivot علاقة تابعة وليست Business Entity مستقلة.
- عدم إضافة Pivot Model مستقل لأن الجدول يمثل العلاقة فقط ولا يحتوي business state.
- Laravel/MySQL هما Source of Truth لهذه العلاقة.

حدود J2:

- لا document selection flow.
- لا authorization خاص باختيار الوثائق.
- لا UI.
- لا RAG / Retrieval / FastAPI / Qdrant.
- لم يُحل cross-user document selection عبر schema معقد أو database trigger؛ يجب منعه لاحقًا في application layer عند تنفيذ document selection flow.

### Verification J2

```text
PR #101 merged on GitHub: PASS
Feature commit:
- 42e373d48a0ff8cec5a0da41e42fb5e159551eff
Merge commit:
- 7c5ca49a04908f6cdf4db5897b8dd369e32b1f4a

Focused J2 tests:
PASS

Full Laravel suite:
PASS
```

لم تُسجل أرقام الاختبارات في PR #101 أو commit المنشور، لذلك لم تُضف أعداد غير موثقة.

## المهمة السابقة المكتملة — J3 Messages + snapshots / metrics

**الحالة:** `DONE` ومتحقق منها في PR #102، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `428989ac4289e451d698c683acfea344f8ef59e9`.

تم تنفيذ:

- إنشاء جدول `messages`.
- إضافة `Message` model وربط `Conversation -> Messages`.
- إضافة `MessageRole` بقيمتي `user` و`assistant`.
- إضافة `MessageStatus` بالحالات `pending`, `completed`, `failed`.
- دعم تخزين `content`, `execution_snapshot`, و`metrics` مع casts مناسبة.
- السماح للرسالة `pending` أن توجد بدون `content` حتى يكتمل مسار السؤال لاحقًا.
- تأسيس `message_sources` مبكرًا ضمن J3 لتثبيت schema المحادثات والمصادر قبل J4، بدون إضافة Retrieval/RAG logic.
- إضافة `MessageSource` model وربط `Message -> MessageSources` و`MessageSource -> ProcessingRun`.
- تخزين source provenance عبر `processing_run_id`, `qdrant_point_id`, `chunk_index`, `source_snapshot`, و`relevance_score`.
- عدم تكرار `document_id`, `processing_profile`, أو `qdrant_collection` في `message_sources` لأنها تستنتج عبر `ProcessingRun`.
- اعتماد cascade من Conversation إلى Messages ومن Message إلى MessageSources.
- اعتماد `restrictOnDelete()` على `processing_run_id` لمنع حذف ProcessingRun ما دامت مشارًا إليها من MessageSource.
- إضافة unique constraint على `message_id + qdrant_point_id`.
- إضافة اختبارات لعقود schema والعلاقات والـcasts والـprovenance والـcascade/restrict behavior.

حدود J3:

- لا تعتبر J4 مكتملة رغم تأسيس جزء كبير من قاعدة بياناتها مبكرًا.
- لا Retrieval أو RAG أو Qdrant query logic.
- لا Conversation policies أو create/list UI أو document-selection flow.
- أي تنفيذ لاحق لـJ4 يجب أن يراجع migration/model الموجودين أولًا ويتجنب إنشاء `message_sources` schema مكرر.

### Verification J3

```text
PR #102 merged on GitHub: PASS
Feature commit:
- 8a40bb6c88ab0397ed10befdd4c35f359172565b
Merge commit:
- 428989ac4289e451d698c683acfea344f8ef59e9

Focused J3 tests:
6 passed (24 assertions)

Laravel full suite:
180 passed (864 assertions)

Migration rollback + re-run:
PASS

Laravel Pint:
PASS
```

## المهمة السابقة المكتملة — J4 message_sources + processing run / profile provenance

**الحالة:** `DONE` ومتحقق من دمجها في PR #103، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `cd74de40c10d56fed93c8bc80489747bd056bd16`.

تم تنفيذ:

- استكمال أساس provenance لـ`message_sources` بالاعتماد على `MessageSource.processing_run_id -> ProcessingRun`.
- عدم إضافة `document_id` أو `processing_profile` أو `qdrant_collection` إلى `message_sources`؛ تستنتج هذه البيانات عبر الـProcessingRun الموثوقة.
- تغيير FK الخاصة بـ`message_sources.processing_run_id` من `restrictOnDelete()` إلى `cascadeOnDelete()`.
- عند حذف ProcessingRun تحذف MessageSources المرتبطة بها، بينما تبقى Message نفسها.
- دمج `kind`, `started_at`, `indexing_started_at`, و`failed_at` داخل migration الإنشاء الأصلية لـ`document_processing_runs`.
- حذف migration الإضافية القديمة `2026_09_03_060000_add_progress_fields_to_document_processing_runs_table.php` لأن baseline التطوير يبنى من الصفر.
- تحديث `MessagePersistenceTest` لإثبات cascade من ProcessingRun إلى MessageSources مع بقاء Message.
- تحديث `ProcessingRunSchemaTest` ليتحقق من default/casts ضمن الـbaseline النهائي وإزالة backfill test القديم المرتبط بالـmigration المحذوفة.

### Verification J4

```text
PR #103 merged on GitHub: PASS
Feature commit:
- 3ec50ec782e24c727b8bac8f3ad3ef23600f8bdd
Merge commit:
- cd74de40c10d56fed93c8bc80489747bd056bd16

GitHub PR diff / schema-provenance test audit:
PASS

لم تُنشر أرقام tests في PR #103 أو مناقشته، لذلك لم تُسجل أعداد غير موثقة.
```

## المهمة السابقة المكتملة — J5 Conversation policies

**الحالة:** `DONE` ومتحقق من دمجها في PR #104، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `61332b33af8828fefa7c6cc2673561bf9e996393`.

تم تنفيذ:

- إضافة `ConversationPolicy`.
- السماح للمستخدم المسجل بـ`viewAny` و`create`.
- قصر `view` و`update` و`delete` على مالك المحادثة فقط.
- معيار الملكية: `conversation.user_id === user.id`.
- منع مستخدم آخر من إدارة Conversation لا يملكها.
- استخدام Laravel Gate / Policy authorization.
- لم تتم إضافة Routes أو Controllers أو UI أو workflow جديد للمحادثات ضمن J5.
- الملفات المضافة في J5:
  - `laravel-app/app/Policies/ConversationPolicy.php`
  - `laravel-app/tests/Feature/Policies/ConversationPolicyTest.php`

### Verification J5

```text
PR #104 merged on GitHub: PASS
Feature branch:
- task/J5-conversation-policies
Feature commit:
- 3656cc74600b2bfae2452b06ebaa62a8f4355415
Merge commit:
- 61332b33af8828fefa7c6cc2673561bf9e996393

Focused tests:
3 passed (8 assertions)

Laravel Pint:
PASS

Full Laravel suite:
183 passed (869 assertions)
```

## المهمة السابقة المكتملة — J6 Create / list conversations

**الحالة:** `DONE` ومتحقق من دمجها في PR #105، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `eeb5d4d7f1fd6f5ca4fb58ccd42b34dc09855235`.

تم تنفيذ:

- إضافة workflow لإنشاء Conversation جديدة.
- إضافة صفحة لعرض Conversations الخاصة بالمستخدم الحالي فقط.
- استخدام `ConversationPolicy` الموجودة من J5 عبر `viewAny` لعرض القائمة و`create` لإنشاء Conversation.
- حصر Query القائمة بالمستخدم authenticated عبر `user()->conversations()` لمنع تسريب Conversations بين المستخدمين.
- إنشاء Conversation عبر علاقة المستخدم Server-side.
- عدم الوثوق بـ`user_id` القادم من client؛ حتى عند إرساله لا يمكن تزوير ownership.
- بقاء `title` اختيارية / nullable وفق schema الحالية، مع حد أقصى 255 حرفًا.
- إضافة Conversations إلى App Shell / Sidebar.
- عدم تنفيذ اختيار Documents أو Messages أو Retrieval أو Chat workflow ضمن J6.

### Verification J6

```text
PR #105 merged on GitHub: PASS
Feature branch:
- task/J6-create-list-conversations
Feature commit:
- 7b8790d0bbc090fd4c5eb09262a4ed02fa1ef5bb
Merge commit:
- eeb5d4d7f1fd6f5ca4fb58ccd42b34dc09855235

Focused J6 tests:
5 passed (19 assertions)

Full Laravel suite:
188 passed (888 assertions)

Laravel Pint:
PASS — 164 files
```

## المهمة السابقة المكتملة — J7 Multi-document selection

**الحالة:** `DONE` ومتحقق من دمجها في PR #106، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `c519fe270a6383e687e203a360bb7963f97a0df3`.

تم تنفيذ:

- إضافة صفحة لإدارة وثائق Conversation يملكها المستخدم.
- عرض Documents الخاصة بالمستخدم الحالي فقط.
- دعم اختيار وثيقة واحدة أو عدة وثائق وربطها بالمحادثة.
- دعم تغيير الاختيار لاحقًا، وإزالة وثيقة، أو إلغاء تحديد جميع الوثائق.
- استخدام علاقة `Conversation::documents()` الحالية و`sync()` لمزامنة الاختيار ومنع duplicate pivot rows.
- حماية Conversation عبر `ConversationPolicy`.
- التحقق Server-side من أن كل `document_id` المرسل يعود للمستخدم الحالي.
- رفض محاولة ربط وثيقة مملوكة لمستخدم آخر.
- حماية owner / other user / guest ضمن اختبارات الـworkflow.

حدود J7:

- لا Ready / Indexed / runtime-capable filtering.
- لا Retrieval.
- لا FastAPI.
- لا Qdrant retrieval.
- لا LLM answer generation.
- لا Messages / Chat execution.
- تبقى هذه الجاهزية التشغيلية مؤجلة صراحةً إلى J8 وما بعدها.

### Verification J7

```text
PR #106 merged on GitHub: PASS
Feature branch:
- task/J7-multi-document-selection
Feature commit:
- 6ac9a0406968d3b26db5a8a04ba141a17d286aec
Merge commit:
- c519fe270a6383e687e203a360bb7963f97a0df3

Focused J7 tests:
18 passed (73 assertions)

Full Laravel suite:
194 passed (923 assertions)

Laravel Pint:
PASS
```

## المهمة السابقة المكتملة — J8 Ready / indexed / runtime-capable document filtering

**الحالة:** `DONE` ومتحقق من دمجها في PR #107، والمدمجة في `main` بتاريخ 2026-09-04 عند merge commit `5d4804fec694b26a102802965c28720f0664580e`.

تم تنفيذ:

- إضافة `ConversationRuntimeDocumentService` كطبقة Application/Domain-facing مستقلة تفصل معنى `selected/attached` عن معنى `runtime-capable`.
- قراءة Documents المختارة من علاقة المحادثة، مع حصرها بالمستخدم authenticated وإعادة تحميل `activeProcessingRun` اللازمة للتحقق.
- إعادة استخدام `DocumentAvailabilityResolver` كمصدر الحقيقة لقرار الجاهزية، وعدم إنشاء منطق Ready/Indexed موازٍ داخل Conversation UI أو service.
- fail-closed عند active processing run مفقودة أو فاسدة أو لا تعود لنفس Document؛ لا تصبح الوثيقة runtime-capable في هذه الحالات.
- الوثيقة المختارة غير الجاهزة تبقى مرتبطة بالمحادثة ولا تحذف من `conversation_document`؛ تستبعد فقط من runtime-capable set.
- عند اكتمال فهرسة الوثيقة لاحقاً تصبح runtime-capable تلقائياً دون detach/reattach.
- الحفاظ على Safe Reprocessing: active indexed Run A تبقي الوثيقة `Ready` وصالحة runtime أثناء Run B الجديدة pending/processing/indexing، كما يبقى A فعالاً إذا فشلت B.
- تحويل Conversation document selector إلى Livewire component مع immediate user-driven selection state، مع server-side validation/ownership قبل `sync()`.
- إضافة targeted Livewire polling لاختيار الوثائق عندما توجد Documents ذات `poll_required` لتظهر readiness الجديدة تلقائياً.
- إضافة `DocumentStatePoller` و`DocumentUiSnapshotService` لتحديث Workspace/Documents library/Document details من Laravel/MySQL وPresentation Layer الحالية دون Browser business logic.
- إضافة `SidebarDocuments` كـLivewire component مستقل لتحديث قائمة الوثائق في Sidebar دون جعل الصفحة كاملة poll بلا حاجة.
- إزالة مسار JavaScript القديم الذي كان ينفذ fetch دوري ثم `window.location.reload()` عند تغير snapshot.
- استخدام `Livewire.navigate()` عند تغير document-state fingerprint مع حفظ/استعادة scroll، واستخدام Livewire navigation في الواجهة حيث يلزم.
- لا Browser polling مباشر إلى FastAPI أو Qdrant، ولا ثقة بـprocessing_run_id أو qdrant_collection قادمة من Browser.
- لم تنفذ J8 Retrieval أو FastAPI query أو Qdrant retrieval أو LLM answer generation أو AskConversationJob.

### Verification J8

```text
PR #107 merged on GitHub: PASS
Feature branch:
- task/J8-runtime-capable-document-filtering
Feature commit:
- fee362ea9dd031d53e0516ec800ed0ab0b01df29
Merge commit:
- 5d4804fec694b26a102802965c28720f0664580e

Focused J8 + reactive tests:
14 passed (35 assertions)

DocumentDetailsPage regression:
5 passed (23 assertions)

Laravel Pint:
PASS

Frontend production build:
PASS

Full Laravel suite:
207 passed (956 assertions)
```

## المهمة السابقة المكتملة — K1 Trusted document_targets

**الحالة:** `DONE` ومتحقق من دمجها في PR #108، والمدمجة في `main` بتاريخ 2026-09-05 عند merge commit `3fc41d4490c46012d314f4a58bf446e3e1946fad`.

تم تنفيذ:

- إضافة `TrustedDocumentTarget` كـ`final readonly` typed DTO يحمل فقط `documentId`, `processingRunId`, و`processingProfile`.
- إضافة `ConversationDocumentTargetService` وإعادة استخدام `ConversationRuntimeDocumentService` من J8 بدل تكرار منطق runtime readiness.
- أخذ `processingRunId` و`processingProfile` من `activeProcessingRun` الموثوقة Server-side، وعدم الثقة بأي processing/runtime identifiers قادمة من Browser.
- دعم multi-document وmixed profiles: `cloud` و`hybrid_local`.
- الحفاظ على Safe Reprocessing: active indexed Run القديمة تبقى target المستخدمة إذا كانت replacement run الأحدث Processing أو Failed.
- corrupted foreign-document pivot لا يسرّب target.
- cross-document active run لا ينتج target.
- الوثائق selected لكنها غير جاهزة تبقى selected ولا تنتج runtime target.
- target resolution لا يعدل `conversation_document` أو `Document` أو `ProcessingRun`.

حدود K1:

- لا query embeddings.
- لا Qdrant retrieval.
- لا RRF.
- لا reranking.
- لا FastAPI query/retrieval endpoint.
- لا LLM generation.
- لا context building.
- لا citations.
- لا streaming.
- لا Chat execution.

### Verification K1

```text
PR #108 merged on GitHub: PASS
Feature branch:
- task/K1-trusted-document-targets
Feature commit:
- 28759b3fd2df80283fd8d3fba831aaadee569433
Merge commit:
- 3fc41d4490c46012d314f4a58bf446e3e1946fad

K1 focused tests:
10 passed (22 assertions)

K1 + J8 regression:
18 passed (43 assertions)

Laravel full suite:
217 passed (978 assertions)

Laravel Pint:
PASS
```

## المهمة السابقة المكتملة — K2 Cloud Query Embedding / Retrieval

**الحالة:** `DONE` ومتحقق من دمجها في PR #109، والمدمجة في `main` بتاريخ 2026-09-05 عند merge commit `d512feb1100c7d1fa791340bcfb9eac92b3cbb84`.

تم تنفيذ:

- إضافة `CloudJinaQueryEmbedder` لتحويل سؤال المستخدم إلى embedding باستخدام Jina `task = retrieval.query` وdimension = `1024`.
- رفض blank query قبل استدعاء provider.
- الحفاظ على retry semantics الحالية لـJina، مع تحويل provider failure النهائي إلى structured `ApplicationException`.
- Fail-Closed عند malformed provider result أو عدد vectors غير صحيح أو dimension غير `1024` أو قيم vector غير صالحة.
- إضافة `CloudRetrievalService` كطبقة application orchestration للمسار Cloud.
- إضافة `CloudRetrievalTarget` و`CloudRetrievalResult` كـtyped dataclasses.
- استخدام `resolve_qdrant_collection()` لتحديد Cloud collection Server-side من الـProcessing Profile الموثوقة.
- إضافة `QdrantCloudDenseRetriever` واستخدام named vector `dense_vector`.
- فرض Qdrant filter دائماً على:
  - `user_id`
  - `document_id`
  - `processing_run_id`
  - `processing_profile = cloud`
- إجراء defensive validation بعد Qdrant للتأكد أن كل result ينتمي لنفس trusted scope قبل تحويله إلى typed result.
- عدم إرجاع raw vectors عبر `with_vectors=False`.
- عدم وجود unfiltered retrieval أو fallback إلى Hybrid Local.
- إرجاع typed retrieval results فقط؛ لا يتم توليد جواب LLM ضمن K2.

حدود K2 / Out of Scope:

- K3 Hybrid Local query embedding / retrieval.
- generalized K4 filtering.
- BM25 / RRF.
- reranking.
- cross-profile fusion.
- LLM generation.
- context building.
- citations.
- Chat execution.
- streaming.
- UI / Livewire.
- Laravel Chat orchestration.
- migrations.

### Verification K2

```text
PR #109 merged on GitHub: PASS
PR title:
- feat(K2): add cloud query retrieval
Feature branch:
- task/K2-cloud-query-retrieval
Feature commit:
- 608b50955616c96f898c51f5080ceb98beb503d2
Merge commit:
- d512feb1100c7d1fa791340bcfb9eac92b3cbb84

K2 focused tests:
16 passed

FastAPI full suite:
173 passed

Flaky regression note:
- ظهر اختبار قديم غير متعلق بـK2 مرة واحدة كـflaky network/redirect failure.
- مر الاختبار نفسه منفرداً 5/5 مرات.
- بعدها مر FastAPI full suite كاملاً: 173 passed.
- لا يصنف ذلك كـK2 defect.
```

## المهمة السابقة المكتملة — K3 Hybrid Local Query Embedding / Retrieval

**الحالة:** `DONE` ومتحقق من دمجها في PR #110، والمدمجة في `main` بتاريخ 2026-09-05 عند merge commit `c2e488a8d45e07b86bb6d25f01cb78a9f70edf8b`.

تم تنفيذ:

- إضافة `LocalBgeM3QueryEmbedder` لتحويل سؤال المستخدم إلى Hybrid Local query embedding باستخدام نفس `BAAI/bge-m3` المستخدم لفهرسة الوثائق.
- query vector ببُعد `1024` وبنفس Local embedding semantics المستخدمة في document indexing.
- إعادة استخدام `LocalModelCoordinator` نفسه؛ لا يوجد model lifecycle منفصل للـquery.
- إضافة `HybridLocalRetrievalService` وtyped `HybridLocalRetrievalTarget` / `HybridLocalRetrievalResult`.
- استخدام `resolve_qdrant_collection()` لتحديد Hybrid Local Qdrant collection Server-side.
- إضافة `QdrantHybridLocalDenseRetriever` واستخدام named vector `dense_vector`.
- فرض trusted scope دائماً على:
  - `user_id`
  - `document_id`
  - `processing_run_id`
  - `processing_profile = hybrid_local`
- إجراء defensive scope validation بعد Qdrant للتأكد أن كل result ينتمي للنطاق الموثوق قبل تحويله إلى typed result.
- عدم إرجاع raw vectors عبر `with_vectors=False`.
- إرجاع typed retrieval results فقط.
- Fail-Closed عند runtime/model/inference/scope failures.
- عدم وجود Local → Cloud fallback.

حدود K3 / Out of Scope:

- K4 user / document / run filters.
- K5 Per-profile Dense + BM25 + RRF.
- BM25 / RRF.
- reranking.
- generation.

### Verification K3

```text
PR #110 merged on GitHub: PASS
PR title:
- feat(K3): add hybrid local query retrieval
Feature branch:
- task/K3-hybrid-local-query-retrieval
Feature commit:
- 21ea644202f03d0430682d4e37c4464d4205b8fa
Merge commit:
- c2e488a8d45e07b86bb6d25f01cb78a9f70edf8b

K3 focused/regression tests:
44 passed

FastAPI full suite:
192 passed
```

## المهمة السابقة المكتملة — K4 Trusted User / Document / Run Retrieval Filters

**الحالة:** `DONE` ومتحقق من دمجها في PR #111، والمدمجة في `main` بتاريخ 2026-09-05 عند merge commit `3c7be519fac934de2ce1f9264be202623c69f757`.

تم تنفيذ:

- إضافة abstraction مشتركة صغيرة باسم `RetrievalScope` تحمل فقط:
  - `user_id`
  - `document_id`
  - `processing_run_id`
  - `processing_profile`
- توحيد trusted retrieval scope لمساري Cloud وHybrid Local دون إلغاء استقلال خدمات كل Profile.
- استخدام نفس `RetrievalScope` كمصدر موحد لـexact Qdrant retrieval filter وللتحقق الدفاعي post-query من payload النتائج.
- بقاء Cloud retrieval مقيداً بـ`ProcessingProfile.CLOUD`.
- بقاء Hybrid Local retrieval مقيداً بـ`ProcessingProfile.HYBRID_LOCAL`.
- بقاء اختيار Qdrant collection Server-side عبر `resolve_qdrant_collection()`.
- بقاء `DENSE_VECTOR_NAME`, `with_vectors=False`, وtyped retrieval results.
- عدم وجود unfiltered retrieval path.
- منع cross-user وcross-document وcross-run وcross-profile leakage عبر exact scope filter + defensive validation.
- أي Qdrant result لا يطابق trusted scope يفشل Fail-Closed ولا يتحول إلى typed result آمن.
- توحيد security boundary لتصبح قابلة لإعادة الاستخدام لمساري Cloud وHybrid Local.
- عدم تعديل Document Processing.
- عدم تعديل Laravel.

حدود K4 / Out of Scope:

- K5 Per-profile Dense + BM25 + RRF.
- BM25 query retrieval.
- sparse query generation.
- Dense + BM25 fusion.
- RRF.
- reranking.
- Jina reranker.
- BGE reranker.
- cross-profile fusion.
- generation.
- citations.

### Verification K4

```text
PR #111 merged on GitHub: PASS
PR title:
- refactor(K4): centralize trusted retrieval filters
Feature branch:
- task/K4-user-document-run-filters
Feature commit:
- a69fd712c61e73b4100d95be542a7a4942bef9b0
Merge commit:
- 3c7be519fac934de2ce1f9264be202623c69f757

Focused / K2-K3 regression:
35 passed in 2.29s

Full FastAPI suite:
192 passed in 10.52s
```

## المهمة السابقة المكتملة — K5 Per-Profile Dense + BM25 + RRF Retrieval

**الحالة:** `DONE` ومتحقق من دمجها في PR #112، والمدمجة في `main` بتاريخ 2026-09-05 عند merge commit `cc5dcc4f951ffac00e07db592a1d32091bf9a602`.

تم تنفيذ:

- أصبح Cloud retrieval يجمع Jina dense query embedding مع Cloud BM25 query representation باستخدام `Qdrant/bm25` و`multilingual` tokenizer.
- أصبح Hybrid Local retrieval يجمع BGE-M3 dense query embedding مع Local BM25 query representation باستخدام نفس primitive المستخدمة في فهرسة الوثائق.
- يستخدم كل Profile مساراً مستقلاً من Dense + BM25 ثم Qdrant-native RRF؛ لا يوجد cross-profile fusion ضمن K5.
- يستخدم المساران Dense prefetch وSparse/BM25 prefetch على named vectors الحالية ثم `Fusion.RRF`.
- يوجد candidate expansion قبل الـfusion عبر `RRF_CANDIDATE_MULTIPLIER = 2` مع final limit مستقل.
- بقيت `RetrievalScope` الخاصة بـK4 المصدر الموحد للـexact trusted filters على `user_id`, `document_id`, `processing_run_id`, و`processing_profile`.
- exact trusted filter يطبق على Dense candidate retrieval وSparse/BM25 candidate retrieval والـfinal fused query.
- final fused results تخضع defensive scope validation نفسها وتفشل Fail-Closed عند أي mismatch.
- بقي `with_vectors=False`، واختيار Qdrant collection Server-side، والtyped retrieval results دون تغيير.
- لا يوجد unfiltered retrieval path ولا cross-user/document/run/profile leakage.
- لا يوجد Local → Cloud fallback.
- لم تتغير Qdrant schema أو indexing semantics.

حدود K5 / Out of Scope:

- K6 Cloud Jina reranker.
- K7 Local BGE reranker.
- K8 cross-profile rank fusion.
- generation.
- context building.
- citations.
- Chat execution.

### Verification K5

```text
PR #112 merged on GitHub: PASS
PR title:
- feat(K5): add per-profile dense BM25 RRF retrieval
Feature branch:
- task/K5-per-profile-dense-bm25-rrf
Feature commit:
- 45470543615ab6c9d2e5edf7d7ee2c055af0c721
Merge commit:
- cc5dcc4f951ffac00e07db592a1d32091bf9a602
```

## آخر مهمة مكتملة — K6 Cloud Jina Reranker

**الحالة:** `DONE` ومتحقق من دمجها في PR #113، والمدمجة في `main` بتاريخ 2026-09-05 عند merge commit `9b2301875077c1a6a0dc1999a7ed3101bd49eaa7`.

تم تنفيذ:

- إضافة Jina reranking لمسار `cloud` باستخدام `jina-reranker-v2-base-multilingual`.
- تنفيذ reranking بعد Dense + BM25 + RRF.
- توسيع candidate pool قبل reranking ثم تطبيق final limit بعد إعادة الترتيب.
- استخدام `return_documents=false` وإعادة ترتيب نفس trusted candidates بالاعتماد على provider result indexes دون إعادة بناء النتائج من محتوى Jina.
- التحقق Fail-Closed من malformed responses والـduplicate indexes والـout-of-range indexes والـinvalid/non-finite relevance scores.
- إعادة استخدام retry/error handling الخاصة بمزود Jina.
- الحفاظ على trusted scope:
  - `user_id`
  - `document_id`
  - `processing_run_id`
  - `processing_profile`
- عدم تعديل Hybrid Local أو Qdrant schema/indexing أو generation.

### Verification K6

```text
PR #113 merged on GitHub: PASS
PR title:
- feat(K6): add cloud Jina reranker
Feature branch:
- task/K6-cloud-jina-reranker
Feature commit:
- a2fd18f6535f99099e69a7ed764158136334da9c
Merge commit:
- 9b2301875077c1a6a0dc1999a7ed3101bd49eaa7

Focused regression suite:
90 passed

Full FastAPI suite:
218 passed
```

## المهمة الحالية/التالية

```text
K7 — Local BGE reranker
```

Baseline المهمة التالية:

```text
اكتملت K6 ودمجت في main عبر PR #113. أصبح مسار cloud يطبق Jina reranking باستخدام jina-reranker-v2-base-multilingual بعد Dense + BM25 + RRF.
يتم توسيع candidate pool قبل reranking ثم تطبيق final limit بعده، وتستخدم result indexes لإعادة ترتيب نفس trusted candidates دون إعادة بناء النتائج من محتوى Jina.
تخضع استجابة المزود للتحقق Fail-Closed ضد malformed responses والـduplicate/out-of-range indexes والـinvalid scores، مع إعادة استخدام Jina retry/error handling.
يبقى trusted scope على user_id/document_id/processing_run_id/processing_profile محفوظاً، ولا توجد تغييرات على Hybrid Local أو Qdrant schema/indexing أو generation.
يحدد PROJECT_RAG_MASTER_PLAN.md أن المهمة التالية حرفياً هي K7 — Local BGE reranker.
تبقى K وما بعدها دون تغيير من حيث الترقيم، ولا يوجد Compare/Winner/temporary artifact lifecycle في المعمارية المستهدفة.
```

---

# 12. التتبع التاريخي

التصاميم والمهام التي أزيلت من الخريطة النشطة لا تحفظ هنا كمهام ملغاة.

للتدقيق التاريخي يرجع إلى Git / Pull Requests، وبشكل خاص الـbaseline التاريخي السابق (وليس حالة `main` الحالية):

```text
main@a1f28097b398b9bb277f85990a55e489bd54d880
```

هذا يحافظ على التاريخ بدون تلويث خريطة التنفيذ الحالية بمهام لم تعد جزءاً من النظام المستهدف.
