@extends('layouts.app')

@section('title', $seoData['title'])
@section('description', $seoData['description'])
@section('canonical', $seoData['url'])
@section('og_title', $seoData['title'])
@section('og_url', $seoData['url'])
@section('og_description', $seoData['description'])

@section('schema')
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebPage",
            "name": "سياسة الخصوصية",
            "description": "نحن نحترم خصوصيتك ونسعى لحماية بياناتك الشخصية في المنتدى.",
            "url": "{{ $seoData['url'] }}",
            "publisher": {
                "@type": "Organization",
                "name": "{{ config('app.name', 'المنتدى') }}"
            }
        }
        </script>
@endsection

@section('content')
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-4 fw-bold text-accent mb-3"><i class="fas fa-shield-alt me-2"></i> سياسة الخصوصية</h1>
                <p class="lead text-muted">نحن نأخذ خصوصية زوارنا وأعضائنا على محمل الجد وملتزمون بحمايتها</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="glass-panel p-5 rounded-4 shadow-sm mb-4">
                    <h3 class="h4 fw-bold mb-3 text-primary"><i class="fas fa-user-secret me-2"></i> جمع المعلومات</h3>
                    <p class="text-muted fs-5 lh-lg mb-4">
                        نقوم بجمع بعض المعلومات الأساسية عند زيارتك للموقع، مثل عنوان الـ IP، نوع المتصفح، والصفحات التي قمت
                        بزيارتها. عند التسجيل في المنتدى، نطلب منك تقديم عنوان بريد إلكتروني واسم مستخدم للتمكن من التفاعل
                        في الموقع. نحن لا نقوم ببيع أو تأجير أو مشاركة هذه المعلومات مع أي طرف ثالث لأغراض تسويقية.
                    </p>

                    <h3 class="h4 fw-bold mb-3 text-primary"><i class="fas fa-cookie-bite me-2"></i> استخدام ملفات تعريف
                        الارتباط (Cookies)</h3>
                    <p class="text-muted fs-5 lh-lg mb-4">
                        يستخدم المنتدى ملفات تعريف الارتباط لتحسين تجربة المستخدم، مثل حفظ بيانات تسجيل الدخول وتفضيلات
                        العرض (كالوضع الثلجي/العادي). يمكنك تعطيل ملفات تعريف الارتباط من متصفحك، ولكن قد يؤثر ذلك على بعض
                        وظائف الموقع.
                    </p>

                    <h3 class="h4 fw-bold mb-3 text-primary"><i class="fas fa-lock me-2"></i> حماية البيانات</h3>
                    <p class="text-muted fs-5 lh-lg mb-4">
                        نحن نستخدم إجراءات أمنية متقدمة، بما في ذلك تشفير كلمات المرور (MD5 مملح ومطابق للأنظمة القديمة
                        والحديثة) وبروتوكول HTTPS المشفر، لحماية معلوماتك من الوصول غير المصرح به. ومع ذلك، نذكرك بأن لا
                        تشارك أي معلومات حساسة جداً كنوافذ المحادثة للعامة.
                    </p>

                    <h3 class="h4 fw-bold mb-3 text-primary"><i class="fas fa-envelope-open-text me-2"></i> التواصل البريدي
                    </h3>
                    <p class="text-muted fs-5 lh-lg mb-0">
                        قد نستخدم بريدك الإلكتروني لإرسال تنبيهات حول المواضيع التي اشتركت بها أو رسائل إدارية هامة. يمكنك
                        في أي وقت تعديل تفضيلات التواصل من خلال لوحة تحكم حسابك.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection