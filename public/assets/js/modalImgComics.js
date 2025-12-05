document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("modalImage");
    const captionText = document.getElementById("caption");
    const closeBtn = document.getElementsByClassName("close")[0];

    // Ouvrir le modal pour l'image principale
    const mainCover = document.getElementById("main-cover");
    mainCover.addEventListener("click", function () {
        modal.style.display = "block";
        modalImg.src = this.src;
        captionText.innerHTML = this.alt;
    });

    // Ouvrir le modal pour les variants
    document.querySelectorAll('.variant-card img').forEach(img => {
        img.addEventListener('click', function () {
            modal.style.display = "block";
            modalImg.src = this.src;
            captionText.innerHTML = this.alt;
        });
    });

    // Fermer le modal
    closeBtn.onclick = function () {
        modal.style.display = "none";
    }

    // Fermer en cliquant n'importe où en dehors de l'image
    modal.onclick = function (e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    }
});
