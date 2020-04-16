<?php

    namespace App\Models;
    class Turn {

        /**
         * 
         */
        public function getAllTurns($pdo, $gameID) {
            try {
                $sql = "SELECT playerID, x, y, date, IF(ISNULL(shipID), 0, 1) as hit FROM turn WHERE gameID = {$gameID};";
                $userRows = $pdo->query($sql)->fetchAll();
                return $userRows;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return null;
            }
        }

        public function getSankShips($pdo, $gameID, $playerID) {
            try {
                $sql = "SELECT ship.shipID, COUNT(*) as count, shipTypes.length, MIN(ship.x) as x, MIN(ship.y) as y, MIN(ship.isHorizontal) as isHorizontal FROM turn JOIN ship ON turn.shipID = ship.id JOIN shipTypes ON shipTypes.id = ship.shipID WHERE turn.gameID = {$gameID} AND turn.playerID = {$playerID} GROUP BY ship.shipID, turn.playerID;";
                $userRows = $pdo->query($sql)->fetchAll();

                $fullShips = array();

                for($i=0; $i<sizeof($userRows); $i++) {
                    if ($userRows[$i]['count'] == $userRows[$i]['length']) {
                        $fullShips[] = $userRows[$i];
                    }
                }

                return $fullShips;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return $e;
            }
        }

        public function getHitFields($pdo, $gameID, $playerID) {
            try {
                $sql = "SELECT * FROM turn WHERE gameID = {$gameID} AND playerID != {$playerID} ORDER BY date DESC LIMIT 1;";
                $userRow = $pdo->query($sql)->fetch();

                return $userRow;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return $e;
            }
        }

        public function getHitRate($pdo, $gameID) {
            try {
                $sql = "SELECT turn.playerID, (COUNT(IF(ISNULL(ship.shipID), NULL, 1))/ COUNT(*) * 100) as hitrate FROM turn LEFT JOIN ship ON turn.shipID = ship.id WHERE turn.gameID = {$gameID} GROUP BY turn.playerID;";
                $userRows = $pdo->query($sql)->fetchAll();

                return $userRows;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return $e;
            }
        }


        public function addMove($pdo, $gameID, $playerID, $fieldX, $fieldY) {
            $userRow = null;
            try {
                $sql = "SELECT * FROM turn WHERE gameID = {$gameID} AND playerID = {$playerID} AND x = {$fieldX} AND y = {$fieldY};";
                $userRow = $pdo->query($sql)->fetch();
                if ($userRow != null) {
                    return -1;
                }
                $sql = "SELECT * FROM ship WHERE gameID = {$gameID} AND playerID != {$playerID} AND x = {$fieldX} AND y = {$fieldY};";
                $userRow = $pdo->query($sql)->fetch();
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return -1;
            }
            $pdo->beginTransaction();
    
            try {
                $currentDate = date('Y-m-d H:i:s');
                if ($userRow == null) {
                    $sql = "INSERT INTO turn (gameID, playerID, `date`, x, y) VALUES ({$gameID}, {$playerID},'{$currentDate}', {$fieldX}, {$fieldY});";
                }
                else {
                    $sql = "INSERT INTO turn (gameID, playerID, shipID, `date`, x, y) VALUES ({$gameID}, {$playerID}, {$userRow['id']},'{$currentDate}', {$fieldX}, {$fieldY});";
                }
                $sth = $pdo->prepare($sql);
                
                $sth->execute();
                $currentID = $pdo->lastInsertId(); 
                $pdo->commit();
                if ($userRow == null) {
                    return 0;
                }
                else {
                    return 1;
                }
        
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return $e;
            }

            return $userRows != null;
        }


        public function gameFinished($pdo, $gameID, $playerID) {
            try {
                $sql = "SELECT ship.shipID, COUNT(*) as count,shipTypes.length, turn.playerID FROM turn JOIN ship ON turn.shipID = ship.id JOIN shipTypes ON shipTypes.id = ship.shipID WHERE turn.gameID = {$gameID} GROUP BY ship.shipID, turn.playerID;";
                $userRows = $pdo->query($sql)->fetchAll();

                $myFullShips = array();
                $opponentFullShips = array();

                for($i=0; $i<sizeof($userRows); $i++) {
                    if ($userRows[$i]['count'] == $userRows[$i]['length']) {
                        if ($userRows[$i]['playerID'] == $playerID) {
                            $myFullShips[] = $userRows[$i];
                        }
                        else {
                            $opponentFullShips[] = $userRows[$i];
                        }
                        
                    }
                }

                return sizeof($myFullShips) == 10 || sizeof($opponentFullShips) == 10 ? 1 : 0;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return $e;
            }
        }
    }