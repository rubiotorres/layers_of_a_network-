require("dns")

local DNS_table, DNS_log, t_channel = ...
local socket = require("socket")
local server = assert(socket.bind("127.0.0.1", 1054))

function split(inputstr)
	local t={}
	for str in string.gmatch(inputstr, "([^\n]+)") do
		table.insert(t, str)
	end
	return t
end

function send_response(response)
	local socket = require("socket")
	local host, port = "127.0.0.1", 1053
	local tcp = assert(socket.tcp())
	tcp:connect(host, port)
	tcp:settimeout(1)
	
	out("Sending response to layer below...\n\nResponse:\n"..response)
	
	local ok = tcp:send(response)
	if not ok then
		out("Error sending to layer below - Layer unavailable!\n")
	else
		out("Sent to layer below!\n")
	end
	tcp:close()
end

out("Server thread running, listening...")

while 1 do
	local client = server:accept()
	
	local is_request = client:receive() == "DNS Request"
	local dest_ip, my_ip, lookup = client:receive(), client:receive(), client:receive()
	client:close()
	
	if not is_request then return end
	
	out("Received connection from layer below.\n\nSegment: \nDNS Request\nSource: "..dest_ip.."\nDestination: "..my_ip.."\nMessage: "..lookup.."\n")

	request = new_request(lookup, DNS_table, DNS_log, dest_ip)							
	t_channel:push(request)
	
	out("Resolved DNS: "..lookup.." - "..(request.response or "Not found :/"))
	
	message = "DNS Response\n"..my_ip.."\n"..dest_ip.."\n"
				
	if request.response then
		message = message..request.response.."\n"
	else
		message = message.."Not found :/\n"
	end	
	
	send_response(message)
end
