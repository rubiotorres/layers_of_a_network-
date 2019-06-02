function run_server_bg(DNS_table, DNS_log, t_channel)
	local threadCode = "server.lua"
	
	local thread = love.thread.newThread(threadCode)
    thread:start(DNS_table, DNS_log, t_channel)
end

function client_test(msg)
	local my_ip = '127.0.0.1'
	local destination = '192.168.1.4'
    local socket = require("socket")
    local host, port = "192.168.1.4", 1054
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
	
	msg = "DNS Request\n"..destination..":1051\n"..my_ip..":1051\n"..msg
		
    tcp:send(msg.."\n")
	
    tcp:close()
end

function client_test_phy()
	local msg = ''
	
	local socket = require("socket")
    local host, port = "192.168.1.4", 1051
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
			
    tcp:send(msg)
	
    tcp:close()
end