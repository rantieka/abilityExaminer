<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section>
        <x-slot name="heading">
            Rekrutmen Funnel
        </x-slot>

        <x-slot name="afterHeader">
            <div style="display:flex; flex-direction:row; align-items:center; gap:0.5rem;">
                {{-- Position Filter --}}
                <x-filament::input.wrapper size="sm">
                    <x-filament::input.select
                        wire:model.live="selectedPosition"
                        wire:loading.attr="disabled"
                        size="sm"
                    >
                        <option value="">Semua Posisi</option>
                        @foreach($this->getPositions() as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                {{-- Month Filter --}}
                <x-filament::input.wrapper size="sm">
                    <x-filament::input.select
                        wire:model.live="selectedMonth"
                        wire:loading.attr="disabled"
                        size="sm"
                    >
                        <option value="">Semua Bulan</option>
                        @foreach($this->getMonths() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </x-slot>

        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
        >
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="bar"
                x-data="chart({
                            cachedData: @js($this->getCachedData()),
                            maxHeight: @js($this->getMaxHeight()),
                            options: @js($this->getOptions()),
                            type: 'bar',
                        })"
                class="fi-wi-chart-canvas-ctn"
            >
                <canvas x-ref="canvas"></canvas>
                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
