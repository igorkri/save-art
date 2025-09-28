@extends('layouts.saveart')
@section('title', __('profile_legal.title'))
@section('content')
  <div class="under_header">
    <img src="../../img/under_header/under_header13.webp" alt="">
    <h3>Налаштування</h3>
  </div>

  <section class="profile_settings" id="profile_settings">
    <div class="block">
      @include('profile.nav.tab')
      <form>
        <div class="window on">

          <!-- видалення профілю -->
          <div class="fill delete_project">
            <div class="head">
              <h6>Ви видаляєте свій профіль.</h6>
              <button type="button" class="bttn close">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z" />
                </svg>
              </button>
            </div>
            <div class="body">
              <p>
                Якщо в профілі є незакриті фінансові операції, то перш ніж він буде видалений, він пройде перевірку
                модераторами.
              </p>
            </div>
            <div class="foot">
              <button type="button" class="btn dark tall">Так, видалити</button>
              <button type="button" class="btn yellow tall">Ні, залишитись</button>
            </div>
          </div>

        </div>
        <h6>Безпека</h6>
        <div class="input">
          <p>Електронна пошта</p>
          <div>
            <input type="text" placeholder="sample@mail.com">
          </div>
          <p>У випадку зміни електронної пошти ми відправимо лист-підтвердження на нову адресу</p>

        </div>
        <div class="input pass">
          <p>Поточний пароль</p>
          <div>
            <input type="password" placeholder="Введіть пароль">
            <button type="button" class="eye"></button>
          </div>

        </div>
        <div class="input pass">
          <p>Новий пароль</p>
          <div>
            <input type="password" placeholder="Введіть новий пароль">
            <button type="button" class="eye"></button>
          </div>

        </div>
        <div class="input pass">
          <p>Новий пароль</p>
          <div>
            <input type="password" placeholder="Підтвердіть новий пароль">
            <button type="button" class="eye"></button>
          </div>

        </div>
        <button type="submit">
          <svg width="14" height="12" viewBox="0 0 14 12" fill="" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
              d="M13.9992 2.91495L5.415 11.4992L0.000786781 6.08495L2.415 3.67073L5.415 6.67073L11.585 0.500732L13.9992 2.91495Z" />
          </svg>
        </button>
        <hr class="dark">
        <button type="button" class="btn_decor dark profileDeleteBtn" id="profileDeleteBtn">
          <p>Видалити профіль</p>
          <div>
            <svg width="16" height="16" viewBox="0 0 16 16" fill="" xmlns="http://www.w3.org/2000/svg">
              <path
                d="M10.4999 7.99998L15.9999 2.5L13.4999 0L7.99995 5.50012L2.49963 0L0 2.49988L5.50014 8.00002L0 13.5004L2.49988 16L8 10.4999L13.5 15.9999L16 13.4999L10.4999 7.99998Z" />
            </svg>
          </div>
        </button>
      </form>
    </div>
  </section>
@endsection