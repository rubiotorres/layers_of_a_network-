#!/usr/local/bin/php -q
<?php
error_reporting(E_ALL);

/* Permite que o script fique por aí esperando conexões. */
set_time_limit(0);

/* Ativa o fluxo de saída implícito para ver o que estamos recebendo à medida que ele vem. */
ob_implicit_flush();

$address = '0.0.0.0';
$port = 9000;

if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket_create() falhou: razao: " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() falhou: razao: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket_listen() falhou: razao: " . socket_strerror(socket_last_error($sock)) . "\n";
}

do {
    if (($msgsock = socket_accept($sock)) === false) {
        echo "socket_accept() falhou: razao: " . socket_strerror(socket_last_error($sock)) . "\n";
        break;
    }
    /* Envia instruções. */
    $msg=1024*1024*1024*1024;
    $length = strlen($msg);
        
    while (true) {
        
        $sent = socket_write($msgsock, $msg, $length);
            
        if ($sent === false) {
        
            break;
        }
            
        // Check if the entire message has been sented
        if ($sent < $length) {
                
            // If not sent the entire message.
            // Get the part of the message that has not yet been sented as message
            $msg = substr($msg, $sent);
            print($msg);
            // Get the length of the not sented part
            $length -= $sent;

        } else {
            
            break;
        }
            
    }

    do {
        if (false === ($buf = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
            echo "socket_read() falhou: razao: " . socket_strerror(socket_last_error($msgsock)) . "\n";
            break 2;
        }
        if (!$buf = trim($buf)) {
            continue;
        }
        if ($buf == 'quit') {
            break;
        }
        if ($buf == 'shutdown') {
            socket_close($msgsock);
            break 2;
        }
        $talkback = "PHP: Você disse: '$buf'.\n";
        socket_write($msgsock, $talkback, strlen($talkback));
        echo "$buf\n";
    } while (true);
    socket_close($msgsock);
} while (true);

socket_close($sock);
?>