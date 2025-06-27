document.addEventListener("DOMContentLoaded", function () {
    const filterSelect = document.getElementById("difficultyFilter");
    const storyCards = document.querySelectorAll(".story-card");

    filterSelect.addEventListener("change", function () {
        const selectedDifficulty = this.value;

        storyCards.forEach(card => {
            const cardDifficulty = card.getAttribute("data-difficulty");

            if (selectedDifficulty === "all" || cardDifficulty === selectedDifficulty) {
                card.style.display = "block"; // Show card
            } else {
                card.style.display = "none"; // Hide card
            }
        });
    });
});
