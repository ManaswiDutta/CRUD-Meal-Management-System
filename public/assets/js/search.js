// assets/js/search.js

document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("searchInput") || document.getElementById("studentSearch") || document.querySelector("input[type='search']");
    const button = document.getElementById("searchButton");
    const tbody = document.getElementById("usersTableBody") || document.getElementById("studentsTableBody") || document.querySelector("table tbody");

    // require a button to trigger search; do nothing otherwise
    if (!button || !input || !tbody) return;

    function getRows() {
        return Array.from(tbody.querySelectorAll("tr")).filter(r => !r.classList.contains('no-results-row'));
    }

    function showNoResults(cols) {
        let noRow = tbody.querySelector(".no-results-row");
        if (!noRow) {
            noRow = document.createElement("tr");
            noRow.className = "no-results-row";
            const td = document.createElement("td");
            td.colSpan = cols;
            td.style.textAlign = "center";
            td.style.padding = "12px";
            td.textContent = "No matching results.";
            noRow.appendChild(td);
            tbody.appendChild(noRow);
        }
    }

    function clearNoResults() {
        const noRow = tbody.querySelector(".no-results-row");
        if (noRow) noRow.remove();
    }

    function filterRows(query) {
        const q = (query || "").toLowerCase().trim();
        const rows = getRows();
        let anyVisible = false;

        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll("td"));
            const username = (cells[1]?.textContent || "").toLowerCase();
            const email = (cells[2]?.textContent || "").toLowerCase();
            const role = (cells[3]?.textContent || "").toLowerCase();
            const match = !q || username.includes(q) || email.includes(q) || role.includes(q);
            row.style.display = match ? "" : "none";
            if (match) anyVisible = true;
        });

        if (!anyVisible) {
            const table = tbody.closest("table");
            const cols = table ? table.querySelectorAll("th").length || 5 : 5;
            showNoResults(cols);
        } else {
            clearNoResults();
        }
    }

    // only trigger search when the button is clicked
    button.addEventListener("click", function (e) {
        e.preventDefault();
        filterRows(input.value);
    });
});
