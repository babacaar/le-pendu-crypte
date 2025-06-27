document.addEventListener("DOMContentLoaded", function () {
    const buttons = document.querySelectorAll(".letter-button");
    const errorCount = document.getElementById("error-count");
    const hangmanCount = document.getElementById("hangman-image");
    const modalElement = document.getElementById("gameOverModal");
    const gameOverModal = new bootstrap.Modal(modalElement);
    const restartButton = document.getElementById("restartGame");
    const decryptInput = document.getElementById("decrypt-input");
    const decryptButton = document.getElementById("decrypt-submit");
    const hintButton = document.getElementById("hint-button");
    const hintSection = document.getElementById("hint-section");
    const nextWordButton = document.getElementById("nextWord");
    const winModalElement = document.getElementById("winModal");
    const winModal = new bootstrap.Modal(winModalElement);

    buttons.forEach(button => {
        button.addEventListener("click", function () {
            let letter = this.getAttribute("data-letter");
            let gameMode = this.getAttribute("data-mode");
            let storySlug = this.getAttribute("data-slug");

            let checkLetterEndpoint = (gameMode === "story") 
                ? `/story-mode/check-letter/${storySlug}?letter=${letter}`
                : `/free-mode/check-letter?letter=${letter}`;
            fetch(checkLetterEndpoint)
                .then(response => response.json().catch(() => {
                    return { success: false, message: "Invalid JSON response from server." };
                }))
                .then(data => {
                    
                    if (data.gameOver) {
                        gameOverModal.show();

                    } else {
                        document.querySelector(".word-display h2").textContent = data.updatedWord;
                        errorCount.textContent = data.errorCount;
                        hangmanCount.src = "/images/hangman/hangman-" + data.errorCount + ".png";
                        if (!data.updatedWord.includes("_") || data.wordCompleted) {
                            disableAllButtons();
                        }
                        let currentErrors = parseInt(errorCount.textContent, 10);
                 
                        if(currentErrors === 5) {
                           
                            hintButton.classList.remove("hidden");
                        }
                    }

                    button.classList.add("used");
                    button.disabled = true;
                })
                .catch(error => {
                    console.error("Fetch Error:", error);
                });
        });
    });

    // Handle game restart
    restartButton.addEventListener("click", function () {
        let gameMode = restartButton.getAttribute("data-mode"); // Check the game mode
        let storySlug = restartButton.getAttribute("data-slug"); // Get story slug if in story mode
        console.log(gameMode);
        let restartEndpoint = (gameMode === "story") 
            ? `/story-mode/start/${storySlug}`  // Restart for story mode
            : `/free-mode/restart`;  // Restart for free mode

        let redirectUrl = (gameMode === "story") 
            ? `/story-mode/play/${storySlug}`  // Redirect to story play
            : `/free-mode/play`;  // Redirect to free mode play

        fetch(restartEndpoint, { method: "POST" })
            .then(() => {
                window.location.href = redirectUrl; // Redirect after restart
            });
    });

     // Handle decryption submission
     decryptButton.addEventListener("click", function () {
        let guess = decryptInput.value.trim().toUpperCase();
        let gameMode = decryptButton.getAttribute("data-mode"); 
        let storySlug = decryptButton.getAttribute("data-slug"); 
        let decryptEndpoint = (gameMode === "story") 
        ? `/story-mode/decrypt-word/${storySlug}`
        : `/free-mode/decrypt-word`;

        fetch(decryptEndpoint, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ guess: guess })
        })
        .then(response => response.json())
        .then(data => {
            
            if (data.success) {
                winModal.show();
                if (data.storyComplete) {
                    
                    window.location.href = data.redirectUrl;
                }
            } else {
                alert("❌ Mauvais mot, essayez encore !");
            }
        });
    });

    

    // Allow pressing Enter to submit the word
    decryptInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault(); // Prevent form submission
            decryptButton.click();
        }
    });

    // ✅ Function to disable all buttons when game is over or word is fully revealed
    function disableAllButtons() {
        buttons.forEach(button => {
            button.classList.add("used"); // Add the used style
            button.disabled = true; // Disable button interaction
        });
    }

    hintButton.addEventListener("click", function () {
        hintSection.classList.remove("hidden");
        hintButton.classList.add("hidden");
    });
});