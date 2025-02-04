<?php
 /**
  *------
  * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
  * Falconry implementation : © Sam Richardson <samedr16@gmail.com>
  * 
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  * 
  * falconry.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class Falconry extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        
        
        $this->initGameStateLabels( array( 

            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
            'fast_duel_nobles' => 100,
            'allied_nobility' => 101,
            'joint_nobility' => 102,
            'team_setup_3' => 103,
            'team_setup_4' => 104

        ) );        
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "falconry";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = $this->getGameinfos();
        // Set the initial value of 'revert' to false
        $this->globals->set("REVERT", false);

        $default_colors = $gameinfos['player_colors'];
 
        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( ',', $values );
        $this->DbQuery( $sql );
        $this->reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        $this->reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //$this->setGameStateInitialValue( 'my_first_global_variable', 0 );
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //$this->initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //$this->initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // Function to get card type args for a specific color

        // Setup the initial game situation
        $players = $this->getCollectionFromDb("SELECT player_id, player_color, player_no FROM player ORDER BY player_no;");
        $playersNumber = $this->getPlayersNumber();

        // Initialize the positions for different player counts
        $positions = [
            2 => [
                ['column' => 2, 'row' => 2], // First player falconer
                ['column' => 2, 'row' => 3]  // Second player falconer
            ],
            3 => [
                ['column' => 3, 'row' => 3], // First player falconer
                ['column' => 2, 'row' => 3], // Second player falconer
                ['column' => 3, 'row' => 2]  // Third player falconer
            ]
        ];

        $cardQueries = [];

        if ($playersNumber == 2) {
            $playerArray = array_values($players);
            // Get card type args for both players
            $player1Cards = $this->getCardTypeArgsForColor($playerArray[0]['player_color']);
            $player2Cards = $this->getCardTypeArgsForColor($playerArray[1]['player_color']);
            
            // Place falconers opposite each other
            $cardQueries[] = sprintf(
                "INSERT INTO card (card_type_arg, `column`, `row`, player_id) VALUES 
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d);",
                $player1Cards['falconer'], $positions[2][0]['column'], $positions[2][0]['row'], $playerArray[0]['player_id'],
                $player1Cards['bird'], 3, 3, $playerArray[0]['player_id'],
                $player2Cards['falconer'], $positions[2][1]['column'], $positions[2][1]['row'], $playerArray[1]['player_id'],
                $player2Cards['bird'], 3, 2, $playerArray[1]['player_id']
            );

       // [Previous helper functions and 2-player code remain the same]

        } else if ($playersNumber == 3) {
            $playerArray = array_values($players);
            $isAlliedNobility = $this->getGameStateValue('allied_nobility') == 2;
            
            if ($isAlliedNobility) {
                // Allied nobility variant - lone player goes last
                $positions = [
                    ['column' => 2, 'row' => 3], // First player
                    ['column' => 3, 'row' => 2], // Second player
                    ['column' => 3, 'row' => 3]  // Lone player (goes last)
                ];
                
                // Reorder players so lone player is last
                $orderedPlayers = [
                    $playerArray[1],  // Team player 1
                    $playerArray[2],  // Team player 2
                    $playerArray[0]   // Lone player
                ];

                // Update team assignments
                $this->DbQuery("
                    UPDATE player
                    SET 
                        team = CASE
                            WHEN player_id = {$orderedPlayers[2]['player_id']} THEN 0
                            WHEN player_id = {$orderedPlayers[0]['player_id']} THEN {$orderedPlayers[1]['player_id']}
                            WHEN player_id = {$orderedPlayers[1]['player_id']} THEN {$orderedPlayers[0]['player_id']}
                        END,
                        marker = CASE
                            WHEN player_id IN ({$orderedPlayers[0]['player_id']}, {$orderedPlayers[1]['player_id']}) THEN marker - 5
                            ELSE marker
                        END,
                        team_no = CASE
                            WHEN player_id = {$orderedPlayers[2]['player_id']} THEN 2
                            ELSE 1
                        END
                    WHERE player_id IN ({$orderedPlayers[0]['player_id']}, {$orderedPlayers[1]['player_id']}, {$orderedPlayers[2]['player_id']});
                ");
                
                $this->activeNextPlayer();
            } else {
                // Standard 3-player mode - starting player in middle
                $positions = [
                    ['column' => 3, 'row' => 2], // Left player
                    ['column' => 3, 'row' => 3], // Middle player (starter)
                    ['column' => 2, 'row' => 3]  // Right player
                ];
                
                // Reorder players to put first player in middle position
                $orderedPlayers = [
                    $playerArray[2],  // Left player
                    $playerArray[0],  // Middle player (starter)
                    $playerArray[1]   // Right player
                ];
            }

            // Get card type args for all players
            $playerCards = [];
            foreach ($orderedPlayers as $player) {
                $playerCards[] = $this->getCardTypeArgsForColor($player['player_color']);
            }

            // Insert cards with birds in storage
            $cardQueries[] = sprintf(
                "INSERT INTO card (card_type_arg, `column`, `row`, player_id) VALUES 
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d),
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d),
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d);",
                $playerCards[0]['falconer'], $positions[0]['column'], $positions[0]['row'], $orderedPlayers[0]['player_id'],
                $playerCards[0]['bird'], -3, -3, $orderedPlayers[0]['player_id'],
                $playerCards[1]['falconer'], $positions[1]['column'], $positions[1]['row'], $orderedPlayers[1]['player_id'],
                $playerCards[1]['bird'], -3, -3, $orderedPlayers[1]['player_id'],
                $playerCards[2]['falconer'], $positions[2]['column'], $positions[2]['row'], $orderedPlayers[2]['player_id'],
                $playerCards[2]['bird'], -3, -3, $orderedPlayers[2]['player_id']
            );

            // [Previous code remains the same until 4-player section]

        // [Previous code remains the same until 4-player section]

        } else if ($playersNumber == 4) {
            $playerArray = array_values($players);
            
            // Define positions for 4 players in clockwise order
            $positions = [
                ['column' => 2, 'row' => 2], // Position for Player 1
                ['column' => 3, 'row' => 2], // Position for Player 2
                ['column' => 3, 'row' => 3], // Position for Player 3 (diagonal from 1)
                ['column' => 2, 'row' => 3]  // Position for Player 4 (diagonal from 2)
            ];
            
            // Get card type args for all players
            $playerCards = array_map(function($player) {
                return $this->getCardTypeArgsForColor($player['player_color']);
            }, $playerArray);

            if ($this->getGameStateValue('joint_nobility') == 2) {
                // First get all player IDs
                $playerIds = [];
                foreach ($players as $player) {
                    $playerIds[$player['player_no']] = $player['player_id'];
                }

                // Update team assignments without self-referencing subqueries
                $this->DbQuery("
                    UPDATE player
                    SET 
                        team = CASE
                            WHEN player_no = 1 THEN {$playerIds[3]}
                            WHEN player_no = 2 THEN {$playerIds[4]}
                            WHEN player_no = 3 THEN {$playerIds[1]}
                            WHEN player_no = 4 THEN {$playerIds[2]}
                        END,
                        team_no = CASE
                            WHEN player_no IN (1, 3) THEN 1
                            WHEN player_no IN (2, 4) THEN 2
                        END
                    WHERE player_no IN (1, 2, 3, 4)
                ");
            }

            // Insert cards for all players in clockwise order with proper team positioning
            $cardQueries[] = sprintf(
                "INSERT INTO card (card_type_arg, `column`, `row`, player_id) VALUES 
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d),
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d),
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d),
                (%d, %d, %d, %d), 
                (%d, %d, %d, %d);",
                // Player 1 (Team 1) at (2,2)
                $playerCards[0]['falconer'], $positions[0]['column'], $positions[0]['row'], $playerArray[0]['player_id'],
                $playerCards[0]['bird'], -3, -3, $playerArray[0]['player_id'],
                // Player 2 (Team 2) at (3,2)
                $playerCards[1]['falconer'], $positions[1]['column'], $positions[1]['row'], $playerArray[1]['player_id'],
                $playerCards[1]['bird'], -3, -3, $playerArray[1]['player_id'],
                // Player 3 (Team 1) at (3,3)
                $playerCards[2]['falconer'], $positions[2]['column'], $positions[2]['row'], $playerArray[2]['player_id'],
                $playerCards[2]['bird'], -3, -3, $playerArray[2]['player_id'],
                // Player 4 (Team 2) at (2,3)
                $playerCards[3]['falconer'], $positions[3]['column'], $positions[3]['row'], $playerArray[3]['player_id'],
                $playerCards[3]['bird'], -3, -3, $playerArray[3]['player_id']
            );
        }


        // Execute all card insertion queries
        foreach ($cardQueries as $query) {
            $this->DbQuery($query);
        }

        // Activate first player
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    private function getCardTypeArgsForColor($color) {
        foreach ($this->fal_cards as $card) {
            if ($card['color'] === $color) {
                if ($card['type'] === 'falconer') {
                    $falconer = $card['type_arg'];
                } else if ($card['type'] === 'bird') {
                    $bird = $card['type_arg'];
                }
            }
        }
        return ['falconer' => $falconer, 'bird' => $bird];
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = $this->getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_no, player_score score, marker, team_no FROM player ";
        $result['players'] = $this->getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $sql = "SELECT * FROM `card`";
        $result['cards'] = $this->getCollectionFromDB($sql);
       
        $result['current_turn'] = $this->getActivePlayerId();

        $result['card_types'] = $this->fal_cards;

        $result['grid']['row'] = intval($this->getUniqueValueFromDB("SELECT Max(`row`) FROM `card`;"));
        $result['grid']['column'] = intval($this->getUniqueValueFromDB("SELECT Max(`column`) FROM `card`;"));


        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        if ($this->getPlayersNumber() == 2) {
            $total_cards = 36;
        }
        else if ($this->getPlayersNumber() == 3 && $this->getGameStateValue('allied_nobility') == 2) {
            $total_cards = 44;
        } else if ($this->getPlayersNumber() == 3 && $this->getGameStateValue('allied_nobility') == 1) {
            $total_cards = 54;
        } else {
            $total_cards = 72;
        }

        $cards_played = $total_cards;
        $players = $this->getCollectionFromDb("SELECT marker FROM player;");

        foreach ($players as $player) {
            $cards_played -= intval($player["marker"]);
        }
        

        return round($cards_played / $total_cards * 100);
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function updateCardEdges() {
        // add where clauses to not change the card in the negative for multiplayer
        if (intval($this->getUniqueValueFromDB("SELECT MIN(`row`) FROM `card` WHERE `row` > 0;")) == 1) {
            $this->DbQuery("UPDATE `card` SET `row` = `row` + 1 WHERE `row` > 0");
            $this->DbQuery("UPDATE `move` SET `previous_row` = CASE WHEN `previous_row` IS NOT NULL AND `previous_row` > 0 THEN `previous_row` + 1
            ELSE `previous_row` END, `new_row` = `new_row` + 1");

        } else if (intval($this->getUniqueValueFromDB("SELECT MIN(`row`) FROM `card` WHERE `row` > 0;")) == 3) {
            $this->DbQuery("UPDATE `card` SET `row` = `row` - 1 WHERE `row` > 0;");
            $this->DbQuery("UPDATE `move` SET `previous_row` = CASE WHEN `previous_row` IS NOT NULL AND `previous_row` > 0 THEN `previous_row` - 1
            ELSE `previous_row` END, `new_row` = `new_row` - 1");
        } 
        if (intval($this->getUniqueValueFromDB("SELECT MIN(`column`) FROM `card` WHERE `column` > 0;")) == 1) {
            $this->DbQuery("UPDATE `card` SET `column` = `column` + 1 WHERE `column` > 0;");
            $this->DbQuery("UPDATE `move` SET `previous_col` = CASE WHEN `previous_col` IS NOT NULL AND `previous_col` > 0 THEN `previous_col` + 1
            ELSE `previous_col` END, `new_col` = `new_col` + 1");

        } else if (intval($this->getUniqueValueFromDB("SELECT MIN(`column`) FROM `card` WHERE `column` > 0;")) == 3) {
            $this->DbQuery("UPDATE `card` SET `column` = `column` - 1 WHERE `column` > 0;");
            $this->DbQuery("UPDATE `move` SET `previous_col` = CASE WHEN `previous_col` IS NOT NULL AND `previous_col` > 0 THEN `previous_col` - 1
            ELSE `previous_col` END, `new_col` = `new_col` - 1");
        }
    }

    // Function to check if removing a card would create a winning condition for another player
    function validateCardRemoval($current_row, $current_col, $card_to_move_id, $numToCheck) {
        $moving_card = $this->getObjectFromDB("SELECT * FROM card WHERE card_id = $card_to_move_id");
        $query = "SELECT * FROM card WHERE NOT card_id = $card_to_move_id ORDER BY `row`, `column`";
        $cards = $this->getCollectionFromDb($query);
    
        $cards_at_position = array_filter($cards, function($card) use ($current_row, $current_col) {
            return $card['row'] == $current_row && $card['column'] == $current_col;
        });
        
        if (empty($cards_at_position)) {
            return true;
        }
    
        // Simply find the highest zIndex card underneath
        $highest_zIndex = -1;
        $highest_card = null;
        foreach ($cards_at_position as $card) {
            $card_zIndex = intval($this->fal_cards[$card['card_type_arg']]['zIndex']);
            if ($card_zIndex > $highest_zIndex) {
                $highest_zIndex = $card_zIndex;
                $highest_card = $card;
            }
        }
    
        $check_player_id = $highest_card ? $highest_card['player_id'] : null;
    
        if (!$check_player_id || $check_player_id == $moving_card['player_id']) {
            return true;
        }
    
        $board = [];
        foreach ($cards as $card) {
            $row = $card['row'];
            $column = $card['column'];
            if (!isset($board[$row])) {
                $board[$row] = [];
            }
            if (!isset($board[$row][$column])) {
                $board[$row][$column] = [];
            }
            $board[$row][$column][] = $card;
        }
    
        $directions = [
            [0, 1],  // Horizontal
            [1, 0],  // Vertical
            [1, 1],  // Diagonal down-right
            [1, -1], // Diagonal down-left
        ];
    
        $player_id = $check_player_id;
        
        foreach ($directions as $dir) {
            // Extend the search range to ensure we catch all possible lines
            $start_row = $current_row - ($numToCheck - 1);
            $end_row = $current_row + ($numToCheck - 1);
            $start_col = $current_col - ($numToCheck - 1);
            $end_col = $current_col + ($numToCheck - 1);
            
            for ($row = $start_row; $row <= $end_row; $row++) {
                for ($col = $start_col; $col <= $end_col; $col++) {
                    $count = 0;
                    $valid = true;
                    $line_positions = [];
                    
                    for ($i = 0; $i < $numToCheck; $i++) {
                        $check_row = $row + $i * $dir[0];
                        $check_col = $col + $i * $dir[1];
                        
                        // Store positions we're checking for debugging
                        $line_positions[] = [$check_row, $check_col];
                        
                        if (isset($board[$check_row][$check_col]) && !empty($board[$check_row][$check_col])) {
                            // Find the highest card at this position
                            $highest_zIndex = -1;
                            $winning_player_owns_highest = false;
                            
                            foreach ($board[$check_row][$check_col] as $card) {
                                $card_zIndex = intval($this->fal_cards[$card['card_type_arg']]['zIndex']);
                                if ($card_zIndex > $highest_zIndex) {
                                    $highest_zIndex = $card_zIndex;
                                    $winning_player_owns_highest = ($card['player_id'] == $player_id);
                                }
                            }
                            
                            if ($winning_player_owns_highest) {
                                $count++;
                            } else {
                                $valid = false;
                                break;
                            }
                        } else {
                            $valid = false;
                            break;
                        }
                    }
                    
                    if ($valid && $count == $numToCheck) {
                        // Found a valid line that would be broken
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    function checkFiveInARow($current_row, $current_col, $card_moved_id, $numToCheck) {
        $moved_card = $this->getObjectFromDB("SELECT * FROM card WHERE card_id = $card_moved_id");
        if (!$moved_card) {
            return false;
        }
        
        $player_id = $moved_card['player_id'];
        $query = "SELECT * FROM card ORDER BY `row`, `column`";
        $cards = $this->getCollectionFromDb($query);
    
        if (empty($cards)) {
            return false;
        }
    
        $board = [];
        foreach ($cards as $card) {
            $row = $card['row'];
            $column = $card['column'];
            if (!isset($board[$row])) {
                $board[$row] = [];
            }
            if (!isset($board[$row][$column])) {
                $board[$row][$column] = [];
            }
            $board[$row][$column][] = $card;
        }
    
        $directions = [
            [0, 1],  // Horizontal
            [1, 0],  // Vertical
            [1, 1],  // Diagonal down-right
            [1, -1], // Diagonal down-left
        ];
        
        foreach ($directions as $dir) {
            // Expand search range to include sequences both before and after current position
            $start_row = $current_row - ($numToCheck - 1);
            $end_row = $current_row + ($numToCheck - 1);
            $start_col = $current_col - ($numToCheck - 1);
            $end_col = $current_col + ($numToCheck - 1);
            
            for ($row = $start_row; $row <= $end_row; $row++) {
                for ($col = $start_col; $col <= $end_col; $col++) {
                    $count = 0;
                    $valid = true;
                    $positions = []; // For debugging
                    
                    for ($i = 0; $i < $numToCheck; $i++) {
                        $check_row = $row + $i * $dir[0];
                        $check_col = $col + $i * $dir[1];
                        $positions[] = [$check_row, $check_col]; // For debugging
                        
                        if (isset($board[$check_row][$check_col]) && !empty($board[$check_row][$check_col])) {
                            // Find the highest card at this position
                            $highest_zIndex = -1;
                            $player_owns_highest = false;
                            
                            foreach ($board[$check_row][$check_col] as $card) {
                                $card_zIndex = intval($this->fal_cards[$card['card_type_arg']]['zIndex']);
                                if ($card_zIndex > $highest_zIndex) {
                                    $highest_zIndex = $card_zIndex;
                                    $player_owns_highest = ($card['player_id'] == $player_id);
                                }
                            }
                            
                            if ($player_owns_highest) {
                                $count++;
                            } else {
                                $valid = false;
                                break;
                            }
                        } else {
                            $valid = false;
                            break;
                        }
                    }
                    
                    if ($valid && $count == $numToCheck) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }


    function isAdjacent($col, $row, $card_id) {
        // Query to check if there is a card directly beneath
        $beneathCardExists = self::getUniqueValueFromDB("
            SELECT COUNT(*) 
            FROM `card`
            WHERE `column` = $col AND `row` = $row" . 
            ($card_id == 400 ? "" : " AND card_id != $card_id")
        );

        // If a card is directly beneath, return true
        if ($beneathCardExists > 0) {
            return true;
        }
        
        
        // Query to fetch all cards from the database except the one being checked (if it exists).
        $cards = self::getObjectListFromDB("
            SELECT `column`, `row` 
            FROM `card` 
            WHERE " . ($card_id == 400 ? "1" : "card_id != $card_id")
        );
    
        // Iterate through existing cards to check adjacency.
        foreach ($cards as $card) {
            if (
                ($card['column'] == $col && abs($card['row'] - $row) == 1) || // Adjacent vertically
                ($card['row'] == $row && abs($card['column'] - $col) == 1)    // Adjacent horizontally
            ) {
                return true; // Card is adjacent
            }
        }
    
        return false; // No adjacent cards found
    }
    

    function getPlayerIdOfHighestZIndexBeneath($cardId) {
        // Step 1: Fetch column, row, and card_type_arg of the given card
        $cardDetails = self::getObjectFromDB("
            SELECT `column`, `row`, `card_type_arg`
            FROM `card`
            WHERE `card_id` = $cardId
        ");
        
        if (!$cardDetails) {
            throw new Exception("Card with ID $cardId not found.");
        }
        
        $column = $cardDetails['column'];
        $row = $cardDetails['row'];
        $currentCardTypeArg = $cardDetails['card_type_arg'];
    
        // Step 2: Get the zIndex of the current card
        $currentZIndex = $this->fal_cards[$currentCardTypeArg]['zIndex'];
    
        // Step 3: Fetch all cards in the same column and row, excluding the current card
        $cardsInSamePosition = self::getCollectionFromDB("
            SELECT `card_id`, `card_type_arg`, `player_id`
            FROM `card`
            WHERE `column` = $column
              AND `row` = $row
              AND `card_id` != $cardId
        ");
    
        // Step 4: Filter cards with a lower zIndex and find the highest
        $highestZIndex = -1;
        $playerIdWithHighestZIndex = null;
    
        foreach ($cardsInSamePosition as $card) {
            $zIndex = $this->fal_cards[$card['card_type_arg']]['zIndex'];
            if ($zIndex < $currentZIndex && $zIndex > $highestZIndex) {
                $highestZIndex = $zIndex;
                $playerIdWithHighestZIndex = $card['player_id'];
            }
        }
    
        // Step 5: Return the player ID of the card with the highest zIndex
        return $playerIdWithHighestZIndex;
    }
    


//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in falconry.action.php)
    */

    // function checkMoveCard($playerId, $cardId, $toRow, $toCol, $zIndex) {
    //     $this->checkAction('checkMoveCard');
    //     $current_player_id = $this->getActivePlayerId();
    //     // Check if the card ID is 400
    //     if ($cardId == 400 || $current_player_id != $playerId) {
    //         // Query to check if the target position is already occupied
    //         $sql = "SELECT COUNT(*) FROM `card` WHERE `row` = $toRow AND `column` = $toCol;";
    //         $checkTile = $this->getUniqueValueFromDB($sql);
    
    //         // If a card exists in the target position, reject the action
    //         if (intval($checkTile) > 0 || $current_player_id != $playerId) {
                
                
    //             // Throw an exception to stop further processing
    //             throw new BgaUserException(self::_('This action is not allowed.'));
    //         }
    //     }
    // }

    function playTile($playerId, 
    $cardId, 
    $toRow, 
    $toCol,
    $fromRow,
    $fromCol,
    $fromStorage) {
        $this->checkAction('moveCard');
        $active_player_id = $this->getActivePlayerId();
        // Check if the card ID is 400
        if ($active_player_id != $playerId) {
            self::notifyPlayer($playerId, 'actionRejected', clienttranslate("It's not your turn"), []);
                
            // Throw an exception to stop further processing
            throw new BgaUserException(self::_("It's not your turn"));
        }

        // //check if adjacent
        if (!$this->isAdjacent($toCol, $toRow, $cardId)) {
            self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You must place this card adjacent to another card"), []);
                
            // Throw an exception to stop further processing
            throw new BgaUserException(self::_("You must place this card adjacent to another card"));
        }

        $cardsInSpot = $this->getCollectionFromDB("SELECT card_type_arg FROM `card` WHERE `row` = $toRow AND `column` = $toCol;");

            foreach ($cardsInSpot as $index) {
                $toCard = $this->fal_cards[$index["card_type_arg"]]["type"];
                if ($cardId == 400) {
                    self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You may not place a marker on a $toCard"), []);
                                
                    // Throw an exception to stop further processing
                    throw new BgaUserException(self::_("You may not place a marker on a $toCard"));

                }
                else {
                
                if ( intval($this->fal_cards[$index["card_type_arg"]]["zIndex"]) >= intval($this->fal_cards[$this->getUniqueValueFromDB("SELECT card_type_arg FROM `card` WHERE card_id = $cardId;")]["zIndex"])) { 
                    $cardPlaced = $this->fal_cards[$this->getUniqueValueFromDB("SELECT card_type_arg FROM `card` WHERE card_id = $cardId;")]["type"];

                    self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You may not place a $cardPlaced on a $toCard"), []);
                                
                    // Throw an exception to stop further processing
                    throw new BgaUserException(self::_("You may not place a $cardPlaced on a $toCard"));
                } 
                // else if ($this->fal_cards[$index["card_type_arg"]]["color"] == $this->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id = $playerId")) {
                //     self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You may not place a card on your own color"), []);
                                
                //     // Throw an exception to stop further processing
                //     throw new BgaUserException(self::_("You may not place a card on your own color"));
                // }

            }
        }




        if ($cardId == 400) {
            if ($this->getUniqueValueFromDB("SELECT marker FROM player WHERE player_id = $playerId") == 0) {
                self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You don't have any more marker cards"), []);
                throw new BgaUserException(self::_("You don't have any more marker cards"));
            }

            // Query to check if the target position is already occupied
            
                $color = $this->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id = $playerId;");
                if ($color == '008000') {
                    $card_type = 2;
                }
                else if ($color == 'ffa500') {
                    $card_type = 5;
                }
                else if ($color == '0000ff') {
                    $card_type = 8;
                }
                else if ($color == 'ff0000') {
                    $card_type = 11;
                }
            $card = "INSERT INTO `card` (card_type_arg, `column`, `row`, player_id) VALUES ($card_type, $toCol, $toRow, $playerId);";
            $this->DbQuery($card);
            $this->DbQuery("UPDATE player SET marker = marker - 1 WHERE player_id = $playerId");
                
        }
        else {
            $fromRow = intval($this->getUniqueValueFromDB("SELECT `row` FROM `card` WHERE card_id = $cardId;"));
            $fromCol = intval($this->getUniqueValueFromDB("SELECT `column` FROM `card` WHERE card_id = $cardId;"));
            if (intval($this->getUniqueValueFromDB("SELECT COUNT(*) FROM `card` WHERE `column` = $fromCol AND `row` = $fromRow AND card_id != $cardId AND $fromCol > 0;")) > 0) {
                $cardsOnTop = $this->getCollectionFromDB("SELECT card_type_arg FROM `card` WHERE `column` = $fromCol AND `row` = $fromRow AND card_id != $cardId;");
                foreach ($cardsOnTop as $card_idx) {
                    if (intval($this->fal_cards[$card_idx["card_type_arg"]]["zIndex"]) > intval($this->fal_cards[$this->getUniqueValueFromDB("SELECT card_type_arg FROM `card` WHERE card_id = $cardId;")]["zIndex"])) {
                        // validation to ensure a card beneath another card cannot be played
                        self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You may not move this card"), []);
                                
                        // Throw an exception to stop further processing
                        throw new BgaUserException(self::_("You may not move this card"));
                    }
                }
            }

            // make sure they aren't moving another player's tile
            if ($this->getUniqueValueFromDB("SELECT player_id FROM `card` WHERE card_id = $cardId") != $playerId) {
                self::notifyPlayer($playerId, 'actionRejected', clienttranslate("You may not move another player's tile"), []);
                // Throw an exception to stop further processing
                throw new BgaUserException(self::_("You may not move another player's tile"));
            }

            if (($this->getPlayersNumber() == 2 && $this->getGameStateValue('fast_duel_nobles') == 1) || ($this->getGameStateValue('allied_nobility') == 2 && ($this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $playerId") == 0 || $this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $playerId") == $this->getPlayerIdOfHighestZIndexBeneath($cardId)))) {
                $numToCheck = 5;
            } else {
                $numToCheck = 6;
            }


            if (!$this->validateCardRemoval($fromRow, $fromCol, $cardId, $numToCheck)) {
                throw new BgaUserException(_("This move is not allowed as it would create a winning condition for another player"));
            }
            
            $card = "UPDATE `card` SET `column` = $toCol, `row` = $toRow WHERE card_id = $cardId;";
            $this->DbQuery($card);
        }
        function findValueByColor($cards, $targetColor) {
            foreach ($cards as $card) {
                if ($card['color'] == $targetColor && $card['type'] == "marker") {
                    return $card['value']; // Return the 'value' of the matching card
                }
            }
            return null; // Return null if no match is found
        }
        $currentStateName = $this->gamestate->state()['name'];
