@extends('layouts.app')

@section('title', 'إعدادات الحساب')

@section('content')
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="glass-panel p-4 mb-4">
                    <h1 class="h5 fw-bold mb-3"><i class="fas fa-user-cog text-accent me-2"></i> إعدادات الحساب</h1>
                    <p class="small text-muted-custom mb-0">اسم العضوية: <strong>{{ $user->username }}</strong> — لا يمكن تغييره.</p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="glass-panel p-4 mb-4">
                    <h2 class="h6 fw-bold mb-3"><i class="fas fa-key me-2"></i> تغيير كلمة المرور</h2>
                    <form method="POST" action="{{ route('account.password') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control bg-dark text-light border-secondary" required autocomplete="current-password">
                            @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور الجديدة</label>
                            <input type="password" id="password" name="password"
                                   class="form-control bg-dark text-light border-secondary" required minlength="6" autocomplete="new-password">
                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">تأكيد كلمة المرور الجديدة</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control bg-dark text-light border-secondary" required minlength="6" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary">تحديث كلمة المرور</button>
                    </form>
                </div>

                <div class="glass-panel p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="fas fa-envelope me-2"></i> تغيير البريد الإلكتروني</h2>
                    <form method="POST" action="{{ route('account.email') }}" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الجديد</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="form-control bg-dark text-light border-secondary" required maxlength="100" autocomplete="email">
                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="email_current_password" class="form-label">كلمة المرور الحالية (للتأكيد)</label>
                            <input type="password" id="email_current_password" name="current_password"
                                   class="form-control bg-dark text-light border-secondary" required autocomplete="current-password">
                        </div>
                        <button type="submit" class="btn btn-outline-accent">تحديث البريد</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
