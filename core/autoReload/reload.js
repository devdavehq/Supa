const socket = new WebSocket('ws://localhost:2088/reload');

socket.onmessage = function(event) {
    if (event.data === 'reload') {
        location.reload(); // Reload the page when a message is received
    }
};

socket.onopen = function() {
    console.log('WebSocket connection established.');
};

socket.onclose = function() {
    console.log('WebSocket connection closed.');
};

socket.onerror = function(error) {
    console.error('WebSocket error:', error);
};