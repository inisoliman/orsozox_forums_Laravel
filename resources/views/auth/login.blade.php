@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title('تسجيل الدخول'))
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="container mt-4">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="bi bi-person-circle" style="font-size:3rem;color:var(--accent-secondary)"></i>
            </div>
            <h2>تسجيل الدخول</h2>
            <p class="text-center text-muted-custom mb-4">سجّل دخولك بنفس بيانات حسابك القديم</p>

            @if($errors->any())
                <div class="alert alert-modern alert-danger-modern mb-3">
                    <i class="bi bi-exclamation-triangle ms-2"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="username" class="form-label text-muted-custom">
                        <i class="bi bi-person ms-1"></i> اسم المستخدم
                    </label>
                    <input type="text" name="username" id="username"
                        class="form-control form-control-dark @error('username') border-danger @enderror"
                        value="{{ old('username') }}" placeholder="أدخل اسم المستخدم" required autofocus>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label text-muted-custom">
                        <i class="bi bi-lock ms-1"></i> كلمة المرور
                    </label>
                    <input type="password" name="password" id="password"
                        class="form-control form-control-dark @error('password') border-danger @enderror"
                        placeholder="أدخل كلمة المرور" required>
                </div>

                <button type="submit" class="btn btn-accent w-100 py-2">
                    <i class="bi bi-box-arrow-in-left ms-1"></i> تسجيل الدخول
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('home') }}" class="text-muted-custom" style="font-size:0.85rem">
                    <i class="bi bi-arrow-right ms-1"></i> العودة للرئيسية
                </a>
            </div>
        </div>
    </div>
@endsection