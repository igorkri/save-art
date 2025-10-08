<nav>
    <a href="{{ route('profile.legal') }}" class="{{ request()->routeIs('profile.legal') ? 'on' : '' }}">{{ __('profile_tab.legal') }}</a>
    <a href="{{ route('profile.personal') }}" class="{{ request()->routeIs('profile.personal') ? 'on' : '' }}">{{ __('profile_tab.personal') }}</a>
    <a href="{{ route('profile.social') }}" class="{{ request()->routeIs('profile.social') ? 'on' : '' }}">{{ __('profile_tab.social') }}</a>
    <a href="{{ route('profile.safety') }}" class="{{ request()->routeIs('profile.safety') ? 'on' : '' }}">{{ __('profile_tab.safety') }}</a>
</nav>
