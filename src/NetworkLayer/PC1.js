var net = require('net');
const WebSocket = require('ws')
const readline = require('readline-sync')

var routing = new Array();



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

function start(){
	out("PC1 started")
	createRouteTb();
	showRoutes();
	
	enviaPacote();
}

function createRouteTb(){
    var lines = require('fs').readFileSync("routing.txt", 'utf-8')
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
	out("IP Rede \t Mascara \t Gateway");
	for (i = 0; i < routing.length; i++){
		out(routing[i][0] +
		" \t " + routing[i][1] +
		" \t " + routing[i][2]);
	}
}


function enviaPacote(){
	var ipenvio, i,
		gateway, ipdestino,
		iporigem = readline.question("IP Origem: "),
		maskorigem = readline.question("Mascara origem: "),
		ipdest = readline.question("IP Destino: ");
	ipdestino = ipdest;
	iporigem = iporigem.split(".",4);
	maskorigem = maskorigem.split(".",4);
	ipdest = ipdest.split(".",4);
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
	out(redeorigem);
	out(rededest);
	if(rededest == redeorigem){
		out('Enviando para pc...');
		var connection = new WebSocket('ws://'+ipdestino+':9090');
	}
	else{
		for(i=0; i < routing.length; i++){
			if(rededest == routing[i][0]){
				ipdestino = routing[i][2];
				out('Enviando para gateway...');
				var connection = new WebSocket('ws://'+ipdestino+':8080');
				break;
			}
			if(i = routing.length -1){
				out('Enviando para deafult gateway...');
				ipdestino = routing[routing.length - 1][2];
				var connection = new WebSocket('ws://'+ipdestino+':8080');
			}
		}
	}
	var version, hlenght, servicetype, tlenght, id, flags, offset, ttl, 
	protocol, checksum, data;
	ttl = 64;
	connection.onopen = () => {
	  connection.send(iporigem+ipdestino+ttl) 
	}
	connection.onerror = (error) => {
	  out(`WebSocket error: ${error}`)
	}
	connection.onmessage = (e) => {
	  out(e.data)
	}
}
start();
