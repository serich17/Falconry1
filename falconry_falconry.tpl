{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Falconry implementation : © Sam Richardson <samedr16@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    falconry_falconry.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<div id="storage-row">
        <div class="movetop"></div> 
        <div class="movedown"></div>
        <div id="centerGrid" class="centerGridBtn"></div>
        <div class="moveleft"></div> 
        <div class="moveright"></div> 
        <div id="tile-storage" style="width: max-content; {DISPLAY_STORAGE};">
        <div class="title">Storage</div>
        <div class="store-markers">
            <div id="marker" class="store"></div>
            <div id="bird" class="store"></div>
        </div>
        </div>
        <div id="zoom-group">
            <div id="zoomplus"></div>
            <div class="centerGridBtn"></div>
            <div id="zoomminus"></div>
        </div>
    </div>

    <div id="map_container">
    
        
    	<div id="map_scrollable"></div>
        <div id="map_surface"></div>
        <div id="game-board" class="game-board"></div>

        
    </div>
    <div id="map_footer" class="whiteblock">
        <a href="#" id="enlargedisplay">↓  {LABEL_ENLARGE_DISPLAY}  ↓</a>
    </div>

    
    




<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

</script>  

{OVERALL_GAME_FOOTER}
