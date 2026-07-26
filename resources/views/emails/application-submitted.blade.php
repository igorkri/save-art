@component('mail::message')
# Нова заявка на співпрацю

**Ім'я:** {{ $applicant['name'] }}

**Email:** {{ $applicant['email'] }}

@if(!empty($applicant['phone']))
**Телефон:** {{ $applicant['phone'] }}
@endif

@if(!empty($applicant['about']))
**Про себе:**

{{ $applicant['about'] }}
@endif

Заявку надіслано з форми на сайті save-art.in.ua.
@endcomponent
