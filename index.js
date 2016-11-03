var app = require('express')();
var http = require('http').Server(app);
var io = require('socket.io')(http);
//pair of user's name and user's socket
var currentUsers = [];
var currentSockets = [];
var typingUsers = [];

//** DB **		
		
var db_options = {
    host: "61.219.119.103",
    user: "popular",
    password: "NQqVNYFXfZWj99qT",
    database: "popular"
};
var mysql = new require("mysql");
var db = null;
 
db = mysql.createConnection(db_options);
db.connect(function(err) {
    if(err) {
        console.error(err);
        return;
    }
	alert(87);
    console.log("Mysql Connect");
});
//將mysql的client 存入 exports
exports.db = db;
	

var db = config.db;

//Query
db.query("SELECT * FROM club", function(err, rows, fiels) {
    if(err){
        console.log(err);
        return ;
    }
	alert(98);
    //rows是資料庫query出來的所有資料(JSON)
    console.log(rows);
    //fiels是欄位的資訊
    console.log(fiels);
});


console.info("这是info");


app.get('/', function(req, res){
  res.sendFile(__dirname + '/index.html');
});

io.on('connection', function(socket){
  socket.on('chat message', function(userName, receiveName, msg, currentdate){
	if(receiveName == 'all'){
		//send message to all client without self
		socket.broadcast.emit('chat message', userName, receiveName, msg, currentdate);
	}
	else{
		currentSockets[currentUsers.indexOf(receiveName)].emit('chat message', userName, receiveName, msg, currentdate);
	}
  });
  
  socket.on('new user', function(userName){
	//check user's name exist or not
	if(currentUsers.indexOf(userName)==-1){
		currentUsers.push(userName);
		currentSockets.push(socket);
		io.emit('user join', userName);
		io.emit('add userList', userName);
	}
	else{
		socket.emit('userName exist', userName);
	}
  });
  
  socket.on('start typing', function(name){
	if(typingUsers.indexOf(name)==-1){
		typingUsers.push(name);
	}
	io.emit('typing message', typingUsers);
  });
  
  socket.on('stop typing', function(name){
	if(typingUsers.indexOf(name)!=-1){
		typingUsers.splice(typingUsers.indexOf(name),1);
	}
	io.emit('typing message', typingUsers);
  });  
  
  socket.on('disconnect', function(){
	if(currentSockets.indexOf(socket)!=-1){
		//when disconnect username doesn't null, show user left message
		//problem: sometimes client receive null left message, and nobody was disconnect.
		if(currentUsers[currentSockets.indexOf(socket)] != null){
			io.emit('user left', currentUsers[currentSockets.indexOf(socket)]);
		}
		//if disconnect user was typing, remove the name from typing list
		if(typingUsers.indexOf(currentUsers[currentSockets.indexOf(socket)])!=-1){
			typingUsers.splice(typingUsers.indexOf(currentUsers[currentSockets.indexOf(socket)]),1);
			io.emit('typing message', typingUsers);
		}
		//remove leaved user's name and socket
		currentUsers.splice(currentSockets.indexOf(socket),1);
		currentSockets.splice(currentSockets.indexOf(socket),1);
	}
  });
  
  //when a new client connected add current users to client selector
  for (var i = 0; i < currentUsers.length; i++) {
    socket.emit('add userList', currentUsers[i]);
  }
});

http.listen(process.env.PORT || 3000, function(){
  console.log('listening on *:3000');
});
