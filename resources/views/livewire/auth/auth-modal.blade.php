<div>
    @if($showReset)
        <livewire:auth.password-reset-form />
    @elseif($showLogin)
        <livewire:auth.login-form />
    @else
        <livewire:auth.register-form />
    @endif
</div>
