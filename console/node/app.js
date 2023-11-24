/*
 Node.js server script for pub/sub transport companies
 Required node packages: express, redis, socket.io
 */
var config = require(__dirname+'/../config/local/node').config;

var redis = require("redis"),
    client = redis.createClient({
        host: config['redisPubSub'].host,
        port: config['redisPubSub'].port,
        db: config['redisPubSub'].db
    });

var io = require('socket.io').listen(config['socketPort']);

io.on('connection', function (socket) {
    socket.emit('set id', socket.id);
});

client.on("message", function(channel, message){
    eval('var msg = ' + message);

    var socket_id = msg.socket_id;
    delete msg.socket_id;

    if(msg.distance !== undefined) {
        // Запрос к гуглу для получения расстояния и времени
        io.sockets.to(socket_id).emit('set distance', msg);
    }

    if(msg.name !== undefined){
        // Просто отправка сообщения (компании)
        io.sockets.to(socket_id).emit('new result', message);
    }

    if(msg.tk_fail){
        //не удалось получить данные ТК
        io.sockets.to(socket_id).emit('tk_fail', message);
    }

    if( msg.total_tk !== undefined ){
        //кол-во тк участвующих в поиске
        io.sockets.to(socket_id).emit('total_tk', msg.total_tk);
    }
});

client.subscribe("pubsub");



io.on("error", function (err) {
    console.log("Error " + err);
});
