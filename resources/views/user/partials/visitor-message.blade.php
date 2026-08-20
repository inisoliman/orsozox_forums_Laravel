{{--
    بطاقة رسالة زائر واحدة — تُستخدم في قائمة رسائل الزوار وفي استجابة AJAX بعد الإرسال.
    المتغيرات المتوقعة:
        - $message : \App\Models\VisitorMessage (محملة مع author)
--}}
<div class="vm-card mb-2 animate-in" id="vm-{{ $message->vmid }}">
    <div class="vm-avatar">{{ mb_substr($message->postusername, 0, 1) }}</div>
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <a href="{{ route('user.show', $message->postuserid) }}" class="fw-bold text-accent text-decoration-none">
                {{ $message->postusername }}
            </a>
            <small class="text-muted-custom" title="{{ $message->created_date->format('Y/m/d h:i A') }}">
                <i class="fas fa-clock me-1"></i>{{ $message->created_date->diffForHumans() }}
            </small>
        </div>
        <div class="vm-content mt-1">{!! $message->parsed_content !!}</div>
    </div>
</div>