require "dns"
require "initial"
require "connection"

DNS_table = {}
sorted_ips = {}
DNS_log = {}
curr = ''
typing_check = ''
t_channel = love.thread.newChannel()

function run_phy_layer()
	local threadCode = [[
		os.execute("python PhysicalLayer/server.py")
	]]
	love.thread.newThread(threadCode):start()
end

function love.load(arg)
	DNS_table = load_table()

	log_timer = 0
	sorted_ips = sort_ips(DNS_table)
	
	run_server_bg(DNS_table, DNS_log, t_channel)
	run_phy_layer()	
end

function love.update(dt)
	if log_timer > 0 then
		log_timer = math.max(0, log_timer - 3*dt)
	end
	
	request = t_channel:pop()

	if request then
		add_log(DNS_log, request)
		new_log()
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
		client_test(typing_check)
		typing_check = ''
		new_log()
		return
	end
	
	if key == 'space' then
		client_test_phy()
		new_log()
		return
	end
	
	if key == "backspace" and typing_check then
		typing_check = typing_check:sub(1, #typing_check - 1)
	end
end

function love.draw()
	love.graphics.printf("DNS Server", 0, 50, 900, 'center')
	
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
	
	love.graphics.printf("Press Enter to search...", 20, 560, 900, 'left')

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
	love.graphics.printf("["..request.timestamp.."] - "..request.origin, 540, y+10, 340, 'center')
	
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
