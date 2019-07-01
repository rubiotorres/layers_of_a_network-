var net = require('net');
const readline = require('readline-sync')

var routing = new Array();



var server = net.createServer(function(socket) {
	socket.write('Resp servidor\r\n');
	socket.pipe(socket);
});

function out(str){
	'\033[93m' + console.log(str) + '\033[0m';
}

function startServer(){
	createRouteTb();
	showRoutes();
	// enviaPacote();
}

function createRouteTb(){
    var lines = require('fs').readFileSync("NetworkLayer/routing.txt", 'utf-8')
		.split('\n')
		.filter(Boolean);
	
	var i;
	var data;
	for (i = 0; i < lines.length; i++){
		data = lines[i].split('\t');
		routing[i] = new Array();
		routing[i][0] = data[0];
		routing[i][1] = data[1];
		routing[i][2] = data[2];
	}
}	
function showRoutes(){	
	out("IP Rede \t Mascara \t Gateway \n");
	for (i = 0; i < routing.length; i++){
		out(routing[i][0] +
		" \t " + routing[i][1] +
		" \t " + routing[i][2] + "\n");
	}
}

function enviaPacote(){
	var iporigem = readline.question("IP Origem: "),
		maskorigem = readline.question("Mascara Destino: "),
		ipdest = readline.question("IP Destino: ");
		maskdest = readline.question("Mascara Destino: ");
	iporigem = iporigem.split(".",4);
	maskorigem = maskorigem.split(".",4);
	ipdest = ipdest.split(".",4);
	maskdest = maskdest.split(".",4);
	var redeorigem = new Array();
	var rededest = new Array();
	redeorigem[0] = iporigem[0] & maskorigem[0];
	redeorigem[1] = iporigem[1] & maskorigem[1];
	redeorigem[2] = iporigem[2] & maskorigem[2];
	redeorigem[3] = iporigem[3] & maskorigem[3];
	
	redeorigem = redeorigem[0]
	+"."+redeorigem[1]
	+"."+redeorigem[2]
	+"."+redeorigem[3];
	
	rededest[0] = ipdest[0] & maskdest[0];
	rededest[1] = ipdest[1] & maskdest[1];
	rededest[2] = ipdest[2] & maskdest[2];
	rededest[3] = ipdest[3] & maskdest[3];
	
	rededest = rededest[0]
	+"."+rededest[1]
	+"."+rededest[2]
	+"."+rededest[3];
	console.log(redeorigem);
	console.log(rededest);
	var i, j, rede;
	for(i = 0; i < routing.length; i++){
		if(routing[i][0] = rededest){
			break;
		}
	}
	
}

startServer();

	//server.listen(1050, '127.0.0.1');
