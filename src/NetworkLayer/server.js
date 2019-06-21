var net = require('net');
var routing = new Array();
const readline = require('readline-sync')
var server = net.createServer(function(socket) {
	socket.write('Resp servidor\r\n');
	socket.pipe(socket);
});
function criaRoteamento(){
    var rotas = readline.question("Numero de rotas: ");
	var i;
	var rede, mask, gateway;
	for (i = 0; i < rotas; i++){
		rede = readline.question(i+1 + " IP Rede: ");
		mask = readline.question(i+1 + " Mascara: ");
		gateway = readline.question(i+1 + " Gateway: ");
		routing[i] = new Array();
		routing[i][0] = rede;
		routing[i][1] = mask;
		routing[i][2] = gateway;
		console.log("\n");
	}
	console.log("IP Rede \t Mascara \t Gateway \n")
}	
function printRoteamento(){	
	for (i = 0; i < routing.length; i++){
		console.log(routing[i][0] +
		" \t " + routing[i][1] +
		" \t " + routing[i][2]);
		console.log("\n");
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

criaRoteamento();
printRoteamento();
enviaPacote();

	//server.listen(1050, '127.0.0.1');
