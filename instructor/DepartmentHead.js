function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
}

document.addEventListener("DOMContentLoaded", () => {
  const menuTrigger = document.querySelector(".instructor-box");
  const menu = document.getElementById("dropdownMenu");
  const academicYearFilter = document.getElementById("academic-year-filter");
  const semesterFilter = document.getElementById("semester-filter");
  const reportStatus = document.getElementById("report-status");
  const facultyResults = document.getElementById("faculty-results");
  const facultyReportModal = document.getElementById("faculty-report-modal");
  const facultyReportModalContent = document.getElementById(
    "faculty-report-modal-content",
  );

  const filters = {
    academic_year: "",
    semester: "",
  };
  let reportRequestId = 0;
  let facultyRows = [];
  let returnFocusTarget = null;

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function renderSelect(select, options, selectedValue, placeholder) {
    select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>${options
      .map(
        (option) =>
          `<option value="${escapeHtml(option.value)}">${escapeHtml(option.label)}</option>`,
      )
      .join("")}`;
    select.value = String(selectedValue || "");
    select.disabled = options.length === 0;
  }

  function getRatingClass(rating) {
    const classes = {
      outstanding: "rating-outstanding",
      "very good": "rating-very-good",
      good: "rating-good",
      fair: "rating-fair",
      poor: "rating-poor",
    };
    return classes[String(rating || "").toLowerCase()] || "rating-no-score";
  }

  function getInitials(name) {
    return String(name || "Faculty")
      .trim()
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0))
      .join("")
      .toUpperCase();
  }

  function renderFaculty(rows) {
    facultyRows = rows;
    if (!rows.length) {
      facultyResults.innerHTML =
        '<tr><td colspan="6" class="empty-row">No department evaluation results are available for this period and filter.</td></tr>';
      return;
    }

    facultyResults.innerHTML = rows
      .map((faculty) => {
        const score =
          faculty.score === null ? null : Number(faculty.score);
        const percentage =
          faculty.percentage === null ? null : Number(faculty.percentage);
        const overallRating =
          score !== null && Number.isFinite(score)
            ? `${score.toFixed(2)} / 5.00`
            : "—";
        const percentageText =
          percentage !== null && Number.isFinite(percentage)
            ? `${percentage.toFixed(1)}%`
            : "—";
        return `<tr>
          <td>
            <div class="faculty-report-name">
              <span class="faculty-report-avatar" aria-hidden="true">${escapeHtml(getInitials(faculty.name))}</span>
              <span class="faculty-report-identity">
                <strong>${escapeHtml(faculty.name)}</strong>
                <small>${escapeHtml(faculty.faculty_id)}</small>
              </span>
            </div>
          </td>
          <td>
            <span class="faculty-report-period">
              <strong>${escapeHtml(filters.academic_year)}</strong>
              <small>${escapeHtml(filters.semester)}</small>
            </span>
          </td>
          <td>
            <span class="faculty-report-score">
              <strong>${overallRating}</strong>
              <small>(${percentageText})</small>
            </span>
          </td>
          <td>${escapeHtml(faculty.responses)}</td>
          <td><span class="faculty-report-status ${getRatingClass(faculty.rating)}">${escapeHtml(faculty.rating)}</span></td>
          <td>
            <button type="button" class="faculty-report-view" data-faculty-record-id="${Number(faculty.faculty_record_id)}" aria-label="View evaluation for ${escapeHtml(faculty.name)}" title="View evaluation">
              <i class="ph ph-eye" aria-hidden="true"></i>
            </button>
          </td>
        </tr>`;
      })
      .join("");
  }

  function openFacultyReport(faculty) {
    const score =
      faculty.score === null ? null : Number(faculty.score);
    const percentage =
      faculty.percentage === null ? null : Number(faculty.percentage);
    const categories = Array.isArray(faculty.categories)
      ? faculty.categories
      : [];
    const categoryCards = categories.length
      ? categories
          .map(
            (category) => {
              const average = Number(category.average);
              const categoryPercentage = Number.isFinite(average)
                ? Math.round((average / 5) * 100)
                : 0;
              const averageText = Number.isFinite(average)
                ? `${average.toFixed(2)} / 5.00`
                : "—";
              return `<article class="department-report-category-card">
                <h4>${escapeHtml(category.name)}</h4>
                <div>
                  <span>${categoryPercentage}%</span>
                  <strong>${averageText}</strong>
                </div>
              </article>`;
            },
          )
          .join("")
      : '<p class="department-report-category-empty">No category ratings are available.</p>';
    const rating = escapeHtml(faculty.rating);
    const overallRating =
      score !== null && Number.isFinite(score)
        ? score.toFixed(2)
        : "—";
    const percentageText =
      percentage !== null && Number.isFinite(percentage)
        ? `${percentage.toFixed(1)}%`
        : "—";

    facultyReportModalContent.innerHTML = `
      <header class="department-report-modal-header">
        <h2 id="faculty-report-modal-title">Evaluation Details</h2>
        <button type="button" class="department-report-close" aria-label="Close evaluation details">
          <i class="ph ph-x" aria-hidden="true"></i>
        </button>
      </header>
      <div class="department-report-modal-body">
        <div class="department-report-profile">
          <span class="faculty-report-avatar" aria-hidden="true">${escapeHtml(getInitials(faculty.name))}</span>
          <div>
            <strong>${escapeHtml(faculty.name)}</strong>
            <small>ID: ${escapeHtml(faculty.faculty_id)}</small>
            <small>${escapeHtml(filters.academic_year)} · ${escapeHtml(filters.semester)}</small>
          </div>
        </div>
        <div class="department-report-summary">
          <article class="department-report-summary-card">
            <span>Overall Rating</span>
            <strong>${overallRating}</strong>
            <small>out of 5.0</small>
            <span class="department-report-percentage">${percentageText}</span>
          </article>
          <article class="department-report-summary-card">
            <span>Student Respondents</span>
            <strong>${escapeHtml(faculty.responses)}</strong>
            <small>students responded</small>
          </article>
          <article class="department-report-summary-card">
            <span>Report Status</span>
            <strong class="faculty-report-status ${getRatingClass(faculty.rating)}">${rating}</strong>
            <small>performance indicator</small>
          </article>
        </div>
        <section class="department-report-categories">
          <div class="department-report-section-title">
            <div>
              <h3>Evaluation Categories</h3>
              <p>Category scores shown as percentages.</p>
            </div>
            <span>${categories.length} ${categories.length === 1 ? "category" : "categories"}</span>
          </div>
          <div class="department-report-category-cards">
            ${categoryCards}
          </div>
        </section>
      </div>
    `;
    returnFocusTarget = document.activeElement;
    facultyReportModal.hidden = false;
    document.body.classList.add("department-report-modal-open");
    facultyReportModal.querySelector(".department-report-close").focus();
  }

  function closeFacultyReport() {
    facultyReportModal.hidden = true;
    document.body.classList.remove("department-report-modal-open");
    returnFocusTarget?.focus();
  }

  async function loadReport() {
    const requestId = ++reportRequestId;
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value) params.set(key, value);
    });

    reportStatus.textContent = "Loading department results...";
    facultyResults.innerHTML =
      '<tr><td colspan="6" class="empty-row">Loading results...</td></tr>';

    try {
      const response = await fetch(
        `get_department_head_report.php?${params.toString()}`,
        { headers: { Accept: "application/json" }, cache: "no-store" },
      );
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to load department results.");
      }
      if (requestId !== reportRequestId) return;

      filters.academic_year = data.selected_filters?.academic_year || "";
      filters.semester = data.selected_filters?.semester || "";
      const filterOptions = data.filters || {};
      renderSelect(
        academicYearFilter,
        (filterOptions.academic_years || []).map((value) => ({
          value,
          label: value,
        })),
        filters.academic_year,
        "Select academic year",
      );
      renderSelect(
        semesterFilter,
        (filterOptions.semesters || []).map((value) => ({
          value,
          label: value,
        })),
        filters.semester,
        "Select semester",
      );
      renderFaculty(data.faculty || []);
      reportStatus.textContent = data.selected_period_id
        ? `${data.department} · ${filters.academic_year} - ${filters.semester}`
        : "No closed evaluation periods with department results are available.";
    } catch (error) {
      if (requestId !== reportRequestId) return;
      reportStatus.textContent =
        error.message || "Unable to load department results.";
      facultyResults.innerHTML =
        '<tr><td colspan="6" class="empty-row">Results could not be loaded.</td></tr>';
      facultyRows = [];
    }
  }

  facultyResults.addEventListener("click", (event) => {
    const viewButton = event.target.closest(".faculty-report-view");
    if (!viewButton) return;
    const facultyId = Number(viewButton.dataset.facultyRecordId);
    const faculty = facultyRows.find(
      (row) => Number(row.faculty_record_id) === facultyId,
    );
    if (faculty) openFacultyReport(faculty);
  });
  facultyReportModal.addEventListener("click", (event) => {
    if (
      event.target === facultyReportModal ||
      event.target.closest(".department-report-close")
    ) {
      closeFacultyReport();
    }
  });

  menu?.addEventListener("click", (event) => event.stopPropagation());
  document.addEventListener("click", (event) => {
    if (
      menu.style.display === "block" &&
      !menu.contains(event.target) &&
      !menuTrigger.contains(event.target)
    ) {
      menu.style.display = "none";
    }
  });
  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      menu.style.display = "none";
      if (!facultyReportModal.hidden) closeFacultyReport();
    }
  });

  academicYearFilter.addEventListener("change", () => {
    filters.academic_year = academicYearFilter.value;
    filters.semester = "";
    loadReport();
  });
  semesterFilter.addEventListener("change", () => {
    filters.semester = semesterFilter.value;
    loadReport();
  });
  loadReport();
});
