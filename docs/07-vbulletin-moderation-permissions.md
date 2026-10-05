# صلاحيات الإشراف المتوافقة مع vBulletin 3.8

## مصدر الخريطة

تم استخراج خريطة الـ bitfields من سجل `title=bitfields` في:

```text
docs/datastore.csv
```

وتخص الحقول التالية في جدول `moderator`:

```text
permissions  -> moderatorpermissions
permissions2 -> moderatorpermissions2
```

## نطاق المشرف

جدول `moderator` يستخدم:

```text
forumid = -1  مشرف عام
forumid = رقم القسم  مشرف داخل هذا القسم
```

وجود سجل في `moderator` مع `permissions=0` و`permissions2=0` لا يمنح أي عملية إشرافية.

## moderator.permissions

| الاسم | القيمة |
|---|---:|
| `caneditposts` | 1 |
| `candeleteposts` | 2 |
| `canopenclose` | 4 |
| `caneditthreads` | 8 |
| `canmanagethreads` | 16 |
| `canannounce` | 32 |
| `canmoderateposts` | 64 |
| `canmoderateattachments` | 128 |
| `canmassmove` | 256 |
| `canmassprune` | 512 |
| `canviewips` | 1024 |
| `canviewprofile` | 2048 |
| `canbanusers` | 4096 |
| `canunbanusers` | 8192 |
| `newthreademail` | 16384 |
| `newpostemail` | 32768 |
| `cansetpassword` | 65536 |
| `canremoveposts` | 131072 |
| `caneditsigs` | 262144 |
| `caneditavatar` | 524288 |
| `caneditpoll` | 1048576 |
| `caneditprofilepic` | 2097152 |
| `caneditreputation` | 4194304 |

## moderator.permissions2

| الاسم | القيمة |
|---|---:|
| `caneditvisitormessages` | 1 |
| `candeletevisitormessages` | 2 |
| `canremovevisitormessages` | 4 |
| `canmoderatevisitormessages` | 8 |
| `caneditalbumpicture` | 16 |
| `candeletealbumpicture` | 32 |
| `caneditsocialgroups` | 64 |
| `candeletesocialgroups` | 128 |
| `caneditgroupmessages` | 256 |
| `candeletegroupmessages` | 512 |
| `canremovegroupmessages` | 1024 |
| `canmoderategroupmessages` | 2048 |
| `canmoderatepicturecomments` | 4096 |
| `candeletepicturecomments` | 8192 |
| `canremovepicturecomments` | 16384 |
| `caneditpicturecomments` | 32768 |
| `canmoderatepictures` | 65536 |
| `caneditdiscussions` | 131072 |
| `candeletediscussions` | 262144 |
| `canremovediscussions` | 524288 |
| `canmoderatediscussions` | 1048576 |
| `cantransfersocialgroups` | 2097152 |

## سياسة المشروع

- مجموعة `6` (`Administrators`) تتجاوز قيود bitmask وتملك كل أدوات الإشراف.
- مجموعتا `5` (`Super Moderators`) و`7` (`Moderators`) لا تتجاوزان `moderator.permissions` و`permissions2` ونطاق القسم.
- المستخدم العادي لا يملك أدوات الإشراف.
- `candeleteposts` يستخدم للحذف البسيط، مع `visible=2` وسجل `deletionlog`.
- `canremoveposts` يدل على قدرة vBulletin على الإزالة، لكن الحذف الفعلي النهائي مقيد في هذا المشروع بالأدمن فقط.
- `canmassmove` يصرح بالنقل الجماعي.
- `canmassprune` يصرح بعمليات الحذف الجماعية، ولا يتجاوز شرط الأدمن للحذف الفعلي.
- `canmanagethreads` يصرح بإدارة المواضيع والدمج حسب نطاق المشرف.
- `canopenclose` يصرح بفتح وإغلاق الموضوع.
- `canmoderateposts` يصرح بمراجعة الردود.
- `canmoderatevisitormessages` يصرح بمراجعة رسائل الزوار.

## الحذف والحالات

```text
visible = 0  منتظر المراجعة / غير منشور
visible = 1  منشور
visible = 2  محذوف حذفاً بسيطاً
```

حذف الموضوع حذفاً بسيطاً يغير `thread.visible` فقط ويحافظ على حالات الردود كما هي. حذف الرد يغير `post.visible` لذلك الرد فقط. الاستعادة لا تعيد إحياء ردود حُذفت مستقلاً.

## Cloudflare Auto-Minify وBlade

عند تفعيل Cloudflare Auto-Minify، قد تُدمج أسطر JavaScript المضمنة. لذلك:

- يمنع استخدام تعليقات `//` داخل `<script>` في Blade.
- تستخدم تعليقات الكتل `/* ... */` فقط.
- النتيجة السابقة لاستخدام `//` كانت `Unexpected end of input` وعدم تنفيذ `submit`، فكان النموذج يعيد تحميل الصفحة.
- النماذج التي تعتمد على JavaScript للتحقق أو الإرسال تستخدم `novalidate` عندما تحتوي حقولاً مخفية أو محررات `display:none`.

## السجلات

- `deletionlog` مفتاحه المركب `(primaryid,type)`.
- `moderatorlog` يسجل النقل والدمج والحذف والاستعادة والتثبيت والإغلاق والعمليات الجماعية.
- كل عملية متعددة الصفوف يجب أن تكون داخل transaction وتعيد بناء عدادات القسم والموضوع.
