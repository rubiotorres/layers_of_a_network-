var net = require('net');

var client = new net.Socket();
client.connect(1050, '127.0.0.1', function() {
	console.log('Connected');
});

client.on('data', function(data) {
	console.log('Recebido: ' + data);
	client.destroy(); // kill client after server's response
});

client.on('close', function() {
	console.log('Encerrada');
});
