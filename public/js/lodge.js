var searchOpponent = setInterval(() => {
    sendRequest();
}, 1500);

$(document).ready(function() {
    sendRequest();
});

function redirectToGame() {
    window.location.pathname = "/SetShips";
}

function sendRequest() {
    let _this = this;
    $.ajax({
        type: 'GET',
        url: '/getFreePlayer',
        contentType: "application/json",
        dataType: 'json',
        success: function(val) {
            if (typeof(val) == 'object') {
                clearInterval(_this.searchOpponent);
                _this.redirectToGame();
            }
        }
    });
}