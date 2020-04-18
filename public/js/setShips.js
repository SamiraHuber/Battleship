let isPortrait =  window.innerHeight > window.innerWidth;
let gridSize = isPortrait ? window.innerWidth*.7/10 : window.innerHeight*.7/10;

let canvas = createCanvas(isPortrait ? window.innerWidth*.7 : window.innerHeight*.7, isPortrait ? window.innerWidth*.7 : window.innerHeight*.7);
let ctx = canvas.getContext('2d');

let ships = [];
var submitted = false;
let interval = null;

$(document).ready(function() {
    drawGrid(ctx,gridSize,canvas.width,canvas.height);
    drawShips();
});

function drawShips() {
    for (var i = 0; i < 10; i++) {
        let ship = new Ship(0, i, i < 1 ? 5 : i < 3 ? 4 : i < 6 ? 3 : 2, i, "grid");
        ship.init();
        ships.push(ship);
    }
}

function drawGrid(ctx,gridSize,w,h) {
    var linesX = w/gridSize;
    var linesY = h/gridSize;
    ctx.strokeStyle = '#555';
    for (var x=1; x<linesX; x++) {
        var start = gridSize*x;
        ctx.moveTo(start, 0);
        ctx.lineTo(start, h);
        ctx.stroke();
    }
    for (var y=1; y<linesY; y++) {
        var start = gridSize*y;
        ctx.moveTo(0, start);
        ctx.lineTo(w, start);
        ctx.stroke();
    }
}

function createCanvas (w,h) {
    var canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    document.getElementById("grid").appendChild(canvas);
    return canvas;
}

function getShipPositions() {
    shipArray = [];
    ships.forEach(s => {
        let ship = {
            'elements': [],
            'id': s['id']
        };

        for (var i = 0; i < s.amount; i++) {
            let coords = {
                'x': s.isHorizontal ? Math.round(s.x/gridSize) + i : Math.round(s.x/gridSize),
                'y': s.isHorizontal ? Math.round(s.y/gridSize) : Math.round(s.y/gridSize) + i,
                'isHorizontal': s.isHorizontal == true ? 1 : 0
            }
            ship.elements.push(coords);
        }
        shipArray.push(ship);
    });
    return shipArray;
}

function saveShips() {
    if (this.submitted) {
        return;
    }

    this.document.getElementById("blocker").style.display = "block";
    let _this = this;
    
    $.ajax({
        type: 'GET',
        url: '/saveShips',
        data: {ships: getShipPositions()},
        contentType: "application/json",
        dataType: 'json',
        success: function(val) {
            if (val == 1) {
                _this.waitForOpponent();
                _this.submitted = true;
            }
            else {
                document.getElementById("blocker").style.display = "none";
                document.getElementById("title").style.display = "none";
                document.getElementById("error_message").style.display = "block";
            }
        }
    });   
}

function redirectToGame() {
    window.location.pathname = "/Game";
}

function waitForOpponent() {
    interval = setInterval(() => {
        checkIfOpponentReady();
    }, 1500);
}

function checkIfOpponentReady() {
    let _this = this;
    $.ajax({
        type: 'GET',
        url: '/waitForOpponent',
        contentType: "application/json",
        dataType: 'json',
        success: function() {
            _this.redirectToGame();
        }
    });
}