@props(['settings', 'size' => 'h-9 w-9'])

@if ($settings->logo_url)
    <img src="{{ $settings->logo_url }}" alt="{{ $settings->brand_name }}"
         class="{{ $size }} flex-none rounded-xl object-contain">
@else
    <span class="grid {{ $size }} flex-none place-items-center rounded-xl bg-primary-600 text-white">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2 15c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0M2 9c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0" />
        </svg>
    </span>
@endif
