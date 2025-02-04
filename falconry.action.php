<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Falconry implementation : © Sam Richardson <samedr16@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * falconry.action.php
 *
 * Falconry main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.bgaPerformAction("myAction", ...)
 *
 */
  
  
  class action_falconry extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( $this->isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = $this->getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "falconry_falconry";
            $this->trace( "Complete reinitialization of board game" );
      }
  	}
  	
  	// TODO: defines your action entry points there

    public function moveCard()
{
    $this->setAjaxMode();
    
    // Basic move information
    $playerId = $this->getArg("playerId", AT_posint, true);
    $cardId = $this->getArg("cardId", AT_posint, true);
    
    // New position
    $toRow = $this->getArg("toRow", AT_posint, true);
    $toCol = $this->getArg("toCol", AT_posint, true);
    
    // Previous position (might be null if from storage)
    $fromRow = $this->getArg("fromRow", AT_int, false);
    $fromCol = $this->getArg("fromCol", AT_int, false);
    
    // Storage tracking
    $fromStorage = $this->getArg("fromStorage", AT_bool, true);

    $this->game->playTile(
        $playerId, 
        $cardId, 
        $toRow, 
        $toCol, 
        $fromRow,
        $fromCol,
        $fromStorage,
    );
    
    $this->ajaxResponse();
}

    function confirmAction() {
        $this->setAjaxMode();
      // Apply the game logic to confirm the player's move
      $this->game->applyMove();
    //   self::notifyAllPlayers('moveConfirmed', clienttranslate('${player_name} confirmed their move'), [
    //       'player_name' => self::getActivePlayerName()
    //   ]);
    $this->ajaxResponse();
  }
  
  function undoAction() {
      // Revert the player's move
      $this->setAjaxMode();

      $this->game->revertMove();
    //   self::notifyAllPlayers('moveUndone', clienttranslate('${player_name} undid their move'), [
    //       'player_name' => self::getActivePlayerName()
    //   ]);
    $this->ajaxResponse();
  }

  function undoAction2() {
    // Revert the player's move
    $this->setAjaxMode();
    $this->game->revertMove2();
  //   self::notifyAllPlayers('moveUndone', clienttranslate('${player_name} undid their move'), [
  //       'player_name' => self::getActivePlayerName()
  //   ]);
  $this->ajaxResponse();
}
  

    // public function checkMoveCard() {
    //   $this->setAjaxMode();

    //   $playerId = $this->getArg("playerId", AT_posint, true);
    //  $cardId = $this->getArg("cardId", AT_posint, true);
    //  $toRow = $this->getArg("toRow", AT_posint, true);
    //  $toCol = $this->getArg("toCol", AT_posint, true);
    //  $zIndex = $this->getArg("zIndex", AT_posint, true);

    //  $this->game->checkMoveCard($playerId, $cardId, $toRow, $toCol, $zIndex);

    //   $this->ajaxResponse();
    // }

    /*
    
    Example:
  	
    public function myAction()
    {
        $this->setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "bgaPerformAction" method
        $arg1 = $this->getArg( "myArgument1", AT_posint, true );
        $arg2 = $this->getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        $this->ajaxResponse( );
    }
    
    */

  }
  

