# 🧹 حل مشكلة تضخم الكاش + ضبط الكرون — Cache & Cron Guide

> هذا المستند يحل مشكلة تضخم مجلدي `cache` و `sessions` على Hostinger،
> ويعطيك خطوات ما بعد رفع الملفات بالترتيب.

---

## 🔍 لماذا يتضخم الكاش ولا يُنظَّف تلقائياً؟ (السبب الحقيقي)

المشروع **مُجهَّز أصلاً** بنظام تنظيف ذكي:
- سكريبت تنظيف آمن: `scripts/hostinger-cache-cleanup.sh` (يحذف الملفات القديمة فقط حسب العمر).
- جدولة Laravel في `routes/console.php` (queue، تنظيف، إيميلات...).

**لكن كل هذا لا يعمل لسبب واحد:**
> ❌ **لا يوجد Cron أساسي على Hostinger يشغّل `php artisan schedule:run`.**

بدون هذا الـ Cron الواحد، **كل** المهام المجدولة لا تعمل (لا التنظيف، ولا إرسال الإيميلات المجدولة). لذلك:
- الكاش يتراكم بلا حذف.
- مجلد sessions يكبر.
- الإيميلات المجدولة (queue:work) لا تُرسل.

**الحل = إضافة Cron واحد أساسي + Cron للتنظيف.** (الخطوات بالأسفل.)

---

## ✅ أولاً: مراجعة ملف `.env` (مهم جداً)

### ❌ مشاكل موجودة حالياً في `.env`:

1. **`QUEUE_CONNECTION` مُكرَّر مرتين:**
   ```env
   QUEUE_CONNECTION=sync        ← السطر الأول
   ...
   QUEUE_CONNECTION=database    ← السطر الأخير (هو الفعّال)
   ```
   القيمة الفعلية = `database`. **يجب حذف أحد السطرين.**

2. **البريد على Gmail** (قد يُحجب على الاستضافة المشتركة).

### ✔️ القرار حسب حالتك:

#### الخيار (أ) — موصى به: استخدام الكرون (الإيميلات + التنظيف يعملان تلقائياً)
احذف سطر `QUEUE_CONNECTION=sync` وأبقِ:
```env
QUEUE_CONNECTION=database
```
> لأن `routes/console.php` يشغّل `queue:work --queue=emails` كل دقيقة عبر الجدولة.
> هذا يتطلب إضافة Cron الأساسي (الخطوة 1 بالأسفل).

#### الخيار (ب) — الأبسط: بدون كرون للإيميلات
احذف سطر `QUEUE_CONNECTION=database` وأبقِ:
```env
QUEUE_CONNECTION=sync
```
> الإيميلات تُرسل فوراً مباشرة. لكن ستظل تحتاج Cron للتنظيف فقط (الخطوة 2).

### ✔️ إعداد البريد الموصى به (بريد الدومين بدل Gmail):
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=admin@orsozox.com
MAIL_PASSWORD=<كلمة مرور بريد admin@orsozox.com>
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=admin@orsozox.com
MAIL_FROM_NAME="منتديات أرثوذكس"
```

### ✔️ بقية المتغيرات الحالية صحيحة:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://orsozox.com/forums   ✅
CACHE_STORE=file                     ✅ (مع تنظيف الكرون)
SESSION_DRIVER=file                  ✅ (مع تنظيف الكرون)
```
> بديل اختياري لتقليل الملفات: `CACHE_STORE=database` و `SESSION_DRIVER=database`
> (يخزّن الكاش/الجلسات في قاعدة البيانات بدل الملفات — لا تتضخم على القرص).
> لكن مع الكرون، إبقاؤها `file` جيد وأسرع.

---

## 🚀 ثانياً: خطوات ما بعد رفع الملفات (بالترتيب)

### الخطوة 0 — صلاحيات السكريبت (مرة واحدة)
عبر Terminal في Hostinger (أو File Manager):
```bash
cd ~/domains/orsozox.com/public_html/forums    # عدّل المسار لمسارك الحقيقي
chmod +x scripts/hostinger-cache-cleanup.sh
```
> لمعرفة مسارك الحقيقي اكتب: `pwd` داخل مجلد المشروع.

---

### الخطوة 1 — إضافة Cron الأساسي (يشغّل جدولة Laravel)
من **hPanel → Advanced → Cron Jobs → Add New**:

