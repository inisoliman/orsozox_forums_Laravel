<x-filament-panels::page>
    <form wire:submit="save" class="space-y-4">
        {{ $this->form }}

        <div class="flex justify-end">
            <button type="submit" class="fi-btn">
                حفظ أقسام sitemap
            </button>
        </div>
    </form>
</x-filament-panels::page>
