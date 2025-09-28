<div class="modal_content" id="modal_register">
    <div class="head">
        <div class="title">
            <h6>save-art.in.ua</h6>
            <button type="button" class="close" wire:click="close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z" />
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
                <button type="button" wire:click="switchToLogin">
                    <h6>Вхід до спільноти</h6>
                </button>
                <button type="button" class="on">
                    <h6>Реєстрація</h6>
                </button>
            </div>
            <div class="links">
                <a href="#">save-art.in.ua</a>
                <a href="#">art-ua.com</a>
                <a href="#">art-ua.info</a>
            </div>
        </div>
    <form class="login_form" wire:submit.prevent="register">
            <div class="input">
                <div>
                    <input type="text" wire:model.lazy="name" placeholder="Ваше ім’я" >
                </div>
                @error('name') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="input">
                <div>
                    <input type="email" wire:model.lazy="email" placeholder="Електронна пошта" >
                </div>
                @error('email') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="input pass">
                <div>
                    <input type="password" wire:model.lazy="password" placeholder="Пароль" >
                    <button type="button" class="eye"></button>
                </div>
                @error('password') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="input pass">
                <div>
                    <input type="password" wire:model.lazy="password_confirmation" placeholder="Повторіть пароль" >
                    <button type="button" class="eye"></button>
                </div>
                @error('password_confirmation') <span class="error">{{ $message }}</span> @enderror
            </div>
            <label class="checkbox">
                <input type="checkbox" wire:model="terms" >
                <span>Я приймаю умови використання платформи</span>
            </label>
            @error('terms') <span class="error">{{ $message }}</span> @enderror
            <button type="submit" class="btn" wire:loading.attr="disabled">Зареєструватися</button>
        </form>
    </div>
    <div class="foot">
        <a href="" class="google">Продовжити з Google</a>
    </div>
</div>
