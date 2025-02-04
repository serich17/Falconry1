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
 * states.inc.php
 *
 * Falconry game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    
    // Note: ID=2 => your first state

    2 => array(
    		"name" => "playerTurn",
    		"description" => clienttranslate('${actplayer} must play a card'),
    		"descriptionmyturn" => clienttranslate('${you} must play or move a card'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "moveCard"),
    		"transitions" => array( "transition" => 5, "confirmTurn" => 8)
    ),

    3 => array(
        "name" => "revert1",
        "type" => "game",
        "action" => "stRevert1",
        "args" => "argMyGameState",
        "transitions" => array("playerTurn" => 2, "transition" => 5)
    ),

    4 => array(
        "name" => "revert2",
        "type" => "game",
        "action" => "stRevert2",
        "args" => "argMyGameState",
        "transitions" => array("playerTurn" => 2)
    ),
    5 => array(
        "name" => "transition",
        "type" => "game",
        "action" => "stTransition",
        "args" => "argConfirmTurn",
        "transitions" => array("playerTurn2" => 7)
    ),


    6 => array(
        "name" => "extraRevert",
        "type" => "game",
        "action" => "stExtraRevert",
        "args" => "argConfirmTurn",
        "transitions" => array("revert2" => 4)
    ),

    7 => array(
        "name" => "playerTurn2",
        "description" => clienttranslate('${actplayer} must play a second card'),
        "descriptionmyturn" => clienttranslate('${you} must play or move a second card'),
        "type" => "activeplayer",
        "args" => "argConfirmTurn",
        "possibleactions" => array( "moveCard", "undoAction2"),
        "transitions" => array( "confirmTurn" => 8, "extraRevert" => 6 )
    ),


    8 => array(
        "name" => "confirmTurn",
        "description" => clienttranslate('${actplayer} must confirm their turn'),
        "descriptionmyturn" => clienttranslate('${you} must confirm your turn'),
        "type" => "activeplayer",
        "possibleactions" => array("confirmAction", "undoAction"),
        "args" => "argConfirmTurn",
        "transitions" => array( "checkWin" => 10, "revert" => 9)
    ),
    
    9 => array(
            "name" => "revert",
            "type" => "game",
            "action" => "stRevert",
            "args" => "argConfirmTurn",
            "transitions" => array("revert1" => 3)
        ),

    //check if player has 5 tiles
    10 => array(
            "name" => "checkWin",
            "description" => "",
            "type" => "game",
            "action" => "stCheckWin",
            "transitions" => array( "postEnd" => 98, "nextPlayer" => 11 )
        ),

        11=> array( 
            "name"=> "nextPlayer",
            "type" => "game",
            "action" => "stNextPlayer",
            "args" => "argMyGameState",
            "updateGameProgression" => true,
            "transitions" => array( "playerTurn"=> 2,)
        ),




/*
    Examples:
    
    2 => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,   
        "transitions" => array( "endGame" => 99, "nextPlayer" => 10 )
    ),
    

    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/    
    98=> array( 
        "name"=> "postEnd",
        "type" => "game",
        "action" => "stpostEnd",
        "args" => "argMyGameState",
        "transitions" => array( "gameEnd"=> 99,)
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



