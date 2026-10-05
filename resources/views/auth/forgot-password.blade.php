@extends('layouts.app')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="glass-panel p-4">
                    <h1 class="h4 fw-bold mb-4 text-center"><i class="fas fa-key text-accent me-2"></i> استعادة كلمة المرور</h1>

                    @if (session('status'))
                        <div class="alert alert-info">{{ session('status') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني المسجل</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                   class="form-control bg-dark text-light border-secondary" required autocomplete="email">
                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">إرسال رابط إعادة التعيين</button>
                    </form>

                    <p class="text-center mt-3 mb-0 small">
                        <a href="{{ route('login') }}" class="text-accent">العودة لتسجيل الدخول</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
