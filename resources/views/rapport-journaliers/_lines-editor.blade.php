@php
    $lignes = $lignes ?? [];
@endphp

<div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="mb-0">{{ __('Reading details') }}</h6>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addLigneBtn">
        <i class="bi bi-plus-lg me-1"></i>{{ __('Line') }}
    </button>
</div>

<div id="lignesContainer">
    @foreach ($lignes as $i => $l)
        @include('rapport-journaliers._ligne-range', ['i' => $i, 'l' => $l])
    @endforeach
</div>

<template id="ligneTemplate">
    @include('rapport-journaliers._ligne-range', ['i' => '__INDEX__', 'l' => []])
</template>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('lignesContainer');
                const template = document.getElementById('ligneTemplate');

                document.getElementById('addLigneBtn')?.addEventListener('click', function () {
                    const index = container.querySelectorAll('.ligne-range').length;
                    const html = template.innerHTML.replaceAll('__INDEX__', index);
                    container.insertAdjacentHTML('beforeend', html);
                });

                container.addEventListener('click', function (e) {
                    if (e.target.closest('.removeLigne')) {
                        e.target.closest('.ligne-range').remove();
                    }
                });

                if (container.querySelectorAll('.ligne-range').length === 0) {
                    document.getElementById('addLigneBtn')?.click();
                }
            });
        </script>
    @endpush
@endonce
