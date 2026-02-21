@extends('layouts.app')

@section('title', $seoData['title'])
@section('description', $seoData['description'])
@section('canonical', $seoData['url'])
@section('og_title', $seoData['title'])
@section('og_url', $seoData['url'])
@section('og_description', $seoData['description'])

@section('schema')
    {!! \App\Helpers\SeoHelper::schemaContactPage() !!}
@endsection

@section('content')
    <div class="container py-5">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-4 fw-bold text-accent mb-3"><i class="fas fa-paper-plane me-2"></i> اتصل بنا</h1>
                <p class="lead text-muted">نحن هنا للإجابة على جميع استفساراتك واقتراحاتك</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="glass-panel p-5 rounded-4 shadow-sm mb-4">
                    <div class="row text-center mb-5">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <i class="fas fa-envelope fs-1 text-primary mb-3"></i>
                            <h3 class="h5 fw-bold">البريد الإلكتروني</h3>
                            <p class="text-muted" dir="ltr">orsozox@gmail.com</p>
                        </div>
                        <div class="col-md-6">
                            <i class="fas fa-clock fs-1 text-accent mb-3"></i>
                            <h3 class="h5 fw-bold">أوقات العمل</h3>
                            <p class="text-muted">متاحون على مدار الساعة لخدمتكم</p>
                        </div>
                    </div>

                    <hr class="mb-5">

                    <!-- Contact Form -->
                    <h3 class="h4 fw-bold mb-4 border-bottom pb-2">نموذج المراسلة الحر</h3>

                    @if(session('success'))
                        <div class="alert alert-success d-flex align-items-center mb-4">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('page.contact.submit') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="name" class="form-label fs-5">الاسم أو اللقب</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="form-control form-control-lg bg-dark text-light border-secondary" id="name"
                                placeholder="أدخل اسمك الكريم" required>
                        </div>
                        <div class="mb-4">
                            <label for="email" class="form-label fs-5">البريد الإلكتروني</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="form-control form-control-lg bg-dark text-light border-secondary" id="email"
                                placeholder="أدخل بريدك الإلكتروني لنتواصل معك" required>
                        </div>
                        <div class="mb-4">
                            <label for="message" class="form-label fs-5">الرسالة أو الاستفسار</label>
                            <textarea name="message"
                                class="form-control form-control-lg bg-dark text-light border-secondary" id="message"
                                rows="5" placeholder="اكتب رسالتك، اقتراحك، أو طلب تصحيح أي محتوى..."
                                required>{{ old('message') }}</textarea>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold"><i
                                    class="fas fa-paper-plane me-2"></i> إرسال الرسالة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection