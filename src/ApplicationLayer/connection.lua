function client()
    local socket = require("socket")
    local host, port = "127.0.0.1", 1234
    local tcp = assert(socket.tcp())

    tcp:connect(host, port);
    tcp:send("hello world\n");

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
        client:send("it works\n")
		client:close()

    end
end