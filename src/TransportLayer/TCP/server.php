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

#do {
    if (($msgsock = socket_accept($sock)) === false) {
        echo "socket_accept() falhou: razao: " . socket_strerror(socket_last_error($sock)) . "\n";
        #break;
    }
    /* Envia instruções. */
    $msg="oi";
    $length = 4;
    $cont=1;
    while (true) {
        
        $sent = socket_write($msgsock, $msg, $length);
            
        if ($sent === false) {
        
            break;
        }
        elseif($cont>2){
            echo "ou";
            $cont=0;
            $length=$length+1;
        }
        elseif($sent==$length){
            $cont+=1;
        }
        
        print($sent);
        printf("\n");
        print($msg);
        printf("\n");
        print($sent);

        // Check if the entire message has been sented
        if ($sent>0) {
                
            // If not sent the entire message.
            // Get the part of the message that has not yet been sented as message            print($length);
            print($length);

            $msg = substr($msg, $sent);
            // Get the length of the not sented part
/*             $length -= $sent;
 */
        } else {
            
            break;
        }
            
    }            

socket_close($sock);
?>