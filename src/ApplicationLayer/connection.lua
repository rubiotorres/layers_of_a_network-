function run_server_bg(DNS_table, DNS_log, t_channel)
	local threadCode = "server.lua"
	
	local thread = love.thread.newThread(threadCode)
    thread:start(DNS_table, DNS_log, t_channel)
end

function client_test(msg)
    local socket = require("socket")
    local host, port = "127.0.0.1", 1054
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
	
	msg = "127.0.0.1\n127.0.0.1\n"..msg
		
    tcp:send(msg.."\n")
	
    local s, status, partial = tcp:receive("*a")
	
    result = s or partial
    tcp:close()
end
