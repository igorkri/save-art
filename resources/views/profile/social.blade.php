@extends('layouts.saveart')
@section('title', __('profile_legal.title'))
@section('content')
  <div class="under_header">
    <img src="../../img/under_header/under_header12.webp" alt="">
    <h3>Налаштування</h3>
  </div>

  <section class="profile_settings" id="profile_settings">
    <div class="block">
      @include('profile.nav.tab')
      <form>
        <h6>Ваші соцмережі</h6>
        <div class="input">
          <p>Вебсайт</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <div class="input">
          <p>Facebook</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <div class="input">
          <p>Instagram</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <div class="input">
          <p>LinkedIn</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <div class="input">
          <p>Pinterest</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <div class="input">
          <p>X</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <div class="input">
          <p>YouTube</p>
          <div>
            <input type="text" placeholder="Вставте посилання">
          </div>
        </div>
        <button type="submit">
          <svg width="14" height="12" viewBox="0 0 14 12" fill="" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
              d="M13.9992 2.91495L5.415 11.4992L0.000786781 6.08495L2.415 3.67073L5.415 6.67073L11.585 0.500732L13.9992 2.91495Z" />
          </svg>
        </button>
      </form>
    </div>
  </section>
@endsection