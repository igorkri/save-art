@extends('layouts.saveart')
@section('title', __('profile_new.title'))
@section('content')
    <div class="under_header">
        <img src="{{ asset('img/under_header/under_header19.webp') }}" alt="{{ __('header.logo_alt') }}">
        <h3>{{ __('profile_new.welcome') }}</h3>
    </div>

    <section class="profile_settings" id="profile_settings">
        <div class="block">
            <form action="{{ route('profile.new.store') }}" method="POST">
                @csrf
                <h5>
                    {{ __('profile_new.intro') }}
                </h5>
                <div class="box">
                    <h6>{{ __('profile_new.preference_title') }}</h6>
                    <p>
                        {{ __('profile_new.preference_text') }}
                    </p>
                    <label class="radio tall">
                        <input type="radio" name="art" value="{{ \App\UserRole::Owner->value }}" checked>
                        <span>{{ __('profile_new.create_and_support') }}</span>
                    </label>
                    <label class="radio tall">
                        <input type="radio" name="art" value="{{ \App\UserRole::Mecenat->value }}">
                        <span>{{ __('profile_new.support_only') }}</span>
                    </label>
                </div>
                <button type="submit" class="btn yellow">{{ __('profile_new.continue') }}</button>
            </form>
        </div>
    </section>
@endsection
