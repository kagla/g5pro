{{-- 찜 하트 — 구매 버튼 줄 안(품절이라 폼이 없으면 홀로) 두 자리에서 같은 모양으로 쓰려고
     한 곳에 모았다. 상태(채운 하트·개수)는 아래 스크립트가 눌릴 때마다 고쳐 그린다.
     구매 폼 안에 들어가므로 type=button 이 필수다 — submit 이면 하트가 주문을 보낸다. --}}
<button type="button" class="wish-btn {{ $wish_on ? 'is-on' : '' }}" id="cart_wish"
        data-it-id="{{ $item['it_id'] }}"
        aria-pressed="{{ $wish_on ? 'true' : 'false' }}"
        title="{{ $wish_on ? '찜 취소' : '찜하기' }}">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20.4 4.5 13a4.7 4.7 0 0 1 6.6-6.7l.9.9.9-.9A4.7 4.7 0 0 1 19.5 13Z"/></svg>
    <span class="wish-btn-label">{{ $wish_on ? '찜함' : '찜하기' }}</span>
    <span class="wish-btn-n" {{ $wish_count ? '' : 'hidden' }}>{{ number_format($wish_count) }}</span>
</button>
