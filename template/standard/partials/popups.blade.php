@foreach ($popups as $p)
@php $style = 'left:'.(int)$p['left'].'px;top:'.(int)$p['top'].'px;width:'.(int)$p['width'].'px;'; @endphp
<div class="layer-popup" id="pop-{{ $p['id'] }}" style="{{ $style }}">
    <div class="layer-popup-body">{!! $p['content_html'] ? $p['content'] : nl2br($p['content']) !!}</div>
    <div class="layer-popup-foot">
        <label><input type="checkbox" data-hours="{{ $p['disable_hours'] }}" data-id="{{ $p['id'] }}" class="pop-disable"> {{ $p['disable_hours'] }}시간 동안 열지 않기</label>
        <button type="button" class="pop-close" data-id="{{ $p['id'] }}">닫기</button>
    </div>
</div>
@endforeach
