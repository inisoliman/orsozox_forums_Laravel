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
                        <h2 class="display-4 fw-bold text-success">{{ count($members) }}</h2>
                        <p class="text-muted mb-0">أعضاء</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 border-secondary">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-secondary">{{ count($guests) }}</h2>
                        <p class="text-muted mb-0">زوار</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card glass-card h-100 border-info">
                    <div class="card-body text-center">
                        <h2 class="display-4 fw-bold text-info">{{ count($bots) }}</h2>
                        <p class="text-muted mb-0">عناكب بحث</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Members Table --}}
        @if(count($members) > 0)
            <div class="card glass-card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i> الأعضاء</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>العضو</th>
                                <th>المكان</th>
                                <th>الوقت</th>
                                <th>IP</th>
                                <th>المتصفح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($members as $member)
                                <tr>
                                    <td>
                                        <a href="{{ route('user.show', $member['user']->userid) }}"
                                            class="fw-bold text-decoration-none">
                                            {{ $member['user']->username }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-light text-dark border">{{ $member['location'] }}</span></td>
                                    <td class="small text-muted">{{ $member['last_activity'] }}</td>
                                    <td class="small">{{ $member['ip_address'] }}</td>
                                    <td class="small text-muted">{{ $member['browser'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Guests Table --}}
        @if(count($guests) > 0)
            <div class="card glass-card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-secret me-2"></i> الزوار</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>المكان</th>
                                <th>الوقت</th>
                                <th>المتصفح</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($guests as $guest)
                                <tr>
                                    <td class="font-monospace">{{ $guest['ip_address'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $guest['location'] }}</span></td>
                                    <td class="small text-muted">{{ $guest['last_activity'] }}</td>
                                    <td class="small text-muted" title="{{ $guest['user_agent'] }}">
                                        {{ $guest['browser'] }}
                                        <i class="fas fa-info-circle ms-1 text-muted" style="cursor:help"></i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Bots Table --}}
        @if(count($bots) > 0)
            <div class="card glass-card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i> عناكب البحث</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>البوت</th>
                                <th>المكان</th>
                                <th>الوقت</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($bots as $bot)
                                <tr>
                                    <td class="fw-bold">{{ \Illuminate\Support\Str::limit($bot['user_agent'], 30) }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $bot['location'] }}</span></td>
                                    <td class="small text-muted">{{ $bot['last_activity'] }}</td>
                                    <td class="font-monospace small">{{ $bot['ip_address'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection