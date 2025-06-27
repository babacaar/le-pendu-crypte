document.addEventListener('DOMContentLoaded', function () {
    const addChapterBtn = document.getElementById('add-chapter');
    const chaptersList = document.getElementById('chapters-list');
    const prototypeContainer = document.getElementById('chapter-prototype');
    const prototype = prototypeContainer.dataset.prototype;
    let index = document.querySelectorAll('.chapter-item').length;

    addChapterBtn.addEventListener('click', function () {
        const newForm = prototype.replace(/__name__/g, index);
        const newDiv = document.createElement('div');
        newDiv.innerHTML = newForm;
        newDiv.classList.add('chapter-item', 'card', 'p-3', 'mb-3');

        // Add title dynamically
        const chapterTitle = document.createElement('h3');
        chapterTitle.classList.add('chapter-title');
        chapterTitle.innerText = `Chapitre ${index + 1}`;
        newDiv.prepend(chapterTitle);

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.classList.add('remove-chapter', 'btn', 'btn-danger', 'mt-2');
        removeBtn.innerHTML = '🗑 Supprimer';
        newDiv.appendChild(removeBtn);

        chaptersList.appendChild(newDiv);
        index++;
    });

    // Remove chapter button logic
    chaptersList.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-chapter')) {
            e.target.closest('.chapter-item').remove();
        }
    });

    // 📝 Auto-expand textareas when typing
    document.addEventListener('input', function (event) {
        if (event.target.classList.contains('auto-expand')) {
            event.target.style.height = 'auto';
            event.target.style.height = (event.target.scrollHeight + 5) + 'px';
        }
    });
});