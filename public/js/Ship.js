class Ship {
    constructor(x, y, amount, id, parent, isHorizontal = true) {
        this.id = id
        this.isHorizontal = true;
        this.amount = amount;
        this.x = x*gridSize;
        this.y = y*gridSize;
        this.oldX = this.x;
        this.oldY = this.y;
        this.size = window.innerHeight > window.innerWidth ? window.innerWidth * 0.7/10 : window.innerHeight * 0.7/10;
        this.div = null;
        this.parent = parent;
    }

    init() {
        this.draw();
        this.dragAndDrop();
    }

    draw() {
        this.div = document.createElement('div');
        this.div.classList.add("ship");
        if (this.isHorizontal) {
            this.div.classList.add("ship_" + this.id);
        }
        else {
            this.div.classList.add("ship_" + this.id + "_rotated");
        }
        this.div.style.width = this.isHorizontal ? gridSize * this.amount + 'px' : gridSize + 'px';
        this.div.style.height = !this.isHorizontal ? gridSize * this.amount + 'px' : gridSize + 'px';
        this.div.style.transform = 'translate('+this.x+'px,'+this.y+'px)';
        this.div.style.borderRadius = '5em';
        document.getElementById(this.parent).appendChild(this.div);
    }

    rotate() {
        this.isHorizontal = !this.isHorizontal;
        this.div.style.width = this.isHorizontal ? gridSize * this.amount + 'px' : gridSize + 'px';
        this.div.style.height = !this.isHorizontal ? gridSize * this.amount + 'px' : gridSize + 'px';
        if (this.isHorizontal) {
            this.div.classList.remove("ship_" + this.id + "_rotated");
            this.div.classList.add("ship_" + this.id);
        }
        else {
            this.div.classList.remove("ship_" + this.id);
            this.div.classList.add("ship_" + this.id + "_rotated");
        }
        
    }

    dragAndDrop() {
        let _this = this;
        this.div.onmousedown = () => {
            if (submitted) {
                return;
            }
            document.onmouseup = () => {
                document.onmousemove = document.onmouseup = '';
                let shipLength = this.isHorizontal ? this.x + gridSize * (this.amount  - 1) : this.x;
                let shipHeight = !this.isHorizontal ? this.y + gridSize * (this.amount  - 1) : this.y;
                if (this.x < 0 || shipLength >= canvas.width || this.y < 0 || shipHeight >= canvas.height) {
                    this.div.style.transform = 'translate(' + this.oldX + 'px,' + this.oldY + 'px)';
                    this.x = this.oldX;
                    this.y = this.oldY;
                }
                else {
                    for (var i = 0; i < ships.length; i++) {
                        if (ships[i] == this) {
                            continue;
                        }
                        let othershipLength = ships[i].isHorizontal ? ships[i].x + gridSize * (ships[i].amount  - 1) : ships[i].x;
                        let othershipHeight = !ships[i].isHorizontal ? ships[i].y + gridSize * (ships[i].amount  - 1) : ships[i].y;
                        if (ships[i].x <= this.x && othershipLength >= this.x || ships[i].x <= shipLength && othershipLength >= this.x) {
                            if (ships[i].y <= this.y && othershipHeight >= this.y || ships[i].y <= shipHeight && othershipHeight >= this.y) {
                                this.div.style.transform = 'translate(' + this.oldX + 'px,' + this.oldY + 'px)';
                                this.x = this.oldX;
                                this.y = this.oldY;
                                return;
                            }
                        }
                    }
                    this.oldX = this.x;
                    this.oldY = this.y;
                }
            }
            document.onmousemove = (e) => {
                this.x = Math.floor((e.clientX - (window.innerWidth-canvas.width)/2)/gridSize)*gridSize;
                this.y = Math.floor((e.clientY - (window.innerHeight-canvas.height)/2)/gridSize)*gridSize;
                this.div.style.transform = 'translate(' + this.x + 'px,' + this.y + 'px)';
            }
        }

        this.div.ondblclick = () => {
            if (submitted) {
                return;
            }
            this.rotate();
            let shipLength = this.isHorizontal ? this.x + gridSize * (this.amount  - 1) : this.x;
            let shipHeight = !this.isHorizontal ? this.y + gridSize * (this.amount  - 1) : this.y;
            //check if out of field
            if (this.x < 0 || shipLength >= canvas.width || this.y < 0 || shipHeight >= canvas.height) {
                this.rotate();
                return;
            }
            //check if on other ship
            for (var i = 0; i < ships.length; i++) {
                if (ships[i] == this) {
                    continue;
                }
                let othershipLength = ships[i].isHorizontal ? ships[i].x + gridSize * (ships[i].amount  - 1) : ships[i].x;
                let othershipHeight = !ships[i].isHorizontal ? ships[i].y + gridSize * (ships[i].amount  - 1) : ships[i].y;
                if ((ships[i].x <= this.x && othershipLength >= this.x) || (ships[i].x <= shipLength && othershipLength >= this.x)) {
                    if (ships[i].y <= this.y && othershipHeight >= this.y || ships[i].y <= shipHeight && othershipHeight >= this.y) {
                        this.rotate();
                        return;
                    }
                }
            }
        }
    }
}