<?php

    namespace App\Models;
    class Player {

        /**
        * Adds current player to database
        * @param pdo $pdo-connection
        * @param string $nickname
        * @return integer last inserted id
        */
        public function addPlayer($pdo, $nickname) {
            $pdo->beginTransaction();
    
            try {
                $currentDate = date('Y-m-d H:i:s');
                $currentTime = date('H:i:s');
                $sql = "INSERT INTO player (name, date, waiting, isPlaying) VALUES ('{$nickname}', '{$currentDate}', '{$currentTime}', 0);";
                
                $sth = $pdo->prepare($sql);
                
                $sth->execute();
                $currentID = $pdo->lastInsertId(); 
                $pdo->commit();
                return $currentID;
        
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return NULL;
            }
        }

        public function updateWaitingTime($pdo, $playerID) {
            $pdo->beginTransaction();
    
            try {
                $currentTime = date('H:i:s');
                $sql = "UPDATE player SET waiting = '{$currentTime}' WHERE id = {$playerID}";
                $sth = $pdo->prepare($sql);
                
                $sth->execute();
                $pdo->commit();    
                return 1;    
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return $e;
            }
        }

        /**
        * Find a player which is waiting for a game
        * @param pdo $pdo
        * @param int $playerID
        * @return int id of the created game
        */
        public function findWaitingPlayer($pdo, $playerID) {
            try {
                $currentTime = date('H:i:s');
                $sql = "SELECT * FROM player WHERE isPlaying = 0 AND id != {$playerID} AND waiting > NOW() - INTERVAL 3 SECOND ORDER BY date";
                $userRow = $pdo->query($sql)->fetch();
                if ($userRow != null) {
                    $this->setPlayersPlaying($pdo, $playerID, $userRow['id']);
                    $gameID = $this->createGame($pdo, $playerID, $userRow['id']);
                    return $gameID;
                }
                return null;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
            }
        }

        /**
         * creates game in database
         * @param   DATABASE    $pdo - connection to database
         * @param   INTEGER     $playerID - id of current player
         * @param   INTEGER     $opponentID - id of selected opponent
         * @return  INTEGER     id of created game
         */
        public function createGame($pdo, $playerID, $opponentID) {
            $pdo->beginTransaction();
    
            try {
                $activePlayerID = rand(0,1) == 0 ? $playerID : $opponentID;
                $sql = "INSERT INTO game (opponent_1, opponent_2, activePlayerID) VALUES ({$opponentID}, {$playerID}, {$activePlayerID});";
                
                $sth = $pdo->prepare($sql);
                
                $sth->execute();
                $currentID = $pdo->lastInsertId(); 
                $pdo->commit();
                return $currentID;
        
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return NULL;
            }
        }

        /**
         * set flag isPlaying to 1
         * @param   DATABASE    $pdo - Database connection
         * @param   INTEGER     $playerID - id of current plyer
         * @param   INTEGER     $opponentID - id of current opponent
         */
        public function setPlayersPlaying($pdo, $playerID, $opponentID) {
            $pdo->beginTransaction();
    
            try {
                $sql = "UPDATE player SET isPlaying = 1 WHERE id = {$playerID} OR id = {$opponentID}";
                $sth = $pdo->prepare($sql);
                
                $sth->execute();
                $currentID = $pdo->lastInsertId(); 
                $pdo->commit();        
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return NULL;
            }
        }

        /**
         * returns all players sorted by the hitrate
         * @param   DATABASE    $pdo - Database connection
         * @return  ARRAY       all players with name, id and hitrate
         */
        public function GetHighscore($pdo) {
            try {
                $sql = "SELECT player.name, turn.playerID, (COUNT(IF(ISNULL(ship.shipID), NULL, 1))/ COUNT(*) * 100) as hitrate FROM turn LEFT JOIN ship ON turn.shipID = ship.id LEFT JOIN player ON player.id = turn.playerID GROUP BY turn.playerID ORDER BY hitrate DESC;";
                $userRows = $pdo->query($sql)->fetchAll();

                return $userRows;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return $e;
            }
        }
    }
?>