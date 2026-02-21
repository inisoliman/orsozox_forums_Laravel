@extends('layouts.app')

@section('title', $seoData['title'])
@section('description', $seoData['description'])
@section('canonical', $seoData['url'])
@section('og_title', $seoData['title'])
@section('og_url', $seoData['url'])
@section('og_description', $seoData['description'])

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaEditorialPolicy() !!}
@endsection

@section('content')
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-4 fw-bold text-accent mb-3"><i class="fas fa-file-signature me-2"></i> سياسة التحرير
                    والمراجعة</h1>
                <p class="lead text-muted">منهجيتنا في نشر المحتوى وضمان موثوقيته وتوافقه مع معايير الثقة (E-E-A-T)</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="glass-panel p-5 rounded-4 shadow-sm mb-4">
                    <h2 class="h3 fw-bold mb-4 border-bottom pb-2">1. المرجعية الدينية (Religious Reference)</h2>
                    <div class="content-text fs-5 lh-lg mb-4">
                        <p>
                            نحن نلتزم بأن يكون كل موضوع يُنشر في المنتدى مستنداً إلى مراجع دينية موثوقة ومثبتة. يمنع منعاً
                            باتاً نشر الفتاوى غير المسندة، أو اجتزاء النصوص من سياقها بهدف التضليل.
                        </p>
                    </div>

                    <h2 class="h3 fw-bold mb-4 border-bottom pb-2">2. عملية مراجعة المحتوى (Content Review Process)</h2>
                    <div class="content-text fs-5 lh-lg mb-4">
                        <p>
                            يمتلك المنتدى مجلساً إشرافياً وإدارياً مستقلاً. تخضع المواضيع ذات الأهمية القصوى والحساسة
                            لمراجعة دقيقة لتدقيق المعلومات (Fact-Checking) قبل أن تعتبر موثوقة.
                            <br>
                            المواضيع التي أتمت مرحلة التدقيق العلمي، تظهر فيها علامة توثق اسم الخادم/المشرف المراجع وتاريخ
                            المراجعة.
                        </p>
                    </div>

                    <h2 class="h3 fw-bold mb-4 border-bottom pb-2">3. سياسة التصحيح (Correction Policy)</h2>
                    <div class="content-text fs-5 lh-lg mb-4">
                        <p>
                            نحن نؤمن بأن الصواب هو غايتنا. إذا وجد أي قارئ أو باحث أن هناك محتوى ديني أو علمي يحتاج لتصويب،
                            فإنه يمكنه فوراً التبليغ عن المشاركة، وسيقوم فريق التحرير بمراجعتها وتعديلها مع وضع ملاحظة "تم
                            التعديل لتصحيح خطأ مطبعي/تاريخي".
                        </p>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <a href="{{ route('page.about') }}"
                        class="btn btn-outline-primary btn-lg px-5 rounded-pill shadow-sm">عودة لصفحة من نحن</a>
                </div>
            </div>
        </div>
    </div>
@endsection