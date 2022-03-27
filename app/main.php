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
