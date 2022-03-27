주소 DB 구축
===========

# 개요
본 프로그램에 사용한 기본 주소 검색 데이터는 우체국의 우편번호 파일을 이용한다. 이 파일은 압축되어 있고, 파일 내용은 UTF-8로 엔코딩되어 저장되어 있다. 본 프로그램은 이 파일을 그대로 읽어 데이터베이스에 저장한다.

# 파일 구성
본 응용프로그램은 Lib 폴더에 데이터베이스 제어용 프로그램과 명령 프롬프트의 내용을 추출하는 프로그램, 유틸리티로 구성되어 있다. 그리고 이들 파일을 포함하여 데이터를 가져오는 기능을 하는 ```main.php``` 파일로 구성되어 있다.

# 테이블 구조
아래 구조는 우체국의 우편번호 데이터의 구조와 일치한다.
```sql
create table juso (
    zipcode         varchar(5)      not null,
    sido            varchar(20)     not null,
    en_sido         varchar(40),
    sigungu         varchar(20)     not null,
    en_sigungu      varchar(40),
    upmyun          varchar(20),
    en_upmyun       varchar(40),
    road_code       varchar(12)     not null,
    road_name       varchar(80)     not null,
    en_load_name    varchar(80),
    below           tinyint         default 0,
    build_no        int,
    build_sub_no    int,
    build_serial_no varchar(25),
    large_delivery  varchar(40),
    sgg_build_name  varchar(200),
    law_dong_code   varchar(10),
    law_dong_name   varchar(20),
    li_name         varchar(20),
    ad_dong_name    varchar(40),
    mountain        tinyint         default 0,
    jibun           int,
    upmyun_serial   varchar(2),
    jibun_sub       int,
    old_zip_code    varchar(6),
    old_zip_serial  varchar(3),
    index road_name_only (road_name),
    index road_name_build_no (road_name, build_no)
)
```

# 매인 프로그램
매인 프로그램의 전체 코드는 아래와 같다.
```php
<?php
include 'Lib/Database.php';
include 'Lib/CommandLineParse.php';
include 'Lib/util.php';

$parse = new CommandLineParse();
$host = $parse->get('h');
$user = $parse->get('u');
$password = $parse->get('p');
$database = $parse->get('d');
$table = $parse->get('t') ?? 'juso';

if( !$host || !$user || !$password || !$database ) {
    echo "Usage {$argv[0]} -h host_url -u user -p password -d database_name -t table_name\n";
    exit(-1);
}

$config = [
    'driver' => 'mysql',
    'database' => $database,
    'username' => $user,
    'password' => $password,
    'host' => $host
];

$db = new Database( $config );
if( !$db->isAble() ) {
    echo "Can not connect to database.\n";
    exit(-1);
}

$sql = "create table $table (
    zipcode         varchar(5)      not null,
    sido            varchar(20)     not null,
    en_sido         varchar(40),
    sigungu         varchar(20)     not null,
    en_sigungu      varchar(40),
    upmyun          varchar(20),
    en_upmyun       varchar(40),
    road_code       varchar(12)     not null,
    road_name       varchar(80)     not null,
    en_load_name    varchar(80),
    below           tinyint         default 0,
    build_no        int,
    build_sub_no    int,
    build_serial_no varchar(25),
    large_delivery  varchar(40),
    sgg_build_name  varchar(200),
    law_dong_code   varchar(10),
    law_dong_name   varchar(20),
    li_name         varchar(20),
    ad_dong_name    varchar(40),
    mountain        tinyint         default 0,
    jibun           int,
    upmyun_serial   varchar(2),
    jibun_sub       int,
    old_zip_code    varchar(6),
    old_zip_serial  varchar(3),
    index road_name_only (road_name),
    index road_name_build_no (road_name, build_no)
)";

$db->exec("drop table if exists $table");
$db->exec( $sql );
$db->exec('set sql_log_bin = 0');

$cur_dir = getcwd();
$files = _getDirectoryEntry($cur_dir, 'file');
$targets = [];
foreach( $files as $key => $file ) {
    if( preg_match('/\.txt$/', $file ) ) $targets[] = $file;
}

if( empty( $targets ) ) {
    echo( "File not found.\n");
    exit;
}

foreach( $targets as $file ) {
    $fd = fopen( $cur_dir . '/' . $file, 'r');
    if( !$fd ) echo "$file : open error\n";
    else {
        $p_count = 0;
        $l_count = 0;
        $buffer = [];
        fgets($fd);
        while( !feof($fd) ) {
            $data = fgets($fd);
            $l_count++;
            if( strlen( $data ) <= 0 ) continue;
            else $c_data = $data;

            if( !$c_data ) continue;
            $c_data = addslashes($c_data);
            $p_data = explode('|', $c_data);
            if( count( $p_data ) != 26 ) continue;
            foreach( $p_data as $key => $val ) $p_data[$key] = "'$val'";
            $buffer[] = '(' . implode(',', $p_data) . ')';
            if( count( $buffer ) >= 2000 ) {
                $cnt = insert( $db, $buffer );
                $buffer = [];
                $p_count += $cnt;
            }
            echo "$file : " . number_format($p_count) . '/' . number_format($l_count) . "\r";
        }
        if( count( $buffer) > 0 ) {
            $cnt = insert( $db, $table, $buffer );
            $buffer = [];
            $p_count += $cnt;
        }
        fclose( $fd );
        echo "$file : " . number_format($p_count) . '/' . number_format($l_count) . "\n";
    }
}

function insert( $db, $table, $array ) {
    $sql = "insert into $table values " . implode(',', $array);
    try {
        $db->exec( $sql );
        return count( $array );
    } catch (Exception $e) {
        return 0;
    }
}
```

