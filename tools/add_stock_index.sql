-- 재고 대기수량 SUM 쿼리용 커버링 인덱스
--
-- get_it_stock_qty / get_option_stock_qty (lib/shop.lib.php) 는
-- it_id, io_id, io_type, ct_stock_use, ct_status 로 걸러 ct_qty 를 SUM 한다.
-- 기존 인덱스는 it_id 단일이라 옵션 하나를 조회해도 해당 상품의 전 행을
-- 테이블에서 읽었다. 아래 인덱스는 조회 조건 전체 + SUM 대상(ct_qty)을
-- 포함하는 커버링 인덱스라 테이블 접근 없이 인덱스만 읽는다.
--
-- 측정 (대기 주문 120만 건 상품 기준):
--   아이템 재고 SUM  2.10s -> 0.45s
--   옵션 재고 SUM    2.03s -> 0.031s
--   옵션 20개 상품 상세 페이지  40.9s -> 0.61s
--
-- 적용: mysql g5pro < tools/add_stock_index.sql

ALTER TABLE g5_shop_cart
  ADD KEY idx_stock (it_id, io_id, io_type, ct_stock_use, ct_status, ct_qty);
