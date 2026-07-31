{{-- 목록 항목의 상태 표시 — 목록 변형 4종이 공용으로 쓴다.
     게시판 설정에 따라 순정 get_list() 가 켜 주는 것만 나타난다 --}}
@if ($it['comment_cnt'])<span class="n">[{{ $it['comment_cnt'] }}]</span>@endif
@if ($it['icon_new'])<span class="badge-new">N</span>@endif
@if ($it['icon_hot'])
<span class="flag hot" title="인기글"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3s4.5 3.6 4.5 8a4.5 4.5 0 0 1-9 0c0-1.4.6-2.6 1.3-3.5.2 1.4 1 2.2 1.8 2.2 1 0 1.6-.9 1.4-2.3A9 9 0 0 0 12 3Z"/></svg><span class="sound_only">인기글</span></span>
@endif
@if ($it['icon_secret'])
<span class="flag" title="비밀글"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8.2 10.5V7.8a3.8 3.8 0 0 1 7.6 0v2.7"/></svg><span class="sound_only">비밀글</span></span>
@endif
@if ($it['icon_file'])
<span class="flag" title="첨부파일"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11.5 12.2 19.3a4.5 4.5 0 0 1-6.4-6.4l8-8a3 3 0 0 1 4.2 4.2l-7.9 8a1.5 1.5 0 0 1-2.1-2.1l7.3-7.3"/></svg><span class="sound_only">첨부파일</span></span>
@endif
@if ($it['icon_link'])
<span class="flag" title="링크"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13.5a3.5 3.5 0 0 0 5 0l3-3a3.5 3.5 0 0 0-5-5l-1.5 1.5"/><path d="M14 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.5 3.5 0 0 0 5 5L12.5 17"/></svg><span class="sound_only">링크</span></span>
@endif
