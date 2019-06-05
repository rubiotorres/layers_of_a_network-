function split(inputstr)
        local t={}
        for str in string.gmatch(inputstr, "([^:]+)") do
                table.insert(t, str)
        end
        return t
end

local file = io.open("./config.txt", "rb")
local contents = split(file:read "*a")
file:close()

HOST = contents[1]
PORT = contents[2] + 1

require("dns")

local DNS_table, DNS_log, t_channel = ...
local socket = require("socket")
local server = assert(socket.bind(HOST, PORT + 3))

function split(inputstr)
	local t={}
	for str in string.gmatch(inputstr, "([^\n]+)") do
		table.insert(t, str)
	end
	return t
end

function send_response(response)
	local socket = require("socket")
	local host, port = HOST, PORT + 2
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

while 1 do
	out("Server listening...")
	local client = server:accept()
	
	local protocol = client:receive()
	local is_request = client:receive() == "DNS Request"
	local dest_ip, my_ip, lookup = client:receive(), client:receive(), client:receive()
	client:close()
	
	if not is_request then 
		out("Received connection. DNS Response, discarding...")
	else
		out("Received connection.\n\nSegment: \n"..protocol.."\nDNS Request\nSource: "..dest_ip.."\nDestination: "..my_ip.."\nMessage: "..lookup.."\n")

		request = new_request(lookup, DNS_table, DNS_log, dest_ip)							
		t_channel:push(request)
		
		out("Resolved DNS: "..lookup.." - "..(request.response or "Not found :/"))
		
		message = protocol.."\nDNS Response\n"..my_ip.."\n"..dest_ip.."\n"
					
		if request.response then
			message = message..request.response.."\n"
		else
			message = message.."Not found :/\n"
		end	
		
		send_response(message)
	end
end
