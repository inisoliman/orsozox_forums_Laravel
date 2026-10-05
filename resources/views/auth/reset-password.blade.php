@extends('layouts.app')

@section('title', 'إعادة تعيين كلمة المرور')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="glass-panel p-4">
                    <h1 class="h4 fw-bold mb-4 text-center"><i class="fas fa-lock-open text-accent me-2"></i> كلمة مرور جديدة</h1>

                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}" novalidate>
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" id="password" name="password"
                                   class="form-control bg-dark text-light border-secondary" required minlength="6" autocomplete="new-password">
                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control bg-dark text-light border-secondary" required minlength="6" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">حفظ كلمة المرور</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
