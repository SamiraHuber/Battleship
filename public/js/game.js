let myTurn = false;
var fullShips = [];

let gridSize = $("#opponent").width()/10;

var canvas = createCanvas($("#myself").width(), $("#myself").height(), "myself");
var ctx1 = canvas.getContext('2d');

var canvasOpp = createCanvas($("#opponent").width(), $("#opponent").height(), "opponent");
var ctx2 = canvasOpp.getContext('2d');

drawGrid(ctx1,gridSize,canvas.width,canvas.height);
drawGrid(ctx2,gridSize,canvasOpp.width,canvasOpp.height);

$( document ).ready(function() {
    drawShips(); 
    checkNextTurn();
});

var nextTurn = setInterval(() => {
    if (!myTurn) {
        checkNextTurn();
    }
}, 1500);

canvasOpp.onclick = function (e) {
    let x = Math.floor((e.clientX - $("#opponent")[0].offsetLeft)/gridSize);
    let y = Math.floor((e.clientY - $("#opponent")[0].offsetTop)/gridSize);
    makeTurn(x, y);
}

function drawGrid(ctx,gridSize,w,h) {
    var linesX = w/gridSize;
    var linesY = h/gridSize;
    ctx.strokeStyle = '#CCC';
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

function createCanvas (w,h,element) {
    var canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    document.getElementById(element).appendChild(canvas);
    return canvas;
}

function drawShips() {
    let addedShips = [];
    ships.forEach(ship => {
        if (!addedShips.includes(ship['shipID'])) {
            switch(ship['shipID']) {
                case "0":
                    var s = new Ship(ship['x'], ship['y'], 5, ship['shipID'], "myself", parseInt(ship['isHorizontal']) == 1)
                    s.draw();
                    addedShips.push(ship['shipID']);
                    break;
                case "1":
                case "2":
                    var s = new Ship(ship['x'], ship['y'], 4, ship['shipID'], "myself", parseInt(ship['isHorizontal']) == 1)
                    s.draw();
                    addedShips.push(ship['shipID']);
                    break;
                case "3":
                case "4":
                case "5":
                    var s = new Ship(ship['x'], ship['y'], 3, ship['shipID'], "myself", parseInt(ship['isHorizontal']) == 1)
                    s.draw();
                    addedShips.push(ship['shipID']);
                    break;
                case "6": 
                case "7":
                case "8":
                case "9":
                    var s = new Ship(ship['x'], ship['y'], 2, ship['shipID'], "myself", parseInt(ship['isHorizontal']) == 1)
                    s.draw();
                    addedShips.push(ship['shipID']);
                    break;
                default:
                    break;
            }
        }
        
    });
}

function markFieldHit(x, y) {
    let square = document.createElement('div');
    square.classList.add("shipElement")
    square.id = "shipElement_" + x + "_" + y;
    square.style.width = gridSize + 'px';
    square.style.height = gridSize + 'px';
    square.style.transform = 'translate('+gridSize * x+'px,'+gridSize * y+'px)';
    document.getElementById("opponent_hits").appendChild(square);
}

function markFieldMiss(x, y) {
    let square = document.createElement('div');
    square.classList.add("water");
    square.style.width = gridSize + 'px';
    square.style.height = gridSize + 'px';
    square.style.transform = 'translate('+gridSize * x+'px,'+gridSize * y+'px)';
    document.getElementById("opponent_hits").appendChild(square);
}

function showFullShips(val) {
    fullShips = val['fullShips'];

    fullShips.forEach(newShip => {
        var amount = newShip.shipID < 1 ? 5 : newShip.shipID < 3 ? 4 : newShip.shipID < 6 ? 3 : 2;
        for (var i = 0; i < amount; i++) {
            let x = newShip.isHorizontal == 1 ? parseInt(newShip.x) + i : newShip.x;
            let y = newShip.isHorizontal == 1 ? newShip.y : parseInt(newShip.y) + i;
            let id = "shipElement_" + x + "_" + y;
            let element = document.getElementById(id);
            if (element != null) {
                element.parentNode.removeChild(element);
            }
        }
        //only if there is no ship drawn yet
        let ship = new SunkShip(newShip.x, newShip.y, newShip.shipID < 1 ? 5 : newShip.shipID < 3 ? 4 : newShip.shipID < 6 ? 3 : 2 , newShip.shipID, newShip.isHorizontal == 1);
        ship.draw();
    });
}

function makeTurn(x, y) {
    if (!myTurn) {
        return;
    }
    myTurn = false;
    document.getElementById("turn").innerText = "Waiting for opponent!";
    let _this = this;
    $.ajax({
        type: 'GET',
        url: '/makeTurn',
        contentType: "application/json",
        data: {'x': x, 'y': y},
        dataType: 'json',
        success: function(val) {
            if (val == 1) {
                _this.markFieldHit(x, y);
            }
            else if (val == 0) {
                _this.markFieldMiss(x, y);
            }
            else if (val == -1) {
                _this.myTurn = true;
                document.getElementById("turn").innerText = "Your turn!";
                document.getElementById("message").innerText = "Something went wrong - try again!";
            }
        }
    });
}

function displayHitsOnMyself(val) {
    myTurn = true;
    document.getElementById("turn").innerText = "Your turn!";

    if (!val['hitFields']) {
        return;
    }
    let pos = val['hitFields'];

    let square = document.createElement('div');
    square.classList.add("hitMyself");
    square.style.width = gridSize + 'px';
    square.style.height = gridSize + 'px';
    square.style.transform = 'translate('+gridSize * pos['x']+'px,'+gridSize * pos['y']+'px)';
    document.getElementById("myself_hits").appendChild(square);
}

function goToHighscore() {
    window.location.pathname = "/Highscore";
}

function checkNextTurn() {
    let _this = this;
    $.ajax({
        type: 'GET',
        url: '/waitForNextTurn',
        contentType: "application/json",
        dataType: 'json',
        success: function(val) {
            if (val['finished'] == 1) {
                _this.goToHighscore();
            }
            if (val['myturn'] == 1) {
                _this.displayHitsOnMyself(val);
            }
            if (val['fullShips'].length != _this.fullShips.length) {
                _this.showFullShips(val);
            }
            document.getElementById("message").innerText = "Your hit rate: " + parseInt(val['myHitRate']) + "% // " + opponentName + "s hit rate: "  + parseInt(val['opponentHitRate'])+ "%";
        }
    });
}
