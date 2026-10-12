@if (count($stats = $settings->stats()))
    <section class="border-y border-slate-100 bg-muted">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ($stats as $stat)
                <div class="text-center lg:text-left">
                    <p class="font-heading text-4xl font-extrabold tracking-tight text-primary-600 lg:text-5xl">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-sm font-medium text-navy-800/60">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
