function add_entry(routes, net, mask, gate)
	table.insert(routes, 1, {net, mask, gate})
end

function rm_entry(routes, index)
	table.remove(routes, index)
end

function load_routes()
	local routes = {}
	local file = io.open("./NetworkLayer/routing.txt", "rb")
	local contents = file:read "*a"
	print (contents)
	local rows = split(contents, "\n")
	local data = {}
	
	for index, row in ipairs(rows) do
		data = split(row, "\t")
		routes[index] = {data[1], data[2], data[3]}
	end
	
	io.close(file)
	
	return routes
end

function save_routes(routes)
	local file = io.open("NetworkLayer/routing.txt", "w")
	
	for index, row in ipairs(routes) do
		file:write(row[1].."\t"..row[2].."\t"..row[3].."\n")
	end
	
	io.close(file)
end

function split(inputstr, sep)
        local t={}
		local sep = sep or ":"
        for str in string.gmatch(inputstr, "([^"..sep.."]+)") do
                table.insert(t, str)
        end
        return t
end