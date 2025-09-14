<div class="modal_content" x-data="{ show: @entangle('show') }" x-show="show" x-transition>
    <div class="head">
        <div class="title">
            <button type="button" class="back" wire:click="$dispatch('openLoginForm')">
                <svg width="8" height="12" viewBox="0 0 8 12" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 12L0 6L8 0L8 12Z" />
                </svg>
            </button>
            <h6>Відновлення паролю</h6>
            <button type="button" class="close" wire:click="close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z" />
                </svg>
            </button>
        </div>
        <h6>
            Вкажіть адресу електронної пошти, яку ви використовували при реєстрації, і ми надішлемо вам інструкції щодо
            зміни паролю.
        </h6>
    </div>
    <div class="body">
        <form class="login_form" wire:submit.prevent="resetPassword">
            <div class="input">
                <div>
                    <input type="email" wire:model.blur="email" placeholder="Електронна пошта" required>
                </div>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div class="input pass">
                <div>
                    <input type="password" wire:model.blur="password" placeholder="Новий пароль" required>
                    <button type="button" class="eye"></button>
                </div>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div class="input pass">
                <div>
                    <input type="password" wire:model.blur="password_confirmation" placeholder="Повторіть новий пароль" required>
                    <button type="button" class="eye"></button>
                </div>
                @error('password_confirmation')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn" wire:loading.attr="disabled">
                <span wire:loading.remove>Змінити пароль</span>
                <span wire:loading>Змінюю...</span>
            </button>
        </form>
    </div>
</div>

