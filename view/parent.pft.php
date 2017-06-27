<!doctype html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Hello, World!</title>
</head>
<body>
<header>
    @yield('header')
</header>
<section>
    @yield('section')
</section>
<footer>
    @yield('footer')
</footer>
<h1>변수 a의 값은 {{ $a }}</h1>
</body>
</html>