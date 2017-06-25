# Portfolio Framework

## 목적
* 프레임워크를 직접 구현하면서 스킬을 향상시킨다.
* 일자리를 구하자!!

## 개발 환경
* Bitnami WAMP Stack
* PHP 5.6
* MySQL 5.6

## 개발 도구
* [PHPStorm](https://www.jetbrains.com/phpstorm/)

## 목표
* 전반적으로 라라벨을 사용하면서 썼던 기능들을 구현하고자 노력했음.
* 따라서 라라벨 기능을 최대한 비슷하게 구현하는 것이 목표임.
* 궁극적으로 언젠가 라라벨과 같은 프레임워크를 구현하는 것이 목표

## 특징
* 오토로드 기능을 직접 구현해보고자 컴포저 오토로드를 사용하지 않았음.
* [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/), [PSR-4](http://www.php-fig.org/psr/psr-4/)를 준수하고자 노력하였음.

## 프로젝트 구조
* app - 컨트롤러, 미들웨어 커스텀 폴더
    * Controller
    * Middleware
* bootstrap - 오토로드, 헬퍼함수 등
* config - 프레임워크 전반적인 설정들(DB 정보 등)
* public - 웹 루트 폴더
* route - 라우팅
* src - 프레임워크에서 사용되는 클래스들
    * Controller
    * DB
    * Injector - 의존성 주입 도구
    * Kernel - 프레임워크 라이프 사이클 담당
    * Middleware
    * PofolService - 라이프 사이클 요소들의 인터페이스
    * Request
    * Response
    * Router
    * Session
    * View
        * Compiled - 컴파일된 템플릿 파일 저장소
        * PFTEngine - 템플릿 컴파일 엔진
* view - 뷰 파일