// reload.js

let socket = new WebSocket('ws://localhost:2088');

socket.onmessage = function(event) {
    if (event.data === 'reload') {
        location.reload();
    }
};