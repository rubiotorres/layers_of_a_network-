var net = require('net');
const readline = require('readline-sync')

var routing = new Array();



var server = net.createServer(function(socket) {
	socket.write('Resp servidor\r\n');
	socket.pipe(socket);
});

function out(str){
	var timestamp = "[" + formatDate() + "]"
	console.log("\x1B[33m--RED-- >>", timestamp ,str, '\x1B[0m');
}

function formatDate(){
	var date = new Date();
	var formatted = "" +
		(date.getMonth() + 1).toString().padStart(2, "0") + "/" +
		date.getDay().toString().padStart(2, "0") + "/" +
		(1900 + date.getYear()).toString().substring(2) + " " +
		date.getHours().toString().padStart(2, "0") + ":" +
		date.getMinutes().toString().padStart(2, "0") + ":" +
		date.getSeconds().toString().padStart(2, "0");
	return formatted;
}

function startServer(){
	out("Server listening...")
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
	var ipenvio, i,
		ipgateway,
		iporigem = readline.question("IP Origem: "),
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
	
	rededest[0] = ipdest[0] & maskorigem[0];
	rededest[1] = ipdest[1] & maskorigem[1];
	rededest[2] = ipdest[2] & maskorigem[2];
	rededest[3] = ipdest[3] & maskorigem[3];
	
	rededest = rededest[0]
	+"."+rededest[1]
	+"."+rededest[2]
	+"."+rededest[3];
	console.log(redeorigem);
	console.log(rededest);
	if (rededest == redeorigem)
		ipenvio = iporigem;
	else{
		for(i=0; i < routing.length; i++)
			if(rededest == routing[i][0])
				gateway = routing[i][2];
			if(i = routing.length -1)
				gateway = routing[routing.length - 1][2];
	}
		
	
}

startServer();
