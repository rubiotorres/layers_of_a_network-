require "dns"
require "initial"

DNS_table = {}
sorted_ips = {}
DNS_log = {}
curr = ''
typing_check = ''

function love.load(arg)
	DNS_table = load_table()

	log_timer = 0
	sorted_ips = sort_ips(DNS_table)
end

function love.update(dt)
	if log_timer > 0 then
		log_timer = math.max(0, log_timer - 3*dt)
	end
end

function love.textinput(text)
	typing_check = typing_check..text
end

function love.keypressed(key)
	if key == 'escape' then
		love.event.push('quit')
	end
	
	if key == 'return' and typing_check then
		new_request(typing_check, DNS_table, DNS_log)
		typing_check = ''
		return
	end
	
	if key == "backspace" and typing_check then
		typing_check = typing_check:sub(1, #typing_check - 1)
	end
end

function love.draw()
	love.graphics.printf("DNS Server - running...", 0, 50, 900, 'center')
	
	draw_table()
	draw_log()
	
	love.graphics.print("Search: " .. typing_check,20, 500)
	love.graphics.print("Press Enter to search", 20, 540)
end

function draw_table()
	love.graphics.printf("Names table", 20, 150, 500, 'center')
	local index = 0
	for _, ip in ipairs(sorted_ips) do		
		love.graphics.rectangle("line", 20, 190 + 30*index, 500,30)
		love.graphics.line(220, 190+30*index, 220, 220 + 30*index)
		
		if curr == ip and log_timer > 0 then
			green = (1-log_timer)
			love.graphics.setColor(green,1,green)
		end
		
		love.graphics.printf(ip, 20, 200 + 30*index, 200, 'center')
		love.graphics.printf(DNS_table[ip], 220, 200 + 30*index, 300, 'center')
		
		love.graphics.setColor(1,1,1)
		index = index + 1
	end
end

function draw_log()
	love.graphics.printf("Request log", 540, 150, 340, 'center')
	local index = #DNS_log - 1
	local y, alpha
	
	for _, request in ipairs(DNS_log) do
		y = 100+70*index + 70*(1-log_timer)
		alpha = 1
		
		if index > 0 then
			love.graphics.line(540, y, 880, y)
		end
	
		if index == 0 then
			y = 170+70*index
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
	love.graphics.printf("["..request.timestamp.."]", 540, y+10, 340, 'center')
	
	if request.reverse then
		love.graphics.printf("Request for name: "..request.lookup, 540, y+30, 340, 'center')
	else
		love.graphics.printf("Request for IP: "..request.lookup, 540, y+30, 340, 'center')
	end
	
	if request.response then
		love.graphics.setColor(0,1,0, alpha)
		love.graphics.printf("Returned response: "..request.response, 540, y+50, 340, 'center')
	else
		love.graphics.setColor(1,0,0, alpha)
		love.graphics.printf("DNS lookup failed :/", 540, y+50, 340, 'center')
	end
	love.graphics.setColor(1,1,1)
end

function new_log()
	log_timer = 1
end
