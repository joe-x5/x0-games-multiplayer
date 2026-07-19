const mapData = {
  minX: 1,
  maxX: 14,
  minY: 4,
  maxY: 12,
  blockedSpaces: {
    "7x4": true,
    "1x11": true,
    "12x10": true,
    "4x7": true,
    "5x7": true,
    "6x7": true,
    "8x6": true,
    "9x6": true,
    "10x6": true,
    "7x9": true,
    "8x9": true,
    "9x9": true,
  },
};

// Options for Player Colors... these are in the same order as our sprite sheet
const playerColors = ["blue", "red", "orange", "yellow", "green", "purple"];

//Misc Helpers
function randomFromArray(array) {
  return array[Math.floor(Math.random() * array.length)];
}
function getKeyString(x, y) {
  return `${x}x${y}`;
}

function createName() {
  const prefix = randomFromArray([
    "COOL",
    "SUPER",
    "HIP",
    "SMUG",
    "COOL",
    "SILKY",
    "GOOD",
    "SAFE",
    "DEAR",
    "DAMP",
    "WARM",
    "RICH",
    "LONG",
    "DARK",
    "SOFT",
    "BUFF",
    "DOPE",
  ]);
  const animal = randomFromArray([
    "BEAR",
    "DOG",
    "CAT",
    "FOX",
    "LAMB",
    "LION",
    "BOAR",
    "GOAT",
    "VOLE",
    "SEAL",
    "PUMA",
    "MULE",
    "BULL",
    "BIRD",
    "BUG",
  ]);
  return `${prefix} ${animal}`;
}

function isSolid(x,y) {
  const blockedNextSpace = mapData.blockedSpaces[getKeyString(x, y)];
  return (
    blockedNextSpace ||
    x >= mapData.maxX ||
    x < mapData.minX ||
    y >= mapData.maxY ||
    y < mapData.minY
  )
}

function getRandomSafeSpot() {
  return randomFromArray([
    { x: 1, y: 4 },
    { x: 2, y: 4 },
    { x: 1, y: 5 },
    { x: 2, y: 6 },
    { x: 2, y: 8 },
    { x: 2, y: 9 },
    { x: 4, y: 8 },
    { x: 5, y: 5 },
    { x: 5, y: 8 },
    { x: 5, y: 10 },
    { x: 5, y: 11 },
    { x: 11, y: 7 },
    { x: 12, y: 7 },
    { x: 13, y: 7 },
    { x: 13, y: 6 },
    { x: 13, y: 8 },
    { x: 7, y: 6 },
    { x: 7, y: 7 },
    { x: 7, y: 8 },
    { x: 8, y: 8 },
    { x: 10, y: 8 },
    { x: 8, y: 8 },
    { x: 11, y: 4 },
  ]);
}

