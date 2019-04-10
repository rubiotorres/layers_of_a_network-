<?php
error_reporting(E_ALL);

echo "<h2>Conexão TCP/IP</h2>\n";

/* Obtem a porta para o serviço WWW. */
$service_port = 9000;#getservbyname('www', 'tcp');

/* Obtem o endereço IP para o host alvo. */
$address = gethostbyname('0.0.0.0');

/* Cria o socket TCP/IP  */
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socket_create() falhou: razao: " . socket_strerror(socket_last_error()) . "\n";
} else {
    echo "OK.\n";
}

echo "Tentando se conectar a '$address' na porta '$service_port'...";
$result = socket_connect($socket, $address, $service_port);
if ($result === false) {
    echo "socket_connect() falhou.\nRazao: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
} else {
    echo "OK.\n";
}

echo "Enviando solicitação HEAD HTTP...";
socket_write($socket, $in, strlen($in));
echo "OK.\n";

echo "Resposta de leitura:\n\n";
while ($out = socket_read($socket, 2048)) {
    echo $out;
}
echo "Fechando socket...";
socket_close($socket);
echo "OK.\n\n";
?>