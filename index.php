<!doctype html>
<html>
  <head>
    <title>Kenny message<?php echo 123;?></title>
    <style>
      * { margin: 0; padding: 0; box-sizing: border-box;}
      body { font: 13px Helvetica, Arial; }
	  #chatInputBar{ width: 100%; overflow: hidden; background-color: #000;padding: 3px;}
	  #chatInputBar input { border: 0; padding: 10px; width: 100%;}
	  #chatInputBar #userControl {float: left;}
	  #chatInputBar select {border: 0; padding: 9px;}
	  #chatInputBar span { display: block; overflow: hidden; padding: 0 5px;}
	  #chatInputBar #snedImageBtn { border: 0; padding: 10px; width: auto; background: rgb(130, 224, 255);}
	  #chatInputBar #snedMessageBtn { border: 0; padding: 10px; width: auto; background: rgb(130, 224, 255); float: right;}
      #messages {width:100%; position: fixed; top:0; bottom: 64px; overflow:auto;}
      #messages li { padding: 5px 10px;}
      #messages li:nth-child(odd) { background: #eee; }
	  #typingMessage{background: yellow; width:100%;}
	  #footer{position: fixed; bottom: 0; width:100%;}
	  #downloadHistroyBtn{ position: fixed; top: 0; right: 0; background: rgb(130, 224, 255);border: 0; padding: 10px; width: auto;}
    </style>
	<script src='https://cdn.socket.io/socket.io-1.3.5.js'></script>
    <script src='https://code.jquery.com/jquery-1.11.3.js'></script>
	<script>
		function downloadInnerHtml() {
			var elHtml = document.getElementById('messages').innerHTML;
			var link = document.createElement('a');
			mimeType = 'text/html' || 'text/plain';

			var currentdate = new Date(); 
			var filename = currentdate.getFullYear()+'-'+(currentdate.getMonth()+1)+'-'+currentdate.getDate()+'_history.html';
			link.setAttribute('download', filename);
			link.setAttribute('href', 'data:text/html; charset=UTF-8,' + '<meta charset="UTF-8" />' + encodeURIComponent(elHtml));
			link.click(); 
		}
		String.prototype.startsWith = function (str)
		{
		   if(this.indexOf(str) == 0)
			return true;
		   else
			return false;
		}
	</script>
  </head>
