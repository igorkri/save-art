<nav>
    <a href="{{ route('profile.legal') }}" class="{{ request()->routeIs('profile.legal') ? 'on' : '' }}">Юридичні дані</a>
    <a href="{{ route('profile.personal') }}" class="{{ request()->routeIs('profile.personal') ? 'on' : '' }}">Персональні дані</a>
    <a href="{{ route('profile.social') }}" class="{{ request()->routeIs('profile.social') ? 'on' : '' }}">Соцмережі</a>
    <a href="{{ route('profile.safety') }}" class="{{ request()->routeIs('profile.safety') ? 'on' : '' }}">Безпека</a>
</nav>
