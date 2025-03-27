
-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- Falconry implementation : © Sam Richardson <samedr16@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;



CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type_arg` int(11) NOT NULL COMMENT 'Index of the fal_cards from material.inc.php',
  `column` INT NOT NULL,
  `row` INT NOT NULL,
  `player_id` INT unsigned NOT NULL,
  `last_moved` TINYINT DEFAULT 0,
  PRIMARY KEY (`card_id`),
  FOREIGN KEY (`player_id`) REFERENCES player(`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `move` (
  `move_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_value` VARCHAR(24) NOT NULL,
  `card_id` INT NOT NULL,
  `from_storage` BOOLEAN,
  `previous_row` INT,
  `previous_col` INT,
  `new_row` INT,
  `new_col` INT,
  PRIMARY KEY (`move_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


ALTER TABLE player ADD COLUMN marker INT DEFAULT 18, ADD COLUMN team INT DEFAULT NULL, ADD COLUMN team_no INT DEFAULT NULL, ADD COLUMN turn_order INT DEFAULT NULL, ADD COLUMN table_order INT DEFAULT NULL;



-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