<body>
    <ul id='messages'>
    </ul>
	<div id='footer'>
		<div id='typingMessage'>
		</div>
		<div id='chatInputBar'>
			<button id = 'snedMessageBtn'>發送</button>
			<div id = 'userControl'>
				<button id = 'snedImageBtn'>+</button>
				<select id = 'userSelect'>
					<option value='all'>All</option>
				</select>
			</div>
			<span><input id='m' autocomplete='off'/></span>
		</div>
	</div>
	<button id = 'downloadHistroyBtn' onclick ='downloadInnerHtml()'>儲存對話</button>
	<input id = 'imageFile' type='file' style='visibility: hidden;' accept='image/*'>
    <script>
		//check login device to change font-size
		var isMobile = {
			Android: function() {
				return navigator.userAgent.match(/Android/i);
			},
			BlackBerry: function() {
				return navigator.userAgent.match(/BlackBerry/i);
			},
			iOS: function() {
				return navigator.userAgent.match(/iPhone|iPad|iPod/i);
			},
			Opera: function() {
				return navigator.userAgent.match(/Opera Mini/i);
			},
			Windows: function() {
				return navigator.userAgent.match(/IEMobile/i);
			},
			any: function() {
				return (isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
			}
		};
		if(isMobile.any()){
			$('*').css('font-size','30pt');
		}
		else{
			$('*').css('font-size','12pt');
		}
	</script>
	<script>
		var socket = io();
		var userName;
		var receiveName = 'all';
		var strRegex = "^((https|http|ftp|rtsp|mms)?://)"
				+ "?(([0-9a-z_!~*'().&amp;=+$%-]+: )?[0-9a-z_!~*'().&amp;=+$%-]+@)?" //ftp的user@
				+ "(([0-9]{1,3}\.){3}[0-9]{1,3}" // IP形式的URL- 199.194.52.184
				+ "|" // 允許IP和DOMAIN（域名）
				+ "([0-9a-z_!~*'()-]+\.)*" // 域名- www.
				+ "([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\." // 二級域名
				+ "[a-z]{2,6})" // first level domain- .com or .museum
				+ "(:[0-9]{1,4})?" // 埠- :80
				+ "((/?)|" // a slash isn't required if there is no file name
				+ "(/[0-9a-z_!~*'().;?:@&amp;=+$,%#-]+)+/?)$";

		var urlCheck=new RegExp(strRegex);

		socket.on('add userList', function (name) {
			//add user list to selector without user-self
			if(userName!=name){
				$('#userSelect').append($('<option></option>').attr('value', name).text(name));
			}
        });
		
		socket.on('userName exist', function (name) {
            alert('Your user name '+name+' was exist!!');
			//user name exist, ask again
			AskUserName();
        });
		
        socket.on('chat message', function (userName, receiveName, msg, currentdate) {
			AppendMessage(userName, receiveName, msg, currentdate);
			ScrollBarMoveBottom();
			notifyMe();
        });
		
		socket.on('user join',function (Name) {
			$('#messages').append($('<li>').text(ChangeDateFormatToTime(new Date())+ Name + ' 已加入聊天'));
			ScrollBarMoveBottom();
		});
		
		socket.on('typing message', function (typingUsers) {
			if(typingUsers.length != 0){
				$('#typingMessage').text(typingUsers + ' is typing...');
			}
			else{
				$('#typingMessage').text('');
			}
        });
		
		socket.on('user left', function (Name) {
            $('#messages').append($('<li>').text(ChangeDateFormatToTime(new Date())+ Name + ' 已離開聊天'));
			//remove leaved user in selector 
			$('#userSelect').find('[value=\''+Name+'\']').remove();
			socket.emit('stop typing',userName);
			ScrollBarMoveBottom();
        });
		
		socket.on('disconnect', function () {
			$(window).unbind('beforeunload');
			alert('Lost connection!!\nRefresh page.');
			window.location.reload();
		});
		
		$('#snedMessageBtn').click(function () {
			submitMessage();
        });
		
		$('#snedImageBtn').click(function() {
			$('#imageFile').click();
		});
		
		$('#imageFile').change(function(){
			if($('#imageFile').val()!=''){
				readImage(this);
				$('#imageFile').val('');
				$('#m').focus();
			}
		});
		
		//change target User
		$('#userSelect').change(function(){
			receiveName = $('#userSelect').find(':selected').val();
			socket.emit('stop typing',userName);
		});
		
		//when focus on message input and press enter key
		$('#m').keydown(function (e) {
			if (e.keyCode == 13) {
				submitMessage();
			}
		});
		
		//who is typing
		$('#m').keyup(function() {
			if(receiveName == 'all'){
				if($('#m').val() != ''){
					socket.emit('start typing',userName);
				}
				else{
					socket.emit('stop typing',userName);
				}
			}
		});

		$(window).on('beforeunload', function () {
            return 'Are you sure want to leave??';
        });
		
		function AskUserName(){
			do{
				userName = prompt('請輸入您的暱稱', '訪客');
			}while(userName == null || userName == '')
			socket.emit('new user',userName);
		}
		
		//make message scrollbar always at bottom
		function ScrollBarMoveBottom(){
			$('#messages').animate({
				scrollTop: $('#messages')[0].scrollHeight
			},'slow');
		}
		
		function notifyMe() {
			// Check if the browser supports notifications
			if (('Notification' in window)) {
				// Check whether notification permissions have already been granted
				if (Notification.permission === 'granted') {
					// If it's okay let's create a notification
					var notification = new Notification('You\'ve got new messages.');
					setTimeout(notification.close.bind(notification), 1000);
				}
				// Otherwise, we need to ask the user for permission
				else if (Notification.permission !== 'denied') {
					Notification.requestPermission(function (permission) {
						// If the user accepts, let's create a notification
						if (permission === 'granted') {
							var notification = new Notification('You\'ve got new messages.');
							setTimeout(notification.close.bind(notification), 1000);
						}
					});
				}
			}
		}
		
		//submit string messages
		function submitMessage(){
			if($('#m').val().trim() != ''){
				var date = new Date();
				$('#messages').css('bottom',$('#footer').outerHeight());
				AppendMessage(userName, receiveName, $('#m').val(), date)
				ScrollBarMoveBottom();
				socket.emit('chat message', userName, receiveName, $('#m').val(), date);
				$('#m').val('');
			}
			socket.emit('stop typing',userName);
			$('#m').focus();
		}
		
		function AppendMessage(userName, receiveName, msg, date){
			if(urlCheck.test(msg)){
				var hrefUrl = '';
				if(msg.startsWith('http')){
					hrefUrl = msg;
				}
				else{
					hrefUrl = '//'+msg;
				}
				if(receiveName == 'all'){
					$('#messages').append($('<li>').append([
						(ChangeDateFormatToTime(date)) +userName+ ': ',
						$('<a>', { href: hrefUrl,target:'_blank'}).text(msg)
					]));
				}
				else{
					$("#messages").append($('<li>').css({
						"color": "#000066",
						"font-weight": "bolder"
					}).append([
						(ChangeDateFormatToTime(date)) + userName + " to " + receiveName + ": ",
						$('<a>', { href: hrefUrl,target:"_blank"}).text(msg)
					]));
				}
			}
			else if(msg.startsWith('data:image')){
				if(receiveName == 'all'){
					$('#messages').append($('<li>').append([
						(ChangeDateFormatToTime(date)) +userName+ ': ',
						$('<img>', {src: msg, width: 300, height: 'auto'})
					]));
				}
				else{
					$('#messages').append($('<li>').css({
						'color': '#000066',
						'font-weight': 'bolder'
					}).append([
						(ChangeDateFormatToTime(date)) + userName + ' to ' + receiveName + ': ',
						$('<img>', {src: msg, width: 300, height: 'auto'})
					]));
				}
			}
			else{
				if(receiveName == 'all'){
					$('#messages').append($('<li>').text(ChangeDateFormatToTime(date) +userName+ ': '+msg));
				}
				else{
					$('#messages').append($('<li>').text(ChangeDateFormatToTime(date) +userName+' to '+receiveName + ': '+msg).css({
						'color': '#000066',
						'font-weight': 'bolder'
					}));
				}
			}			
		}
		
		function readImage(input) {
			if ( input.files && input.files[0] ) {
				var FR= new FileReader();
				FR.onload = function(e) {
					var date = new Date();
					AppendMessage(userName, receiveName, e.target.result, date)
					socket.emit('chat message', userName, receiveName, e.target.result, date);
				};       
				FR.readAsDataURL( input.files[0] );
				ScrollBarMoveBottom();
			}
		}
	
		function ChangeDateFormatToTime(currentdate){
			var date = new Date(currentdate);
			var datetime ='[' + Appendzero(date.getHours()) + ':' + Appendzero(date.getMinutes()) + ':' + Appendzero(date.getSeconds())+']';
			return datetime;
		}

		function Appendzero(obj){
			if(obj<10) return "0" +""+ obj;
			else return obj;
		}
		
		AskUserName();
    </script>
</body>
</html>