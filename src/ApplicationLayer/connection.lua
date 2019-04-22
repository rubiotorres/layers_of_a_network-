function client(msg)
    local socket = require("socket")
    local host, port = "127.0.0.1", 1234
    local tcp = assert(socket.tcp())

    tcp:connect(host, port);
    tcp:send(msg.."\n");

    local s, status, partial = tcp:receive()
	print(s or partial)

    tcp:close()
end

function server()
    local socket = require("socket")
    local server = assert(socket.bind("*", 1234))
    local tcp = assert(socket.tcp())

    print(socket._VERSION)
    print(tcp)

    while 1 do

        local client = server:accept()
        line = client:receive()
		request= new_request(line, DNS_table, DNS_log)
        print(line)
        if request.response then
            client:send(request.response.."\n")
        else
            client:send("Not found\n")
        end
		client:close()

    end
end