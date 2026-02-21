@extends('layouts.app')

@section('title', $seoData['title'])
@section('description', $seoData['description'])
@section('canonical', $seoData['url'])
@section('og_title', $seoData['title'])
@section('og_url', $seoData['url'])
@section('og_description', $seoData['description'])

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaAboutPage() !!}
@endsection

@section('content')
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-4 fw-bold text-accent mb-3"><i class="fas fa-landmark me-2"></i> من نحن</h1>
                <p class="lead text-muted">رؤيتنا، هويتنا، وموثوقيتنا (E-E-A-T)</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="glass-panel p-5 rounded-4 shadow-sm mb-4">
                    <h2 class="h3 fw-bold mb-4 border-bottom pb-2">عن {{ config('app.name') }}</h2>
                    <div class="content-text fs-5 lh-lg">
                        <p>
                            تأسس هذا الكيان في عام <strong>2005</strong>، ليكون منارة تعليمية ودينية موثوقة. نحن نحرص بشدة
                            على تقديم محتوى يخضع لأعلى معايير الدقة والمراجعة.
                        </p>
                        <p>
                            هدفنا الرئيسي (Mission) هو إثراء الوعي، نشر التعاليم المعتمدة، وتوفير مرجعية موثوقة بعيدة عن
                            الشائعات، ليكون المنتدى صرحاً يجمع الباحثين عن المعرفة الأصيلة.
                        </p>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="glass-panel p-4 rounded-4 h-100 border-top border-accent border-3">
                            <h3 class="h4 fw-bold mb-3"><i class="fas fa-scale-balanced me-2 text-primary"></i> هويتنا
                                الدينية</h3>
                            <p class="text-muted lh-lg">
                                ككيان مؤسسي مسؤول، نستند في محتوانا إلى المراجع المعتمدة، ونلتزم بالمنهجية العلمية والدينية
                                لحماية القارئ من المحتوى المضلل. جميع الأقسام والمنتديات تُدار وفق هذه الهوية.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-panel p-4 rounded-4 h-100 border-top border-success border-3">
                            <h3 class="h4 fw-bold mb-3"><i class="fas fa-shield-halved me-2 text-success"></i> المسؤولية
                                التحريرية</h3>
                            <p class="text-muted lh-lg">
                                نلتزم أمام الله ثم أمام قرائنا بصحة المعلومات المنشورة. هناك فريق مخصص (Editorial Team) يقوم
                                بالمراجعة الدورية للمواضيع وتحديثها للحفاظ على الوزن العلمي والموثوقية (Trust).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <a href="{{ route('page.contact') }}" class="btn btn-primary btn-lg px-5 rounded-pill shadow-sm"><i
                            class="fas fa-envelope me-2"></i> تواصل معنا</a>
                </div>
            </div>
        </div>
    </div>
@endsection