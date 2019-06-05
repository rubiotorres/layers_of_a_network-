function run_server_bg(DNS_table, DNS_log, t_channel)
	local threadCode = "server.lua"
	
	local thread = love.thread.newThread(threadCode)
    thread:start(DNS_table, DNS_log, t_channel)
end

function client_test(msg, is_tcp, destination)
	local protocol = "UDP"
	local destination = destination or HOST..":1051"
	if is_tcp then protocol = 'TCP' end
	
	local my_ip = HOST
    local socket = require("socket")
    local host, port = HOST, PORT + 3
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
	
	msg = protocol.."\nDNS Request\n"..destination.."\n"..my_ip..":"..PORT.."\n"..msg
		
    tcp:send(msg.."\n")
	
    tcp:close()
end

function client_test_phy()

end