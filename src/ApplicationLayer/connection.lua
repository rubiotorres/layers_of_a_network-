function client(msg)
    local socket = require("socket")
    local host, port = "127.0.0.1", 54
    local tcp = assert(socket.tcp())

    tcp:connect(host, port)
	tcp:settimeout(1)
    tcp:send(msg.."\n")

    local s, status, partial = tcp:receive("*a")
    result = s or partial
    tcp:close()
end

function run_server_bg(DNS_table, DNS_log, t_channel)
	local threadCode = [[
		require("dns")
		
		local my_ip = '192.168.10.0'
		local layer_port = 51
		
		local DNS_table, DNS_log, t_channel = ...
		local socket = require("socket")
		local server = assert(socket.bind("127.0.0.1", 54))
		local tcp = assert(socket.tcp())
		
		print ("Server thread running, listening...")

		while 1 do
			local client = server:accept()
			line = client:receive()
						
			request = new_request(line, DNS_table, DNS_log)
						
			t_channel:push(request)
			
			local dest_ip, dest_port = client:getpeername()
			
			message = my_ip.."\n"..dest_ip..":"..dest_port.."\n"
						
			if request.response then
				message = message..request.response.."\n"
			else
				message = message.."Not found\n"
			end
			
			client:send(message)
			client:close()
		end
	]]
	thread = love.thread.newThread(threadCode)
    thread:start(DNS_table, DNS_log, t_channel)
end
