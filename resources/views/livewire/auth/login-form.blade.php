<div class="modal_content" id="modal_login">
      <div class="head">
        <div class="title">
          <h6>save-art.in.ua</h6>
          <button type="button" class="close">
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
            <button type="button" class="on">
              <h6>Вхід до спільноти</h6>
            </button>
            <button type="button" wire:click="switchToRegister">
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
              <input type="text" wire:model.lazy="email" placeholder="Ім’я або електронна пошта">
            </div>
            @error('email')
              <div class="error" style="color: #e53e3e; font-size: 0.9em; margin-top: 2px;">{{ $message }}</div>
            @enderror
          </div>
          <div class="input pass">
            <div x-data="{ show: false }">
              <input :type="show ? 'text' : 'password'" wire:model.lazy="password" placeholder="Пароль">
              <button type="button" class="eye" @click="show = !show" :aria-label="show ? 'Сховати' : 'Показати'" tabindex="0"></button>
            </div>
            @error('password')
              <div class="error" style="color: #e53e3e; font-size: 0.9em; margin-top: 2px;">{{ $message }}</div>
            @enderror
          </div>
          <button type="button" class="forgot">Я не пам’ятаю пароль</button>
          <button type="submit" class="btn" wire:loading.attr="disabled">@if($loading) Вхід... @else Увійти @endif</button>
        </form>
      </div>
      <div class="foot">
        <a href="" class="google">Продовжити з Google</a>
      </div>
    </div>