- **Command to run:**
```
cd /home/USERNAME/domains/orsozox.com/public_html/forums && php artisan schedule:run >> /dev/null 2>&1
```
- **الوقت:** كل دقيقة → `* * * * *`

> ⚠️ استبدل `USERNAME` والمسار بمسارك الحقيقي (من أمر `pwd`).
> هذا الكرون الواحد يشغّل **كل** المهام المجدولة (الإيميلات + views:flush + غيرها).

---

### الخطوة 2 — إضافة Cron تنظيف الكاش (الأهم لمشكلتك)
أضف Cron Job ثانٍ:

- **Command to run:**
```
/bin/bash /home/USERNAME/domains/orsozox.com/public_html/forums/scripts/hostinger-cache-cleanup.sh >> /home/USERNAME/domains/orsozox.com/public_html/forums/storage/logs/cache-cleanup.log 2>&1
```
- **الوقت:** كل ساعة → `0 * * * *`

> هذا يحذف ملفات الكاش الأقدم من 6 ساعات، والجلسات الأقدم من يوم، والـ views الأقدم من يوم، والـ logs الأقدم من أسبوع — **بأمان** (لا يحذف ملفات حديثة).

---

### الخطوة 3 — تنظيف فوري لمرة واحدة (لتفريغ المتراكم الآن)
عبر Terminal:
```bash
cd ~/domains/orsozox.com/public_html/forums
bash scripts/hostinger-cache-cleanup.sh
```
أو إن لم يتوفر SSH، شغّل الأمر مرة واحدة عبر Cron ثم احذفه.

---

### الخطوة 4 — إعادة بناء كاش الإعدادات (بعد تعديل .env)
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```
> مهم بعد أي تعديل في `.env` أو `config/`.

---

## 🎛️ ضبط حساسية التنظيف (اختياري)
السكريبت يقبل متغيرات بيئة للتحكم في عمر الحذف (بالدقائق):
```bash
CACHE_MAX_AGE_MINUTES=180   \
SESSION_MAX_AGE_MINUTES=720 \
bash scripts/hostinger-cache-cleanup.sh
```
| المتغير | الافتراضي | المعنى |
|---------|-----------|--------|
| `CACHE_MAX_AGE_MINUTES` | 360 (6 ساعات) | حذف كاش أقدم من |
| `SESSION_MAX_AGE_MINUTES` | 1440 (يوم) | حذف جلسات أقدم من |
| `VIEW_MAX_AGE_MINUTES` | 1440 (يوم) | حذف views أقدم من |
| `LOG_MAX_AGE_MINUTES` | 10080 (أسبوع) | حذف logs أقدم من |

> لو الكاش يتضخم بسرعة كبيرة، قلّل `CACHE_MAX_AGE_MINUTES` إلى 120 أو 60.

---

## ✅ كيف أتأكد أن الكرون يعمل؟
بعد ساعة من الإضافة، افحص ملف اللوج:
```bash
cat storage/logs/cache-cleanup.log
```
يجب أن ترى أسطراً مثل:
```
[2026-06-11 11:00:01] cleanup started ...
[2026-06-11 11:00:01] cleaned file cache: 1240 file(s) ...
[2026-06-11 11:00:01] cleanup finished
```
ولفحص جدولة Laravel:
```bash
php artisan schedule:list
```

---

## 📌 ملخص سريع (Checklist)
- [ ] صحّح `.env`: احذف `QUEUE_CONNECTION` المكرر (اترك واحداً فقط).
- [ ] (موصى به) غيّر البريد إلى `smtp.hostinger.com` / 465 / ssl.
- [ ] `chmod +x scripts/hostinger-cache-cleanup.sh`.
- [ ] أضف Cron أساسي: `* * * * * ... php artisan schedule:run`.
- [ ] أضف Cron تنظيف: `0 * * * * ... hostinger-cache-cleanup.sh`.
- [ ] شغّل تنظيفاً فورياً مرة واحدة.
- [ ] أعد بناء الكاش (`config:cache` ...).
- [ ] بعد ساعة، تأكد من `cache-cleanup.log`.

> بعد هذه الخطوات: لن تحتاج للدخول يدوياً لحذف الكاش مرة أخرى. ✅
