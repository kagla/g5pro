@extends('layout.default')
@section('content')
<div class="member-box center">
    <h2>회원가입 완료</h2>
    <p><strong>{!! $mb_nick !!}</strong>({!! $mb_id !!})님, 회원가입을 축하합니다.</p>
    <p class="muted">로그인 후 서비스를 이용하실 수 있습니다.</p>
    <a class="btn btn-primary" href="{{ G5_BBS_URL }}/login.php">로그인</a>
    <a class="btn" href="{{ G5_URL }}/">처음으로</a>
</div>
@endsection
