<div class="modal_content" x-data="{ show: @entangle('show') }" x-show="show" x-transition>
    <div class="head">
        <div class="title">
            <h6>save-art.in.ua</h6>
            <button type="button" class="close" wire:click="close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z"/>
                </svg>
            </button>
        </div>
        <h6>
            save-art.in.ua — платформа для митців та бажаючих долучитись до створення, розвитку і збереження новітнього
            українського
            мистецтва
        </h6>
    </div>
    <div class="body">
        <div>
            <div class="switching">
                <button type="button" class="on">
                    <h6>Вхід до спільноти</h6>
                </button>
                <button type="button">
                    <h6>Реєстрація</h6>
                </button>
            </div>
            <div class="links">
                <a href="#">save-art.in.ua</a>
                <a href="#">art-ua.com</a>
                <a href="#">art-ua.info</a>
            </div>
        </div>
        <form class="login_form" wire:submit.prevent="login">
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
                    <input type="password" wire:model.blur="password" placeholder="Пароль" required>
                    <button type="button" class="eye"></button>
                </div>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <label class="checkbox">
                <input type="checkbox" wire:model="remember">
                <span>Запам'ятати мене</span>
            </label>
            <button type="button" class="forgot">Я не пам'ятаю пароль</button>
            <button type="submit" class="btn" wire:loading.attr="disabled">
                <span wire:loading.remove>Увійти</span>
                <span wire:loading>Входжу...</span>
            </button>
        </form>
    </div>
    <div class="foot">
        <a href="" class="google">Продовжити з Google</a>
    </div>
</div>
