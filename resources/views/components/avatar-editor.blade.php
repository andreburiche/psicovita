@props([
    'displayName',
    'initials',
    'hasStoredAvatar' => false,
    'storedUrl' => null,
    'style' => null,
    'syncNote' => null,
])

@php
    $resolvedStyle = \App\Support\AvatarStyleOptions::resolve($style);
    $checkboxCard = 'flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border border-slate-200/90 bg-white p-3 text-center text-xs font-medium text-slate-600 shadow-sm transition hover:border-violet-300/70 hover:bg-violet-50/40 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/80 has-[:checked]:text-violet-800 dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-violet-500/40 dark:has-[:checked]:border-violet-500 dark:has-[:checked]:bg-violet-950/40 dark:has-[:checked]:text-violet-200';
    $shapeOptions = [
        'circle' => __('Circular'),
        'rounded' => __('Arredondado'),
        'square' => __('Quadrado'),
    ];
    $ringOptions = [
        'violet' => __('Violeta'),
        'indigo' => __('Índigo'),
        'emerald' => __('Esmeralda'),
        'rose' => __('Rosa'),
        'none' => __('Sem moldura'),
    ];
    $filterOptions = [
        'none' => __('Original'),
        'grayscale' => __('P&B'),
        'warm' => __('Quente'),
        'cool' => __('Frio'),
    ];
@endphp

