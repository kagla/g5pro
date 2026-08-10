@extends('layout.default')
@section('content')
<div class="member-box center">
    <h2>회원가입 완료</h2>
    <p><strong>{!! $mb_nick !!}</strong>({!! $mb_id !!})님, 회원가입을 축하합니다.</p>

    {{-- 상태에 따라 할 말이 다르다. 메일인증을 안 쓰는 사이트는 가입과 동시에 로그인되므로
         "로그인 하세요" 는 거짓말이 된다(순정 register_form_update.php 가 그때 세션을 심는다). --}}
    @if ($need_certify)
    <p class="muted">
        {{ $mb_email !== '' ? $mb_email : '가입하신 이메일' }} 로 인증 메일을 보냈습니다.<br>
        메일 속 링크를 눌러야 가입이 마무리됩니다.
    </p>
    <div class="btn-row">
        <a class="btn btn-primary" href="{{ G5_URL }}/">처음으로</a>
    </div>
    @elseif ($is_login)
    <p class="muted">로그인된 상태입니다. 지금부터 바로 이용하실 수 있습니다.</p>
    <div class="btn-row">
        <a class="btn btn-primary" href="{{ G5_URL }}/">처음으로</a>
    </div>
    @else
    <p class="muted">로그인 후 서비스를 이용하실 수 있습니다.</p>
    {{-- 버튼 둘은 한 줄에 — .member-box 가 세로 flex 라 감싸지 않으면 위아래로 쌓인다 --}}
    <div class="btn-row">
        <a class="btn btn-primary" href="{{ G5_BBS_URL }}/login.php">로그인</a>
        <a class="btn" href="{{ G5_URL }}/">처음으로</a>
    </div>
    @endif

</div>
@endsection
