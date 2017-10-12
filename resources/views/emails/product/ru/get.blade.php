@extends('emails.layouts.system')

@section('content')
    <p>
        Здравствуйте!
    </p>
    <p>
        Ваш запрос на {{ $is_demo ? 'демо версию продукта' : 'продукт' }} "{{ $product['name'] }}" успешно обработан на сайте <a href="{{config('app.url')}}">{{config('app.name')}}</a>.
    </p>
    <p>
        Скачать его вы можете в своем личном кабинете.
    </p>
    <p>
        По завершению доступа, вы получите уведомление.
    </p>
@stop