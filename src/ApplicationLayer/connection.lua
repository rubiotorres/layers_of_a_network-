function run_server_bg(DNS_table, DNS_log, t_channel)
	local threadCode = "server.lua"
	
	local thread = love.thread.newThread(threadCode)
    thread:start(DNS_table, DNS_log, t_channel)
end

function client_test(msg, is_tcp)
	local protocol = "UDP"
	if is_tcp then protocol = 'TCP' end
	
	local my_ip = '127.0.0.1'
	local destination = '169.254.151.170'
    local socket = require("socket")
    local host, port = "127.0.0.1", 1054
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
	
	msg = protocol.."\nDNS Request\n"..destination..":1051\n"..my_ip..":1051\n"..msg
		
    tcp:send(msg.."\n")
	
    tcp:close()
end

function client_test_phy()

end