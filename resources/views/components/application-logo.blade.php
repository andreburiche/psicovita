{{-- Compatível com Breeze: delega para a marca PsiConecta. --}}
@props([
    'variant' => 'topbar',
])
<x-psiconecta-logo :variant="$variant" {{ $attributes }} />
