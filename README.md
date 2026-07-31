# 그누보드5 프로

그누보드5에 BladeOne 템플릿 엔진을 얹은 배포판입니다. 순정의 동작은 그대로 두고 화면만 스킨 대신 Blade 템플릿으로 그립니다.

## 순정과 다른 점

**화면을 스킨이 아니라 템플릿으로 그립니다.** 순정 화면이 스킨을 부르는 자리에서 전역 변수를 배열로 정리해 Blade 뷰에 넘깁니다. 스킨을 복사해 고치는 대신 뷰 하나를 편집하면 되고, PHP 로직과 화면 코드가 섞이지 않습니다.

**DB 문자셋이 utf8mb4입니다.** 게시글·상품명·닉네임 어디에나 이모지가 들어갑니다.

**날짜에 `0000-00-00`을 쓰지 않습니다.** 값이 없으면 `NULL`입니다. `sql_mode`가 엄격한 환경에서 제로데이트가 일으키던 문제가 없습니다.

**설치 기본값을 정리했습니다.** 쇼핑몰 테이블 접두사가 게시판 접두사를 따라가고(`g5_` → `g5_shop_`), 기본 게시판 네 개의 포인트가 모두 0으로 시작합니다.

## 요구 환경

- PHP 7.4 이상 — BladeOne이 타입 프로퍼티와 화살표 함수를 씁니다
- MySQL 5.7 이상 또는 MariaDB 10.2 이상 — utf8mb4와 InnoDB Dynamic 행 형식
- Apache `mod_rewrite` — 짧은주소를 쓸 경우

## 설치

```bash
mkdir data && chmod 707 data
```

DB를 미리 만든 뒤 브라우저로 `/install`에 접속해 안내를 따릅니다. 문자셋은 설치 과정에서 utf8mb4로 맞춰집니다. 끝나면 `install` 디렉터리를 지웁니다.

## 디렉터리

순정 구조를 그대로 따릅니다. 아래가 이 배포판에서 추가된 부분입니다.

| 경로 | 내용 |
|---|---|
| `template/one/` | 기본 템플릿. 레이아웃·게시판·쇼핑몰 뷰와 스타일시트 |
| `extend/pro.10.extend.php` | 런타임. BladeOne 로드, 템플릿 선택, 렌더 함수 |
| `extend/pro.20.map.extend.php` | 게시판·회원 화면의 전역 변수를 뷰 데이터로 정리 |
| `extend/pro.30.map.shop.extend.php` | 쇼핑몰 화면의 같은 역할 |
| `lib/bladeone/` | BladeOne 템플릿 엔진 |
| `docs/migrations/` | 기존 DB를 옮기는 SQL과 검사 스크립트 |

## 템플릿 만들기

`template/` 아래에 디렉터리를 만들고 `template/one`의 뷰 구성을 따라 작성합니다. 관리자 환경설정에서 사용할 템플릿을 고릅니다.

개발 중에는 `config.php`에 아래를 넣어 설정값보다 우선 적용할 수 있습니다.

```php
define('G5_TEMPLATE', '템플릿명');
```

## 기존 그누보드5에서 옮겨오기

코드는 옛 DB 위에서도 그대로 동작합니다. 날짜를 기록할 때 컬럼이 `NULL`을 받는지 확인하고, 받지 않으면 기존 방식대로 쓰기 때문입니다. 그래서 코드만 먼저 올려도 됩니다.

DB까지 옮기려면 자기 DB에 맞는 SQL을 생성합니다. 실행하지 않고 출력만 하므로 내용을 확인한 뒤 적용하세요.

```bash
php docs/migrations/2026-07-31-utf8mb4-date-null/generate.php > migration.sql

mysqldump -u계정 -p DB명 > backup.sql      # 반드시 백업하고
mysql -u계정 -p DB명 < migration.sql
```

적용 후 `config.php`의 `G5_DB_CHARSET`을 `utf8mb4`로 바꾸고 검사를 돌립니다. 문자셋·제로데이트·소스 상태를 한 번에 확인하며, 이상이 없으면 종료 코드 0입니다.

```bash
php docs/migrations/2026-07-31-utf8mb4-date-null/verify.php
```

## 라이선스

LGPL-2.1. 자세한 내용은 [LICENSE](LICENSE)를 보세요.

번들된 제3자 구성요소는 각자의 라이선스를 따릅니다 — BladeOne(MIT), PHPExcel(LGPL), PHPMailer(LGPL), 스마트에디터2(LGPL), 각 결제사 SDK.

저작자 · (주)에스아이알소프트 · https://sir.kr