(function () {

  let playerId = `player_${Date.now()}`; // Generate a simple unique ID
  let playerRef = firebase.database().ref(`players/${playerId}`);
  let players = {};
  let playerElements = {};
  let coins = {};
  let coinElements = {};

  const gameContainer = document.querySelector(".game-container");
  const playerNameInput = document.querySelector("#player-name");
  const playerColorButton = document.querySelector("#player-color");

  function placeCoin() {
    const { x, y } = getRandomSafeSpot();
    const coinRef = firebase.database().ref(`coins/${getKeyString(x, y)}`);
    coinRef.set({
      x,
      y,
    })

    const coinTimeouts = [2000, 3000, 4000, 5000];
    setTimeout(() => {
      placeCoin();
    }, randomFromArray(coinTimeouts));
  }

  function attemptGrabCoin(x, y) {
    const key = getKeyString(x, y);
    if (coins[key]) {
      // Remove this key from data, then uptick Player's coin count
      firebase.database().ref(`coins/${key}`).remove();
      players[playerId].coins += 1;
      playerRef.update({ coins: players[playerId].coins });
    }
  }

  function handleArrowPress(xChange=0, yChange=0) {
    const newX = players[playerId].x + xChange;
    const newY = players[playerId].y + yChange;
    if (!isSolid(newX, newY)) {
      //move to the next space
      players[playerId].x = newX;
      players[playerId].y = newY;
      if (xChange === 1) {
        players[playerId].direction = "right";
      }
      if (xChange === -1) {
        players[playerId].direction = "left";
      }
      playerRef.set(players[playerId]);
      attemptGrabCoin(newX, newY);
    }
  }

  // Handle keydown events for KaiOS and Keyboard
  function handleKeyDown(e) {
    switch (e.key) {
      case "ArrowUp":
      case "2": // KaiOS up key
        handleArrowPress(0, -1);
        break;
      case "ArrowDown":
      case "8": // KaiOS down key
        handleArrowPress(0, 1);
        break;
      case "ArrowLeft":
      case "4": // KaiOS left key
        handleArrowPress(-1, 0);
        break;
      case "ArrowRight":
      case "6": // KaiOS right key
        handleArrowPress(1, 0);
        break;
    }
  }

  function initGame() {
    // Add event listener for keydown
    window.addEventListener("keydown", handleKeyDown);

    // Existing Firebase and game setup code...

    // Keyboard controls for desktop (keep if desired)
    // new KeyPressListener("ArrowUp", () => handleArrowPress(0, -1))
    // new KeyPressListener("2", () => handleArrowPress(0, -1))
    // new KeyPressListener("ArrowDown", () => handleArrowPress(0, 1))
    // new KeyPressListener("8", () => handleArrowPress(0, 1))
    // new KeyPressListener("ArrowLeft", () => handleArrowPress(-1, 0))
    // new KeyPressListener("4", () => handleArrowPress(-1, 0))
    // new KeyPressListener("ArrowRight", () => handleArrowPress(1, 0))
    // new KeyPressListener("6", () => handleArrowPress(1, 0))

    const allPlayersRef = firebase.database().ref(`players`);
    const allCoinsRef = firebase.database().ref(`coins`);

    // Listen for all players
    allPlayersRef.on("value", (snapshot) => {
      players = snapshot.val() || {};
      Object.keys(players).forEach((key) => {
        const characterState = players[key];
        let el = playerElements[key];
        // Now update the DOM
        el.querySelector(".Character_name").innerText = characterState.name;
        el.querySelector(".Character_coins").innerText = characterState.coins;
        el.setAttribute("data-color", characterState.color);
        el.setAttribute("data-direction", characterState.direction);
        const left = 16 * characterState.x + "px";
        const top = 16 * characterState.y - 4 + "px";
        el.style.transform = `translate3d(${left}, ${top}, 0)`;
      });
    });

    // When a new player joins
    allPlayersRef.on("child_added", (snapshot) => {
      const addedPlayer = snapshot.val();
      const characterElement = document.createElement("div");
      characterElement.classList.add("Character", "grid-cell");
      if (addedPlayer.id === playerId) {
        characterElement.classList.add("you");
      }
      characterElement.innerHTML = (`
        <div class="Character_shadow grid-cell"></div>
        <div class="Character_sprite grid-cell"></div>
        <div class="Character_name-container">
          <span class="Character_name"></span>
          <span class="Character_coins">0</span>
        </div>
        <div class="Character_you-arrow"></div>
      `);
      playerElements[addedPlayer.id] = characterElement;

      // Fill in some initial state
      characterElement.querySelector(".Character_name").innerText = addedPlayer.name;
      characterElement.querySelector(".Character_coins").innerText = addedPlayer.coins;
      characterElement.setAttribute("data-color", addedPlayer.color);
      characterElement.setAttribute("data-direction", addedPlayer.direction);
      const left = 16 * addedPlayer.x + "px";
      const top = 16 * addedPlayer.y - 4 + "px";
      characterElement.style.transform = `translate3d(${left}, ${top}, 0)`;
      gameContainer.appendChild(characterElement);
    });

    // Remove character DOM element after they leave
    allPlayersRef.on("child_removed", (snapshot) => {
      const removedKey = snapshot.val().id;
      if (playerElements[removedKey]) {
        gameContainer.removeChild(playerElements[removedKey]);
        delete playerElements[removedKey];
      }
    });

    // Coins logic
    allCoinsRef.on("value", (snapshot) => {
      coins = snapshot.val() || {};
    });

    allCoinsRef.on("child_added", (snapshot) => {
      const coin = snapshot.val();
      const key = getKeyString(coin.x, coin.y);
      coins[key] = true;

      // Create the DOM Element
      const coinElement = document.createElement("div");
      coinElement.classList.add("Coin", "grid-cell");
      coinElement.innerHTML = `
        <div class="Coin_shadow grid-cell"></div>
        <div class="Coin_sprite grid-cell"></div>
      `;

      // Position the Element
      const left = 16 * coin.x + "px";
      const top = 16 * coin.y - 4 + "px";
      coinElement.style.transform = `translate3d(${left}, ${top}, 0)`;

      // Keep a reference for removal later and add to DOM
      coinElements[key] = coinElement;
      gameContainer.appendChild(coinElement);
    });

    allCoinsRef.on("child_removed", (snapshot) => {
      const {x,y} = snapshot.val();
      const keyToRemove = getKeyString(x,y);
      if (coinElements[keyToRemove]) {
        gameContainer.removeChild( coinElements[keyToRemove] );
        delete coinElements[keyToRemove];
      }
    });

    // Update player name with text input
    playerNameInput.addEventListener("change", (e) => {
      const newName = e.target.value || createName();
      playerRef.update({
        name: newName
      });
    });

    // Update player color on button click
    playerColorButton.addEventListener("click", () => {
      const currentColor = players[playerId].color;
      const mySkinIndex = playerColors.indexOf(currentColor);
      const nextColor = playerColors[mySkinIndex + 1] || playerColors[0];
      playerRef.update({
        color: nextColor
      });
    });

    // Place first coin
    placeCoin();

    // Initialize player in Firebase
    const name = createName();
    playerNameInput.value = name;
    const { x, y } = getRandomSafeSpot();

    playerRef.set({
      id: playerId,
      name,
      direction: "right",
      color: randomFromArray(playerColors),
      x,
      y,
      coins: 0,
    });

    // Remove me from Firebase when disconnects
    playerRef.onDisconnect().remove();
  }

  // Start the game directly without auth
  initGame();

})();


