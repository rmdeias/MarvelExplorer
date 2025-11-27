document.addEventListener('DOMContentLoaded', () => {
    const mainCover = document.getElementById('main-cover');
    const originalSrc = mainCover.src;

    document.querySelectorAll('.variant-card').forEach(card => {
        const coverSrc = card.dataset.cover;

        // --- Desktop hover ---
        card.addEventListener('mouseenter', () => {
            mainCover.src = coverSrc;
        });
        card.addEventListener('mouseleave', () => {
            mainCover.src = originalSrc;
        });

        // --- Mobile touch ---
        card.addEventListener('touchstart', () => {
            mainCover.src = coverSrc;
        });
        card.addEventListener('touchend', () => {
            mainCover.src = originalSrc;
        });
    });
});
