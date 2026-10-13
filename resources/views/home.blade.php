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
    @include('sections.cta')
    @include('sections.footer')
    @include('sections.floating-wa')
@endsection
