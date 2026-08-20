<div style="font-family: 'Segoe UI', Tahoma, sans-serif; direction: rtl; text-align: right;">
    <div style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e5e7eb;">
        <strong style="font-size: 1.05rem;">{{ $title }}</strong>
    </div>

    @if(!empty($author))
        <div style="margin-bottom: 0.75rem; color: #6b7280; font-size: 0.9rem;">
            بقلم: <strong style="color: #374151;">{{ $author }}</strong>
        </div>
    @endif

    <div style="line-height: 1.8; color: #111827; overflow-wrap: anywhere;"
        class="filament-preview-content">
        {!! $content !!}
    </div>
</div>