@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="FDC LMS" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('images/fdc.png') }}" alt="FDC Logo" class="size-5" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="FDC LMS" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
            <img src="{{ asset('images/fdc.png') }}" alt="FDC Logo" class="size-5" />
        </x-slot>
    </flux:brand>
@endif
