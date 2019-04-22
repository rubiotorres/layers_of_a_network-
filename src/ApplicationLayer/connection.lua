function client()
    local socket = require("socket")
    local host, port = "192.168.100.47", 53
    local tcp = assert(socket.tcp())

    tcp:connect(host, port);
    tcp:send("hello world\n");

    while true do
        local s, status, partial = tcp:receive()
        print(s or partial)
        if status == "closed" then
        break
        end
    end

    tcp:close()
end
function server()
    local socket = require("socket")
    local server = assert(socket.bind("*", 53))
    local tcp = assert(socket.tcp())

    print(socket._VERSION)
    print(tcp)

    while 1 do

        local client = server:accept()
        line = client:receive()
        client:send("it works\n")

    end
end