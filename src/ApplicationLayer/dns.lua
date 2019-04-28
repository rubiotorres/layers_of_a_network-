function sort_ips(DNS_table)
	sorted_ips = {}
	for ip, _ in pairs(DNS_table) do
		table.insert(sorted_ips, ip)
	end
	table.sort(sorted_ips)
	return sorted_ips
end

function resolve_dns(lookup, DNS_table)
	is_reverse = not is_ip(lookup)
	curr = ""
	
	local socket = require("socket")
	local resolved = {}
	
	if is_reverse then
		for ip, name in pairs(DNS_table) do
			if name == lookup then
				curr = ip
				return ip, true
			end
		end
		out("Name not in table, searching...")
		_, resolved = socket.dns.toip(lookup)
		if type(resolved) == 'table' then
			return resolved.ip[1], true
		end
		return nil, true
	else
		if DNS_table[lookup] then
			curr = lookup
			return DNS_table[lookup], false
		end
		local socket = require("socket")
		out("IP not in table, searching...")
		_, resolved = socket.dns.tohostname(lookup)
		if type(resolved) == 'table' then
			return resolved.name, false
		end
		return nil, false
	end
	
	return result, is_reverse
end

function new_request(lookup, DNS_table, DNS_log, origin)
	local origin = origin or "Anon"
	lookup = lookup:gsub("^%s*(.-)%s*$", "%1")
	
	request = {
		lookup = lookup,
		origin = origin,
		timestamp = os.date(),	
	}
	
	request.response, request.reverse = resolve_dns(lookup, DNS_table)
	
	add_log(DNS_log, request)
	return request
end

function add_log(DNS_log, request)
	table.insert(DNS_log, request)
	while #DNS_log > 7 do
		table.remove(DNS_log, 1)
	end
end

function is_ip(ip)
	local chunks = {ip:match("(%d+)%.(%d+)%.(%d+)%.(%d+)")}
    if (#chunks == 4) then
        for _,v in pairs(chunks) do
            if (tonumber(v) < 0 or tonumber(v) > 255) then
                return false
            end
        end
        return true
    else
        return false
    end
end

function out(str)
	str = "--APL-- >> ["..os.date().."] "..str.."\n"
	io.stdout:write(str)
end