<div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-violet-50/50 via-white to-indigo-50/40 p-4 dark:border-slate-700 dark:from-violet-950/20 dark:via-slate-900/60 dark:to-indigo-950/20 sm:p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ __('Foto de perfil') }}</h4>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('Carregue uma imagem, ajuste o enquadramento e personalize a apresentação.') }}</p>
        </div>
        <p class="rounded-full bg-white/80 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600">
            {{ __('JPEG · PNG · WebP · máx. 2 MB') }}
        </p>
    </div>
    @if ($syncNote)
        <p class="mt-2 text-xs font-medium text-violet-700 dark:text-violet-300">{{ $syncNote }}</p>
    @endif

    <div class="mt-4 flex flex-col gap-6 lg:flex-row lg:items-start">
        <div class="flex flex-col items-center gap-3 lg:w-44">
            <div
                class="relative flex h-32 w-32 items-center justify-center overflow-hidden bg-gradient-to-br from-violet-500 to-indigo-600 text-3xl font-bold text-white shadow-lg"
                :class="{
                    'rounded-full': shape === 'circle',
                    'rounded-2xl': shape === 'rounded',
                    'rounded-none': shape === 'square',
                    'ring-2 ring-violet-400/80': ring === 'violet',
                    'ring-2 ring-indigo-400/80': ring === 'indigo',
                    'ring-2 ring-emerald-400/80': ring === 'emerald',
                    'ring-2 ring-rose-400/80': ring === 'rose',
                }"
            >
                <img
                    x-show="hasPreview"
                    x-cloak
                    :src="previewSrc"
                    alt=""
                    class="h-full w-full object-cover"
                    x-on:error="onPreviewError()"
                />
                <span x-show="!hasPreview" aria-hidden="true">{{ $initials }}</span>
            </div>
            <p class="text-center text-[0.65rem] font-medium uppercase tracking-wide text-slate-400">{{ __('Pré-visualização do corte') }}</p>
            <p x-show="canEditCrop" x-cloak class="text-center text-[11px] text-slate-500 dark:text-slate-400">{{ __('Actualiza em tempo real') }}</p>
        </div>

        <div class="min-w-0 flex-1 space-y-4">
            <div class="flex flex-wrap gap-2">
                <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-violet-500">
                    <x-ui.icon name="camera" class="h-4 w-4 text-violet-500" />
                    <span x-text="imageSrc ? @js(__('Trocar foto')) : @js(__('Escolher foto'))"></span>
                    <input type="file" x-ref="filePicker" class="sr-only" accept="image/jpeg,image/png,image/webp" @change="onFileChange" />
                </label>

                <button
                    type="button"
                    x-show="hasStoredAvatar && !removeAvatar && !imageSrc"
                    class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800 transition hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200"
                    @click="markRemove()"
                >
                    {{ __('Remover foto') }}
                </button>

                <button
                    type="button"
                    x-show="removeAvatar"
                    x-cloak
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200"
                    @click="undoRemove()"
                >
                    {{ __('Desfazer remoção') }}
                </button>
            </div>

            <p
                x-show="fileError"
                x-cloak
                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100"
                x-text="fileError"
                role="alert"
            ></p>

            <div x-show="canEditCrop" x-cloak class="space-y-3 rounded-2xl border border-violet-200/70 bg-white/70 p-3 dark:border-violet-800/50 dark:bg-slate-900/50 sm:p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Ajustar enquadramento') }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="rotateBy(-90)" title="{{ __('Rodar à esquerda') }}">↺ 90°</button>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="rotateBy(90)" title="{{ __('Rodar à direita') }}">↻ 90°</button>
                        <button type="button" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200" @click="resetFrame()">{{ __('Repor') }}</button>
                    </div>
                </div>

                <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Arraste para reposicionar, use a roda do rato ou o zoom. A área clara é o que será guardado.') }}</p>

                <div class="relative mx-auto w-full max-w-[280px]">
                    <div
                        class="relative mx-auto aspect-square w-full cursor-grab overflow-hidden bg-slate-950/90 active:cursor-grabbing"
                        :class="cropMaskClass"
                        style="touch-action: none;"
                        @pointerdown.prevent="startDrag($event)"
                        @pointermove.prevent="onDrag($event)"
                        @pointerup="endDrag()"
                        @pointercancel="endDrag()"
                        @wheel.prevent="onWheel($event)"
                    >
                        <img
                            x-ref="sourceImg"
                            :src="imageSrc"
                            alt=""
                            class="pointer-events-none absolute left-1/2 top-1/2 max-w-none select-none"
                            :style="imageTransform"
                            @load="onImageLoaded()"
                            draggable="false"
                        />
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/30" :class="cropMaskClass"></div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-sm font-bold text-slate-600 dark:border-slate-600 dark:text-slate-200" @click="nudgeZoom(-0.1)" aria-label="{{ __('Diminuir zoom') }}">−</button>
                    <div class="min-w-0 flex-1">
                        <label for="avatar_zoom" class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ __('Zoom') }}</label>
                        <input
                            id="avatar_zoom"
                            type="range"
                            :min="minZoom"
                            :max="maxZoom"
                            step="any"
                            x-model.number="zoom"
                            @input="onZoomInput()"
                            class="mt-1 w-full accent-violet-600"
                        />
                    </div>
                    <button type="button" class="rounded-lg border border-slate-200 px-2 py-1 text-sm font-bold text-slate-600 dark:border-slate-600 dark:text-slate-200" @click="nudgeZoom(0.1)" aria-label="{{ __('Aumentar zoom') }}">+</button>
                </div>
            </div>

            <fieldset>
                <legend class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Formato') }}</legend>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    @foreach ($shapeOptions as $value => $label)
                        <label class="{{ $checkboxCard }}">
                            <input type="radio" name="avatar_shape" value="{{ $value }}" x-model="shape" class="sr-only" @checked(old('avatar_shape', $resolvedStyle['shape']) === $value) />
                            <span class="flex h-8 w-8 items-center justify-center border-2 border-current {{ \App\Support\AvatarStyleOptions::shapeClass($value) }}" aria-hidden="true"></span>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Moldura') }}</legend>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-5">
                    @foreach ($ringOptions as $value => $label)
                        <label class="{{ $checkboxCard }}">
                            <input type="radio" name="avatar_ring" value="{{ $value }}" x-model="ring" class="sr-only" @checked(old('avatar_ring', $resolvedStyle['ring']) === $value) />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-xs font-semibold text-slate-700 dark:text-slate-200">{{ __('Filtro') }}</legend>
                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($filterOptions as $value => $label)
                        <label class="{{ $checkboxCard }}">
                            <input type="radio" name="avatar_filter" value="{{ $value }}" x-model="filter" class="sr-only" @checked(old('avatar_filter', $resolvedStyle['filter']) === $value) />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />
        </div>
    </div>
</div>
