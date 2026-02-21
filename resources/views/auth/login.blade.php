@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title('تسجيل الدخول'))
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="glass-panel p-5 animate-in">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-light bg-opacity-10 p-3 mb-3"
                            style="width: 80px; height: 80px;">
                            <i class="fas fa-user-circle" style="font-size:3rem;color:var(--accent-gold)"></i>
                        </div>
                        <h2 class="h3 fw-bold">تسجيل الدخول</h2>
                        <p class="text-muted-custom">سجّل دخولك بنفس بيانات حسابك القديم</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger d-flex align-items-center rounded-3 mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>{{ $errors->first() }}</div>
                        </div>
                    @endif

                    <form action="{{ route('login.submit') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="username" class="form-label text-muted-custom small fw-bold">
                                <i class="fas fa-user me-1"></i> اسم المستخدم
                            </label>
                            <input type="text" name="username" id="username"
                                class="form-control form-control-dark @error('username') border-danger @enderror"
                                value="{{ old('username') }}" placeholder="أدخل اسم المستخدم" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label text-muted-custom small fw-bold">
                                <i class="fas fa-lock me-1"></i> كلمة المرور
                            </label>
                            <input type="password" name="password" id="password"
                                class="form-control form-control-dark @error('password') border-danger @enderror"
                                placeholder="أدخل كلمة المرور" required>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 py-2 fw-bold shadow-sm">
                            <i class="fas fa-sign-in-alt me-1"></i> تسجيل الدخول
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top border-light border-opacity-10">
                        <a href="{{ route('home') }}" class="text-muted text-decoration-none small hover-slide-left">
                            <i class="fas fa-arrow-right me-1"></i> العودة للرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection