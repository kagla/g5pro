<?php
// 예약 모듈 이니시스 페이지 공통 — booking/inicis/ 안의 모든 페이지가 첫 줄에서 include 한다.
// shop/inicis/_common.php 와 같은 자리, 같은 이유로 있는 파일이다.
//
// 한 단계 위(booking/_common.php)를 바로 부르면 될 것 같지만, 이 파일이 이 디렉터리에
// 있어야 하는 진짜 이유는 순정 bbs/alert.php 다. 그 파일은 './_common.php' 를 include 하고
// PHP 는 './' 경로를 부르는 파일이 아니라 작업 디렉터리(= 여기) 기준으로 푼다. 파일이 없으면
// alert() 를 부를 때마다 include 경고가 결제 결과 화면에 그대로 찍힌다.
include_once(dirname(__DIR__).'/_common.php');
