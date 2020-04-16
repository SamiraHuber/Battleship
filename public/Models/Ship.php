<?php

    namespace App\Models;
    class Ship {

        /**
         * add a ship to the database
         * @param DATABASE $pdo - Database connection
         * @param ARRAY $ships - all ships set
         * @param INTEGER $gameID - id of current game
         * @param INTEGER $playerID - id of current player
         */
        public function addShips($pdo, $ships, $gameID, $playerID) {
            try {
                for ($i = 0, $l = sizeof($ships); $i < $l; $i++) {
                    for ($j = 0, $len = sizeof($ships[$i]['elements']); $j < $len; $j++) {
                        
                        $id = $ships[$i]['id'];
                        $x = $ships[$i]['elements'][$j]['x'];
                        $y = $ships[$i]['elements'][$j]['y'];
                        $isHorizontal = $ships[$i]['elements'][$j]['isHorizontal'];

                        $isValid = $this->resetShipsIfDuplicate($pdo, $gameID, $playerID, $x, $y);
                        if ($isValid == 0) {
                            return 0;
                        }

                        $sql = "INSERT INTO ship (gameID, playerID, shipID, x, y, isHorizontal) VALUES ({$gameID}, {$playerID}, {$id}, {$x}, {$y}, {$isHorizontal});";
                        $sth = $pdo->prepare($sql);
                        $sth->execute();
                        $pdo->commit();
                    }
                }
                return 1;
        
            } catch (PDOException $e) {
                $pdo->rollBack();
                return $e;
            }
        }

        private function resetShipsIfDuplicate($pdo, $gameID, $playerID, $x, $y) {
            $pdo->beginTransaction();
            $sql = "SELECT * FROM ship WHERE gameID = {$gameID} AND playerID = {$playerID} AND x = {$x} AND y = {$y};";
            $userRow = $pdo->query($sql)->fetch();

            if ($userRow != null) {
                $sql = "DELETE FROM ship WHERE gameID = {$gameID} AND playerID = {$playerID};";
                $sth = $pdo->prepare($sql);
                $sth->execute();
                $pdo->commit();
                return 0;
            }
            return 1;
        }

        /**
         * Returns the ships which are set by current player
         * 
         * @param  DATABASE $pdo - Database connection
         * @param  INTEGER  $gameID - id of current game
         * @param  INTEGER  $playerID - id of current player
         * @return ARRAY    all shipparts
         */
        public function getMyShips($pdo, $gameID, $playerID) {
            try {
                $sql = "SELECT * FROM ship WHERE gameID = {$gameID} AND playerID = {$playerID};";
                $userRows = $pdo->query($sql)->fetchAll();
                return $userRows;
            } catch (PDOException $e) {
                return $e;
            }
        }


        

    }
?>