# 프로그램 설명

## 기반 프로그램 Include
매인 프로그램의 서두에는 아래와 같이 데이터베이스 제어용, 명령 프롬프트 내용 추출용, 그리고 그 외 유틸리티 프로그램을 가져온다.
```php
include 'Lib/Database.php';
include 'Lib/CommandLineParse.php';
include 'Lib/util.php';
```

## 명령 프롬프트에서 필요한 정보 추출
그 다음 아래와 같이 ```CommandLineParse``` 객체를 생성하여 필요한 정보를 추출한다.
```php
$parse = new CommandLineParse();
$host = $parse->get('h');
$user = $parse->get('u');
$password = $parse->get('p');
$database = $parse->get('d');
$iconv = $parse->get('c') == 'true';
$table = $parse->get('t') ?? 'juso';
```

## 추출정보 검토
명령 프롬프트에서 데이터베이스 호스트, 데이터베이스명, 사용자이름, 비밀번호 정보가 모두 추출되지 않았다면 사용방법 메시지를 출력하고 종료한다.
```php
if( !$host || !$user || !$password || !$database ) {
    echo "Usage {$argv[0]} -h host_url -u user -p password -d database_name -t table_name\n";
    exit(-1);
}
```

## 데이터베이스 연결
필요한 정보가 추출되었다면 아래와 같이 정보를 배열로 저장한 후 데이터베이스 객체를 생성하여 연결한다. 만일 연결에 실페한 경우 오류 메시지를 출력하고 종료한다.
```php
$config = [
    'driver' => 'mysql',
    'database' => $database,
    'username' => $user,
    'password' => $password,
    'host' => $host
];

$db = new Database( $config );
if( !$db->isAble() ) {
    echo "Can not connect to database.\n";
    exit(-1);
}
```

## 테이블 생성
테이블 명령 프롬프트로부터 테이블 이름을 전달 받은 경우 전달된 테이블명으로 테이블을 생성하고 그렇지 않으면 ```juso``` 라는 이름으로 테이블을 생성한다.
```php
$sql = "create table $table (
    zipcode         varchar(5)      not null,
    sido            varchar(20)     not null,
    en_sido         varchar(40),
    sigungu         varchar(20)     not null,
    en_sigungu      varchar(40),
    upmyun          varchar(20),
    en_upmyun       varchar(40),
    road_code       varchar(12)     not null,
    road_name       varchar(80)     not null,
    en_load_name    varchar(80),
    below           tinyint         default 0,
    build_no        int,
    build_sub_no    int,
    build_serial_no varchar(25),
    large_delivery  varchar(40),
    sgg_build_name  varchar(200),
    law_dong_code   varchar(10),
    law_dong_name   varchar(20),
    li_name         varchar(20),
    ad_dong_name    varchar(40),
    mountain        tinyint         default 0,
    jibun           int,
    upmyun_serial   varchar(2),
    jibun_sub       int,
    old_zip_code    varchar(6),
    old_zip_serial  varchar(3),
    index road_name_only (road_name),
    index road_name_build_no (road_name, build_no)
)";

$db->exec("drop table if exists $table");
$db->exec( $sql );
```

