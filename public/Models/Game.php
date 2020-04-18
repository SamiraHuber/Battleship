<?php

    namespace App\Models;
    class Game {

        /**
         * Returns the name of the opponent of the current game
         * 
         * @param   database    %pdo - connection to database
         * @param   integer     $playerID - id of current player
         * @param   integer     $gameID - id of current game
         * @return  string      name of opponent
         */
        public function getOpponentName($pdo, $playerID, $gameID) {
            try {
                $sql = "SELECT game.id, opp_1.id as opponent_1_id, opp_2.id as opponent_2_id, opp_1.name as opponent_1, opp_2.name as opponent_2 FROM game LEFT JOIN player as opp_1 ON game.opponent_1 = opp_1.id LEFT JOIN player as opp_2 ON game.opponent_2 = opp_2.id WHERE game.id = {$gameID};";
                $userRow = $pdo->query($sql)->fetch();
                if ($userRow['opponent_1_id'] == $playerID) {
                    return $userRow['opponent_2'];
                }
                else if ($userRow['opponent_2_id'] == $playerID) {
                    return $userRow['opponent_1'];
                }
                return null;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
            }
        }

        /**
         * Returns all information about the current game the player is assigned to
         * 
         * @param   database    %pdo - connection to database
         * @param   integer     $playerID - id of current player
         * @return  Game        game where current player takes part
         */
        public function getGame($pdo, $playerID) {
            try {
                $sql = "SELECT * FROM game WHERE opponent_1 = {$playerID} OR opponent_2 = {$playerID}";
                $userRow = $pdo->query($sql)->fetch();
                return $userRow;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                return null;
            }
        }


        /**
         * set flag to 1 that player is ready
         * 
         * @param DATABASE $pdo database connection
         * @param INTEGER $gameID id of current game
         * @param INTEGER $playerID id of current player
         */
        public function setReady($pdo, $gameID, $playerID) {
            $pdo->beginTransaction();
    
            try {
                $sql = "SELECT * FROM game WHERE opponent_1 = {$playerID} OR opponent_2 = {$playerID} LIMIT 1;";
                $userRow = $pdo->query($sql)->fetch();

                if ($userRow['opponent_1'] == $playerID) {
                    $sql = "UPDATE game SET isOpp1Ready = 1 WHERE id = {$gameID}";
                }
                else {
                    $sql = "UPDATE game SET isOpp2Ready = 1 WHERE id = {$gameID}";
                }
                $sth = $pdo->prepare($sql);
                $sth->execute();
                $pdo->commit();        
                return 1;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return 0;
            }
        }

        /**
         * checks if opponent has set his/her ships
         * 
         * @param DATABASE $pdo database connection
         * @param INTEGER $gameID id of current game
         * @param INTEGER $playerID id of current player
         */
        public function isOpponentReady($pdo, $gameID, $playerID) {
            try {
                $sql = "SELECT * FROM game WHERE id = {$gameID} AND opponent_1 = {$playerID} OR opponent_2 = {$playerID} LIMIT 1;";
                $userRow = $pdo->query($sql)->fetch();
                if ($userRow['opponent_1'] == $playerID) {
                    if ($userRow['isOpp2Ready'] == 1) {
                        return true;
                    }
                }
                else {
                    if ($userRow['isOpp1Ready'] == 1) {
                        return true;
                    }
                }
                return false;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
            }
        }

        /**
         * checks if active player is current player
         * 
         * @param DATABASE $pdo database connection
         * @param INTEGER $gameID id of current game
         * @param INTEGER $playerID id of current player
         */
        public function isMyTurn($pdo, $gameID, $playerID) {
            try {
                $sql = "SELECT * FROM game WHERE id = {$gameID}";
                $userRow = $pdo->query($sql)->fetch();
                if ($userRow['activePlayerID'] == $playerID) {
                    return 1;
                }
                return 0;
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
            }
        }

        /**
         * sets active player to the id of the other player
         * 
         * @param DATABASE $pdo database connection
         * @param INTEGER $gameID id of current game
         */
        public function changeActivePlayer($pdo, $gameID) {
            $sql = "SELECT * FROM game WHERE id = {$gameID};";
            $userRow = $pdo->query($sql)->fetch();

            if ($userRow == null) {
                return;
            }
            if ($userRow['activePlayerID'] == $userRow['opponent_1']) {
                $opponentID = $userRow['opponent_2'];
            }
            else {
                $opponentID = $userRow['opponent_1'];
            }

            $pdo->beginTransaction();
    
            try {
                $sql = "UPDATE game SET activePlayerID = {$opponentID} WHERE id = {$gameID}";
                $sth = $pdo->prepare($sql);
                
                $sth->execute();
                $pdo->commit();        
            } catch (PDOException $e) {
                die("<p>{$e->getMessage()}");
                $pdo->rollBack();
                return NULL;
            }
        }

    }