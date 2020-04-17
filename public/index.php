<?php
    use \Psr\Http\Message\ServerRequestInterface as Request;
    use \Psr\Http\Message\ResponseInterface as Response;

    require '../vendor/autoload.php';

    use \App\Models\Player;
    use \App\Models\Game;
    use \App\Models\Ship;
    use \App\Models\Turn;
    use \App\Models\Helper;

    $config['displayErrorDetails'] = true;
    $config['addContentLengthHeader'] = false;

    $db_config = parse_ini_file('../private/config.ini'); 

    $config['db']['host']   = $db_config['servername'];
    $config['db']['user']   = $db_config['username'];
    $config['db']['pass']   = $db_config['password'];
    $config['db']['dbname'] = $db_config['dbname'];

    $app = new \Slim\App(['settings' => $config]);

    #region container
    $container = $app->getContainer();

    $container['view'] = new \Slim\Views\PhpRenderer('./pages/');

    $container['db'] = function ($c) {
        $db = $c['settings']['db'];
        $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
            $db['user'], $db['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    };

    $container['helper'] = function ($c) {
        $helper = new Helper();
        return $helper;
    };

    $container['player'] = function ($c) {
        $player = new Player();
        return $player;
    };

    $container['game'] = function ($c) {
        $game = new Game();
        return $game;
    };

    $container['ship'] = function ($c) {
        $ship = new Ship();
        return $ship;
    };

    $container['turn'] = function ($c) {
        $turn = new Turn();
        return $turn;
    };
    #endregion container

    session_start();

    /**
     * HOME
     * 
     * Shows Lobby where you can sign up with a nickname
     * Redirects to WaitingLodge if nickname is valid
     */
    $app->get('/', function ($request, $response) {

        $nickname = $request->getParam('nickname');

        if (!$this->helper->isValidNickname($nickname)) {
            return $this->view->render($response, 'lobby.phtml');
        }

        $playerID = $this->player->addPlayer($this->db, $nickname);

        if ($playerID == null) {
            return $this->view->render($response, 'lobby.phtml');
        }

        $_SESSION['playerID'] = $playerID;

        $gameID = $this->player->findWaitingPlayer($this->db, $playerID);
        if ($gameID == null) {
            return $response->withRedirect($this->router->pathFor('WaitingLodge'));
        }

        $_SESSION['gameID'] = $gameID;
        return $response->withRedirect($this->router->pathFor('SetShips'));
        
    })->setName('Home');

    /**
     * WaitingLodge
     * 
     * Shows Lodge
     * Checking whether new free player comes online
     */
    $app->get('/WaitingLodge', function ($request, $response) {
        //only access if playerID is not null
        if ($_SESSION['playerID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }
        return $this->view->render($response, 'lodge.phtml');
    })->setName('WaitingLodge');


    /**
     * SetShips
     * 
     * User sets ships on grid
     * waits for opponent to also finish setting ships
     */
    $app->get('/SetShips', function ($request, $response, $args) {
        //only access if playerID and gameID is not null
        if ($_SESSION['playerID'] == null || $_SESSION['gameID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }

        $opponentName = $this->game->getOpponentName($this->db, $_SESSION['playerID'], $_SESSION['gameID']);    
        
        return $this->view->render($response, 'setShips.phtml', ['opponent' => $opponentName]);
    })->setName('SetShips');

    /**
     * Highscore
     * 
     * Show a list of all players ordered by the hits devided by the total amount of turns
     */
    $app->get('/Highscore', function (Request $request, Response $response) {
        $_SESSION['playerID'] = null;
        $_SESSION['gameID'] = null;

        $players = $this->player->GetHighscore($this->db);
    
        $response = $this->view->render($response, 'highscore.phtml', ['players' => $players, "router" => $this->router]);
        return $response;
    });

    /**
     * Game
     * 
     * Shows the grid of current player and opponent
     * Game takes place on that path
     */
    $app->get('/Game', function (Request $request, Response $response) {
        if ($_SESSION['playerID'] == null || $_SESSION['gameID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }
        $ships = $this->ship->getMyShips($this->db, $_SESSION['gameID'], $_SESSION['playerID']);
    
        $opponentName = $this->game->getOpponentName($this->db, $_SESSION['playerID'], $_SESSION['gameID']);

        $response = $this->view->render($response, 'game.phtml', ['ships' => $ships, 'playerID' => $_SESSION['playerID'], 'opponent' => $opponentName]);
        return $response;
    });

    /**
     * API
     * 
     * returns the turn based match as json
     */
    $app->get('/game/{gameID}', function ($request, $response, $args) {
        $turns = $this->turn->getAllTurns($this->db, $request->getAttribute('gameID'));

        $payload = json_encode($turns, JSON_NUMERIC_CHECK);
    
        $response->getBody()->write($payload);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    });

    ### region ACTIONS

    /**
     * Returns game if current player is assigned to a game
     * Updates waiting time to make sure that only players which are online are assigned
     */
    $app->get('/getFreePlayer', function ($request, $response, $args) {
        if ($_SESSION['playerID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }

        $game = $this->game->getGame($this->db, $_SESSION['playerID']);
        $this->player->updateWaitingTime($this->db, $_SESSION['playerID']);

        if ($game != null) {
            $_SESSION['gameID'] = $game['id'];
            $payload = json_encode($game);
    
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        }
        return null;

    })->setName('getFreePlayer');

    /**
     * saves the ships which are sorted on the grid and saves it to the fatabase
     */
    $app->get('/saveShips', function ($request, $response, $args) {
        if ($_SESSION['playerID'] == null || $_SESSION['gameID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }

        $data = $request->getParams();
        $payload = json_encode($data['ships']);

        $result = $this->ship->addShips($this->db, $data['ships'], $_SESSION['gameID'], $_SESSION['playerID']);
    
        //only if ship order is correct
        if ($result == 1) {
            $result = $this->game->setReady($this->db, $_SESSION['gameID'], $_SESSION['playerID']);
        }

        $response->getBody()->write($result);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    })->setName('saveShips');

    /**
     * checks if opponent has finished setting the ships on the grid
     */
    $app->get('/waitForOpponent', function ($request, $response, $args) {
        if ($_SESSION['playerID'] == null || $_SESSION['gameID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }

        $result = $this->game->isOpponentReady($this->db, $_SESSION['gameID'], $_SESSION['playerID']);

        $response->getBody()->write($result);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    })->setName('waitForOpponent');

    /**
     * checks if opponent has made a turn
     * checks if game is finished
     * checks whether a ship is fully sunk
     * get field which is hit last
     * calculates hit rate of opponent and current player
     */
    $app->get('/waitForNextTurn', function ($request, $response, $args) {
        if ($_SESSION['playerID'] == null || $_SESSION['gameID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }

        $array = array(
            "myturn" => 0,
            "finished" => 0,
            "hitFields" => null,
            "fullShips" => [],
            "myHitRate" => 0,
            "opponentHitRate" => 0
        );

        //is it my turn?
        $array["myturn"] = $this->game->isMyTurn($this->db, $_SESSION['gameID'], $_SESSION['playerID']);


        //check if game is finished
        $turn =  $this->turn;
        $array['finished'] = $turn->gameFinished($this->db, $_SESSION['gameID'], $_SESSION['playerID']);

        $array['fullShips'] = $turn->getSankShips($this->db, $_SESSION['gameID'], $_SESSION['playerID']);

        $array['hitFields'] = $turn->getHitFields($this->db, $_SESSION['gameID'], $_SESSION['playerID']);

        $hitRate = $turn->getHitRate($this->db, $_SESSION['gameID']);

        for ($i = 0; $i < sizeof($hitRate); $i++) {
            if ($hitRate[$i]['playerID'] == $_SESSION['playerID']) {
                $array['myHitRate'] = (int) $hitRate[$i]['hitrate'];
            }
            else {
                $array['opponentHitRate'] = (int) $hitRate[$i]['hitrate'];
            }
        }
        
        $response = $response->withJson($array);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    })->setName('waitForNextTurn');

    /**
     * adds move to database
     * changes active player if move was valid
     */
    $app->get('/makeTurn', function ($request, $response, $args) {
        if ($_SESSION['playerID'] == null || $_SESSION['gameID'] == null) {
            return $response->withRedirect($this->router->pathFor('Home'));
        }

        $data = $request->getParams();
        $payload = json_encode($data['x']);

        $hit = $this->turn->addMove($this->db, $_SESSION['gameID'], $_SESSION['playerID'], $data['x'], $data['y']);

        //if field is already clicked, than don't hit it again
        if ($hit != -1) {
            $this->game->changeActivePlayer($this->db, $_SESSION['gameID']);
        }

        $response->getBody()->write($hit);
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    })->setName('makeTurn');

    ### endregion ACTIONS

    $app->run();
?>