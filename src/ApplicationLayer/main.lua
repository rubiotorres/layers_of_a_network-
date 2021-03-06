function split(inputstr)
        local t={}
        for str in string.gmatch(inputstr, "([^:]+)") do
                table.insert(t, str)
        end
        return t
end

local file = io.open("./config.txt", "rb")
local contents = split(file:read "*a")
file:close()

HOST = contents[1]
PORT = contents[2] + 1

require "dns"
require "initial"
require "connection"
require "route_conf"

DNS_table = {}
sorted_ips = {}
DNS_log = {}
curr = ''
typing_check = ''
t_channel = love.thread.newChannel()
is_tcp = true
route_conf = nil

scroll = {
	active = nil,
	scrolling = nil,
	scroll_pos = 0,
	scroll_size = 1,
}

function run_layers()
	love.thread.newThread("open_physical_layer.lua"):start()	
	love.thread.newThread("open_transport_layer.lua"):start()	
	love.thread.newThread("open_network_layer.lua"):start()
end

function love.load(arg)
	DNS_table = load_table()

	log_timer = 0
	sorted_ips = sort_ips(DNS_table)
		
	run_server_bg(DNS_table, DNS_log, t_channel)
	run_layers()	
	
	if #sorted_ips > 10 then
		scroll.active = true
		scroll.scroll_size = 10/#sorted_ips
	end
end

function love.update(dt)
	if log_timer > 0 then
		log_timer = math.max(0, log_timer - 3*dt)
	end
	
	if not love.mouse.isDown(1) then scroll.scrolling = nil end
	
	if scroll.scrolling then
		local _, y = love.mouse.getPosition()
		local center = scroll.scroll_size/2
		y = (y-190)/300
		scroll.scroll_pos = y-center
		scroll.scroll_pos = math.max(scroll.scroll_pos, 0)
		scroll.scroll_pos = math.min(scroll.scroll_pos, 1-scroll.scroll_size)
	end
	
	request = t_channel:pop()

	if request then
		if request.response then
			local ip = request.lookup
			local name = request.response
			if is_ip(request.response) then 
				ip = request.response
				name = request.lookup
			end
			add_record(ip, name, DNS_table)
		end
		
		add_log(DNS_log, request)
		new_log()
		
		if #sorted_ips > 10 then
			scroll.active = true
			scroll.scroll_size = 10/#sorted_ips
		end
	end
end

function love.textinput(text)
	if route_conf then
		if route_conf.typing and (tonumber(text) or text == ".") then
			route_conf[1][route_conf.typing] = route_conf[1][route_conf.typing]..text
		end
	else
		typing_check = typing_check..text
	end
end

function love.mousepressed(x, y, k)
	if k == 1 then
		if not route_conf then
			if x > 20 and x < 170 and y > 45 and y < 75 then
				is_tcp = not is_tcp
				return
			end
			
			if x > 200 and x < 350 and y > 45 and y < 75 then
				route_conf = load_routes()
				return
			end
			
			if scroll.active and x > 20 and x < 540 then
				scroll.scrolling = true
			end
		else
			if x > 200 and x < 350 and y > 45 and y < 75 then
				save_routes(route_conf)
				route_conf = nil
				return
			end
			
			local index = math.floor((y-180)/30)
			if index == 0 and not route_conf.typing then
				add_entry(route_conf, "", "", "")
				route_conf.typing = 1
			end
			if index > 0 and index < #route_conf and x > 670 and x < 820 then
				rm_entry(route_conf, index)
			end
		end
		
	end
end