## MySQL Binary 로그 기록 제외
MySQL과 MariaDB의 경우 DDL(Create 문 등), DML(Insert, Update, Delete 문 등)의 경우 그 로그를 남긴다. 운용 시에는 이 로그를 이용하여 손상된 데이터베이스를 복원할 수 있다. 그러나 대량의 데이터를 초기 저장할 때에는 이 파일이 기하급수적으로 크지는 문제가 발생한다. 이 문제를 방지하기 위해 로그 기록을 남기지 않도록 해야 한다.
아래의 코드를 이용하여 로그 기록에서 제외하도록 한다.
```php
$db->exec('set sql_log_bin = 0');
```

## 우편번호 파일 목록 정보 추출
현재 응용프로갬이 실행중인 폴더에서 ```~.txt``` 로 끝나는 텍스트 파일 목록을 가져온다. 만일 파일이 없다면 파일이 없다는 오류 메시지를 출력하고 프로그램을 종료한다.
```php
$cur_dir = getcwd();
$files = _getDirectoryEntry($cur_dir, 'file');
$targets = [];
foreach( $files as $key => $file ) {
    if( preg_match('/\.txt$/', $file ) ) $targets[] = $file;
}

if( empty( $targets ) ) {
    echo( "File not found.\n");
    exit;
}
```

## 파일로부터 데이터를 읽어 데이터베이스에 저장
아래의 반복문 구조를 이용하여 파일의 내용을 읽어 데이터베이스에 저장한다. 목록(targets)으로부터 파일 경로를 하나씩 가져와 파일을 읽기모드로 연 후 ```데이터베이스 저장 코드``` 를 이용하여 저장한다.
```php
foreach( $targets as $file ) {
    $fd = fopen( $cur_dir . '/' . $file, 'r');
    if( !$fd ) echo "$file : open error\n";
    else {
        // 데이터베이스 저장 코드
    }
}
```
### 데이 추출 및 버퍼링
내용 추출을 위해 6개의 변수를 이용한다.
```php
$p_count    // 추출한 자료 수
$l_count    // 파일로부터 읽어 들인 라인 수
$buffer     // 파싱된 자료를 데이터베이스 구조에 맞게 변형한 자료 (약 2000개의 자료를 저장)
$data       // 파일로부터 읽은 자료
$c_data     // 엔코딩되 자료 (현재는 필요 없으나 남겨 둠)
```

아래 코드에서 최초 ```fgets($fd)``` 명령은 해더 한줄을 읽어 내기 위한 것이다. 실재 데이터는 두 번째 줄부터 저장되어 있기 때문이다.
```php
$p_count = 0;
$l_count = 0;
$buffer = [];
fgets($fd);
while( !feof($fd) ) {
    // 데이터 추출 및 저장 코드
}
if( count( $buffer) > 0 ) {
    // 버퍼에 남은 데이터 저장
}
fclose( $fd );
echo "$file : " . number_format($p_count) . '/' . number_format($l_count) . "\n";
```

### 데이터 추출 및 저장
데이터 추출 및 저장하는 코드는 아래와 같다. 먼저 파일 빈 줄의 내용을 제외한 내용을 한 줄씩 읽어 ```|``` 문자를 기준으로 분리한 후 SQL의 INSERT 문에 적합하도록 문자열을 생성하여 buffer에 저장한다.
버퍼에 저장된 내용은 ```insert()``` 함수에게 전달되어 최종 데이터베이스에 저장된다. 2000개의 자료를 하나의 묶음으로 만들어 저장한다.
```php
$data = fgets($fd);
$l_count++;
if( strlen( $data ) <= 0 ) continue;
else $c_data = $data;

if( !$c_data ) continue;
$c_data = addslashes($c_data);
$p_data = explode('|', $c_data);
if( count( $p_data ) != 26 ) continue;
foreach( $p_data as $key => $val ) $p_data[$key] = "'$val'";
$buffer[] = '(' . implode(',', $p_data) . ')';
if( count( $buffer ) >= 2000 ) {
    $cnt = insert( $db, $buffer );
    $buffer = [];
    $p_count += $cnt;
}
echo "$file : " . number_format($p_count) . '/' . number_format($l_count) . "\r";
```

### 버퍼에 남은 데이터 저장
파일을 모두 일거 버퍼에 저장하여 파일의 끝에 도달한 경우 아래의 코드에 의해 남은 데이터를 데이터베이스에 저장한다.
```php
if( count( $buffer) > 0 ) {
    $cnt = insert( $db, $table, $buffer );
    $buffer = [];
    $p_count += $cnt;
}
```

### 부수적인 것들
그 외 처리 중인 파일명, 읽은 라인수, 추출 자료 수 등을 각 처리 후반에 출력하도록 해 두었다. 프로그램 흐름상 필요 없는 부분일 수는 있으나 실행 내용을 실시간으로 확인하기 위해 넣어 두었다.