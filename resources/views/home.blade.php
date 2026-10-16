@extends('layouts.app')

@section('title', $settings->brand_name . ' — ' . $settings->tagline)
@section('description', $settings->hero_subheadline ?? $settings->tagline ?? '')

@section('content')
    @include('sections.nav')
    @include('sections.hero')
    @include('sections.trust')
    @include('sections.services')
    @include('sections.portfolio')
    @include('sections.videos')
    @include('sections.process')
    @include('sections.packages')
    @include('sections.faq')
    @include('sections.cta')
    @include('sections.footer')
    @include('sections.floating-wa')
@endsection

@if ($faqs->isNotEmpty())
    @section('schema')
        <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->answer),
                ],
            ])->values()->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
    @endsection
@endif
