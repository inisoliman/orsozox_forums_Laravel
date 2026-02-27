@extends('layouts.app')

@section('title', 'المتواجدون الآن (تفاصيل)')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-users-cog me-2"></i> تفاصيل المتواجدين الآن</h3>
            <a href="{{ route('home') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للرئيسية
            </a>
        </div>

        <div class="row">
            {{-- Stats Cards --}}
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 border-success">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-success">{{ $total_members }}</h2>
                        <p class="text-muted mb-0">أعضاء</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 border-secondary">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-secondary">{{ $total_guests }}</h2>
                        <p class="text-muted mb-0">زوار</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 border-info">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-info">{{ $total_bots }}</h2>
                        <p class="text-muted mb-0">عناكب بحث</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Unified Sessions Table --}}
        @if(count($users) > 0)
            <div class="card glass-card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> سجل أحدث التواجد ({{ $total }} متصل)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الزائر</th>
                                <th>المكان</th>
                                <th>الوقت</th>
                                <th>IP</th>
                                <th>المتصفح/البوت</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $userRow)
                                <tr>
                                    <td>
                                        @if($userRow['type'] === 'member')
                                            <a href="{{ route('user.show', $userRow['user']->userid) }}"
                                                class="fw-bold text-decoration-none">
                                                <i class="fas fa-user text-success me-1"></i> {{ $userRow['user']->username }}
                                            </a>
                                        @elseif($userRow['type'] === 'bot')
                                            <span class="text-info fw-bold"><i class="fas fa-robot me-1"></i> عنكبوت بحث</span>
                                        @else
                                            <span class="text-secondary fw-bold"><i class="fas fa-user-secret me-1"></i> زائر</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($userRow['location']['url']))
                                            <a href="{{ $userRow['location']['url'] }}"
                                                class="badge text-bg-light border text-decoration-none text-dark"
                                                style="font-size: 0.9em;">
                                                {!! $userRow['location']['text'] !!}
                                            </a>
                                        @else
                                            <span class="badge text-bg-light text-dark border" style="font-size: 0.9em;">
                                                {!! $userRow['location']['text'] !!}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $userRow['last_activity'] }}</td>
                                    <td class="font-monospace small">{{ $userRow['ip_address'] }}</td>
                                    <td class="small text-muted" title="{{ $userRow['user_agent'] }}">
                                        @if($userRow['type'] === 'bot')
                                            {{ \Illuminate\Support\Str::limit($userRow['user_agent'], 30) }}
                                        @else
                                            {{ $userRow['browser'] }}
                                        @endif
                                        <i class="fas fa-info-circle ms-1 text-muted" style="cursor:help"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $paginator->links('pagination::bootstrap-5') }}
            </div>
        @else
            <div class="alert alert-warning text-center">
                لا يوجد أي متصلين في الوقت الحالي.
            </div>
        @endif
    </div>
@endsection