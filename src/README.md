# src 디렉토리
프레임워크를 구성하는 클래스 파일들이 있는 디렉토리이다.

## Controller
`Controller`클래스는 비즈니스 로직을 정의하는 클래스이다. `Model`과 `View`를 중개한다.

`BasicController`는 `app`폴더에서 사용되는 커스텀 컨트롤러 클래스의 부모 클래스다. `app`폴더에 있는 `Controller`클래스가 이 `BasicController`를 상속하고, 다른 모든 커스텀 컨트롤러들은 이 `Controller`클래스를 상속해야 한다.

상속의 편의성을 위해 `app`폴더에 빈 `Controller`클래스를 생성해 둔 것이다. 따라서 커스텀 컨트롤러를 구현할 때에는 굳이 `src`에 있는 `Controller`클래스를 상속할 필요가 없다.

다만, 현재 `BasicController`는 아무 기능도 없다. 나중에 컨트롤러에 여러 기능을 추가할 때를 대비해 미리 만들어둔 것이다.
## DB
`DB`클래스는 데이터베이스 접속을 담당하는 클래스이다. 데이터베이스 정보는 `config`폴더에 있는 `db.php`파일에서 설정한다.

## File
`File`클래스는 파일을 다루는 클래스이다. 아직 구현하지 않았다.

## Injector
`Injector`클래스는 의존성 주입을 담당하는 클래스이다. 객체결합으로 사용되어 의존성이 필요한 객체의 경우, 생성자에 보통 해당 객체 인스턴스르 넘겨주도록 구현되어 있다. 따라서 이런 귀찮은 작업을 대신 해준다.

또한 라라벨에서와 같이, 컨트롤러 메서드에 타입힌트로 `Request`나 `Model`등을 명시만 해놓으면 알아서 주입되어 메서드 내 로직에서 `resolve`된 객체를 바로 사용할 수 있게 도와줄 수 있다.

## Kernel
`Kernel`클래스는 프레임워크의 라이프 사이클을 담당하는 클래스로, 이 `Kernel`클래스가 실질적인 프레임워크라고 볼 수 있다.

`public`폴더의 `index.php`를 보면 알 수 있지만

```php
require __DIR__ . '\\..\\bootstrap\\bootstrap.php';

$kernel = new \Pofol\Kernel\Kernel;

$kernel->app();
```

이렇게 매우 단순한 코드로 이루어져 있다.

1. 부트스트래핑을 한다.
2. `Kernel`객체를 생성한다.
3. `Kernel`객체의 `app`메서드를 실행한다.

즉, `Kernel`클래스의 `app`메서드로 프레임워크가 구동된다. 그리고 `app`메서드는 각 라이프 사이클에 따라 필요한 요소들을 구동시킨다.

## Middleware
`Middleware`클래스는 `Request`가 `Controller`에 닿기 전에 우선적으로 `Request`에 대한 필터링을 수행하는 클래스다.

모든 `Request`에 대해 처리해야 할 일이 있다면 `Middleware`클래스로 수행하면 된다.

`Controller`와 비슷하게, 모든 커스텀 `Middleware`클래스는 `app\Middleware`에 있는 `Middleware`클래스를 상속해야 한다.

## PofolService
`PofolService`클래스는 프레임워크 라이프 사이클에서 핵심 로직을 담당하는 요소들이 구현해야 할 인터페이스이다.

`Kernel`클래스는 내부 메서드로 해당 요소의 `boot()` 메서드를 실행시키기 때문이다.

```php
protected function bootRouter()
{
    $router = new Router($this->request);
    return $router->boot();
}

protected function bootMiddleware()
{
    $middleware = new Middleware($this->request);
    return $middleware->boot();
}

protected function bootController(Route $route)
{
    $controller = new Controller($route);
    return $controller->boot();
}
```

## Request
`Request`클래스는 `php`의 `$_SERVER` `$_POST` `$_GET` 등 요청사이클을 객체 인터페이스로 사용할 수 있게 도와준다.

## Response
`Response`클래스는 `Controller`에서 리턴되어야 할 객체로, 응답헤더, 응답코드 등을 설정하여 결과적으로 클라이언트에게 응답을 보내준다.

## Router
`Router`클래스는 사용자 요청에 대해 라우팅을 수행한다. 라라벨에서는 `/{somethig}/`과 같이 `placeholder`를 설정하지만, 개인적으로는 `express`스타일인 `/:placeholder/`가 더 맘에 들어서 `:`로 구현하였다.

## Session
`Session`클래스는 `$_SESSION`을 객체 인터페이스로 사용할 수 있게 도와준다. 아직 구현하지 않았다.

## Support
`Support` 디렉터리에는 헬퍼클래스들이 정의되어 있다. 현재는 문자열 관련 헬퍼메서드들을 담은 헬퍼클래스인 `Str` 만이 존재한다.
## View
`View`클래스는 라라벨의 `Blade`템플릿과 비슷하게 뷰를 템플릿으로 구현할 수 있게 컴파일러 엔진을 구현하고 이를 이용해서 웹문서를 만들어주는 클래스다.

템플릿 문법은 `Blade`와 거의 비슷하다.