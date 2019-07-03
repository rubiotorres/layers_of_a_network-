var net = require('net');
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
function start(){

	out("Gateway started");
	createRouteTb();
	showRoutes();
	const WebSocket = require('ws')
	 
	const wss = new WebSocket.Server({ port: 8080 })
	 
	wss.on('connection', ws => {
	  ws.on('message', message => {
		out(`Received message => ${message}`)
	  })
	  ws.send('Package received by gateway')
	})
}
start();