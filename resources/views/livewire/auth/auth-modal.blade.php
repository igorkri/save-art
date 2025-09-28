<div>
    @if($showLogin)
        <livewire:auth.login-form />
    @else
        <livewire:auth.register-form />
    @endif
</div>
