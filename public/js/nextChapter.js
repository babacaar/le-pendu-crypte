document.getElementById("nextWord").addEventListener("click", function () {
    let slug = this.getAttribute("data-slug");
    let nextChapter = this.getAttribute("data-next-chapter");

    window.location.href = `/story-mode/play/${slug}?chapter=${nextChapter}`;
});