function love.keypressed(key)
	if key == 'escape' then
		love.event.push('quit')
	end
	
	if not route_conf then
		if key == 'return' and typing_check then
			client_test(typing_check, is_tcp)
			typing_check = ''
			return
		end
		
		if key == "backspace" and typing_check then
			typing_check = typing_check:sub(1, #typing_check - 1)
		end
	else
		if key == "return" then
			if route_conf.typing then
				route_conf.typing = route_conf.typing + 1
				if route_conf.typing == 4 then route_conf.typing = nil end
			end
		end
		
		if key == "backspace" and route_conf.typing then
			route_conf[1][route_conf.typing] = route_conf[1][route_conf.typing]:sub(1, #route_conf[1][route_conf.typing] - 1)
		end
	end
end

function love.draw()
	if route_conf then
		draw_routes()
	else
		draw_table()
		draw_log()
		love.graphics.printf("Search: ",20, 500, 900, 'left')
		love.graphics.printf(typing_check,100, 500, 900, 'left')
		
		if #DNS_log > 0 then
			local request = DNS_log[#DNS_log]
			local alpha = nil
			if #DNS_log == 1 then alpha = 1-log_timer end
			
			love.graphics.setColor(1,1,1, alpha)
			love.graphics.printf("Response:",20, 530, 900, 'left')
			if request.response then
				love.graphics.setColor(0,1,0, alpha)
				love.graphics.printf(request.response, 100, 530, 340, 'left')
			else
				love.graphics.setColor(1,0,0, alpha)
				love.graphics.printf("DNS lookup failed :/", 100, 530, 340, 'left')
			end
			love.graphics.setColor(1,1,1,1)
		end

		if scroll.active then
			draw_scroll()
		end
		
		love.graphics.setColor(1,1,1,1)
		love.graphics.printf("Press Enter to search...", 20, 560, 900, 'left')
		love.graphics.printf("DNS Server", 0, 50, 900, 'center')
	end
	
	local protocol = 'UDP'
	if is_tcp then protocol = 'TCP' end
	
	love.graphics.printf("Using", 20, 20, 150, 'center')
	love.graphics.setColor(0,0,1,1)
	love.graphics.rectangle("line", 20, 45, 150, 30)
	love.graphics.printf(protocol, 20, 52, 150, 'center')
	love.graphics.setColor(1,1,1,1)
	
	love.graphics.setColor(0,0,1,1)
	love.graphics.rectangle("line", 200, 45, 150, 30)
	if route_conf then
		love.graphics.printf("Save and return", 200, 52, 150, 'center')
	else
		love.graphics.printf("Route Config", 200, 52, 150, 'center')
	end
	love.graphics.setColor(1,1,1,1)
end

function draw_scroll()
	love.graphics.setColor(0,0,0)
	love.graphics.rectangle("fill", 520, 190, 15, 300)
	love.graphics.setColor(1,1,1,1)
	love.graphics.line(20, 190, 520, 190)
	love.graphics.rectangle("line", 520, 190, 15, 300)
	love.graphics.line(20, 490, 520, 490)
	love.graphics.setColor(0.7,0.7,0.7)
	love.graphics.rectangle("fill", 521, 190 + 300*scroll.scroll_pos, 13, 300*scroll.scroll_size)
	love.graphics.setColor(1,1,1,1)
end

function draw_routes()
	love.graphics.printf("Routing table", 0, 130, 800, 'center')
	local index
	
	love.graphics.rectangle("line", 150, 180, 500,30)
	love.graphics.line(310, 180, 310, 210)
	love.graphics.line(480, 180, 480, 210)
	
	love.graphics.printf("Network IP", 150, 190, 160, 'center')
	love.graphics.printf("Mask", 310, 190, 170, 'center')
	love.graphics.printf("Gateway", 480, 190, 170, 'center')
	
	if not route_conf.typing then
		love.graphics.setColor(0,1,0)
		love.graphics.rectangle("line", 672, 182, 146,26)
		love.graphics.printf("Add", 670, 188, 150, 'center')
		love.graphics.setColor(1,1,1)
	end
	
	for index, route in ipairs(route_conf) do
		if index < #route_conf and not (index == 1 and route_conf.typing) then
			love.graphics.setColor(1,0,0)
			love.graphics.rectangle("line", 672, 182 + 30*index, 146,26)
			love.graphics.printf("Remove", 670, 188 + 30*index, 150, 'center')
			love.graphics.setColor(1,1,1)
		end
		
		love.graphics.rectangle("line", 150, 180 + 30*index, 500,30)
		love.graphics.line(310, 180 + 30*index, 310, 210 + 30*index)
		love.graphics.line(480, 180 + 30*index, 480, 210 + 30*index)
		
		love.graphics.printf(route[1], 150, 190 + 30*index, 160, 'center')
		love.graphics.printf(route[2], 310, 190 + 30*index, 170, 'center')
		love.graphics.printf(route[3], 480, 190 + 30*index, 170, 'center')
	end
end

function draw_table()
	love.graphics.printf("Names table", 20, 130, 500, 'center')
	
	local index = 0
	local y = 0
	
	if scroll.active then
		local limit = 1-scroll.scroll_size
		local percentage = scroll.scroll_pos/limit
		y = (#sorted_ips - 10) * 30 * percentage
	end
	
	local tb_canvas = love.graphics.newCanvas(500, 300)
	love.graphics.setCanvas(tb_canvas)
	for index, ip in ipairs(sorted_ips) do
		love.graphics.rectangle("line", 0, 30*(index-1)-y, 500,30)
		love.graphics.line(200, 30*(index-1)-y, 200, 30 + 30*(index-1)-y)
		
		if curr == ip and log_timer > 0 then
			green = (1-log_timer)
			love.graphics.setColor(green,1,green)
		end
		
		love.graphics.printf(ip, 0, 10 + 30*(index-1)-y, 200, 'center')
		love.graphics.printf(DNS_table[ip], 200, 10 + 30*(index-1)-y, 300, 'center')
	end
	love.graphics.setCanvas()
	love.graphics.draw(tb_canvas, 20, 190)
	love.graphics.draw(tb_canvas, 20, 190)
	love.graphics.setColor(1,1,1)
end

function draw_log()
	love.graphics.printf("Request log", 540, 150, 340, 'center')
	local index = #DNS_log - 1
	local y, alpha
	
	for _, request in ipairs(DNS_log) do
		y = 100+75*index + 75*(1-log_timer)
		alpha = 1
		
		if index > 0 then
			love.graphics.line(540, y + 5, 880, y + 5)
		end
	
		if index == 0 then
			y = 170+75*index
			alpha = 1-log_timer
		elseif index > 5 then
			alpha = log_timer
		end
		
		draw_log_row(request, y, alpha)
		index = index - 1
	end
end

function draw_log_row (request, y, alpha)
	love.graphics.setColor(1,1,1, alpha)
	love.graphics.printf("["..request.timestamp.."] - "..request.origin, 540, y+10, 340, 'center')
	
	if request.reverse then
		love.graphics.printf("Request for name: "..request.lookup, 540, y+30, 340, 'center')
	else
		love.graphics.printf("Request for IP: "..request.lookup, 540, y+30, 340, 'center')
	end
	
	if request.response then
		love.graphics.setColor(0,1,0, alpha)
		love.graphics.printf("Returned response: \n"..request.response, 500, y+50, 420, 'center')
	else
		love.graphics.setColor(1,0,0, alpha)
		love.graphics.printf("DNS lookup failed :/", 540, y+50, 340, 'center')
	end
	love.graphics.setColor(1,1,1)
end

function new_log()
	log_timer = 1
end
