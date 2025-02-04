/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Falconry implementation : © Sam Richardson <samedr16@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * falconry.js
 *
 * Falconry user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/scrollmap"
],
function (dojo, declare) {
    return declare("bgagame.falconry", ebg.core.gamegui, {
        constructor: function(){
            console.log('falconry constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
            this.somethingSelected = false
            this.color = null
            this.columns = null
            this.cards = null
            this.playerId = null
            this.cardToMove = null
            this.target = null
            this.card_types = null
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            console.log("player id " + this.player_id)  
            this.playerId = this.player_id

            if (this.playerId in gamedatas["players"]) {

            //variables for setup
            this.color = gamedatas["players"][this.playerId].color }

            this.scrollmap = new ebg.scrollmap(); // declare an object (this can also go in constructor)
        //  Make map scrollable      	
            this.scrollmap.create( $('map_container'),$('map_scrollable'),$('map_surface'),$('game-board') ); // use ids from template
            //this.scrollmap.setupOnScreenArrows( 300 ); // this will hook buttons to onclick functions with 150px scroll step
            //SET UP SCROLL STEPS
            const SCROLL_STEP = 300;

            // Reference your buttons
            const moveTopBtn = document.querySelector('.movetop');
            const moveDownBtn = document.querySelector('.movedown');
            const moveLeftBtn = document.querySelector('.moveleft');
            const moveRightBtn = document.querySelector('.moveright');

            // Add event listeners to the buttons
            moveTopBtn.addEventListener('click', () => {
                this.scrollmap.scroll(0, SCROLL_STEP); // Scroll up
            });

            moveDownBtn.addEventListener('click', () => {
                this.scrollmap.scroll(0, -SCROLL_STEP); // Scroll down
            });

            moveLeftBtn.addEventListener('click', () => {
                this.scrollmap.scroll(SCROLL_STEP, 0); // Scroll left
            });

            moveRightBtn.addEventListener('click', () => {
                this.scrollmap.scroll(-SCROLL_STEP, 0); // Scroll right
            });


            //grid centered at end of setup
            dojo.connect( $('enlargedisplay'), 'onclick', this, 'onIncreaseDisplayHeight' );
            this.trl_zoom = 1;
            dojo.connect($('zoomplus'), 'onclick', () => this.onZoomButton(0.25));
            dojo.connect($('zoomminus'), 'onclick', () => this.onZoomButton(-0.25));

            dojo.query('.centerGridBtn').forEach((element) => {
                dojo.connect(element, 'onclick', this, 'centerAndFitGrid');
            });
            
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                         
                // TODO: Setting up players boards if needed
            }

            
            // TODO: Set up your game interface here, according to "gamedatas"

            // find max value in columns

            
            this.renderGrid(gamedatas)           

            
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();
            
            //center grid and fit to screen
            this.centerAndFitGrid()

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */

        case "transition":
            console.log(args.args);
            for (let i = 0; i < args.args.count; i++) {
            let cardToMove1;
            if (args.args.from_storage == "1") {
                cardToMove1 = document.createElement("div");
                cardToMove1.classList.add("card");
                cardToMove1.id = "animation";
                cardToMove1.classList.add(args.args.card_value);
                document.getElementById("marker").appendChild(cardToMove1);
                cardToMove1.style.position = 'absolute';
                cardToMove1.style.left = `0px`;
                cardToMove1.style.top = `0px`;
                cardToMove1.style.zIndex = '1000'; 
                fromStorage = true
            } else if (args.args.previous_col == -3) {
                cardToMove1 = document.createElement("div");
                cardToMove1.classList.add("card");
                cardToMove1.id = "animation";
                cardToMove1.classList.add(args.args.card_value);
                if (document.querySelector(`.${args.args.card_value}`)) {
                    document.querySelector(`.${args.args.card_value}`).style.display = "none"}
                document.getElementById("bird").appendChild(cardToMove1);
                    // ATTEMPT TO Position the new card exactly over the existing one in storage 
                cardToMove1.style.position = 'absolute';
                cardToMove1.style.left = `0px`;
                cardToMove1.style.top = `0px`;
                cardToMove1.style.zIndex = '1000';
                fromStorage = true
            } else {
                cardToMove1 = document.querySelector(`.${args.args.card_value}[data-row='${args.args.previous_row}'][data-column='${args.args.previous_col}']`);
                fromStorage = true
            }

            // Select the target element, which should be the cell where the card is moving to
            let element1 = document.querySelector(`.game-board > div[data-row='${args.args.new_row}'][data-column='${args.args.new_col}']`);
            
            this.cardToMove = cardToMove1;
            this.target = element1;

            this.animateToScrollmapLocation(cardToMove1, element1, fromStorage)
                .then(() => {
                    element1.appendChild(cardToMove1);
                    cardToMove1.style.position = ""
                    cardToMove1.style.visibility = '';
                })
                .catch((error) => {
                    console.error("Animation error:", error);
                });
            }
            break;
        case "playerTurn2":

            setTimeout(() => {
                console.log(args.args)
                this.renderGrid(args.args);
            }, 500);

            break;



    case 'confirmTurn':
    console.log(args.args);
    let cardToMove;
    
    //console.log(existingCardInStorage)
    if (args.args.from_storage == "1") {
        cardToMove = document.createElement("div");
        cardToMove.classList.add("card");
        cardToMove.id = "animation";
        cardToMove.classList.add(args.args.card_value);
        document.getElementById("marker").appendChild(cardToMove);
             cardToMove.style.position = 'absolute';
             cardToMove.style.left = `0px`;
             cardToMove.style.top = `0px`;
                cardToMove.style.zIndex = '1000'; 
        fromStorage = true
        
    } else if (args.args.previous_col == -3) {
        cardToMove = document.createElement("div");
        cardToMove.classList.add("card");
        cardToMove.id = "animation";
        cardToMove.classList.add(args.args.card_value);
        if (document.querySelector(`.${args.args.card_value}`)) {
            document.querySelector(`.${args.args.card_value}`).style.display = "none"}
        document.getElementById("bird").appendChild(cardToMove);
            cardToMove.style.position = 'absolute';
            cardToMove.style.left = `0px`;
            cardToMove.style.top = `0px`;
            cardToMove.style.zIndex = '1000'; 
            fromStorage = true        
    } else {
        cardToMove = document.querySelector(`.${args.args.card_value}[data-row='${args.args.previous_row}'][data-column='${args.args.previous_col}']`);
        fromStorage = true
    }

    // Select the target element, which should be the cell where the card is moving to
    let element = document.querySelector(`.game-board > div[data-row='${args.args.new_row}'][data-column='${args.args.new_col}']`);
    
    this.cardToMove = cardToMove;
    this.target = element;

    this.animateToScrollmapLocation(cardToMove, element, fromStorage)
        .then(() => {
            element.appendChild(cardToMove);
            cardToMove.style.position = ""
            cardToMove.style.visibility = '';
        })
        .catch((error) => {
            console.error("Animation error:", error);
        });

        setTimeout(() => {
            this.renderGrid(args.args)
        }, 500);

    break;


    


                    
        case 'revert':
            for (let i = 0; i < args.args.count; i++) {
                if (i == 0) {
                    i = "";
                }
                
                const moveData = {
                    card_value: args.args["card_value" + i],
                    from_storage: args.args["from_storage" + i],
                    previous_row: args.args["previous_row" + i],
                    previous_col: args.args["previous_col" + i],
                    new_row: args.args["new_row" + i],
                    new_col: args.args["new_col" + i]
                };
        
                if (moveData.from_storage == "1") {
                    // Handle move from storage to grid
                    const start = document.querySelector(`.${args.args.card_value}[data-row='${args.args.new_row}'][data-column='${args.args.new_col}']`);
                    const end = document.createElement("div");
                    end.classList.add("card");
                    document.getElementById("marker").appendChild(end);
                    
                             end.style.position = 'absolute';
                              end.style.left = `0px`;
                               end.style.top = `0px`;
                                end.style.zIndex = '1000'; 
                        
                    this.animateToScrollmapLocation(start, end, false);
                    end.remove();
                    start.remove();
                } else if (args.args.previous_col == -3) {
                    // Handle move from storage to grid
                    const start = document.querySelector(`.${args.args.card_value}[data-row='${args.args.new_row}'][data-column='${args.args.new_col}']`);
                    const end = document.createElement("div");
                    end.classList.add("card");
                    document.getElementById("bird").appendChild(end);
                    
                             end.style.position = 'absolute';
                             end.style.left = `0px`;
                             end.style.top = `0px`;
                                end.style.zIndex = '1000'; 
                        
                    this.animateToScrollmapLocation(start, end, false);
                    end.remove();
                    start.remove();
                } else {
                    // Handle grid to grid move
                    this.reverseMove(moveData);
                }
        
                if (i == "") {
                    i = 0;
                }
            }
            
            break;

            case 'extraRevert':
                for (let i = 0; i < args.args.count; i++) {
                    if (i == 0) {
                        i = "";
                    }
                    
                    const moveData = {
                        card_value: args.args["card_value" + i],
                        from_storage: args.args["from_storage" + i],
                        previous_row: args.args["previous_row" + i],
                        previous_col: args.args["previous_col" + i],
                        new_row: args.args["new_row" + i],
                        new_col: args.args["new_col" + i]
                    };
            
                    if (moveData.from_storage == "1") {
                        // Handle move from storage to grid
                        const start = document.querySelector(`.${args.args.card_value}[data-row='${args.args.new_row}'][data-column='${args.args.new_col}']`);
                        const end = document.createElement("div");
                        end.classList.add("card");
                        document.getElementById("marker").appendChild(end);
                        
                                
                                 end.style.position = 'absolute';
                                 end.style.left = `0px`;
                                 end.style.top = `0px`;
                                    end.style.zIndex = '1000'; 
                            
                        this.animateToScrollmapLocation(start, end, false);
                        end.remove();
                        start.remove();
                    } else if (args.args.previous_col == -3) {
                        // Handle move from storage to grid
                        const start = document.querySelector(`.${args.args.card_value}[data-row='${args.args.new_row}'][data-column='${args.args.new_col}']`);
                        const end = document.createElement("div");
                        end.classList.add("card");
                        document.getElementById("bird").appendChild(end);
                        
                            
                                 end.style.position = 'absolute';
                                 end.style.left = `0px`;
                                 end.style.top = `0px`;
                                    end.style.zIndex = '1000'; 
                            
                        this.animateToScrollmapLocation(start, end, false);
                        end.remove();
                        start.remove();
                    } else {
                        // Handle grid to grid move
                        this.reverseMove(moveData);
                    }
            
                    if (i == "") {
                        i = 0;
                    }
                }
                
                break;

        case 'revert1':
            // Optionally, re-render the grid after all moves are reverted
            setTimeout(() => {
                console.log(args.args)
                this.renderGrid(args.args)
            }, 500);
            break;

        case 'revert2':
            // Optionally, re-render the grid after all moves are reverted
            setTimeout(() => {
                this.renderGrid(args.args)
            }, 500);
            break;
        
                
           case 'nextPlayer':
                if (args.args.active_player = this.playerId) {
                this.renderGrid(args.args); }
                console.log(args)
                break;
            case 'postEnd':
                this.renderGrid(args.args);
                break;
            case 'dummmy':
                break; 
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/

                    case 'confirmTurn':
                        //Add confirm and undo buttons when it enters confirmturn state
                        this.addActionButton('button_confirm', _('Confirm'), 'onConfirmButtonClick');
                        this.addActionButton('button_undo', _('Undo'), 'onUndoButtonClick');
                        

                        break;

                    case 'playerTurn2':

                        this.addActionButton('button_undo2', _('Undo'), 'onUndoButtonClick2');
                        break;

                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        //Render grid in setup and every time new state is entered
            renderGrid: function(gamedatas) {
                console.log(gamedatas)
            let gridSpecs = gamedatas['grid']
            console.log(gridSpecs)
            let maxCol = gridSpecs.column
            let maxRow = gridSpecs.row
            document.getElementById("marker").innerHTML = ""
            document.getElementById("bird").innerHTML = ""
            if (Object.keys(gamedatas.players).length == 2) {
                document.getElementById("bird").style.display = "none"
            }
            const store = document.createElement("div")
            this.cards = gamedatas['cards']
            this.card_types = gamedatas['card_types']

            for( var player_id in gamedatas.players )
                {
                    var player = gamedatas.players[player_id];
                             
                    // TODO: Setting up players boards if needed
                    
                    const markerNumber = player.marker; // Access marker number for each player
                    console.log(`Player ${player.id} has marker number: ${markerNumber}`)
                    let markerClass = gamedatas.card_types.find(card => card.type == "marker" && card.color == player.color).value
                    let bird = gamedatas.card_types[Object.values(gamedatas.cards).find(card => card.column == "-3" && card.player_id == player.id)?.card_type_arg]?.value ?? null
                    this.updatePlayerMarkers(player.player_no, player.id, markerNumber, markerClass, bird, player.team_no)
                }

            

            this.grid = {column:maxCol + 1, row:maxRow + 1}

            ///Build dynamic JS. Should I change db to include class for card, or should i access the material file somehow from here?
            const game_board = document.getElementById("game-board")
            let grid = "";
            
            let ids = 0
            for (let row = 0; row < this.grid.row; row++) {
                for (let col = 0; col < this.grid.column; col++) {
                    grid += `<div class="card" id="${ids}" data-row="${row + 1}" data-column="${col + 1}"></div>`;
                    ids += 1
                }
            }
            const gameBoard = document.getElementById("game-board")
            
            gameBoard.style.gridTemplateColumns = `repeat(${this.grid.column}, fit-content(100%))`

            game_board.innerHTML = grid
            

            //add event listeners for the elements to see when a selected element trys to be placed there
            gameBoard.childNodes.forEach((element) => {
                element.addEventListener("click", this.playTile.bind(this, element))
               
            })

            /// set up grid and place cards in correct locations
            let storeBool = false
            console.log(Object.keys(this.cards).length)
            console.log(this.cards)
            console.log(Object.keys(this.cards))
            ids = 1000
            Object.keys(this.cards).forEach((i) => {
            // for (let i=1; i<= Object.keys(this.cards).length; i++) {
                let cell = 0
                console.log(this.cards[i])
                let className = this.cards[i].card_type_arg
                ids += 1
                
            if (this.cards[i].row > 0) {
                cell = document.createElement("div")
                cell.classList.add("card", "overlay-card")
                gameBoard.appendChild(cell)
                //cell.style.zIndex = this.cards[i].zIndex
                cell.style.gridRow = this.cards[i].row;
                cell.style.gridColumn = this.cards[i].column;
                cell.setAttribute('data-row', this.cards[i].row);
                cell.setAttribute('data-column', this.cards[i].column);
                cell.style.position = 'absolute'

                // cell = gameBoard.querySelector(
                //     `.game-board :nth-child(${(parseInt(this.cards[i].row) - 1) * parseInt(this.grid.column) + parseInt(this.cards[i].column)})`
                // )
            }
            else if (gamedatas['card_types'][className].color == this.color) {
                cell = document.createElement("div")
                
                cell.style.gridRow = this.cards[i].row;
                cell.style.gridColumn = this.cards[i].column;
                cell.setAttribute('data-row', this.cards[i].row);
                cell.setAttribute('data-column', this.cards[i].column);
                cell.classList.add("card")
                document.getElementById("bird").appendChild(cell)
            }
            cell.id = ids
            
              if (cell) {
                if (this.cards[i].last_moved == 1) {
                    cell.classList.add("lastPlayed")
                }
                cell.style.zIndex = gamedatas['card_types'][className].zIndex
                cell.classList.add(`${gamedatas['card_types'][className].value}`)
            
                
                const selectables = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]
                // make sure it makes either falconers or falcons selectable, only for the current player, with the cards of his color
                if (selectables.includes(parseInt(className)) && gamedatas['card_types'][className].color == this.color) {
                    
                    cell.classList.add(`card--selectable`)
                    //add storage to the selectable items only one time
                    if (!storeBool && parseInt(gamedatas["players"][this.playerId].marker) > 0) {                        
                        store.classList.add("card--selectable")
                        store.addEventListener('click', seeIfSelected.bind(this))
                        storeBool = true
                        //add storage container with the right color
             
                    }
                    // else if (parseInt(gamedatas["players"][this.playerId].marker) == 0) {
                    //     document.getElementById("storage-row").style.display = "none"
                    //   }
                    // add function to check if it is selected and deselect; only allow one selected item at a time
                cell.addEventListener('click', seeIfSelected.bind(this))
                }
                if (!cell.classList.contains("card--selectable")) {
                    cell.addEventListener("click", this.playTile.bind(this, cell))
                    
                }
                
              }

              //add storage container with the right color
              let markerMaterialIndex;
              
              if (this.playerId in gamedatas["players"]) {
              switch (gamedatas["players"][this.playerId].color) {
                case "008000":
                    markerMaterialIndex = 2
                    break
                case "ffa500":
                    markerMaterialIndex = 5
                    break
                case "0000ff":
                    markerMaterialIndex = 8
                    break
                case "ff0000":
                    markerMaterialIndex = 11
                    break
              }
              if (parseInt(gamedatas["players"][this.playerId].marker) > 0) {
              document.getElementById("marker").appendChild(store)
              store.classList.add("card")
              store.id = "store_marker_id"
              store.classList.add(gamedatas['card_types'][markerMaterialIndex].value)
              } }
              
            }) 

            function seeIfSelected(e) {
                if (e.target.classList.contains("card--selected")) {
                    this.somethingSelected = false
                    
                    e.target.classList.remove("card--selected")
                }
                else {
                    this.somethingSelected = true
                    document.querySelectorAll(".card--selected").forEach((element) => {
                        element.classList.remove("card--selected")
                    })
                    e.target.classList.add("card--selected");
                }
            }

            const cardClasses = [
                'greenFalconer', 'greenBird', 'greenMarker',
                'yellowFalconer', 'yellowBird', 'yellowMarker',
                'blueFalconer', 'blueBird', 'blueMarker',
                'redFalconer', 'redBird', 'redMarker'
            ];
            
            cardClasses.forEach(className => {
                const elements = document.getElementsByClassName(className);
                for (let element of elements) {
                    this.addTooltipHtml(element.id, this.getTooltipContent(className));
                }
            });

        },

        getMaxRowsAndColumns: function(gamedatas) {
            let data = gamedatas['cards']
            
            let maxCol = 0;
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                  maxCol = Math.max(maxCol, data[key].column);
                }
              }
              this.columns = maxCol + 1
            // find max value in rows
            let maxRow = 0;
            for (const key in data) {
              if (data.hasOwnProperty(key)) {
                maxRow = Math.max(maxRow, data[key].row);
              }
            }
            return {col: maxCol, row: maxRow}
        },

        getLocationCards: function (element, gridColumnCount) {
            // Check style properties first
                if (element.style.gridRow && element.style.gridColumn) {
                    return {
                        row: parseInt(element.style.gridRow),
                        col: parseInt(element.style.gridColumn),
                        index: (parseInt(element.style.gridRow) - 1) * gridColumnCount + parseInt(element.style.gridColumn)
                    };
                }
            
                // Then check data attributes
                if (element.dataset.row && element.dataset.column) {
                    return {
                        row: parseInt(element.dataset.row),
                        col: parseInt(element.dataset.column),
                        index: (parseInt(element.dataset.row) - 1) * gridColumnCount + parseInt(element.dataset.column)
                    };
                }
        },

        getNthNumberInGrid: function (element, gridColumnCount) {
                
            // Find all children in the grid container
            const gridContainer = element.parentNode;
            const children = Array.from(gridContainer.children);
        
            // Get the index of the element in the container
            const index = children.indexOf(element);
        
            if (index === -1) {
                console.log("Element is not a child of the grid container");
                return -1;
            }
        
            // Calculate the nth number based on rows and columns
            const row = Math.floor(index / gridColumnCount);
            const column = index % gridColumnCount;
            
            console.log(`Element is in row: ${row + 1}, column: ${column + 1}`);
            return {col:column +1, row:row +1, index:index + 1};  // Return 1-based position in the grid
        },

        //add a function to check if the card is being placed to an adjacent tile, otherwise deny action
        isAdjacentToAnyCard: function (divPosition, cardLocations, excludeCardId = null) {
            for (const cardId in cardLocations) {
                // Skip the card being moved
                if (cardId == excludeCardId) continue;
                
                const cardPosition = cardLocations[cardId];
                
                const rowDifference = Math.abs(divPosition.row - cardPosition.row);
                const colDifference = Math.abs(divPosition.col - cardPosition.column);
        
                // Check for strictly orthogonal adjacency (not diagonal)
                // Adjacent means exactly one coordinate differs by 1, while the other remains the same
                if ((rowDifference == 1 && colDifference == 0) || (rowDifference == 0 && colDifference == 1)) {
                    return true; // The div is adjacent to this card
                }
            }
            return false; // No cards are adjacent to the div
        },

        findCardIdByElement: function(cardElement) {
            // If you have a way to associate the card element with its ID
            // This might involve checking data attributes or other identifiers
            for (const cardKey in this.cards) {
                const card = this.cards[cardKey];
                
                // Check if the current card matches the element's current position
                if (card.row == cardElement.style.gridRow && 
                    card.column == cardElement.style.gridColumn &&
                    this.card_types[card.card_type_arg].zIndex == cardElement.style.zIndex
                    &&  cardElement.classList.contains(this.card_types[card.card_type_arg].value)) {
                    return card.card_id; // Return the card's unique identifier
                }
            }
            return null;
        },

        animateToScrollmapLocation: async function (sourceElement, targetElement, fromStorage, duration = 500) {
            function getElementInfo(element) {
                const rect = element.getBoundingClientRect();
                return {
                    left: rect.left + window.scrollX,
                    top: rect.top + window.scrollY,
                    width: rect.width,
                    height: rect.height,
                    row: element.getAttribute('data-row'),
                    col: element.getAttribute('data-column')
                };
            }
            let scale
            // Get current scale
            if (fromStorage) {
                scale = this.returnScale();
            } else {
                scale = 1
            }
            // Create clone at exact source position
            const clone = sourceElement.cloneNode(true);
            clone.id = sourceElement.id + '-clone';
            document.body.appendChild(clone);
        
            const sourceInfo = getElementInfo(sourceElement);
            const targetInfo = getElementInfo(targetElement);
        
            // Position clone at starting position and apply scaling
            clone.style.position = 'absolute';
            clone.style.left = `${sourceInfo.left}px`;
            clone.style.top = `${sourceInfo.top}px`;
            //clone.style.width = `${sourceInfo.width }px`; // Adjust for actual card size
            //clone.style.height = `${sourceInfo.height}px`; // Adjust for actual card size
            //clone.style.transform = `scale(${scale})`; // Apply initial scale
            clone.style.transformOrigin = 'top left'; // Set transform origin
            clone.style.zIndex = '1000';
            clone.style.transition = `transform ${duration}ms cubic-bezier(0.65, 0, 0.35, 1)`;
        
            // Hide original element
            sourceElement.style.visibility = 'hidden';
        
            // Force a reflow
            clone.offsetHeight;
        
            // Calculate transform to target, accounting for scale
            const deltaX = (targetInfo.left - sourceInfo.left) / scale;
            const deltaY = (targetInfo.top - sourceInfo.top) / scale;
            clone.style.transform = `scale(${scale}) translate(${deltaX}px, ${deltaY}px)`;
        
            return new Promise((resolve) => {
                clone.addEventListener('transitionend', () => {
                    clone.remove();
                    sourceElement.style.visibility = '';
        
                    if (targetElement.closest('.game-board')) {
                        targetElement.appendChild(sourceElement);
                        sourceElement.style.visibility = '';
                    }
                    resolve();
                }, { once: true });
            });
        },
        
                
        reverseMove: async function(moveData, duration = 500) {
            let sourceElement;
            
            if (moveData.from_storage == "1") {
                // Handle storage to grid reversal
                sourceElement = document.querySelector(`.${moveData.card_value}`);
                if (sourceElement) {
                    const storageArea = document.querySelector(".store-markers");
                    const tempTarget = document.createElement("div");
                    tempTarget.classList.add("card");
                    storageArea.appendChild(tempTarget);
        
                    await this.animateToScrollmapLocation(sourceElement, tempTarget, false, duration);
                    if (tempTarget.parentNode) {
                        tempTarget.parentNode.removeChild(tempTarget);
                    }
                    if (sourceElement.parentNode) {
                        sourceElement.parentNode.removeChild(sourceElement);
                    }
                }
            } else {
                // Handle grid to grid reversal
                sourceElement = document.querySelector(`.${moveData.card_value}[data-row='${moveData.new_row}'][data-column='${moveData.new_col}']`);
                if (sourceElement) {
                    const targetElement = this.findGridCell(moveData.previous_row, moveData.previous_col);
                    if (targetElement) {
                        await this.animateToScrollmapLocation(sourceElement, targetElement, true, duration);
                        if (targetElement.parentNode) {
                            targetElement.appendChild(sourceElement);
                        }
                        if (sourceElement.parentNode) {
                            sourceElement.style.visibility = '';
                        }
                    }
                }
            }
        },
        
        
        
        
                
        findGridCell: function(row, col) {
            const elements = document.querySelectorAll('.game-board > div');
            return Array.from(elements).find(el => {
                const dataRow = el.getAttribute('data-row');
                const dataColumn = el.getAttribute('data-column');
                return dataRow == row && dataColumn == col;
            });
        },
        
        

        onConfirmButtonClick: function () {
            this.bgaPerformAction("confirmAction", { 
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)                            
            });
        },
        
        onUndoButtonClick: function () {
            // Logic to undo the action
            this.bgaPerformAction("undoAction", { 
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)                            
            });
        },
        
        onUndoButtonClick2: function () {
            // Logic to undo the action
            this.bgaPerformAction("undoAction2", { 
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)                            
            });
        },

        onIncreaseDisplayHeight: function(evt) {
            console.log('Event: onIncreaseDisplayHeight');
            evt.preventDefault();
        	
            var cur_h = toint(dojo.style( $('map_container'), 'height'));
            dojo.style($('map_container'), 'height', (cur_h + 300) + 'px');
        },

        onZoomButton: function(deltaZoom) {
            zoom = this.trl_zoom + deltaZoom;
            this.trl_zoom = zoom <= 0.7 ? 0.7 : zoom >= 2 ? 2 : zoom;
            dojo.style($('map_scrollable'), 'transform', 'scale(' + this.trl_zoom + ')');
            dojo.style($('game-board'), 'transform', 'scale(' + this.trl_zoom + ')');
        },
        
        // Add this JavaScript to your game logic
        centerAndFitGrid: function () {
            const gridItems = document.querySelectorAll('.game-board > .card');

            let maxRow = 0;
            let maxColumn = 0;

            // Iterate through each grid item
            gridItems.forEach(item => {
                const row = parseInt(item.getAttribute('data-row'), 10);
                const column = parseInt(item.getAttribute('data-column'), 10);

                if (!isNaN(row)) {
                    maxRow = Math.max(maxRow, row);
                }
                if (!isNaN(column)) {
                    maxColumn = Math.max(maxColumn, column);
                }
            });

            // Scroll to the center of the grid
            this.scrollmap.scrollto(-maxColumn/2*100, -maxRow/2*100);



            // Get the container and grid elements
            const container = $('map_container');
            const grid = $('game-board');
            const scrollable = $('map_scrollable');
            
            // Reset zoom level to 1 temporarily to get true dimensions
            this.trl_zoom = 1;
            dojo.style(scrollable, 'transform', 'scale(1)');
            dojo.style(grid, 'transform', 'scale(1)');
            
            // Get the dimensions
            let containerRect = container.getBoundingClientRect();
            let gridRect = grid.getBoundingClientRect();
            
            // Calculate optimal zoom level
            const horizontalZoom = (containerRect.width - 40) / gridRect.width;
            const verticalZoom = (containerRect.height - 40) / gridRect.height;
            let zoomLevel = Math.min(horizontalZoom, verticalZoom);
            zoomLevel = Math.min(Math.max(zoomLevel, 0.2), 2); // Apply constraints
            
            // Apply the zoom
            this.trl_zoom = zoomLevel;
            dojo.style(scrollable, 'transform', 'scale(' + zoomLevel + ')');
            dojo.style(grid, 'transform', 'scale(' + zoomLevel + ')');
            
            
        },

        returnScale: function () {
            const gameBoard = document.querySelector('.game-board');

            // Get the computed style
            const computedStyle = window.getComputedStyle(gameBoard);

            // Get the transform property
            const transform = computedStyle.transform;

            if (transform && transform !== 'none') {
                // Extract the scale value from the matrix
                const values = transform.match(/matrix.*\((.+)\)/)[1].split(', ');

                // Scale value (for uniform scaling, it's the first value)
                const scale = parseFloat(values[0]);
                console.log('Scale:', scale);
                return scale
            } else {
                return 1
            }
        },

        updatePlayerMarkers: function(playerNo, playerId, markerCount, markerClass, bird, team) {
            let playerBoard = document.querySelector(`#player_board_${playerId} .player-board-game-specific-content`);
            
            if (!playerBoard) {
                console.error(`Player board not found for ID: ${playerId}`);
                return;
            }

            let playerOrder = document.querySelector(`#player_order_${playerId}`);
            if (!playerOrder) {
                playerOrder = document.createElement("div")
                playerOrder.id = `player_order_${playerId}`
                playerOrder.classList.add("player-marker-count")
                playerOrder.innerText = `Player ${playerNo}`
                playerOrder.style.background = "none"
                playerOrder.style.boxShadow = "none"
                playerBoard.appendChild(playerOrder)
            }
        
            // Check if marker count element already exists
            let markerElement = document.querySelector(`#player_markers_${playerId}`);
            if (!markerElement) {
                // Create a new div if it doesn't exist
                markerElement = document.createElement("div");
                markerElement.id = `player_markers_${playerId}`;
                markerElement.classList.add("player-marker-count");
                playerBoard.appendChild(markerElement);
            }
            let birdElement = document.querySelector(`#player_bird_${playerId}`);
            if (!birdElement && bird) {
                // Create a new div if it doesn't exist
                birdElement = document.createElement("div");
                birdElement.id = `player_bird_${playerId}`;
                birdElement.classList.add("player-marker-count", bird, "player-bird-icon");
                playerBoard.appendChild(birdElement);
            } else if (birdElement && !bird) {
                birdElement.style.display = "none"
            } else if (birdElement && bird) {
                birdElement.style.display = ""
            }
            let teamElement = document.querySelector(`#player_team_${playerId}`);
            if (!teamElement && team) {
                // Create a new div if it doesn't exist
                teamElement = document.createElement("div");
                teamElement.id = `player_team_${playerId}`;
                teamElement.classList.add("player-team");
                teamElement.innerText = "Team " + (team == 1 ? "A" : team == 2 ? "B" : team);
                teamElement.style.backgroundColor = team == 1 ? "#4A90E2" : team == 2 ? "#E74C3C" : "gray";
                playerBoard.appendChild(teamElement);
            }

        
            // Update the text
            markerElement.innerHTML = `<div>${markerCount}</div><div id="icon-id${playerId}" class="${markerClass} player-marker-icon"></div>`;
        },
        
        getTooltipContent: function(className) {
            const tooltips = {
                'greenFalconer': '<b>Falconers</b> are the most powerful cards, and can claim other players\' markers or ambush their birds. Your falconer may be placed alone or on top of a marker or bird card of another color.',
                'greenBird': '<b>Birds</b> can claim other players\' markers. Your bird may be placed alone, or on top of a marker card of another color.',
                'greenMarker': '<b>Markers</b> are the most basic card type, and may only be placed alone in open spots connected to other cards.',
                'yellowFalconer': '<b>Falconers</b> are the most powerful cards, and can claim other players\' markers or ambush their birds. Your falconer may be placed alone or on top of a marker or bird card of another color.',
                'yellowBird': '<b>Birds</b> can claim other players\' markers. Your bird may be placed alone, or on top of a marker card of another color.',
                'yellowMarker': '<b>Markers</b> are the most basic card type, and may only be placed alone in open spots connected to other cards.',
                'blueFalconer': '<b>Falconers</b> are the most powerful cards, and can claim other players\' markers or ambush their birds. Your falconer may be placed alone or on top of a marker or bird card of another color.',
                'blueBird': '<b>Birds</b> can claim other players\' markers. Your bird may be placed alone, or on top of a marker card of another color.',
                'blueMarker': '<b>Markers</b> are the most basic card type, and may only be placed alone in open spots connected to other cards.',
                'redFalconer': '<b>Falconers</b> are the most powerful cards, and can claim other players\' markers or ambush their birds. Your falconer may be placed alone or on top of a marker or bird card of another color.',
                'redBird': '<b>Birds</b> can claim other players\' markers. Your bird may be placed alone, or on top of a marker card of another color.',
                'redMarker': '<b>Markers</b> are the most basic card type, and may only be placed alone in open spots connected to other cards.'
            };
            return tooltips[className] || 'Card';
        },
        
        

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            this.bgaPerformAction("myAction", { 
                myArgument1: arg1, 
                myArgument2: arg2,
                ...
            }).then(() =>  {                
                // What to do after the server call if it succeeded
                // (most of the time, nothing, as the game will react to notifs / change of state instead)
            });        
        },        
        
        */

        playTile: function(element) {
            if (this.isCurrentPlayerActive()) {
                // Check if something is selected and it's not the same element being selected
                if (this.somethingSelected && !element.classList.contains("card--selected")) {
                    // Get the selected card to move
                    const cardToMove = document.querySelector(".card--selected");
                    
                    // Get the target location info
                    const targetLocation = this.getLocationCards(element, this.columns);
                    
                    console.log('targetLocation')

                    // Get the current location of the card to move
                    //const currentLocation = this.getLocationCards(cardToMove, this.columns + 1)
                    console.log(targetLocation)
                    console.log(cardToMove)
                    console.log("cardToMove.id ", cardToMove.id)
                    console.log("element.id ",element.id)
                    
                    // Find the card's ID (you might need to adjust this based on your exact data structure)
                    let cardToMoveId = 0
                    if (this.findCardIdByElement(cardToMove)) {
                    cardToMoveId = this.findCardIdByElement(cardToMove) }
                    else {
                        cardToMoveId = 400
                    }
                    console.log(cardToMoveId)

                    ///TODO check if you can find the idbyelement
        
                    // Check adjacency (if required)
                    //if (this.isAdjacentToAnyCard(targetLocation, this.cards, cardToMoveId)) {
                        // Prepare the move
                        cardToMove.classList.remove("card--selected");
                        this.somethingSelected = false;
                        let cardToMove_id = 0
                        console.log(cardToMove.id)
                        console.log(element.id)
                        if (Number.isNaN(cardToMove.id) || cardToMove.id == null || cardToMove.id == "") {
                            cardToMove_id = -1
                        } else {
                            cardToMove_id = parseInt(cardToMove.id)
                        }
                        
                        this.bgaPerformAction("moveCard", { 
                            playerId: this.player_id,
                            cardId: cardToMoveId,
                            // Grid position information
                            toRow: targetLocation.row,
                            toCol: targetLocation.col,
                            fromRow: cardToMove.getAttribute('data-row'),    // Get previous position if it exists
                            fromCol: cardToMove.getAttribute('data-column'), // Get previous position if it exis
                            // Storage tracking
                            fromStorage: cardToMoveId == 400, // true if coming from storage
                        }).then(() =>  {                
                            // What to do after the server call if it succeeded
                            // (most of the time, nothing, as the game will react to notifs / change of state instead)                            
                        });


                        // this.bgaPerformAction("moveCard", { 
                        //     playerId: this.player_id,
                        //     cardId: cardToMoveId,
                        //     toRow: targetLocation.row,
                        //     toCol: targetLocation.col,
                        //     zIndex: cardToMove.style.zIndex
                        // }).then(() =>  {                
                        //     // What to do after the server call if it succeeded
                        //     // (most of the time, nothing, as the game will react to notifs / change of state instead)
                            
                        // });
                        
                      
                    //}
                }
            }
        },


        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your falconry.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });             
});
