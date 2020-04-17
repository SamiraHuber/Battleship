<?php

use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{

    private function connectToTestDB() {
        $db_config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . './private/config.ini'); 
        
        $db_host   = $db_config['servername'];
        $db_user   = $db_config['username'];
        $db_pass   = $db_config['password'];
        $db_name = 'testDB';

        $pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name,
        $db_user, $db_pass);

        return $pdo;
    }

    private function emptyTable($pdo, $tablename) {
        $pdo->beginTransaction();
        $sql = "DELETE FROM {$tablename};";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $pdo->commit();
    }

    private function createGame($pdo, $player1ID, $player2ID) {
        $pdo->beginTransaction();
    
        $sql = "INSERT INTO game (opponent_1, opponent_2, activePlayerID) VALUES ({$player1ID}, {$player2ID}, {$player1ID});";
        
        $sth = $pdo->prepare($sql);
        
        $sth->execute();
        $gameID = $pdo->lastInsertId(); 
        $pdo->commit();
        return $gameID;
    }

    public function test_getOpponentName() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $this->emptyTable($pdo, "player");

        $name = "TestName";
        $player = new \App\Models\Player;
        $player1ID = $player->addPlayer($pdo, $name."1");        
        $player2ID = $player->addPlayer($pdo, $name."2");

        $gameID = $this->createGame($pdo, $player1ID, $player2ID);

        $game = new \App\Models\Game;

        $opponentName = $game->getOpponentName($pdo, $player1ID, $gameID);
        $this->assertEquals($opponentName, $name."2");

        $opponentName = $game->getOpponentName($pdo, $player2ID, $gameID);
        $this->assertEquals($opponentName, $name."1");
    }

    public function test_getGame() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $this->emptyTable($pdo, "player");

        $name = "TestName";
        $player = new \App\Models\Player;
        $player1ID = $player->addPlayer($pdo, $name."1");        
        $player2ID = $player->addPlayer($pdo, $name."2");

        $gameID = $this->createGame($pdo, $player1ID, $player2ID);

        $game = new \App\Models\Game;

        $gameObj = $game->getGame($pdo, $player1ID);

        $this->assertEquals($gameObj['id'], $gameID);
        $this->assertEquals($gameObj['opponent_1'], $player1ID);
        $this->assertEquals($gameObj['opponent_2'], $player2ID);
    }

    public function test_setReady() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $this->emptyTable($pdo, "player");

        $name = "TestName";
        $player = new \App\Models\Player;
        $player1ID = $player->addPlayer($pdo, $name."1");        
        $player2ID = $player->addPlayer($pdo, $name."2");

        $gameID = $this->createGame($pdo, $player1ID, $player2ID);

        $game = new \App\Models\Game;

        $isReady = $game->setReady($pdo, $gameID, $player1ID);
        $this->assertEquals($isReady, 1);

        $gameObj = $game->getGame($pdo, $player1ID);
        $this->assertEquals($gameObj['isOpp1Ready'], 1);

        $isReady = $game->setReady($pdo, $gameID, $player2ID);
        $this->assertEquals($isReady, 1);

        $gameObj = $game->getGame($pdo, $player2ID);
        $this->assertEquals($gameObj['isOpp2Ready'], 1);
    }

    public function test_isOpponentReady() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $this->emptyTable($pdo, "player");

        $name = "TestName";
        $player = new \App\Models\Player;
        $player1ID = $player->addPlayer($pdo, $name."1");        
        $player2ID = $player->addPlayer($pdo, $name."2");

        $gameID = $this->createGame($pdo, $player1ID, $player2ID);

        $game = new \App\Models\Game;

        $game->setReady($pdo, $gameID, $player1ID);

        $isReady = $game->isOpponentReady($pdo, $gameID, $player1ID);
        $this->assertFalse($isReady);
        $isReady = $game->isOpponentReady($pdo, $gameID, $player2ID);
        $this->assertTrue($isReady);
    }

    public function test_isMyTurn() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $this->emptyTable($pdo, "player");

        $name = "TestName";
        $player = new \App\Models\Player;
        $player1ID = $player->addPlayer($pdo, $name."1");        
        $player2ID = $player->addPlayer($pdo, $name."2");

        $gameID = $this->createGame($pdo, $player1ID, $player2ID);

        $game = new \App\Models\Game;

        $isMyTurn = $game->isMyTurn($pdo, $gameID, $player1ID);
        $this->assertTrue($isMyTurn == 1);

        $isMyTurn = $game->isMyTurn($pdo, $gameID, $player2ID);
        $this->assertFalse($isMyTurn == 1);
    }

    public function test_changeActivePlayer() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $this->emptyTable($pdo, "player");

        $name = "TestName";
        $player = new \App\Models\Player;
        $player1ID = $player->addPlayer($pdo, $name."1");        
        $player2ID = $player->addPlayer($pdo, $name."2");

        $gameID = $this->createGame($pdo, $player1ID, $player2ID);

        $game = new \App\Models\Game;

        $opponent1Turn = $game->isMyTurn($pdo, $gameID, $player1ID);
        $this->assertTrue($opponent1Turn == 1);

        $game->changeActivePlayer($pdo, $gameID);

        $opponent1Turn = $game->isMyTurn($pdo, $gameID, $player1ID);
        $this->assertFalse($opponent1Turn == 1);
    }

    
}