// Ask for username if not stored
let username = localStorage.getItem('username');
if (!username) {
  username = prompt("Enter your username:");
  if (username) {
    localStorage.setItem('username', username);
  } else {
    username = "Player";
    localStorage.setItem('username', username);
  }
}

// Inside initGame()
function initGame() {
  // Add keyboard event listener
  window.addEventListener("keydown", handleKeyDown);

  // Add touch support
  let touchStartX = 0;
  let touchStartY = 0;

  window.addEventListener('touchstart', (e) => {
    touchStartX = e.touches[0].clientX;
    touchStartY = e.touches[0].clientY;
  });

  window.addEventListener('touchend', (e) => {
    const deltaX = e.changedTouches[0].clientX - touchStartX;
    const deltaY = e.changedTouches[0].clientY - touchStartY;

    if (Math.abs(deltaX) > Math.abs(deltaY)) {
      if (deltaX > 30) {
        handleArrowPress(1, 0);
      } else if (deltaX < -30) {
        handleArrowPress(-1, 0);
      }
    } else {
      if (deltaY > 30) {
        handleArrowPress(0, 1);
      } else if (deltaY < -30) {
        handleArrowPress(0, -1);
      }
    }
  });

  // Use the username from localStorage
  const name = username;
  playerNameInput.value = name;

  // Create player with this username
  playerRef.set({
    id: playerId,
    name,
    direction: "right",
    color: randomFromArray(playerColors),
    x,
    y,
    coins: 0,
  });

  // rest of your initGame code...
}
