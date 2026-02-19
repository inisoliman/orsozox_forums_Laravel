@extends('layouts.app')

@section('title', \App\Helpers\SeoHelper::title($user->username, 'الملف الشخصي'))
@section('description', 'الملف الشخصي للعضو ' . $user->username . ' - ' . $user->posts . ' مشاركة')
@section('robots', 'noindex, follow')

@section('content')
    <div class="container mt-4">

        {{-- Breadcrumb --}}
        <div class="breadcrumb-modern">
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bi bi-house"></i> الرئيسية</a></li>
                    <li class="breadcrumb-item active">{{ $user->username }}</li>
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            {{-- Profile Card --}}
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="profile-avatar-lg">
                        {{ mb_substr($user->username, 0, 1) }}
                    </div>
                    <h2 class="profile-username">{{ $user->username }}</h2>
                    @if($user->usertitle)
                        <p class="text-accent" style="font-size:0.9rem">{{ $user->usertitle }}</p>
                    @endif

                    <div class="profile-stats">
                        <div class="profile-stat">
                            <span class="profile-stat-value">{{ number_format($user->posts ?? 0) }}</span>
                            <span class="profile-stat-label">مشاركة</span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value">{{ number_format($threads->total()) }}</span>
                            <span class="profile-stat-label">موضوع</span>
                        </div>
                        <div class="profile-stat">
                            <span class="profile-stat-value">{{ number_format($user->reputation ?? 0) }}</span>
                            <span class="profile-stat-label">سمعة</span>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 text-start"
                        style="border-top:1px solid var(--border-color);position:relative;z-index:1">
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted-custom"><i class="bi bi-calendar-plus"></i> تاريخ التسجيل</small>
                            <small>{{ $user->join_date_formatted->format('Y/m/d') }}</small>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <small class="text-muted-custom"><i class="bi bi-clock-history"></i> آخر زيارة</small>
                            <small>{{ $user->last_visit_formatted->diffForHumans() }}</small>
                        </div>
                        @if($user->homepage)
                            <div class="d-flex justify-content-between">
                                <small class="text-muted-custom"><i class="bi bi-globe"></i> الموقع</small>
                                <small><a href="{{ $user->homepage }}" target="_blank" rel="noopener">زيارة</a></small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- User Threads --}}
            <div class="col-lg-8">
                <div class="section-header">
                    <div class="icon"><i class="bi bi-chat-text"></i></div>
                    <h2>مواضيع {{ $user->username }}</h2>
                    <span class="text-muted-custom" style="font-size:0.85rem">{{ $threads->total() }} موضوع</span>
                </div>

                @forelse($threads as $thread)
                    <div class="thread-item animate-in">
                        <div class="thread-icon">
                            <i class="bi bi-chat-text"></i>
                        </div>
                        <div class="thread-content">
                            <div class="thread-title">
                                <a href="{{ $thread->url }}">{{ $thread->title }}</a>
                            </div>
                            <div class="thread-meta">
                                <span><i class="bi bi-folder"></i> {{ $thread->forum->title ?? '' }}</span>
                                <span><i class="bi bi-clock"></i> {{ $thread->created_date->diffForHumans() }}</span>
                                <span><i class="bi bi-eye"></i> {{ number_format($thread->views) }}</span>
                                <span><i class="bi bi-chat"></i> {{ number_format($thread->replycount) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <p>لا توجد مواضيع لهذا العضو</p>
                    </div>
                @endforelse

                <div class="d-flex justify-content-center mt-4">
                    {{ $threads->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection