
document.addEventListener("DOMContentLoaded", function () {
    let pseudoInput = document.querySelector('[name="user_form[pseudo]"]');
    let feedback = document.getElementById("pseudo-availability");

    if (pseudoInput) {
        pseudoInput.addEventListener("input", function () {
            let pseudo = pseudoInput.value.trim();

            if (pseudo.length < 3) {
                feedback.textContent = "";
                return;
            }

            fetch(checkPseudoUrl + "?pseudo=" + encodeURIComponent(pseudo))
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        feedback.textContent = "✅ Pseudo disponible";
                        feedback.style.color = "green";
                    } else {
                        feedback.textContent = "❌ Pseudo déjà pris";
                        feedback.style.color = "red";
                    }
                })
                .catch(error => console.error("Erreur lors de la vérification du pseudo:", error));
        });
    }
});