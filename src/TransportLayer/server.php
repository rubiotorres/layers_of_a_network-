#!/usr/bin/php -q
<?php
error_reporting(E_ALL);
echo "Conexao TCP/IP em PHP\n";
/* Defina a porta */
$porta = 12000;
/* Define o host */
$host = '127.0.0.1'; #gethostbyname("NOMESERVIDOR");
/* Crie um socket */
$socket_servidor = socket_create(AF_INET, SOCK_STREAM, 0);
if ($socket_servidor < 0) {
    print "Nao foi possivel obter socket para conexao com $host\n";
    exit;
}
/* De um bind na porta */
$bind = socket_bind($socket_servidor, $host, $porta);
if ($bind < 0) {
    print "Nao foi possivel fazer BIND no $host:$porta\n";
    exit;
}
$listen = socket_listen($socket_servidor, 5);
if ($listen < 0) {
    print "Nao foi possivel fazer LISTEN no $host:$porta\n";
    exit;
}
$sendquant = 8;
$conexao = 0;
$hits = 0;
print "Aguardando conexoes na porta $porta\n";
while (true) {
    $socket_cliente = socket_accept($socket_servidor);
    if ($socket_cliente < 0) {
        print "Nao foi possivel aceitar conexao com cliente remoto\n";
        break;
    }
    $conexao++;
    print "Conexao numero $conexao\n";
    $funcao = socket_read($socket_cliente, 256);
    if ($funcao) {
        print "Funcao: $funcao\n";
        if ($funcao == 'LER') {
            $msgler = "Funcao Ler";
            socket_write($socket_cliente, $msgler, strlen($msgler));
        } else if ($funcao == 'ESCREVER') {
            $msg = socket_read($socket_cliente, 100);
            if ($msg) {
                print "Mensagem recebida: $msg\n";
            }
        } else if ($funcao == 'RECEBER') {
            $msg = "www.google.com";
            $length = 4;
            $cont = 1;
            while (true) {
                //Escreve os dados
                $sent = socket_write($socket_cliente, $msg, $length);
                //verifica término do envio
                if ($sent === false) {
                    break;
                } elseif ($cont > 1) { //Verifica quanntidade de acertos 
                    $cont = 0;
                    $length = $length + 1; // Caso tenha +2 acertos manda mais 1 
                } elseif ($sent == $length) { //Vrifica tamanho da mssg
                    $cont += 1;
                }

                print("Quantidade de dados enviados: " . $sent . "\n");
                print("Menssagem enviada: " . $msg . "\n");
                print("Acertos: " . $cont . "\n");

                if ($sent > 0) {
                    print("Quantidade de mssg à ser eviada: ".$length."\n");
                    $msg = substr($msg, $sent);
                } else {
                    break;
                }
            }
        } else {
            print "Funcao nao implementada $funcao\n";
        }
        socket_close($socket_cliente);
    }
}
socket_close($socket_servidor);
print "Servidor saindo...\n";
?>