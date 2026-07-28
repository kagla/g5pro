@extends('layout.default')
@section('content')
<h2 class="sound_only">최신글</h2>
<div class="latest-grid">
    @include('partials.latest', ['bo_table' => 'notice', 'rows' => 6])
    @include('partials.latest', ['bo_table' => 'free',   'rows' => 6])
    @include('partials.latest', ['bo_table' => 'qa',     'rows' => 6])
    @include('partials.latest', ['bo_table' => 'gallery','rows' => 6])
</div>
@endsection
