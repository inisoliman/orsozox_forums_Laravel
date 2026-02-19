@extends('layouts.app')

@section('title', 'غير مصرح بالوصول')

@section('content')
    <div class="container mt-5">
        <div style="max-width:550px; margin:0 auto; text-align:center; padding: 3rem 2rem;">

            {{-- Icon --}}
            <div
                style="width:90px;height:90px;border-radius:50%;background:rgba(239,68,68,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2.5rem;color:var(--accent-red);">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            {{-- Heading --}}
            <h1 style="font-size:1.6rem;font-weight:800;color:var(--text-primary);margin-bottom:0.8rem;">
                {{ $title ?? 'هذا القسم للأعضاء فقط' }}
            </h1>

            {{-- Message --}}
            <p style="color:var(--text-secondary);font-size:1rem;line-height:1.8;margin-bottom:2rem;">
                {{ $message ?? 'ليس لديك صلاحية للوصول إلى هذا القسم. قد يكون مخصصاً لفئة معينة من الأعضاء أو يتطلب تسجيل الدخول.' }}
            </p>

            {{-- Actions --}}
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                @guest
                    <a href="{{ route('login') }}" class="btn-accent"
                        style="padding:0.7rem 2rem;border-radius:8px;text-decoration:none;">
                        <i class="bi bi-box-arrow-in-left ms-1"></i> تسجيل الدخول
                    </a>
                @endguest
                <a href="{{ route('home') }}" class="btn-ghost"
                    style="padding:0.7rem 2rem;border-radius:8px;text-decoration:none;">
                    <i class="bi bi-house ms-1"></i> الصفحة الرئيسية
                </a>
                <button onclick="history.back()" class="btn-ghost"
                    style="padding:0.7rem 2rem;border-radius:8px;cursor:pointer;">
                    <i class="bi bi-arrow-right ms-1"></i> رجوع
                </button>
            </div>

        </div>
    </div>
@endsection