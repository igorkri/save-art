@php
    $supportedLocales = [
        'uk' => [
            'native' => 'Українська',
            'img' => asset('img/ua.webp'),
            'placeholder' => $placeholder ?: 'Вкажіть назву',
            'iconClass' => 'lang_icon ua',
        ],
        'en' => [
            'native' => 'English',
            'img' => asset('img/en.webp'),
            'placeholder' => $placeholder ?: 'Enter the name',
            'iconClass' => 'lang_icon en',
        ],
    ];
    $field = $field ?? 'name';
    $values = $values ?? [];
    $errors = $errors ?? null;
    $type = $type ?? 'text';
    $label = $label ?? '';
@endphp

<div class="fields">
    @if($label)
        <p>{{ $label }}</p>
    @endif
    @foreach($supportedLocales as $locale => $info)
        <div class="line">
            <div class="{{ $info['iconClass'] }}">
                <img src="{{ $info['img'] }}" alt="{{ $info['native'] }}">
            </div>
            <div class="input">
                <div>
                    <input type="{{ $type }}"
                           id="{{ $field }}_{{ $locale }}"
                           name="{{ $field }}[{{ $locale }}]"
                           class="form-control @if($errors && $errors->has($field.'.'.$locale)) is-invalid @endif"
                           placeholder="{{ $info['placeholder'] }}"
                           value="{{ old($field.'.'.$locale, $values[$locale] ?? '') }}">
                    @if($errors && $errors->has($field.'.'.$locale))
                        <div class="invalid-feedback">{{ $errors->first($field.'.'.$locale) }}</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
