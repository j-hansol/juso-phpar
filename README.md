우편번호 (주소) 검색용 DB 구축을 위한 Phar 파일 생성
==========================

# Phar 개요

Phar 기능은 응용프로그램을 쉽게 배표하기 위해 하나의 압축 파일에 저장하는 방법을 제공한다. 그리고 한줄의 코드로 tar, zip 및 phar 형식으로 변환할 수 있다. 이렇게 압축된 파일은 웹 또는 명령줄(shell)에서 다른 PHP 파일처럼 쉽게 실행 할 수도 있다.
phar 은 Phar라는 스트림레퍼 객체를 이용해서 생성한다.

# 생성을 위한 준비
PHP의 기본 설정에는 Phar 파일을 읽기만 가능하도록 설정되어 있다. Phar 파일을 생성하려면 이 설정을 변경하여 쓰기가 가능하도록 해야 한다.
아래와 같이 수정한다.
```ini
[Phar]
; http://php.net/phar.readonly
phar.readonly = Off
```

## 프로젝트 생성
나는 아래와 같이 프로젝트 폴더를 생성했다. 실재 응용프로그램은 app 폴더 안에 위치해 있다.
```
project-folder
├── app
│   ├── Lib
│   │   ├── CommandLineParse.php
│   │   ├── Database.php
│   │   └── util.php
│   └── main.php
└── build
```

# 파일 생성을 위한 빌드 프로그램
phar 파일은 아래와 같인 빌드응 포르그램을 이용해야 한다. 아직 빌드 툴이나 위젯이 없다. 아쉽지만 각 응용프로그램에 맞게 빌드파일을 직접 작성하여 이용해야 한다.

```php
<?php
try {
    $phar_path = __DIR__ . "/build/juso.phar";

    if( !file_exists(__DIR__ . '/build') ) mkdir( __DIR__ . '/build' );
    if (file_exists($phar_path . '.gz')) unlink($phar_path . '.gz');
    if (file_exists($phar_path)) unlink($phar_path);

    $phar = new Phar($phar_path);
    $phar->startBuffering();

    $defaultStub = $phar->createDefaultStub('main.php');
    $phar->buildFromDirectory(__DIR__ . '/app');
    $stub = "#!/usr/bin/env php \n" . $defaultStub;

    $phar->setStub($stub);
    $phar->stopBuffering();

    $phar->compressFiles(Phar::GZ);
    echo "$phar_path successfully created" . PHP_EOL;
} catch (Exception $e ) {
    echo $e->getMessage();
}
```

## Phar 빌드 경로 지정
아래와 같이 빌드된 파일이 위치할 경로를 지정한다. 아래의 예는 프로젝트 폴더 아래에 ```build``` 폴더에 ```juso.phar``` 파일에 생성되도록 했다.
```php
$phar_path = __DIR__ . "/build/juso.phar"
```

## 기존 빌드 파일 및 폴더 삭제
빌드 폴더 및 빌드 과정에서 생성된 빌드된 파일과 임시파일과 폴더를 삭제한다.
```php
if( !file_exists(__DIR__ . '/build') ) mkdir( __DIR__ . '/build' );
if (file_exists($phar_path . '.gz')) unlink($phar_path . '.gz');
if (file_exists($phar_path)) unlink($phar_path);
```

## Phar 객체 생성 및 버퍼링 시작
이재 Phar 객체를 아래와 같이 생성하고 압축을 위해 버퍼링을 시작한다. 객체 생성 시 빌드 파일의 경로를 함께 전달한다.
```php
$phar = new Phar($phar_path);
$phar->startBuffering();
```

## 부트로더 코드 생성
압축된 Phar 파일의 포함된 모드가 호출되어 정상 실행되기 위한 부트로더를 아래의 명령으로 생성한다. 아래의 명령으로 ```phar``` 파일의 압축을 해제하고 지정된 파일일 include하는 코드를 생성한다.
```php
$defaultStub = $phar->createDefaultStub('main.php');
```

## 응용프로그램 폴더를 압축파일에 포함 및 부트로더 설정
아래와 같이 ```app``` 펄더를 압축파일에 포함한다. 그리고 부트로더를 포함하여 실행시 응용프로그램이 실행되도록 한다.
```php
$phar->buildFromDirectory(__DIR__ . '/app');
$stub = "#!/usr/bin/env php \n" . $defaultStub;
$phar->setStub($stub);
$phar->stopBuffering();
```

## 압축
아래와 같은 코드로 ```phar``` 확장자를 가진 압축파일을 생성한다.
```php
$phar->compressFiles(Phar::GZ);
```

## 참고
주소 DB 구축 코드는 아래의 링크를 클릭하여 확인한다. 여기서는 오직 Phar 파일 빌드에 대해서만 언급한다.

[주소DB 구축](juso_db.md)
