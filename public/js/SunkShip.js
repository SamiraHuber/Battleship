
class SunkShip {
    constructor(x, y, amount, id, isHorizontal) {
        this.id = id
        this.isHorizontal = isHorizontal;
        this.amount = amount;
        this.x = x*gridSize;
        this.y = y*gridSize;
        this.div = null;
    }

    draw() {
        this.div = document.createElement('div');
        this.div.classList.add("sunkship");
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
        document.getElementById("opponent_hits").appendChild(this.div);
    }
}