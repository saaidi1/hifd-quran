<x-filament-panels::page>
    <form wire:submit="enregistrer">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button type="submit" icon="heroicon-o-check" size="lg">
                حفظ تقارير المجموعة
            </x-filament::button>

            <x-filament::button
                type="button"
                color="gray"
                icon="heroicon-o-arrow-path"
                wire:click="chargerEtudiants"
            >
                إعادة التحميل
            </x-filament::button>

            <span class="text-sm text-gray-500 dark:text-gray-400">
                المقطع مُعبَّأ تلقائيا انطلاقا من الواجب المقرر — يكفي إدخال النقطة.
            </span>
        </div>
    </form>
</x-filament-panels::page>
