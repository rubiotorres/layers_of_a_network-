#!/usr/bin/php -q
<?php
/* Cliente */
error_reporting(E_ALL);
echo "Conexao TCP/IP em PHP\n";
/* Defina a porta */
$porta = 1053;
/* Define o host */
$host = '127.0.0.1'; #gethostbyname("NOMESERVIDOR");
/* Crie um socket */
$funcao = array("LER", "ESCREVER");
$threeway = 0;
for ($n = 0; $n < 3; $n++) {
    $socket_cliente = socket_create(AF_INET, SOCK_STREAM, 0);
    print "$socket_cliente\n";
    if ($socket_cliente < 0) {
        print "Nao foi possivel obter socket para conexao com $host\n";
        exit;
    }
    /* De um connect na porta */
    $connect = socket_connect($socket_cliente, $host, $porta);
    if ($connect < 0) {
        print "Nao foi possivel conectar no $host:$porta\n";
        exit;
    }
    if ($n < 2) {
        print "Conexao $n $host:$porta Funcao : $funcao[$n]\n";
        socket_write($socket_cliente, $funcao[$n], strlen($funcao[$n]));
    } else {
        print "Conexao $n $host:$porta Funcao : $funcao[0]\n";
        socket_write($socket_cliente, "RECEBER", strlen("RECEBER"));
    }

    switch ($n) {
        case 0:
            if ($threeway == 0) {
                $msg = "SYN";
                socket_write($socket_cliente, $msg, strlen($msg));
                print "Mensagem enviada: $msg\n";
                break;
            }
        case 1:
            if ($threeway == 0) {
                $msg = socket_read($socket_cliente, 100);
                if ($msg) {
                    print "bind request: $msg\n";
                }
                break;
            }

        case 2:
            if ($threeway == 1) {
                $msg = "Binded";
                socket_write($socket_cliente, $msg, strlen($msg));
                print "Mensagem enviada\n";
                break;
            }
        case 3:
            $msg = "";
            do {
                $test = socket_read($socket_cliente, 8);
                echo "Recebida agora: " . $test . "\n";
                $msg = $msg . $test;
            } while ($test);
            if ($msg) {
                print "Mensagem recebida: $msg\n";
            }
            break;
    }
    $threeway++;
    socket_close($socket_cliente);
    print "Conexao fechada\n";
}
print "Cliente finalizou normal";
?>