@extends('layouts.app')

@section('title', 'تسجيل عضو جديد')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="glass-panel p-4">
                    <h1 class="h4 fw-bold mb-4 text-center"><i class="fas fa-user-plus text-accent me-2"></i> تسجيل عضو جديد</h1>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('register.submit') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label">اسم العضوية</label>
                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                   class="form-control bg-dark text-light border-secondary" required maxlength="100" autocomplete="username">
                            @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                   class="form-control bg-dark text-light border-secondary" required maxlength="100" autocomplete="email">
                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <input type="password" id="password" name="password"
                                   class="form-control bg-dark text-light border-secondary" required minlength="6" autocomplete="new-password">
                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control bg-dark text-light border-secondary" required minlength="6" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">إنشاء الحساب</button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        لديك حساب؟ <a href="{{ route('login') }}" class="text-accent">تسجيل الدخول</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
