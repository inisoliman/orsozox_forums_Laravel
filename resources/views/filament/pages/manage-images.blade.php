<x-filament-panels::page>
    @php $stats = $this->getStats(); @endphp

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي الصور</div>
            <div class="text-3xl font-bold text-primary-600">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">صور صالحة</div>
            <div class="text-3xl font-bold text-success-600">{{ number_format($stats['valid']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">صور مكسورة</div>
            <div class="text-3xl font-bold text-danger-600">{{ number_format($stats['broken']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="text-sm text-gray-500 dark:text-gray-400">نسبة الصحة</div>
            <div
                class="text-3xl font-bold {{ $stats['health_score'] >= 80 ? 'text-success-600' : ($stats['health_score'] >= 50 ? 'text-warning-600' : 'text-danger-600') }}">
                {{ $stats['health_score'] }}%
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    @if($stats['total'] > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="flex justify-between text-sm mb-2">
                <span class="text-gray-600 dark:text-gray-400">صحة الصور</span>
                <span class="font-semibold">{{ $stats['valid'] }} صالحة / {{ $stats['broken'] }} مكسورة /
                    {{ $stats['pending'] }} انتظار</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                @php
                    $vPct = $stats['total'] > 0 ? ($stats['valid'] / $stats['total']) * 100 : 0;
                    $bPct = $stats['total'] > 0 ? ($stats['broken'] / $stats['total']) * 100 : 0;
                @endphp
                <div class="h-full flex">
                    <div class="bg-success-500 h-full" style="width: {{ $vPct }}%"></div>
                    <div class="bg-danger-500 h-full" style="width: {{ $bPct }}%"></div>
                </div>
            </div>
            @if($stats['last_scan'])
                <div class="text-xs text-gray-400 mt-2">آخر فحص: {{ $stats['last_scan'] }}</div>
            @endif
        </div>
    @endif

    {{-- Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <button wire:click="runQuickScan"
            class="bg-primary-600 hover:bg-primary-700 text-white rounded-xl p-4 text-center transition-colors">
            <x-heroicon-o-magnifying-glass class="w-6 h-6 mx-auto mb-1" />
            <div class="text-sm font-semibold">مسح سريع</div>
            <div class="text-xs opacity-75">آخر 100 موضوع</div>
        </button>

        <button wire:click="toggleProxy"
            class="rounded-xl p-4 text-center transition-colors {{ $proxyEnabled ? 'bg-success-600 hover:bg-success-700 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
            <x-heroicon-o-shield-check class="w-6 h-6 mx-auto mb-1" />
            <div class="text-sm font-semibold">بروكسي الصور</div>
            <div class="text-xs opacity-75">{{ $proxyEnabled ? 'مُفعّل' : 'معطّل' }}</div>
        </button>

        <button wire:click="toggleAutoCleanup"
            class="rounded-xl p-4 text-center transition-colors {{ $autoCleanupEnabled ? 'bg-warning-600 hover:bg-warning-700 text-white' : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
            <x-heroicon-o-trash class="w-6 h-6 mx-auto mb-1" />
            <div class="text-sm font-semibold">التنظيف التلقائي</div>
            <div class="text-xs opacity-75">{{ $autoCleanupEnabled ? 'مُفعّل' : 'معطّل' }}</div>
        </button>

        <button wire:click="exportBrokenCsv"
            class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl p-4 text-center transition-colors">
            <x-heroicon-o-document-arrow-down class="w-6 h-6 mx-auto mb-1" />
            <div class="text-sm font-semibold">تصدير CSV</div>
            <div class="text-xs opacity-75">قائمة الصور المكسورة</div>
        </button>
    </div>

    {{-- Instructions --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
        <h3 class="font-bold text-blue-800 dark:text-blue-300 mb-2">📋 تعليمات الاستخدام</h3>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li>• <strong>مسح سريع:</strong> يفحص آخر 100 موضوع ويرسل الصور للفحص في الخلفية.</li>
            <li>• <strong>بروكسي الصور:</strong> عند تفعيله، يتم تحويل الصور الخارجية عبر البروكسي (يحل مشكلة الصور
                المكسورة).</li>
            <li>• <strong>التنظيف التلقائي:</strong> يستبدل الصور المكسورة بصورة بديلة (Placeholder).</li>
            <li>• <strong>عبر SSH:</strong> <code>php artisan images:scan --limit=5000 --queue</code></li>
        </ul>
    </div>
</x-filament-panels::page>