if ($currentStateName == 'playerTurn') {
    // Clear move history at start of turn
    $this->DbQuery("DELETE FROM `move`;");
}

// Check if this is a new card from storage (card_id = 400)
if ($cardId == 400) {
    $cardId = $this->getUniqueValueFromDB("SELECT card_id FROM `card` ORDER BY card_id DESC LIMIT 1;");
    $color = $this->getUniqueValueFromDB("SELECT player_color FROM player WHERE player_id = $playerId");
    $marker = strval(findValueByColor($this->fal_cards, $color));
    
    // Insert move with storage flag and new position
    $this->DbQuery("INSERT INTO `move` (
        card_value,
        card_id,
        from_storage,
        previous_row,
        previous_col,
        new_row,
        new_col
    ) VALUES (
        '$marker',
        $cardId,
        $fromStorage,
        NULL,
        NULL,
        $toRow,
        $toCol
    );");
} else {
    $arg = $this->getUniqueValueFromDB("SELECT card_type_arg FROM `card` WHERE card_id = $cardId");
    $marker = $this->fal_cards[$arg]["value"];
    // Insert move for existing card with previous position
    $this->DbQuery("INSERT INTO `move` (
        card_value,
        card_id,
        from_storage,
        previous_row,
        previous_col,
        new_row,
        new_col
    ) VALUES (
        '$marker',
        $cardId,
        FALSE,
        $fromRow,
        $fromCol,
        $toRow,
        $toCol
    );");
}

    if ($currentStateName == "playerTurn") {
        // Set the initial value of 'revert' to false
        $this->globals->set("REVERT", false);

    }


        if (($this->getPlayersNumber() == 2 && $this->getGameStateValue('fast_duel_nobles') == 1) || $currentStateName == 'playerTurn2' || ($this->getGameStateValue('allied_nobility') == 2 && $this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $playerId") != 0)) {
            $this->gamestate->nextState('confirmTurn');
        } 
        //else if ($this->getPlayersNumber() == 3 || $this->getPlayersNumber() == 4 || $this->getGameStateValue('fast_duel_nobles') == 2) {
            else {
            
            $this->gamestate->nextState('transition');
            
        }
    }

    function applyMove() {
        $this->checkAction('confirmAction');
        $this->gamestate->nextState('checkWin');
    }

    function revertMove() {  
        $this->checkAction('undoAction');      
        $player = $this->getActivePlayerId();
        
        $move = $this->getUniqueValueFromDB("SELECT move_id FROM `move` ORDER BY move_id DESC LIMIT 1;");
            $cardId = intval($this->getUniqueValueFromDB("SELECT card_id FROM `move` WHERE move_id = $move;"));
            if($this->getUniqueValueFromDB("SELECT from_storage FROM `move` WHERE move_id = $move;") == 1) {
                $this->DbQuery("DELETE FROM `card` WHERE card_id = $cardId;");
                $this->DbQuery("UPDATE `player` SET marker = marker + 1 WHERE player_id = $player;");
            } else {
                $col = intval($this->getUniqueValueFromDB("SELECT previous_col FROM `move` WHERE move_id = $move;"));
                $row = intval($this->getUniqueValueFromDB("SELECT previous_row FROM `move` WHERE move_id = $move;"));
                $this->DbQuery("UPDATE `card` SET `column` = $col, `row` = $row WHERE card_id = $cardId;");
            }
        
        // change the game state last so that it already reverts the data in the database before it's sent back to JS
        $this->gamestate->nextState('revert');
        
    }

    function revertMove2() {
        $this->checkAction('undoAction2');
        $player = $this->getActivePlayerId();
        $move = $this->getUniqueValueFromDB("SELECT move_id FROM `move` ORDER BY move_id ASC LIMIT 1;");
        
            $cardId = intval($this->getUniqueValueFromDB("SELECT card_id FROM `move` WHERE move_id = $move;"));
            if($this->getUniqueValueFromDB("SELECT from_storage FROM `move` WHERE move_id = $move;") == 1) {
                $this->DbQuery("DELETE FROM `card` WHERE card_id = $cardId;");
                $this->DbQuery("UPDATE `player` SET marker = marker + 1 WHERE player_id = $player;");
            } else {
                $col = intval($this->getUniqueValueFromDB("SELECT previous_col FROM `move` WHERE move_id = $move;"));
                $row = intval($this->getUniqueValueFromDB("SELECT previous_row FROM `move` WHERE move_id = $move;"));
                $this->DbQuery("UPDATE `card` SET `column` = $col, `row` = $row WHERE card_id = $cardId;");
            }
        
        // change the game state last so that it already reverts the data in the database before it's sent back to JS
        $this->gamestate->nextState('extraRevert');

    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        $this->checkAction( 'playCard' ); 
        
        $player_id = $this->getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        $this->notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => $this->getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

       function argMyGameState() {
        $result = array();
    
        $current_player_id = $this->getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_no, player_score score, player_color color, marker, team_no FROM player ";
        $result['players'] = $this->getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $sql = "SELECT * FROM `card`";
        $result['cards'] = $this->getCollectionFromDB($sql);
       
        $result['current_turn'] = $this->getActivePlayerId();

        $result['card_types'] = $this->fal_cards;
        //$result['last_turn'] = intval($this->getUniqueValueFromDB("SELECT gamelog_current_player FROM gamelog ORDER BY gamelog_packet_id DESC LIMIT 1;"));

        $result['grid']['row'] = intval($this->getUniqueValueFromDB("SELECT Max(`row`) FROM `card`;"));
        $result['grid']['column'] = intval($this->getUniqueValueFromDB("SELECT Max(`column`) FROM `card`;"));
        

        return $result;
    }

    function argConfirmTurn() {
        $result = array();

        if ($this->gamestate->state()['name'] == "revert" || $this->gamestate->state()['name'] == "confirmTurn") {
            $moves = $this->getCollectionFromDB("
            SELECT m.*, 
                   CASE WHEN m.previous_row IS NULL THEN -1 ELSE m.previous_row END as previous_row,
                   CASE WHEN m.previous_col IS NULL THEN -1 ELSE m.previous_col END as previous_col,
                   m.card_value,
                   m.new_row,
                   m.new_col,
                   m.from_storage
            FROM move m 
            ORDER BY move_id DESC 
            LIMIT 1
        ");
        }
        else {
            $moves = $this->getCollectionFromDB("
            SELECT m.*, 
                   CASE WHEN m.previous_row IS NULL THEN -1 ELSE m.previous_row END as previous_row,
                   CASE WHEN m.previous_col IS NULL THEN -1 ELSE m.previous_col END as previous_col,
                   m.card_value,
                   m.new_row,
                   m.new_col,
                   m.from_storage
            FROM move m 
            ORDER BY move_id ASC 
            LIMIT 1
        ");
        }
        
    
        $count = 0;
        foreach ($moves as $move) {
            $suffix = ($count == 0) ? "" : strval($count);
            
            $result["card_value$suffix"] = $move['card_value'];
            $result["previous_row$suffix"] = intval($move['previous_row']);
            $result["previous_col$suffix"] = intval($move['previous_col']);
            $result["new_row$suffix"] = intval($move['new_row']);
            $result["new_col$suffix"] = intval($move['new_col']);
            $result["from_storage$suffix"] = $move['from_storage'];
            
            $count++;
        }
        
        $result['count'] = $count;
        if ($this->gamestate->state()['name'] == "transition" && $this->globals->get("REVERT") == true) {
            $result['count'] = 0;
        }
        
            return array_merge($result, $this->argMyGameState());
    }
    

    

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function stRevert1() {
        $player_id = $this->getActivePlayerId();

        // current logic for several game players. later needs to be changed to TODO game options
        if (($this->getPlayersNumber() == 2 && $this->getGameStateValue('fast_duel_nobles') == 1) || ($this->getGameStateValue('allied_nobility') == 2 && $this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $player_id;") != 0)) {
            $this->gamestate->nextState('playerTurn');
        }
        else {
            
            // delete last move
            $move = $this->getUniqueValueFromDB("SELECT move_id FROM `move` ORDER BY move_id DESC LIMIT 1;");
            $this->DbQuery("DELETE FROM `move` WHERE move_id = $move;");
            
            $this->gamestate->nextState('transition');
            
        }
    }

    function stTransition() {
        $this->updateCardEdges();

        $this->gamestate->nextState('playerTurn2');
    }

    function stExtraRevert() {
        $this->updateCardEdges();
        $this->gamestate->nextState('revert2');
    }

function stRevert2() {
    $this->gamestate->nextState('playerTurn');
    $this->DbQuery("DELETE FROM `move`;");
}

    function stRevert() {
        $this->globals->set("REVERT", true);

        $this->gamestate->nextState('revert1');
    }

    function stNextPlayer() {
        $this->gamestate->nextState('playerTurn');       
    }

    function stpostEnd() {
        $this->gamestate->nextState('gameEnd');  
    }


    function stCheckWin() {
        $this->updateCardEdges();
        $moves = $this->getCollectionFromDB("SELECT move_id FROM `move` ORDER BY move_id");


        foreach ($moves as $move) {
            $move_id = $move['move_id'];
            $cardId = $this->getUniqueValueFromDB("SELECT card_id FROM `move` WHERE move_id = $move_id");
            $card_type = $this->getUniqueValueFromDB("SELECT card_type_arg FROM `card` WHERE card_id = $cardId");
            $player_id =  $this->getActivePlayerId();

            if ($this->getUniqueValueFromDB("SELECT from_storage FROM `move` WHERE move_id = $move_id") == 1 || $this->getUniqueValueFromDB("SELECT previous_row FROM `move` WHERE move_id = $move_id") == -3) {
                $move_name = "played";
            } else {
                $move_name = "moved";
            }

            // Get player name for notification
            $player_name = $this->getPlayerNameById($player_id);
    
            // Notify all players about the card played
            $this->notifyAllPlayers(
                'playTile',
                clienttranslate('${player} ${move} a ${card_value} card'),
                array(
                    'player' => $player_name,
                    'move' => $move_name,
                    'card_value' => $this->fal_cards[$card_type]["type"]
                )
            );
            // set last moved to 1 to highlight recently moved cards
            $this->DbQuery("UPDATE `card` SET last_moved = 1 WHERE card_id = $cardId;");

            // remove last cards from next player so that they don't show up on his turn
            $next_player_id = self::getNextPlayerTable()[$player_id];
            $this->DbQuery("UPDATE `card` SET last_moved = 0 WHERE player_id = $next_player_id;");


        }
    
        foreach ($moves as $move) {
            $move_id = $move['move_id'];
            $cardId = $this->getUniqueValueFromDB("SELECT card_id FROM `move` WHERE move_id = $move_id");
            $row = intval($this->getUniqueValueFromDB("SELECT `row` FROM `card` WHERE card_id = $cardId"));
            $col = intval($this->getUniqueValueFromDB("SELECT `column` FROM `card` WHERE card_id = $cardId"));
            $player_id = $this->getUniqueValueFromDB("SELECT player_id FROM `card` WHERE card_id = $cardId");
    
            // Determine number of pieces to check based on game mode
            $numToCheck = ($this->getPlayersNumber() == 2 && $this->getGameStateValue('fast_duel_nobles') == 1) || 
                         ($this->getGameStateValue('allied_nobility') == 2 && 
                          $this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $player_id;") != 0) 
                         ? 5 : 6;
    
            // Check for winning condition
            if ($this->checkFiveInARow($row, $col, $cardId, $numToCheck)) {
                if (($this->getGameStateValue('allied_nobility') == 2 && 
                     $this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $player_id;") != 0) || 
                    $this->getGameStateValue('joint_nobility') == 2) {
                    // Team win condition
                    $this->handleTeamWin($player_id);
                } else {
                    // Single player win
                    $this->handleSinglePlayerWin($player_id);
                }
                
                $this->gamestate->nextState('postEnd');
                return;
            }
        }
    
        $this->activeNextPlayer();
        $this->gamestate->nextState('nextPlayer');
    }
    
    // Handle team victory
    private function handleTeamWin($player_id) {
        self::DbQuery("START TRANSACTION");
        
        try {
            // Get teammate ID
            $teammate_id = $this->getUniqueValueFromDB("SELECT team FROM player WHERE player_id = $player_id");
            
            if (!$teammate_id) {
                throw new BgaSystemException("Could not find teammate for player $player_id");
            }
    
            // Update scores for both team members
            $this->DbQuery("UPDATE player SET player_score = 1, player_score_aux = 1 WHERE player_id IN ($player_id, $teammate_id)");
            $this->DbQuery("UPDATE player SET player_score = 0, player_score_aux = 0 WHERE player_id NOT IN ($player_id, $teammate_id)");
    
            // Get player names for notification
            $player1_name = $this->getPlayerNameById($player_id);
            $player2_name = $this->getPlayerNameById($teammate_id);
    
            // Set both players as active winners
            $this->gamestate->setAllPlayersMultiactive(array($player_id, $teammate_id));
            $this->DbQuery("UPDATE player SET player_is_multiactive = 0 WHERE player_id NOT IN ($player_id, $teammate_id)");
    
            // Notify all players about the team victory
            $this->notifyAllPlayers(
                'endGame',
                clienttranslate('The game has ended! ${team} win!'),
                array(
                    'team' => $player1_name . ' and ' . $player2_name,
                    'winners' => array($player_id, $teammate_id)
                )
            );
    
            // Update game statistics if needed
            // $this->incStat(1, 'team_games_won', $player_id);
            // $this->incStat(1, 'team_games_won', $teammate_id);
    
            self::DbQuery("COMMIT");
        } catch (Exception $e) {
            self::DbQuery("ROLLBACK");
            throw $e;
        }
    }
    
    // Handle single player victory
    private function handleSinglePlayerWin($player_id) {
        self::DbQuery("START TRANSACTION");
        
        try {
            // Update score for winning player
            $this->DbQuery("UPDATE player SET player_score = 1 WHERE player_id = $player_id");
            $this->DbQuery("UPDATE player SET player_score = 0 WHERE player_id != $player_id");
    
            // Get player name for notification
            $player_name = $this->getPlayerNameById($player_id);
    
            // Notify all players about the single player victory
            $this->notifyAllPlayers(
                'endGame',
                clienttranslate('The game has ended! ${winner} wins!'),
                array(
                    'winner' => $player_name,
                    'winner_id' => $player_id
                )
            );
    
            // Update game statistics if needed
            // $this->incStat(1, 'games_won', $player_id);
    
            self::DbQuery("COMMIT");
        } catch (Exception $e) {
            self::DbQuery("ROLLBACK");
            throw $e;
        }
    }
    
    // Changed to public to match parent class
    public function getPlayerNameById($player_id) {
        return $this->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = " . intval($player_id));
    }
    
    


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            $this->applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
