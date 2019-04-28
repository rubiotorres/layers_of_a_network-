function run_server_bg(DNS_table, DNS_log, t_channel)
	local threadCode = "server.lua"
	
	local thread = love.thread.newThread(threadCode)
    thread:start(DNS_table, DNS_log, t_channel)
end

function client_test(msg)
	local my_ip = '127.0.0.1'
	local destination = '192.168.10.0'
    local socket = require("socket")
    local host, port = "127.0.0.1", 1054
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
	
	msg = destination.."\n"..my_ip.."\n"..msg
		
    tcp:send(msg.."\n")
	
    tcp:close()
end

function client_test_phy()
	local msg = '101010101010101010101010101010101010101010101010101010101010101111101100110000100010010100100100111101010101100111111000001010000001100110100001111010010101011100000000000111110011000100111001001100100010111000110001001101100011100000101110001100010011000000101110001100000000101000110001001100100011011100101110001100000010111000110000001011100011000100001010011100000110001101011111011010010110011101101111011100100000101001010010010001101000110001000000'
	
	local socket = require("socket")
    local host, port = "127.0.0.1", 51
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
			
    tcp:send(msg)
	
    tcp:close()
end