<?php

use PHPUnit\Framework\TestCase;

class PlayerTest extends TestCase
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

    public function test_addPlayer() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "player");
        $player = new \App\Models\Player;

        $name = "TestName";
        
        $id = $player->addPlayer($pdo, $name);

        $sql = "SELECT * FROM player WHERE name = '{$name}' AND id = {$id} AND isPlaying = 0;";
        $newPlayer = $pdo->query($sql)->fetchAll();
        $this->assertEquals(sizeof($newPlayer), 1);
    }

    public function test_createGame() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "game");
        $player = new \App\Models\Player;

        $name = "TestName";
        
        $player1ID = $player->addPlayer($pdo, $name);
        $player2ID = $player->addPlayer($pdo, $name);

        $gameID = $player->createGame($pdo, $player1ID, $player2ID);

        $sql = "SELECT * FROM game WHERE (opponent_1 = {$player1ID} OR opponent_2 = {$player1ID}) AND (opponent_1 = {$player2ID} OR opponent_2 = {$player2ID});";
        $game = $pdo->query($sql)->fetch();
        $this->assertEquals($gameID, $game['id']);
    }

    public function test_setPlayersPlaying() {
        $pdo = $this->connectToTestDB();
        $this->emptyTable($pdo, "player");
        $player = new \App\Models\Player;

        $name = "TestName";
        
        $player1ID = $player->addPlayer($pdo, $name);
        $player2ID = $player->addPlayer($pdo, $name);

        $player->setPlayersPlaying($pdo, $player1ID, $player2ID);

        $sql = "SELECT * FROM player WHERE id = {$player1ID};";
        $players = $pdo->query($sql)->fetch();
        $this->assertEquals("1", $players['isPlaying']);

        $sql = "SELECT * FROM player WHERE id = {$player2ID};";
        $players = $pdo->query($sql)->fetch();
        $this->assertEquals("1", $players['isPlaying']);
    }
}