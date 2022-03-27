<?php
/**
 * Created by PhpStorm.
 * User: pig482
 * Date: 30/10/2018
 * Time: 6:14 PM
 */

/**
 * @brief 명령라인으로 실행 시 표준 에러를 출력한다
 * @param $message
 */
function _printSTDError( $message ) {
    if( _isCLI() ) fprintf( STDERR, '%s\n', $message );
    else echo( $message );
    exit();
}

/**
 * @brief 텍스트 파일 내용을 읽어 리턴한다
 * @param $filename
 * @return null|string
 */
function _getTextFileContent( $filename ) {
    if( !file_exists( $filename ) ) return NULL;
    $size = filesize( $filename );
    $fp = fopen( $filename, 'rt' );
    $content = fread( $fp, $size );
    fclose( $fp );
    return $content;
}

/**
 * @brief 텍스트를 파일에 저장한다
 * @param $filename
 * @param $content
 */
function _setTextFileContent( $filename, $content ) {
    $fp = fopen( $filename, 'wt' );
    fwrite( $fp, $content, strlen( $content ) );
    fclose( $fp );
}

/**
 * @brief 지정 디렉토리의 파일 목록을 가져온다
 * @param $path
 * @param $filter
 * @return array
 */
function _getDirectoryEntry( $path, $filter = '' ) {
    if( !is_dir( $path ) ) return array();

    $entries = array();

    if( $dp = opendir( $path ) ) {
        while( ($entiry = readdir( $dp ) ) !== FALSE ) {
            if( !empty( $filter ) && filetype( $path . '/' . $entiry ) == $filter ) $entries[] = $entiry;
        }
        closedir( $dp );
    }
    return $entries;
}

/**
 * @brief 특정 파일 또는 폴더가 쓰기 가능 여부를 판단한다
 * @param $path
 * @return bool
 */
function _isWriteAble( $path ) {
    if( !file_exists( $path ) ) return FALSE;
    $perm = fileperms( $path );
    return ( ($perm & 0444) == 0444 );
}

/**
 * @brief 현재 응용프로그램이 명령줄을 이용하여 실행하였는지 그렇지 않을지를 판단한다
 * @return bool
 */
function _isCLI() {
    if( defined('STDIN') ) return TRUE;
    if( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) return TRUE;

    return FALSE;
}