document.addEventListener("DOMContentLoaded", function () {
    // console.log("✅ main.js loaded!");

    const searchInput = document.querySelector("input[name='search']");
    const categorySelect = document.querySelector("select[name='category']");
    const quizItems = document.querySelectorAll(".quiz-item");

    // if (!searchInput || !categorySelect || quizItems.length === 0) {
    //     console.error("❌ Required elements not found!");
    //     return;
    // }

    // Prevent Enter key from submitting the form inside the search input
    searchInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            // console.log("❌ Enter key blocked!");
        }
    });

    function filterQuizzes() {
        let searchText = searchInput.value.toLowerCase();
        let selectedCategory = categorySelect.value.toLowerCase();

        quizItems.forEach(item => {
            let title = item.getAttribute("data-title") || "";
            let description = item.getAttribute("data-description") || "";
            let category = item.getAttribute("data-category") || "";

            let matchesSearch = title.includes(searchText) || description.includes(searchText);
            let matchesCategory = selectedCategory === "all" || category === selectedCategory;

            item.style.display = (matchesSearch && matchesCategory) ? "block" : "none";
        });
    }

    // Attach event listeners for real-time filtering
    searchInput.addEventListener("input", filterQuizzes);
    categorySelect.addEventListener("change", filterQuizzes);
});
