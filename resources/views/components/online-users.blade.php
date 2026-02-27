<div class="card mb-4">
    <div class="card-header glass-header d-flex align-items-center">
        <i class="fas fa-users me-2 text-primary"></i>
        <h5 class="mb-0">المتواجدون الآن</h5>
        <span class="badge bg-primary ms-auto">{{ $total }}</span>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <small class="text-muted fw-bold">إجمالي المتواجدين ({{ $total }})</small>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="badge bg-success">
                    <i class="fas fa-user me-1"></i> أعضاء: {{ $total_members }}
                </span>
                <span class="badge bg-secondary">
                    <i class="fas fa-user-secret me-1"></i> زوار: {{ $total_guests }}
                </span>
                <span class="badge bg-info text-dark">
                    <i class="fas fa-robot me-1"></i> عناكب بحث: {{ $total_bots }}
                </span>
            </div>
        </div>

        @if(count($members) > 0)
            <hr class="my-3">
            <h6 class="text-muted small fw-bold mb-2">الأعضاء المتواجدون:</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach($members as $member)
                    <a href="{{ route('user.show', $member['user']->userid) }}" class="user-link" data-bs-toggle="tooltip"
                        title="{{ is_array($member['location']) ? $member['location']['text'] : $member['location'] }}">
                        <span class="badge bg-light text-dark border">
                            <i class="fas fa-circle text-success small me-1" style="font-size: 8px;"></i>
                            {{ $member['user']->username }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        @if(count($bots) > 0)
            <hr class="my-3">
            <h6 class="text-muted small fw-bold mb-2">عناكب البحث:</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach($bots as $bot)
                    <span class="badge bg-light text-muted border">
                        <i class="fab fa-google me-1"></i> {{ \Illuminate\Support\Str::limit($bot['user_agent'], 20) }}
                    </span>
                @endforeach
            </div>
        @endif
    </div>
</div>