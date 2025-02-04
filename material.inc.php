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
 * material.inc.php
 *
 * Falconry game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/


$this->fal_cards = array(
  0=> array(
    "type"=> "falconer",
    "type_arg" => 0,
    "color"=> "008000",
    "value"=> "greenFalconer",
    "zIndex"=> "3",
    "label" => clienttranslate("Green Falconer"),
    "colorLabel" => clienttranslate("Green")    
  ),

  1=> array(
    "type"=> "bird",
    "type_arg" => 1,
    "color"=> "008000",
    "value"=> "greenBird",
    "zIndex"=> "2",
    "label" => clienttranslate("Green Bird"),
    "colorLabel" => clienttranslate("Green")    
  ),

  2=> array(
    "type"=> "marker",
    "type_arg" => 2,
    "color"=> "008000",
    "value"=> "greenMarker",
    "zIndex"=> "1",
    "label" => clienttranslate("Green Marker"),
    "colorLabel" => clienttranslate("Green")    
  ),

  3=> array(
    "type"=> "falconer",
    "type_arg" => 3,
    "color"=> "ffa500",
    "value"=> "yellowFalconer",
    "zIndex"=> "3",
    "label" => clienttranslate("Yellow Falconer"),
    "colorLabel" => clienttranslate("Yellow")    
  ),

  4=> array(
    "type"=> "bird",
    "type_arg" => 4,
    "color"=> "ffa500",
    "value"=> "yellowBird",
    "zIndex"=> "2",
    "label" => clienttranslate("Yellow Bird"),
    "colorLabel" => clienttranslate("Yellow")    
  ),

  5=> array(
    "type"=> "marker",
    "type_arg" => 5,
    "color"=> "ffa500",
    "value"=> "yellowMarker",
    "zIndex"=> "1",
    "label" => clienttranslate("Yellow Marker"),
    "colorLabel" => clienttranslate("Yellow")    
  ),

  6=> array(
    "type"=> "falconer",
    "type_arg" => 6,
    "color"=> "0000ff",
    "value"=> "blueFalconer",
    "zIndex"=> "3",
    "label" => clienttranslate("Blue Falconer"),
    "colorLabel" => clienttranslate("Blue")    
  ),

  7=> array(
    "type"=> "bird",
    "type_arg" => 7,
    "color"=> "0000ff",
    "value"=> "blueBird",
    "zIndex"=> "2",
    "label" => clienttranslate("Blue Bird"),
    "colorLabel" => clienttranslate("Blue")    
  ),

  8=> array(
    "type"=> "marker",
    "type_arg" => 8,
    "color"=> "0000ff",
    "value"=> "blueMarker",
    "zIndex"=> "1",
    "label" => clienttranslate("Blue Marker"),
    "colorLabel" => clienttranslate("Blue")    
  ),

  9=> array(
    "type"=> "falconer",
    "type_arg" => 9,
    "color"=> "ff0000",
    "value"=> "redFalconer",
    "zIndex"=> "3",
    "label" => clienttranslate("Red Falconer"),
    "colorLabel" => clienttranslate("Red")    
  ),

  10=> array(
    "type"=> "bird",
    "type_arg" => 10,
    "color"=> "ff0000",
    "value"=> "redBird",
    "zIndex"=> "2",
    "label" => clienttranslate("Red Bird"),
    "colorLabel" => clienttranslate("Red")    
  ),

  11=> array(
    "type"=> "marker",
    "type_arg" => 11,
    "color"=> "ff0000",
    "value"=> "redMarker",
    "zIndex"=> "1",
    "label" => clienttranslate("Red Marker"),
    "colorLabel" => clienttranslate("Red")    
  )

);




