kcaptcha 폰트 스프라이트
========================

이 폴더의 PNG 는 폰트 파일이 아니라 미리 구워 놓은 글자 그림판이다.
kcaptcha 는 이미지 한 장을 그릴 때 이 중 하나를 무작위로 골라 쓴다.
종류가 많을수록 한 벌만 보고 맞춘 풀이기가 흔들린다.

형식
----
- 0행은 글자 경계 표시줄이다. 글자마다 불투명 구간을 두어 시작·끝 x 를 알린다.
  칸 사이는 투명해야 하고, 마지막 글자 뒤에도 투명 여백이 있어야 인식이 닫힌다.
- 1행부터가 글자 그림이다. 배경은 투명, 글자는 검정이다.
- kcaptcha_config.php 의 $alphabet 순서대로 36자(0~9, a~z)가 다 들어 있어야 한다.
- 속이 빈 윤곽선으로 그린다. 꽉 찬 검정 덩어리는 알아보기 쉬워서
  넣어 봐야 평균 난도만 내려간다.

원래 있던 것 (그누보드5 순정)
-----------------------------
  palatino_linotype_bold.png
  perpetua_bold.png
  times_bold.png

260807 에 추가한 것
-------------------
비회원 게시판에 붙던 스팸봇이 숫자 캡차를 매번 한 번에 맞히길래
글자 모양의 가짓수를 늘렸다. 재배포 가능한 자유 폰트로 구웠다.

  dejavu_sans_bold.png       DejaVu Sans Bold
                             Copyright (c) 2003 Bitstream, Inc.
                             Bitstream Vera 라이선스 (DejaVu 변경분은 퍼블릭 도메인)
                             원본: /usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf

  dejavu_serif_bold.png      DejaVu Serif Bold
                             위와 같은 라이선스
                             원본: /usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf

  liberation_sans_bold.png   Liberation Sans Bold
                             Copyright (c) 2012 Red Hat, Inc.
                             SIL Open Font License 1.1
                             원본: /usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf

다시 굽는 법
------------
  php tools/kcaptcha_make_font.php <ttf> <출력.png> [숫자높이=35] [outline|solid]

예:
  php tools/kcaptcha_make_font.php \
      /usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf \
      plugin/kcaptcha/fonts/dejavu_sans_bold.png

새 폰트를 넣은 뒤에는 숫자 10칸에 잉크가 들어갔는지, 36자 경계가 다 닫혔는지
확인해야 한다. 하나라도 어긋나면 캡차가 통째로 깨진다.
