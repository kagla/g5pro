{{-- 상품 이미지 크게보기 (shop/largeimage.php) — 순정 스킨 출력(썸네일 전환·창 크기 맞춤)을
     그대로 담는다. 스크립트가 jQuery 와 순정 클래스에 기대고 있어 손대지 않는다 --}}
@extends('layout.popup')
@section('head')
<script src="{{ G5_JS_URL }}/jquery-1.12.4.min.js"></script>
@endsection
@section('popup_class', 'popup--bare')
@section('content')
{!! $body_html !!}
@endsection
