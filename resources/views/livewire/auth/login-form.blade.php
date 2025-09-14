<div class="modal_content" x-data="{ show: @entangle('show') }" x-show="show">
    <div class="head">
        <div class="title">
            <h6>Вхід</h6>
            <button type="button" class="close" wire:click="close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                </svg>
            </button>
        </div>
    </div>
    <form wire:submit.prevent="login">
        <div class="input">
            <label>Email</label>
            <div>
                <input type="email" wire:model.defer="email" required>
            </div>
        </div>
        <div class="input pass">
            <label>Пароль</label>
            <div>
                <input type="password" wire:model.defer="password" required>
                <button type="button" class="eye">
                    <svg width="22" height="16" viewBox="0 0 22 16" fill="" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11 0.5C6 0.5 1.73 3.61 0 8C1.73 12.39 6 15.5 11 15.5C16 15.5 20.27 12.39 22 8C20.27 3.61 16 0.5 11 0.5ZM11 13C8.24 13 6 10.76 6 8C6 5.24 8.24 3 11 3C13.76 3 16 5.24 16 8C16 10.76 13.76 13 11 13ZM11 5C9.34 5 8 6.34 8 8C8 9.66 9.34 11 11 11C12.66 11 14 9.66 14 8C14 6.34 12.66 5 11 5Z"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="remember">
            <label>
                <input type="checkbox" wire:model.defer="remember">
                <span>Запам'ятати мене</span>
            </label>
            <a href="#" wire:click.prevent="$dispatch('openResetForm')">Забули пароль?</a>
        </div>
        <button type="submit" class="btn">Увійти</button>
        <a href="#" wire:click.prevent="$dispatch('openRegisterForm')" class="reg">Зареєструватися</a>
    </form>
</div>

