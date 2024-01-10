<?php

use Monolog\Logger;

function checkSession()
{
    return isset($_SESSION['user']);
}

function loggerRegister($message = '', $level = 'info')
{
    // creates a log channel
    $log = new Logger('BNG_LOGS');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('logs.log'));

    // adds a logs register level condicioned
    switch ($level) {
        case 'info':
            $log->info($message);
            break;
        case 'notice':
            $log->notice($message);
            break;
        case 'warning':
            $log->warning($message);
            break;
        case 'error':
            $log->error($message);
            break;
        case 'critical':
            $log->critical($message);
            break;
        case 'alert':
            $log->alert($message);
            break;
        case 'emergency':
            $log->emergency($message);
            break;
        default:
            $log->info($message);
            break;
    }
}

function printData($data, $die = true)
{
    echo '<pre>';
    if(is_object($data) || is_array($data)){
        print_r($data);
    } else {
        echo $data;
    }

    if($die){
        die('<br>FIM</br>');
    }
}

function aes_encrypt($value)
{
    return bin2hex(openssl_encrypt($value, 'aes-256-cbc', OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV));
}
function aes_decrypt($value)
{

    if ((strlen($value)) % 2 != 0){
        return false;
    }
    return openssl_decrypt(hex2bin($value), 'aes-256-cbc', OPENSSL_KEY, OPENSSL_RAW_DATA, OPENSSL_IV);
}

function getActiveUsername()
{
    return $_SESSION['user']->name;
}