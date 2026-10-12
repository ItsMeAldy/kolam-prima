@props(['eyebrow' => null, 'title', 'subtitle' => null, 'align' => 'center', 'light' => false])

<div class="{{ $align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl' }}">
    @if ($eyebrow)
        <span class="text-sm font-semibold uppercase tracking-wider {{ $light ? 'text-primary-300' : 'text-primary-600' }}">{{ $eyebrow }}</span>
    @endif
    <h2 class="mt-3 font-heading text-3xl font-extrabold tracking-tight sm:text-4xl {{ $light ? 'text-white' : 'text-navy-900' }}">
        {{ $title }}
    </h2>
    @if ($subtitle)
        <p class="mt-4 text-base leading-relaxed {{ $light ? 'text-white/70' : 'text-navy-800/60' }}">{{ $subtitle }}</p>
    @endif
</div>
