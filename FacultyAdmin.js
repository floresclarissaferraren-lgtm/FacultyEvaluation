const addSubjectModal = document.getElementById("addSubjectModal");
const addFacultyModal = document.getElementById("addFacultyModal");
const addStudentModal = document.getElementById("addStudentModal");
const addCategoryModal = document.getElementById("addCategoryModal");
const addQuestionModal = document.getElementById("addQuestionModal");
const subjectSelectionModal = document.getElementById("subjectSelectionModal");
const viewStudentSubjectsModal = document.getElementById("viewStudentSubjectsModal");
const viewFacultySubjectsModal = document.getElementById("viewFacultySubjectsModal");
const managePeriodsModal = document.getElementById("managePeriodsModal");

document.addEventListener("DOMContentLoaded", () => {
  let currentActiveLink = null;
  let currentRow = null;
  let deleteTarget = null;
  let deleteType = "";
  // ------------------- Notification (now using unified system) ==========================================================================================
  // Note: showNotification and showAlertModal are now provided by unified_notifications.js
  // These use consistent styling across all pages

  // DEPRECATED: Old notification modal system - replaced with unified showGlobalNotification
  // function showNotificationModal() {
  //     const modal = document.getElementById("notificationModal");
  //     modal.style.display = "flex";
  //     modal.style.zIndex = "10002";
  //     modal.style.position = "fixed";
  //     
  //     // Ensure OK button is clickable when modal is shown
  //     setTimeout(() => {
  //         const okBtn = document.getElementById("notificationModalOkBtn");
  //         if (okBtn) {
  //             okBtn.onclick = function() {
  //                 closeNotificationModal();
  //             };
  //         }
  //     }, 100);
  // }

  // function closeNotificationModal() {
  //     const modal = document.getElementById("notificationModal");
  //     modal.style.display = "none";
  //     
  //     // Also close the add question modal
  //     const addQuestionModal = document.getElementById("addQuestionModal");
  //     if (addQuestionModal) {
  //         addQuestionModal.style.display = "none";
  //     }
  // }


  // DEPRECATED: Setup notification modal event listeners - replaced with unified notification system
  // document.addEventListener("DOMContentLoaded", () => {
  //     const notificationModal = document.getElementById("notificationModal");
  //     const okBtn = document.getElementById("notificationModalOkBtn");
  //     
  //     if (notificationModal) {
  //         // Click outside to close
  //         notificationModal.addEventListener("click", (e) => {
  //             // Check if click is on modal backdrop (outside modal content)
  //             if (e.target === notificationModal) {
  //                 closeNotificationModal();
  //             }
  //         });
  //         
  //         // Ensure modal has proper z-index
  //         notificationModal.style.zIndex = "10002";
  //     }
  //     
  //     if (okBtn) {
  //         // OK button click to close
  //         okBtn.addEventListener("click", (e) => {
  //             e.preventDefault();
  //             e.stopPropagation();
  //             closeNotificationModal();
  //         });
  //         
  //         // Ensure button is clickable
  //         okBtn.style.pointerEvents = "auto";
  //         okBtn.style.cursor = "pointer";
  //     }
  // });
  // ================= Sidebar Toggle ==========================================================================================
  window.toggleSidebar = () => {
    const s = document.getElementById("sidebar"), m = document.querySelector("main"), navbar = document.querySelector(".navbar");
    if (window.innerWidth > 768) {
      s.classList.toggle("collapsed");
      m.classList.toggle("full");

      // Directly manipulate navbar styles
      if (s.classList.contains("collapsed")) {
        navbar.style.left = "85px";
        navbar.style.width = "calc(100% - 85px)";
      } else {
        navbar.style.left = "260px";
        navbar.style.width = "calc(100% - 260px)";
      }
    }
    else { s.classList.remove("collapsed"); s.classList.toggle("active"); m.style.marginLeft = "0"; m.style.width = "100%"; }
  };

  window.addEventListener("resize", () => {
    const s = document.getElementById("sidebar"), m = document.querySelector("main");
    if (innerWidth > 768) { s.classList.remove("active", "collapsed"); m.style.marginLeft = ""; m.style.width = ""; }
    else { s.classList.remove("collapsed"); m.classList.remove("full"); }
  });

  document.addEventListener("click", e => {
    const s = document.getElementById("sidebar");
    if (s.classList.contains("active") && !s.contains(e.target) && !e.target.closest(".hamburger")) {
      s.classList.remove("active");
    }
    if (e.target.closest("#sidebar a")) s.classList.remove("active");
  });


  // ================= Section Switching May nabago==========================================================================================
  window.showSection = (id, e) => {
    const currentSection = Array.from(document.querySelectorAll(".section"))
      .find(section => section.style.display !== "none")?.id;
    if (currentSection && currentSection !== id) {
      clearSearchControls();
    }

    // hide all sections
    document.querySelectorAll(".section").forEach(s => {
      s.style.display = "none";
    });

    // show selected section
    const target = document.getElementById(id);
    if (target) {
      target.style.display = "block";
    }

    // sidebar active state
    document.querySelectorAll(".sidebar a").forEach(l => l.classList.remove("active"));

    const link =
      e?.target.closest("a") ||
      document.querySelector(`.sidebar a[data-section="${id}"]`);

    if (link) {
      link.classList.add("active");
      currentActiveLink = link;
    }

    // Update navbar section title
    const navbarTitle = document.getElementById("navbarSectionTitle");
    const navbarSubtitle = document.querySelector(".section-subtitle");
    const sectionTitles = {
      "dashboard-section": "Dashboard",
      "programs-section": "Program Management",
      "faculties-section": "Faculty Management",
      "students-section": "Student Management",
      "criteria-section": "Evaluation Criteria List",
      "report-section": "Evaluation Report",
      "subjects-section": "Subjects Management",
      "manage-section": "Manage Program"
    };

    const sectionSubtitles = {
      "dashboard-section": "Real-time Overview of Faculty Performance and Evaluation Status",
      "programs-section": "Manage and update academic programs offered by the institution",
      "faculties-section": "This section contains all faculty members with their profiles and teaching assignments",
      "students-section": "This section shows all students with their basic details and records for easy management",
      "criteria-section": "This section shows the list of criteria used for evaluating faculty performance",
      "report-section": "This section shows faculty evaluation results and performance ratings",
      "subjects-section": "Manage and update subjects offered across different programs",
      "manage-section": "Manage subjects and classes for this program"
    };

    if (navbarTitle && sectionTitles[id]) {
      navbarTitle.textContent = sectionTitles[id];
    }

    if (navbarSubtitle && sectionSubtitles[id]) {
      navbarSubtitle.textContent = sectionSubtitles[id];
    }

    // =========================
    // AUTO LOAD SECTIONS (DATA ONLY)
    // =========================
    if (id === "dashboard-section") loadDashboardStats?.();
    if (id === "programs-section") loadPrograms?.();
    if (id === "faculties-section") {
      showFacultyListView?.();
      loadFaculty?.();
    }
    if (id === "criteria-section") loadCategories?.();
    if (id === "report-section") {
      loadReportPeriodFilters?.();
      loadEvaluations?.();
    }
    if (id === "students-section") loadStudents?.();
    if (id === "subjects-section") loadSubjectsMainTable?.();

    // =========================
    // Dashboard details toggle
    // =========================
    const dashboardDetails = document.getElementById("dashboard-details");
    if (dashboardDetails) {
      dashboardDetails.style.display =
        id === "dashboard-section" ? "flex" : "none";
    }

    // =========================
    // Show/hide evaluation table based on section
    // =========================
    const evaluationTable = document.querySelector(".evaluation-table");
    if (evaluationTable) {
      const tableWrapper = evaluationTable.closest(".table-wrapper");
      if (tableWrapper) {
        tableWrapper.style.display =
          id === "report-section" ? "block" : "none";
      }
    }
  };

  function clearControlValue(control) {
    if (!control) return;
    control.value = "";
    control.dispatchEvent(new Event("input", { bubbles: true }));
    control.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function clearSearchControls(targetIds = null) {
    const ids = targetIds || [
      "program-search",
      "manage-search",
      "manage-subjects-search",
      "manage-subjects-semester-filter",
      "manage-subjects-year-filter",
      "subjects-search",
      "subjects-year-filter",
      "faculty-search",
      "faculty-status-filter",
      "student-search",
      "student-program-filter",
      "student-yearlevel-filter"
    ];

    ids.forEach(id => clearControlValue(document.getElementById(id)));
  }

  document.addEventListener("click", event => {
    const clearBtn = event.target.closest(".clear-search-btn");
    if (!clearBtn) return;

    const targets = (clearBtn.dataset.clearTargets || "")
      .split(",")
      .map(id => id.trim())
      .filter(Boolean);

    clearSearchControls(targets);
  });

  // ================= Delete Forms ==========================================================================================
  function openDeleteModal(type, name, el) {
    console.log("openDeleteModal called with:", { type, name, el });

    const modalsToClose = [
      addSubjectModal,
      addFacultyModal,
      addStudentModal,
      addCategoryModal,
      addQuestionModal,
      subjectSelectionModal,
      viewStudentSubjectsModal,
      viewFacultySubjectsModal,
      managePeriodsModal
    ];
    modalsToClose.forEach(m => { if (m) m.style.display = "none"; });

    deleteTarget = el;
    deleteType = type;

    const deleteModal = document.getElementById("deleteModal");
    console.log("deleteModal element:", deleteModal);

    if (!deleteModal) {
      console.error("deleteModal element not found!");
      return;
    }

    document.getElementById("deleteMessage").innerText = `Do you want to delete ${type}? \n${name}`;
    document.getElementById("deleteWarning").innerText = `Warning: All details about this ${type} will be deleted.`;

    // Add wider class for subjects delete modal
    const deleteModalCard = document.querySelector(".delete-modal-card");
    if (type === "subject") {
      deleteModalCard.classList.add("subjects-delete");
    } else {
      deleteModalCard.classList.remove("subjects-delete");
    }

    deleteModal.style.display = "flex";
    deleteModal.style.zIndex = "10001";
    deleteModal.style.position = "fixed";
    console.log("Modal display set to flex");
  }

  const closeDeleteModal = () => { document.getElementById("deleteModal").style.display = "none"; deleteTarget = null; deleteType = ""; };
  const confirmDelete = () => {
    console.log("confirmDelete called with deleteType:", deleteType);
    console.log("confirmDelete called with deleteTarget:", deleteTarget);

    if (!deleteTarget || !deleteType) return closeDeleteModal();

    const cfg = {
      faculty: { url: "deleteFaculty.php", key: "faculty_id", type: "form" },
      program: { url: "deleteProgram.php", key: "program_code", type: "form" },
      category: { url: "delete_category.php", key: "category_id", type: "form" },
      question: { url: "delete_question.php", key: "question_id", type: "form" },
      student: { url: "student_crud.php", key: "id", type: "json" }, // student uses JSON
      subject: { url: "subject_crud.php", key: "id", type: "form" }, // subject uses form
      class: { url: "classes_crud.php", key: "id", type: "form" }, // class uses form
      class_subject: { url: "classes_crud.php", key: "custom", type: "form" }, // class subject uses custom handling
      period: { url: "periods_api.php?action=delete", key: "id", type: "json_period" }
    }[deleteType];

    if (!cfg) return closeDeleteModal();

    let fetchOptions;
    if (deleteType === "class_subject") {
      // Custom handling for class subject deletion
      const body = `action=delete_subject&class_id=${deleteTarget.classId}&subject_id=${deleteTarget.subjectId}`;
      console.log("Deleting class subject with body:", body);
      console.log("Delete target:", deleteTarget);

      fetchOptions = {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body
      };
    } else if (cfg.type === "json_period") {
      const val = deleteTarget.dataset[cfg.key] || deleteTarget.id.replace(`${deleteType}-`, "");
      fetchOptions = {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: val })
      };
    } else if (cfg.type === "json") {
      // For students, send JSON with action + id
      const val = deleteTarget.dataset[cfg.key] || deleteTarget.id.replace(`${deleteType}-`, "");
      fetchOptions = {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "delete", id: val })
      };
    } else {
      // For other entities, send form-encoded
      const val = deleteTarget.dataset[cfg.key] || deleteTarget.id.replace(`${deleteType}-`, "");
      let body = `${cfg.key}=${encodeURIComponent(val)}`;

      // Add action parameter for subjects and classes
      if (deleteType === "subject" || deleteType === "class") {
        body += `&action=delete`;
      }

      fetchOptions = {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: body
      };
    }

    fetch(cfg.url, fetchOptions)
      .then(r => r.json())
      .then(resp => {
        if (resp.success || resp.status === "success") {
          if (deleteType === "class_subject") {
            // Remove the subject card from DOM
            deleteTarget.subjectCard.remove();
          } else if (deleteType === "period") {
            deleteTarget.closest("tr")?.remove();
            loadPeriods?.();
            refreshPeriodCard?.();
          } else {
            deleteTarget.remove();
          }
          closeDeleteModal();
          openDeleteSuccess();

          // Refresh subjects list after successful deletion
          if (deleteType === "subject") {
            loadSubjectsMainTable?.();
            loadSubjects?.();
          }

          // Refresh classes list after successful deletion
          if (deleteType === "class") {
            loadClasses();
          }
          if (deleteType === "faculty" || deleteType === "student") {
            refreshSectionStats();
          }

          if (deleteType === "student") {
            // Delay dashboard update to ensure DB transaction is committed
            setTimeout(() => {
              loadDashboardStats();
              console.log("Dashboard stats updated after student delete");
            }, 500);
          }

          if (deleteType === "program") {
            // Update dashboard after program deletion
            setTimeout(() => {
              loadDashboardStats();
              console.log("Dashboard stats updated after program delete");
            }, 500);
          }

          if (deleteType === "faculty") {
            // Refresh faculty list after successful deletion
            setTimeout(() => {
              loadFaculty();
              loadDashboardStats();
              console.log("Faculty list and dashboard updated after faculty delete");
            }, 500);
          }
        } else {
          const errorMessage = resp.error || resp.message || "Unknown error occurred";
          console.error("Delete failed response:", resp);
          showNotification("Error deleting: " + errorMessage, "#f44336", 5000);
          closeDeleteModal();
        }
      })
      .catch(err => {
        console.error(err);
        showNotification("Unexpected error occurred while deleting", "#f44336", 5000);
        closeDeleteModal();
      });

  };

  function openDeleteSuccess() {
    const modal = document.getElementById("deleteSuccessModal");
    modal.style.display = "flex";
    modal.style.zIndex = "10002";
    modal.style.position = "fixed";
    // Also update dashboard when success modal is shown (for immediate feedback)
    if (deleteType === "student") {
      setTimeout(() => {
        loadDashboardStats();
        console.log("Dashboard stats updated on delete success modal");
      }, 100);
    }

    if (deleteType === "program") {
      setTimeout(() => {
        loadDashboardStats();
        console.log("Dashboard stats updated on delete success modal");
      }, 100);
    }
  }
  function closeDeleteSuccess() {
    document.getElementById("deleteSuccessModal").style.display = "none";
    // Update dashboard when success modal is closed
    if (deleteType === "student") {
      loadDashboardStats();
      console.log("Dashboard stats updated on delete success modal close");
    }

    if (deleteType === "program") {
      loadDashboardStats();
      console.log("Dashboard stats updated on delete success modal close");
    }
    // Reset delete type
    deleteType = "";
  }
  document.getElementById("success-ok-btn").onclick = closeDeleteSuccess;

  const modal = document.getElementById("deleteModal");
  modal.querySelector(".cancel-btn").onclick = closeDeleteModal;
  modal.querySelector(".submit-btn").onclick = confirmDelete;

  // ===================== Archive Confirmation Modal ==========================================================
  let _archivePending = null; // stores { row, record, statusMenu, type }

  function openArchiveModal(row, record, statusMenu, type = "faculty") {
    _archivePending = { row, record, statusMenu, type };
    const overlay = document.getElementById("archiveModal");
    const label = type === "student" ? "Student" : "Faculty";
    const title = overlay.querySelector(".archive-modal-title");
    const message = overlay.querySelector(".archive-modal-message");
    const confirmBtn = document.getElementById("archiveConfirmBtn");
    if (title) title.textContent = `Archive ${label}`;
    if (message) message.textContent = `Archive this ${label.toLowerCase()} account?`;
    if (confirmBtn) confirmBtn.textContent = `Yes, Archive`;
    overlay.classList.add("show");
  }

  function closeArchiveModal() {
    const overlay = document.getElementById("archiveModal");
    overlay.classList.remove("show");
    _archivePending = null;
  }
  window.closeArchiveModal = closeArchiveModal;

  document.getElementById("archiveConfirmBtn").addEventListener("click", async () => {
    if (!_archivePending) return;
    const { row, record, statusMenu, type } = _archivePending;
    closeArchiveModal();
    try {
      const result = await updateStatus(type, record.id, "archived");
      if (result.success) {
        row.dataset.status = "archived";
        const statusCellIndex = type === "student" ? 4 : 5;
        row.querySelector(`td:nth-child(${statusCellIndex})`).innerHTML = statusPillHtml("archived");
        if (statusMenu) statusMenu.querySelectorAll(".status-option").forEach(btn => btn.classList.remove("selected"));
        closeStatusMenus();
        showNotification(`${type === "student" ? "Student" : "Faculty"} archived successfully`, "#4caf50");
        refreshSectionStats();
        if (type === "student") loadStudents();
        else loadFaculty();
      } else {
        showNotification(`Failed to archive ${type}: ` + (result.message || "Unknown error"), "#f44336");
      }
    } catch (error) {
      console.error(`Archive ${type} error:`, error);
      showNotification(`Error archiving ${type}`, "#f44336");
    }
  });

  // Click outside archive modal box to close
  document.getElementById("archiveModal").addEventListener("click", (e) => {
    if (e.target.classList.contains("archive-modal-overlay")) closeArchiveModal();
  });

  // ===================== Logout Modal ==========================================================================================
  const logoutModal = document.getElementById("logoutModal");
  const logoutContent = logoutModal.querySelector(".logout-content");
  const cancelBtn = logoutModal.querySelector(".cancel-btn");
  const submitBtn = logoutModal.querySelector(".logout-btn");
  const logoutLink = document.getElementById("logoutLink");

  function showLogoutModal(e) {
    e.preventDefault();
    e.stopPropagation();
    logoutModal.style.display = "flex";
    logoutModal.style.zIndex = "10002";
    logoutModal.style.position = "fixed";
  }
  function closeLogoutModal() {
    logoutModal.style.display = "none";
  }
  function confirmLogout() {
    window.location.href = "EvalMain.php";
  }

  logoutContent.addEventListener("click", e => e.stopPropagation());
  logoutModal.addEventListener("click", closeLogoutModal);
  cancelBtn.addEventListener("click", closeLogoutModal);
  submitBtn.addEventListener("click", confirmLogout);

  logoutLink.addEventListener("click", showLogoutModal);
  // ========================= Programs ==========================================================================================
  const addProgramModal = document.getElementById("addProgramModal"),
    programSubmitBtn = document.getElementById("save-program-btn"),
    programHeader = addProgramModal.querySelector("h3"),
    programCodeInput = document.getElementById("program-code"),
    programNameInput = document.getElementById("program-name");
  let editRowProgram = null;

  // OPEN ADD MODAL
  document.querySelector(".add-program-btn")?.addEventListener("click", () => {
    programHeader.innerText = "ADD PROGRAM"; programSubmitBtn.innerText = "SAVE PROGRAM";
    programCodeInput.value = ""; programNameInput.value = ""; editRowProgram = null;
    // Clear validation states
    programCodeInput.style.borderColor = "";
    programCodeInput.title = "";
    addProgramModal.style.display = "flex";
    addProgramModal.style.zIndex = "10001";
    addProgramModal.style.position = "fixed";
  });

  // REAL-TIME VALIDATION FOR PROGRAM CODE (using existing table data)
  programCodeInput?.addEventListener("input", () => {
    // Convert to uppercase in real-time
    const originalValue = programCodeInput.value;
    const upperValue = originalValue.toUpperCase();
    if (originalValue !== upperValue) {
      const cursorPos = programCodeInput.selectionStart;
      programCodeInput.value = upperValue;
      programCodeInput.setSelectionRange(cursorPos, cursorPos);
    }

    const code = programCodeInput.value.trim();
    if (!code || code.length < 2) {
      programCodeInput.style.borderColor = "";
      return;
    }

    // Check against existing programs in table
    const existingCodes = [];
    document.querySelectorAll(".programs-table tbody tr").forEach(row => {
      const rowCode = row.cells[0]?.textContent?.trim().toUpperCase();
      if (rowCode && (!editRowProgram || row.dataset.id !== editRowProgram.dataset.id)) {
        existingCodes.push(rowCode);
      }
    });

    if (existingCodes.includes(code.toUpperCase())) {
      programCodeInput.style.borderColor = "#f44336";
      programCodeInput.title = "Program Code already exists";
    } else {
      programCodeInput.style.borderColor = "#4caf50";
      programCodeInput.title = "";
    }
  });


  // SAVE / UPDATE
  programSubmitBtn.addEventListener("click", () => {
    const code = programCodeInput.value.trim(), name = programNameInput.value.trim();
    if (!code || !name) {
      showGlobalNotification("Please fill in both Program Code and Program Name", "warning");
      return;
    }

    // Simple validation using existing table data
    const existingCodes = [];
    document.querySelectorAll(".programs-table tbody tr").forEach(row => {
      const rowCode = row.cells[0]?.textContent?.trim().toUpperCase();
      if (rowCode && (!editRowProgram || row.dataset.id !== editRowProgram.dataset.id)) {
        existingCodes.push(rowCode);
      }
    });

    if (existingCodes.includes(code.toUpperCase())) {
      showNotification("Program Code already exists. Please use a different code.", "#f44336", 4000);
      programCodeInput.focus();
      return;
    }

    const url = editRowProgram ? "edit_program.php" : "add_program.php";
    const body = editRowProgram ? `id=${editRowProgram.dataset.id}&program_code=${encodeURIComponent(code)}
  &program_name=${encodeURIComponent(name)}` : `program_code=${encodeURIComponent(code)}&program_name=${encodeURIComponent(name)}`;

    fetch(url, { method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body })
      .then(r => r.text()).then(t => { try { return JSON.parse(t); } catch { return t; } })
      .then(res => {
        const ok = (typeof res === "object" && res.status === "success") || (typeof res === "string" && res.toLowerCase().includes("success"));
        if (ok) {
          loadPrograms(); closeProgramModal();
          if (editRowProgram) {
            showNotification("Program edited successfully!", "#4caf50");
          } else {
            showNotification("Program added successfully!", "#4caf50");
          }
        } else {
          // Show simple notification for duplicate program errors
          if (typeof res === "object" && res.message && res.message.includes("already exists")) {
            showNotification(res.message, "#f44336", 4000);
          } else {
            showNotification(res.message || res || "Error updating Program.", "#f44336", 4000);
          }
        }
      }).catch(err => { console.error("FETCH ERROR:", err); showNotification("Something went wrong.", "#f44336", 4000); });
  });

  // ATTACH ROW EVENTS
  function attachProgramRowEvents(row) {
    row.querySelector(".manage-btn")?.addEventListener("click", () => {
      currentProgramId = row.dataset.id;
      showSection("manage-section");
      document.getElementById("manageTitle").innerText = row.cells[1].innerText;
      showTable("subjects");
      loadSubjects();
    });
    row.querySelector(".edit-btn")?.addEventListener("click", () => {
      programHeader.innerText = "EDIT PROGRAM"; programSubmitBtn.innerText = "UPDATE PROGRAM";
      programCodeInput.value = row.cells[0].innerText; programNameInput.value = row.cells[1].innerText;
      editRowProgram = row;
      // Clear validation states
      programCodeInput.style.borderColor = "";
      programCodeInput.title = "";
      addProgramModal.style.display = "flex";
    });
    row.querySelector(".delete-btn")?.addEventListener("click", () => openDeleteModal("program", row.cells[1].innerText, row));
  }

  // LOAD PROGRAMS
  function loadPrograms() {
    fetch("getProgram.php")
      .then(r => r.json())
      .then(data => {
        const container = document.getElementById("programsCardsContainer");
        container.innerHTML = "";

        // Update program count
        const totalProgramsElement = document.getElementById("totalPrograms");
        const count = data ? data.length : 0;
        totalProgramsElement.textContent = `${count} Program Record`;

        // Check if no data or empty array
        if (!data || data.length === 0) {
          container.innerHTML = '<div style="text-align:center;padding:40px;color:#64748b;">No programs found</div>';
          return;
        }

        // Sort data alphabetically by program_code
        data.sort((a, b) => a.program_code.localeCompare(b.program_code));

        data.forEach((p, index) => {
          const card = document.createElement("div");
          card.className = "program-card";
          card.dataset.id = p.id;
          card.dataset.programCode = p.program_code;
          card.dataset.programName = p.program_name;
          card.dataset.color = index % 8;
          card.innerHTML = `
          <div class="program-card-header">
            <div class="program-icon">${p.program_code}</div>
            <div class="program-content">
              <div class="program-name">${p.program_name}</div>
            </div>
          </div>
          <div class="program-card-actions">
            <button class="btn-manage">
              <i class="ph ph-sliders"></i> Manage
            </button>
            <button class="btn-edit">
              <i class="ph ph-pencil-simple"></i> Edit
            </button>
            <button class="btn-delete">
              <i class="ph ph-trash"></i> Delete
            </button>
          </div>`;

          // Add event listeners
          const manageBtn = card.querySelector('.btn-manage');
          const editBtn = card.querySelector('.btn-edit');
          const deleteBtn = card.querySelector('.btn-delete');

          manageBtn.addEventListener('click', () => manageProgram(p.id, p.program_code));
          editBtn.addEventListener('click', () => editProgram(p.id, p.program_code, p.program_name));
          deleteBtn.addEventListener('click', () => deleteProgram(p.id, p.program_code));

          container.appendChild(card);
        });
      })
      .catch(err => console.error("FETCH ERROR:", err));
  }
  // CLOSE MODAL
  function closeProgramModal() { programCodeInput.value = ""; programNameInput.value = ""; editRowProgram = null; addProgramModal.style.display = "none"; }

  document.getElementById("program-search")?.addEventListener("input", e => {
    const term = e.target.value.toLowerCase();
    const cards = document.querySelectorAll(".program-card");
    cards.forEach(card => {
      const text = card.textContent.toLowerCase();
      card.style.display = text.includes(term) ? "" : "none";
    });
  });

  // Program Card Action Functions
  function manageProgram(id, code) {
    // Set current program for management first
    currentProgramId = id;
    currentProgramCode = code;

    // Navigate to manage section for this program
    showSection('manage-section', event);

    // Update manage title
    const manageTitle = document.getElementById('manageTitle');
    if (manageTitle) {
      manageTitle.textContent = `Manage Program - ${code}`;
    }

    // Show subjects tab by default
    const subjectsTab = document.querySelector('.subject-btn');
    const classesTab = document.querySelector('.classes-btn');
    const subjectsDiv = document.getElementById('subjects');
    const classesDiv = document.getElementById('classes');
    const mw = document.querySelector("#manage-section .header-actions-section .search-wrapper");
    const mc = document.getElementById("manage-clear-search");

    if (subjectsTab && classesTab && subjectsDiv && classesDiv) {
      subjectsTab.classList.add('active');
      classesTab.classList.remove('active');
      subjectsDiv.style.display = 'block';
      classesDiv.style.display = 'none';
      if (mw) mw.style.display = 'none';
      if (mc) mc.style.display = 'none';
    }

    // Load subjects and classes for this program
    loadSubjects();
    loadClasses();
  }

  function editProgram(id, code, name) {
    // Open edit modal with program data
    programHeader.innerText = "EDIT PROGRAM";
    programSubmitBtn.innerText = "UPDATE PROGRAM";
    programCodeInput.value = code;
    programNameInput.value = name;
    editRowProgram = { dataset: { id: id } };
    addProgramModal.style.display = "flex";
    addProgramModal.style.zIndex = "10001";
    addProgramModal.style.position = "fixed";
  }

  function deleteProgram(id, code) {
    openDeleteModal("program", code, { dataset: { id: id } });
  }

  document.addEventListener("DOMContentLoaded", loadPrograms);

  // ========================= CLICK OUTSIDE TO CLOSE MODALS =========================
  function setupModalClickOutside(modal) {
    if (!modal) {
      console.log("Modal not found");
      return;
    }

    console.log("Setting up click outside for modal:", modal.id);

    modal.addEventListener("click", (e) => {
      console.log("Modal clicked:", e.target, e.target === modal);
      // Check if click is on modal backdrop (outside modal content)
      if (e.target === modal) {
        console.log("Closing modal due to outside click");
        modal.style.display = "none";
      }
    });
  }

  // Setup click-outside-to-close for all modals
  function setupAllModalsClickOutside() {
    console.log("Setting up click-outside-to-close for modals");

    const modals = [
      "addProgramModal",
      "addSubjectModal",
      "addFacultyModal",
      "addStudentModal",
      "addCategoryModal",
      "addQuestionModal",
      "subjectSelectionModal",
      "viewStudentSubjectsModal",
      "addSubjectMainModal",
      "managePeriodsModal",
      "deleteModal",
      "deleteSuccessModal",
      "facultyReportModal"
    ];

    modals.forEach(modalId => {
      const modal = document.getElementById(modalId);
      console.log("Found modal:", modalId, modal);
      setupModalClickOutside(modal);
    });
  }

  // Setup immediately when DOM is ready
  document.addEventListener("DOMContentLoaded", setupAllModalsClickOutside);

  // Also setup immediately in case DOM is already loaded
  setupAllModalsClickOutside();

  // Initialize top performance modal close button
  document.addEventListener("DOMContentLoaded", initializeTopPerformanceModal);
  initializeTopPerformanceModal();

  // ========================= MAIN SUBJECTS SECTION =========================
  const addSubjectMainModal = document.getElementById("addSubjectMainModal"),
    saveMainSubjectBtn = document.getElementById("save-main-subject-btn"),
    subjectsMainTbody = document.getElementById("subjects-main-tbody");
  let subjectsMainData = [];
  let selectedClassSemester = "";
  let selectedAcademicYear = "";
  let editingPeriodId = null;
  try {
    selectedClassSemester = localStorage.getItem("selectedClassSemester") || "";
    selectedAcademicYear = localStorage.getItem("selectedAcademicYear") || "";
  } catch (err) {
    selectedClassSemester = "";
    selectedAcademicYear = "";
  }

  function initAddSubjectMainBtn() {
    const addBtn = document.querySelector(".add-subject-main-btn");
    if (addBtn) {
      addBtn.addEventListener("click", () => {
        console.log("Add Subject button clicked");
        // Clear form
        document.getElementById("main-subject-code").value = "";
        document.getElementById("main-subject-desc").value = "";
        document.getElementById("main-program-select").selectedIndex = 0;
        document.getElementById("main-semester-select").selectedIndex = 0;
        document.getElementById("main-year-select").selectedIndex = 0;

        // Load programs for dropdown
        loadProgramsForDropdown();

        addSubjectMainModal.style.display = "flex";
        addSubjectMainModal.style.zIndex = "10001";
        addSubjectMainModal.style.position = "fixed";
        console.log("Modal should be visible now");
        console.log("Modal element:", addSubjectMainModal);
        console.log("Modal display style:", window.getComputedStyle(addSubjectMainModal).display);
      });
    } else {
      console.log("Add Subject button not found");
    }
  }

  // Initialize when DOM is ready
  document.addEventListener("DOMContentLoaded", () => {
    initAddSubjectMainBtn();
  });

  // Also try to initialize immediately in case DOM is already loaded
  initAddSubjectMainBtn();

  // LOAD PROGRAMS FOR DROPDOWN
  function loadProgramsForDropdown() {
    fetch("getProgram.php")
      .then(r => r.json())
      .then(data => {
        const select = document.getElementById("main-program-select");
        select.innerHTML = '<option value="" disabled selected>-- Select Program --</option>';

        data.sort((a, b) => a.program_name.localeCompare(b.program_name));

        data.forEach(p => {
          const option = document.createElement("option");
          option.value = p.id;
          option.textContent = `${p.program_code} - ${p.program_name}`;
          select.appendChild(option);
        });
      })
      .catch(err => console.error("Error loading programs:", err));
  }

  // SAVE SUBJECT
  saveMainSubjectBtn?.addEventListener("click", () => {
    const code = document.getElementById("main-subject-code").value.trim(),
      desc = document.getElementById("main-subject-desc").value.trim(),
      program = document.getElementById("main-program-select").value,
      semester = document.getElementById("main-semester-select").value,
      year = document.getElementById("main-year-select").value;

    if (!code || !desc || !program || !semester || !year) {
      showGlobalNotification("Please fill all fields", "warning");
      return;
    }

    fetch("subject_crud.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `action=add&program_id=${program}&subject_code=${encodeURIComponent(code)}&subject_desc=${encodeURIComponent(desc)}&semester=${encodeURIComponent(semester)}&year_level=${encodeURIComponent(year)}`
    })
      .then(r => r.json())
      .then(res => {
        if (res.status === "success") {
          addSubjectMainModal.style.display = "none";
          loadSubjectsMainTable();
          showNotification("Subject added successfully!", "#4caf50");
        } else {
          alert("Error: " + (res.message || "Failed to add subject"));
        }
      })
      .catch(err => {
        console.error("Error:", err);
        alert("Something went wrong");
      });
  });

  // LOAD ALL SUBJECTS
  function loadSubjectsMainTable() {
    fetch("subject_crud.php?action=get_all")
      .then(r => r.json())
      .then(data => {
        subjectsMainData = Array.isArray(data) ? data : [];
        renderSubjectsMainTable();
      })
      .catch(err => {
        console.error("Error loading subjects:", err);
        subjectsMainTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">Error loading subjects</td></tr>';
      });
  }

  function renderSubjectsMainTable() {
    if (!subjectsMainTbody) return;
    const searchTerm = (document.getElementById("subjects-search")?.value || "").toLowerCase().trim();
    const yearFilter = document.getElementById("subjects-year-filter")?.value || "";
    const data = subjectsMainData.filter(s => {
      const matchesSearch = !searchTerm ||
        String(s.subject_code || "").toLowerCase().includes(searchTerm) ||
        String(s.subject_desc || "").toLowerCase().includes(searchTerm) ||
        String(s.program_name || "").toLowerCase().includes(searchTerm);
      const matchesYear = !yearFilter || String(s.year_level || "") === yearFilter;
      return matchesSearch && matchesYear;
    });

    subjectsMainTbody.innerHTML = "";

    if (!data || data.length === 0) {
      subjectsMainTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">No subjects found</td></tr>';
      return;
    }

    data.forEach(s => {
      const row = document.createElement("tr");
      row.dataset.id = s.id;
      row.innerHTML = `
          <td>${s.subject_code}</td>
          <td>${s.subject_desc}</td>
          <td>${s.program_name || 'N/A'}</td>
          <td>${s.semester || ''}</td>
          <td>${s.year_level}</td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="edit-btn"><i class="ph ph-pencil-simple"></i></button>
              <button class="delete-btn"><i class="ph ph-trash"></i></button>
            </div>
          </td>
        `;

      // EDIT BUTTON
      row.querySelector(".edit-btn").addEventListener("click", () => {
        // TODO: Implement edit functionality
        alert("Edit functionality coming soon");
      });

      // DELETE BUTTON
      row.querySelector(".delete-btn").addEventListener("click", () => {
        openDeleteModal("subject", s.subject_code, row);
      });

      subjectsMainTbody.appendChild(row);
    });
  }

  ["subjects-search", "subjects-year-filter"].forEach(id => {
    document.getElementById(id)?.addEventListener("input", renderSubjectsMainTable);
    document.getElementById(id)?.addEventListener("change", renderSubjectsMainTable);
  });

  function defaultAcademicYear() {
    const today = new Date();
    const startYear = today.getMonth() >= 5 ? today.getFullYear() : today.getFullYear() - 1;
    return `${startYear}-${startYear + 1}`;
  }

  function populateAcademicYearSelect() {
    const select = document.getElementById("dashboard-ay-select");
    if (!select) return;

    const baseYear = new Date().getFullYear();
    const years = [];
    for (let year = baseYear - 1; year <= baseYear + 3; year++) {
      years.push(`${year}-${year + 1}`);
    }

    select.innerHTML = '<option value="">Select Academic Year</option>' +
      years.map(ay => `<option value="${ay}">${ay}</option>`).join("");

    if (!selectedAcademicYear) selectedAcademicYear = defaultAcademicYear();
    select.value = selectedAcademicYear;
  }

  function syncDashboardPeriodSetting() {
    populateAcademicYearSelect();
    const semSelect = document.getElementById("dashboard-semester-select");
    if (semSelect) semSelect.value = selectedClassSemester;
    syncPeriodFormDefaults();
    updateDashboardPeriodBox();
  }

  async function loadDashboardPeriodSetting() {
    try {
      const res = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
      const data = await res.json();
      if (!data || !data.success) return;

      const serverHasSetting = !!(data.current_academic_year && data.current_semester);
      if (data.current_academic_year) selectedAcademicYear = data.current_academic_year;
      if (data.current_semester) selectedClassSemester = data.current_semester;

      try {
        if (selectedAcademicYear) localStorage.setItem("selectedAcademicYear", selectedAcademicYear);
        if (selectedClassSemester) localStorage.setItem("selectedClassSemester", selectedClassSemester);
      } catch (err) { }

      syncDashboardPeriodSetting();
      if (!serverHasSetting && selectedAcademicYear && selectedClassSemester) {
        saveDashboardPeriodSetting();
      }
    } catch (err) { }
  }

  function saveDashboardPeriodSetting() {
    fetch("periods_api.php?action=set_dashboard_period", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({
        academic_year: selectedAcademicYear || "",
        semester: selectedClassSemester || ""
      })
    }).catch(() => { });
  }

  function syncPeriodFormDefaults() {
    const ayInput = document.getElementById("period-ay");
    const semSelect = document.getElementById("period-sem");
    if (ayInput && !editingPeriodId) ayInput.value = selectedAcademicYear || "";
    if (semSelect && !editingPeriodId) semSelect.value = selectedClassSemester || "";
  }

  syncDashboardPeriodSetting();
  document.addEventListener("DOMContentLoaded", () => {
    syncDashboardPeriodSetting();
    loadDashboardPeriodSetting();
  });

  function handleSetClassSemester(semester) {
    if (!semester) return showNotification("Please select semester first", "#f44336");
    selectedClassSemester = semester;
    try {
      localStorage.setItem("selectedClassSemester", semester);
    } catch (err) { }
    syncDashboardPeriodSetting();
    updateDashboardPeriodBox();
    saveDashboardPeriodSetting();

    const classYear = document.getElementById("class-year")?.value || "";
    if (document.getElementById("addClassModal")?.style.display !== "none" && classYear) {
      const facultyBox = document.getElementById("faculty-list");
      if (facultyBox) {
        facultyBox.dataset.facultyData = JSON.stringify({});
        facultyBox.innerHTML = '<h4><i class="ph ph-user"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
      }
      loadSubjectsByYear(classYear);
    }
  }

  document.getElementById("dashboard-ay-select")?.addEventListener("change", (e) => {
    selectedAcademicYear = e.target.value;
    try {
      localStorage.setItem("selectedAcademicYear", selectedAcademicYear);
    } catch (err) { }
    syncPeriodFormDefaults();
    updateDashboardPeriodBox();
    saveDashboardPeriodSetting();
  });

  document.getElementById("dashboard-semester-select")?.addEventListener("change", (e) => {
    handleSetClassSemester(e.target.value);
  });

  // CLOSE MODAL
  addSubjectMainModal?.querySelector(".close-btn")?.addEventListener("click", () => {
    addSubjectMainModal.style.display = "none";
  });

  // ========================= GLOBAL STATE =========================
  let currentProgramId = null;
  let currentSubjectId = null;

  let isEditingSubject = false;
  let editSubjectId = null;

  // Manage-section subjects pagination state
  let subjectsAllData = [];   // full fetched list for current program
  let subjectsFilteredData = [];   // after search/filter
  let subjectsCurrentPage = 1;
  const SUBJECTS_ROWS_PER_PAGE = 50;   // rows before pagination kicks in
  const SUBJECTS_VISIBLE_ROWS = 5;    // rows visible at once inside scroll window

  // ========================= ELEMENTS =========================
  const addManageBtn = document.querySelector(".add-manage-btn"),
    saveSubjectBtn = document.getElementById("save-subject-btn"),
    saveClassBtn = document.getElementById("save-class-btn"),
    subjectsTableBody = document.querySelector("#subjects tbody"),
    classesTableBody = document.querySelector("#classes tbody");

  // ========================= UI SWITCH =========================
  function showTable(tab) {
    const s = document.getElementById("subjects"),
      c = document.getElementById("classes"),
      sb = document.querySelector(".subject-btn"),
      cb = document.querySelector(".classes-btn"),
      mw = document.querySelector("#manage-section .header-actions-section .search-wrapper"),
      mc = document.getElementById("manage-clear-search");

    if (!s || !c) return;

    if (tab === "subjects") {
      s.style.display = "block";
      c.style.display = "none";
      sb?.classList.add("active");
      cb?.classList.remove("active");
      if (mw) mw.style.display = "none";
      if (mc) mc.style.display = "none";
    } else {
      s.style.display = "none";
      c.style.display = "block";
      sb?.classList.remove("active");
      cb?.classList.add("active");
      if (mw) mw.style.display = "flex";
      if (mc) mc.style.display = "inline-flex";
    }
  }

  // ========================= BACK =========================
  document.querySelector(".back-btn")?.addEventListener("click", () => {
    showSection("programs-section");
    currentSubjectId = null;
  });

  // ========================= TAB BUTTONS =========================
  document.querySelector(".subject-btn")?.addEventListener("click", () => {
    showTable("subjects");
    loadSubjects();
  });

  document.querySelector(".classes-btn")?.addEventListener("click", () => {
    showTable("classes");
    loadClasses();
  });

  // ========================= OPEN MODALS =========================
  addManageBtn?.addEventListener("click", () => {
    // Check which tab is active to determine which modal to open
    const subjectsTab = document.querySelector(".subject-btn.active");
    const classesTab = document.querySelector(".classes-btn.active");

    if (subjectsTab) {
      // Open subject modal
      addSubjectModal.style.display = "flex";
      addSubjectModal.style.zIndex = "10001";
      addSubjectModal.style.position = "fixed";
      document.getElementById("subject-code").value = "";
      document.getElementById("subject-desc").value = "";
      document.getElementById("subject-semester").selectedIndex = 0;
      document.getElementById("subject-year").selectedIndex = 0;
      isEditingSubject = false;
      editSubjectId = null;
    } else if (classesTab) {
      // Open class modal
      const addClassModal = document.getElementById("addClassModal");
      addClassModal.style.display = "flex";
      addClassModal.style.zIndex = "10001";
      addClassModal.style.position = "fixed";
      addClassModal.dataset.isEditing = "false";
      delete addClassModal.dataset.editId;
      delete addClassModal.dataset.existingAssignments;
      delete addClassModal.dataset.existingSubjectIds;
      document.getElementById("class-year").selectedIndex = 0;
      document.getElementById("class-block").value = "";
      setClassSubjectMessage("Select year level first to load subjects");
      const facultyBox = document.getElementById("faculty-list");
      if (facultyBox) {
        facultyBox.dataset.facultyData = JSON.stringify({});
        facultyBox.innerHTML = '<h4><i class="ph ph-user"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
      }
    }
  });

  function classSubjectSearchHtml() {
    return `
    <h4><i class="ph ph-book"></i> Assigned Subjects</h4>
    <div class="class-subject-search">
      <input type="text" id="class-subject-search" placeholder="Search assigned subjects..." class="subject-search-input">
      <i class="ph ph-magnifying-glass"></i>
    </div>
    <div class="class-subject-options"></div>
  `;
  }

  function getClassSubjectOptionsBox() {
    const box = document.getElementById("subject-checkbox-list");
    return box?.querySelector(".class-subject-options") || box;
  }

  function setClassSubjectMessage(message) {
    const box = document.getElementById("subject-checkbox-list");
    if (!box) return;
    box.innerHTML = classSubjectSearchHtml();
    getClassSubjectOptionsBox().innerHTML = `<small>${message}</small>`;
    bindClassSubjectSearch();
  }

  function bindClassSubjectSearch() {
    const input = document.getElementById("class-subject-search");
    if (!input) return;
    input.addEventListener("input", () => {
      const term = input.value.toLowerCase().trim();
      document.querySelectorAll("#subject-checkbox-list .class-subject-option").forEach(item => {
        item.style.display = !term || item.dataset.search.includes(term) ? "" : "none";
      });
    });
  }

  // ========================= LOAD SUBJECTS =========================
  function loadSubjects() {
    if (!currentProgramId) return;

    // Reset filters
    const searchInput = document.getElementById("manage-subjects-search");
    const semesterFilter = document.getElementById("manage-subjects-semester-filter");
    const yearFilter = document.getElementById("manage-subjects-year-filter");

    if (searchInput) searchInput.value = "";
    if (semesterFilter) semesterFilter.value = "";
    if (yearFilter) yearFilter.value = "";

    fetch(`subject_crud.php?action=get&program_id=${currentProgramId}`)
      .then(r => r.json())
      .then(data => {
        subjectsAllData = Array.isArray(data) ? data : [];
        subjectsCurrentPage = 1;
        applyManageSubjectFilters();
      });
  }

  function applyManageSubjectFilters() {
    const searchTerm = (document.getElementById("manage-subjects-search")?.value || "").toLowerCase().trim();
    const semester = document.getElementById("manage-subjects-semester-filter")?.value || "";
    const year = document.getElementById("manage-subjects-year-filter")?.value || "";

    subjectsFilteredData = subjectsAllData.filter(s => {
      const text = `${s.subject_code} ${s.subject_desc} ${s.semester} ${s.year_level}`.toLowerCase();
      return (!searchTerm || text.includes(searchTerm))
        && (!semester || s.semester === semester)
        && (!year || s.year_level === year);
    });

    subjectsCurrentPage = 1;
    renderManageSubjectsTable();
  }

  function renderManageSubjectsTable() {
    if (!subjectsTableBody) return;

    const total = subjectsFilteredData.length;

    if (total === 0) {
      subjectsTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">No Subject found for this program</td></tr>';
      renderManageSubjectsPagination(0);
      return;
    }

    const usePagination = total > SUBJECTS_ROWS_PER_PAGE;
    const pageData = usePagination
      ? subjectsFilteredData.slice(
        (subjectsCurrentPage - 1) * SUBJECTS_ROWS_PER_PAGE,
        subjectsCurrentPage * SUBJECTS_ROWS_PER_PAGE
      )
      : subjectsFilteredData;

    subjectsTableBody.innerHTML = "";

    pageData.forEach(s => {
      const row = document.createElement("tr");
      row.dataset.id = s.id;
      row.dataset.semester = s.semester || "";
      row.dataset.year = s.year_level || "";

      row.innerHTML = `
      <td>${s.subject_code}</td>
      <td>${s.subject_desc}</td>
      <td>${s.semester || ''}</td>
      <td>${s.year_level}</td>
      <td class="action-cell">
        <div class="action-buttons">
          <button class="edit-btn"><i class="ph ph-pencil-simple"></i></button>
          <button class="delete-btn"><i class="ph ph-trash"></i></button>
        </div>
      </td>
    `;

      // SELECT SUBJECT - Only click on non-action areas
      row.addEventListener("click", (e) => {
        if (e.target.closest(".action-cell")) return;
        currentSubjectId = s.id;
        showTable("classes");
        loadClasses();
      });

      // EDIT SUBJECT
      row.querySelector(".edit-btn").addEventListener("click", (e) => {
        e.stopPropagation();
        isEditingSubject = true;
        editSubjectId = s.id;

        document.getElementById("subject-code").value = s.subject_code;
        document.getElementById("subject-desc").value = s.subject_desc;

        const semesterSelect = document.getElementById("subject-semester");
        for (let i = 0; i < semesterSelect.options.length; i++) {
          if (semesterSelect.options[i].value === (s.semester || "1st Semester")) {
            semesterSelect.selectedIndex = i;
            break;
          }
        }

        const yearSelect = document.getElementById("subject-year");
        for (let i = 0; i < yearSelect.options.length; i++) {
          if (yearSelect.options[i].value === s.year_level) {
            yearSelect.selectedIndex = i;
            break;
          }
        }

        addSubjectModal.style.display = "flex";
        addSubjectModal.style.zIndex = "10001";
        addSubjectModal.style.position = "fixed";
      });

      // DELETE SUBJECT
      row.querySelector(".delete-btn").addEventListener("click", (e) => {
        e.stopPropagation();
        openDeleteModal("subject", s.subject_code, row);
      });

      subjectsTableBody.appendChild(row);
    });

    renderManageSubjectsPagination(total);
  }

  function renderManageSubjectsPagination(total) {
    const subjectsWrapper = document.getElementById("subjects");
    if (!subjectsWrapper) return;

    let pager = subjectsWrapper.querySelector(".manage-subjects-pagination");
    if (!pager) {
      pager = document.createElement("div");
      pager.className = "manage-subjects-pagination";
      subjectsWrapper.appendChild(pager);
    }

    if (total <= SUBJECTS_ROWS_PER_PAGE) {
      pager.innerHTML = "";
      pager.style.display = "none";
      return;
    }

    pager.style.display = "flex";

    const totalPages = Math.ceil(total / SUBJECTS_ROWS_PER_PAGE);
    const cur = subjectsCurrentPage;

    // Build page buttons: always show first, last, cur-1, cur, cur+1
    const pageSet = new Set(
      [1, totalPages, cur - 1, cur, cur + 1].filter(p => p >= 1 && p <= totalPages)
    );
    const sorted = [...pageSet].sort((a, b) => a - b);

    let html = `<button class="spager-btn spager-prev" ${cur === 1 ? "disabled" : ""} data-page="${cur - 1}">
    <i class="ph ph-caret-left"></i> Prev
  </button>`;

    let prev = 0;
    for (const p of sorted) {
      if (p - prev > 1) html += `<span class="spager-ellipsis">…</span>`;
      html += `<button class="spager-btn spager-num${p === cur ? " active" : ""}" data-page="${p}">${p}</button>`;
      prev = p;
    }

    html += `<button class="spager-btn spager-next" ${cur === totalPages ? "disabled" : ""} data-page="${cur + 1}">
    Next <i class="ph ph-caret-right"></i>
  </button>`;

    html += `<span class="spager-info">Page ${cur} of ${totalPages} &nbsp;(${total} subjects)</span>`;

    pager.innerHTML = html;

    pager.querySelectorAll(".spager-btn:not([disabled])").forEach(btn => {
      btn.addEventListener("click", () => {
        subjectsCurrentPage = parseInt(btn.dataset.page, 10);
        renderManageSubjectsTable();
        subjectsWrapper.querySelector(".table-scroll-container")?.scrollTo({ top: 0, behavior: "smooth" });
      });
    });
  }

  ["manage-subjects-search", "manage-subjects-semester-filter", "manage-subjects-year-filter"].forEach(id => {
    document.getElementById(id)?.addEventListener("input", applyManageSubjectFilters);
    document.getElementById(id)?.addEventListener("change", applyManageSubjectFilters);
  });

  // ========================= SAVE SUBJECT =========================
  saveSubjectBtn?.addEventListener("click", () => {
    const code = document.getElementById("subject-code").value.trim(),
      desc = document.getElementById("subject-desc").value.trim(),
      semester = document.getElementById("subject-semester").value.trim(),
      year = document.getElementById("subject-year").value.trim();

    console.log("Saving subject:", { code, desc, semester, year, currentProgramId, isEditingSubject });

    if (!code || !desc || !semester || !year) {
      showGlobalNotification("Please fill all fields", "warning");
      return;
    }
    if (!currentProgramId) {
      showGlobalNotification("Please select program first", "warning");
      return;
    }

    const url = "subject_crud.php";

    const body = isEditingSubject
      ? `action=edit&id=${editSubjectId}&subject_code=${encodeURIComponent(code)}&subject_desc=${encodeURIComponent(desc)}&semester=${encodeURIComponent(semester)}&year_level=${encodeURIComponent(year)}`
      : `action=add&program_id=${currentProgramId}&subject_code=${encodeURIComponent(code)}&subject_desc=${encodeURIComponent(desc)}&semester=${encodeURIComponent(semester)}&year_level=${encodeURIComponent(year)}`;

    console.log("Sending to:", url);
    console.log("Body:", body);

    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body
    })
      .then(r => {
        console.log("Response status:", r.status);
        return r.json();
      })
      .then(res => {
        console.log("Response:", res);
        if (res.status === "success") {
          isEditingSubject = false;
          editSubjectId = null;
          addSubjectModal.style.display = "none";
          loadSubjects();
          showNotification("Subject saved successfully!", "#4caf50");
        } else {
          alert("Error: " + (res.message || "Unknown error"));
        }
      })
      .catch(err => {
        console.error("Error:", err);
        alert("Something went wrong: " + err.message);
      });
  });

  // ========================= LOAD SUBJECTS BY YEAR =========================
  function loadSubjectsByYear(yearLevel, callback = null) {

    const box = document.getElementById("subject-checkbox-list");
    box.innerHTML = classSubjectSearchHtml();
    getClassSubjectOptionsBox().innerHTML = "<small>Loading subjects...</small>";
    bindClassSubjectSearch();

    if (!selectedClassSemester) {
      box.innerHTML = classSubjectSearchHtml();
      getClassSubjectOptionsBox().innerHTML = "<small>Set semester on the dashboard first</small>";
      bindClassSubjectSearch();
      if (typeof callback === "function") callback([]);
      return;
    }

    console.log(`Fetching subjects for program_id: ${currentProgramId}, year_level: ${yearLevel}, semester: ${selectedClassSemester || "all"}`);

    const semesterParam = selectedClassSemester ? `&semester=${encodeURIComponent(selectedClassSemester)}` : "";
    fetch(`classes_crud.php?action=get_subjects_by_program_year&program_id=${currentProgramId}&year_level=${encodeURIComponent(yearLevel)}${semesterParam}`)
      .then(res => {
        console.log("Response status:", res.status);
        return res.json();
      })
      .then(data => {
        console.log("Received data:", data);

        box.innerHTML = "";

        if (!data || data.length === 0) {
          const semesterText = selectedClassSemester ? ` in ${selectedClassSemester}` : "";
          box.innerHTML = classSubjectSearchHtml();
          getClassSubjectOptionsBox().innerHTML = `<small>No subjects found for year ${yearLevel}${semesterText}</small>`;
          bindClassSubjectSearch();
          if (typeof callback === "function") callback([]);
          return;
        }

        // Clear existing content but keep the header
        box.innerHTML = classSubjectSearchHtml();
        bindClassSubjectSearch();
        const optionsBox = getClassSubjectOptionsBox();
        if (selectedClassSemester) {
          const note = document.createElement("small");
          note.textContent = `Showing ${selectedClassSemester} subjects only`;
          optionsBox.appendChild(note);
        }

        data.forEach(sub => {
          const label = document.createElement("label");
          label.className = "class-subject-option";
          label.dataset.search = `${sub.subject_code || ""} ${sub.subject_desc || ""} ${sub.year_level || ""}`.toLowerCase();
          label.innerHTML = `
          <input type="checkbox" value="${sub.id}">
          ${sub.subject_code} - ${sub.subject_desc}
        `;
          optionsBox.appendChild(label);
        });

        if (typeof callback === "function") callback(data);
      })
      .catch(err => {
        console.error("Error loading subjects:", err);
        box.innerHTML = classSubjectSearchHtml();
        getClassSubjectOptionsBox().innerHTML = "<small>Error loading subjects</small>";
        bindClassSubjectSearch();
        if (typeof callback === "function") callback([]);
      });
  }

  // ========================= AUTO LOAD ON YEAR CHANGE =========================
  document.getElementById("class-year")?.addEventListener("change", (e) => {
    const year = e.target.value;
    const box = document.getElementById("subject-checkbox-list");

    console.log("Year changed to:", year);
    console.log("Current program ID:", currentProgramId);

    box.innerHTML = "";

    if (!year) {
      console.log("No year selected, returning");
      setClassSubjectMessage("Select year level first to load subjects");
      return;
    }

    if (!currentProgramId) {
      console.log("No program ID set");
      setClassSubjectMessage("Please select a program first from the programs list, then click Manage");
      return;
    }

    const facultyBox = document.getElementById("faculty-list");
    if (facultyBox) {
      facultyBox.dataset.facultyData = JSON.stringify({});
      facultyBox.innerHTML = '<h4><i class="ph ph-user"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
    }

    console.log("Calling loadSubjectsByYear with year:", year);
    loadSubjectsByYear(year);
  });

  // ========================= WATCH FOR SUBJECT SELECTION CHANGES =========================
  document.addEventListener("change", (e) => {
    if (e.target.matches("#subject-checkbox-list input[type='checkbox']")) {
      const subjectId = e.target.value;
      const isChecked = e.target.checked;
      const subjectLabel = e.target.closest('label')?.textContent.trim() || `Subject ${subjectId}`;

      console.log(`Subject ${subjectId} ${isChecked ? 'checked' : 'unchecked'}`);

      // Load faculty for this specific subject
      loadFacultyBySubject(subjectId, subjectLabel, isChecked);
    }
  });

  // ========================= LOAD FACULTY BY SUBJECT =========================
  function loadFacultyBySubject(subjectId, subjectLabel, isChecked, selectedFacultyId = null) {
    const facultyBox = document.getElementById("faculty-list");

    if (!facultyBox) {
      console.error("Faculty list box not found");
      return;
    }

    // Initialize faculty list if not exists
    if (!facultyBox.dataset.facultyData) {
      facultyBox.dataset.facultyData = JSON.stringify({});
    }

    const facultyData = JSON.parse(facultyBox.dataset.facultyData);

    if (isChecked) {
      // Add faculty for this subject
      fetch(`classes_crud.php?action=get_faculty_by_subject&subject_id=${subjectId}`)
        .then(r => r.json())
        .then(faculty => {
          console.log(`Faculty for subject ${subjectId}:`, faculty);

          // Store faculty data for this subject
          facultyData[subjectId] = {
            subjectLabel,
            faculty,
            selectedFacultyId: selectedFacultyId || null
          };
          facultyBox.dataset.facultyData = JSON.stringify(facultyData);

          // Update display
          updateFacultyDisplay(facultyData);
        })
        .catch(err => {
          console.error("Error loading faculty:", err);
        });
    } else {
      // Remove faculty for this subject
      delete facultyData[subjectId];
      facultyBox.dataset.facultyData = JSON.stringify(facultyData);

      // Update display
      updateFacultyDisplay(facultyData);
    }
  }

  // ========================= UPDATE FACULTY DISPLAY =========================
  function updateFacultyDisplay(facultyData) {
    const facultyBox = document.getElementById("faculty-list");

    facultyBox.innerHTML = '<h4><i class="ph ph-user"></i> Available Faculty</h4>';

    const entries = Object.entries(facultyData);
    if (entries.length === 0) {
      facultyBox.innerHTML += '<small>Select subjects to show available faculty and assign teachers</small>';
      return;
    }

    entries.forEach(([subjectId, data]) => {
      const subjectSection = document.createElement("div");
      subjectSection.className = "faculty-subject-row";
      subjectSection.style.margin = "1px 0";
      subjectSection.style.padding = "2px 0";
      subjectSection.style.position = "relative";

      const header = document.createElement("div");
      header.style.marginBottom = "2px";
      // Extract only the program code from the subjectLabel (e.g., "ITC16" from "ITC16 - System Integration and Architecture 2")
      const programCode = data.subjectLabel.split(' - ')[0];
      header.innerHTML = `<span style="font-weight: normal; font-size: 12px;">${programCode}</span>`;
      subjectSection.appendChild(header);

      if (!data.faculty || data.faculty.length === 0) {
        const emptyNotice = document.createElement("small");
        emptyNotice.textContent = "No faculty available for this subject.";
        emptyNotice.style.display = "block";
        emptyNotice.style.color = "#d9534f";
        subjectSection.appendChild(emptyNotice);
      } else {
        const select = document.createElement("select");
        select.dataset.subjectId = subjectId;
        select.style.width = "100%";
        select.style.padding = "8px";
        select.style.border = "1px solid #ccc";
        select.style.borderRadius = "4px";
        select.innerHTML = `<option value="">Assigned faculty for this subject</option>` +
          data.faculty.map(f => `
          <option value="${f.id}" ${data.selectedFacultyId == f.id ? "selected" : ""}>
            ${f.name}
          </option>
        `).join("");

        select.addEventListener("change", () => {
          facultyData[subjectId].selectedFacultyId = select.value || null;
          facultyBox.dataset.facultyData = JSON.stringify(facultyData);
        });
        subjectSection.appendChild(select);
      }

      facultyBox.appendChild(subjectSection);
    });
  }

  // ========================= LOAD CLASSES =========================
  function loadClasses() {
    if (!currentProgramId) {
      console.log("No currentProgramId set, cannot load classes");
      return;
    }

    console.log("Loading classes for program_id:", currentProgramId);

    fetch(`classes_crud.php?action=get&program_id=${currentProgramId}`)
      .then(r => {
        console.log("Response status:", r.status);
        console.log("Response headers:", r.headers);
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }
        return r.json();
      })
      .then(data => {
        console.log("Classes data received:", data);

        if (!classesTableBody) {
          console.error("classesTableBody not found");
          return;
        }

        classesTableBody.innerHTML = "";

        if (!data || data.length === 0) {
          classesTableBody.innerHTML = "<tr><td colspan='5'>No classes found for this program</td></tr>";
          return;
        }

        data.forEach(c => {
          const row = document.createElement("tr");
          row.dataset.id = c.id;
          row.dataset.searchKey = `${(c.block || "").toLowerCase()} ${(c.year_level || "").toLowerCase()}`;

          row.innerHTML = `
          <td>${c.block}</td>
          <td>${c.year_level}</td>
          <td><span class="status-badge active">Loading...</span></td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="view-subjects-btn" title="View Subjects"><i class="ph ph-eye"></i></button>
              <button class="edit-btn" title="Edit Class"><i class="ph ph-pencil-simple"></i></button>
              <button class="delete-btn" title="Delete Class"><i class="ph ph-trash"></i></button>
            </div>
          </td>
        `;

          // Fetch subject count for each class
          fetch(`classes_crud.php?action=get&class_id=${c.id}`)
            .then(r => r.json())
            .then(subjects => {
              const subjectCount = Array.isArray(subjects) ? subjects.length : 0;
              const statusCell = row.querySelector('.status-badge');
              if (statusCell) {
                statusCell.textContent = `${subjectCount} Subject${subjectCount !== 1 ? 's' : ''} Assigned`;
              }
            })
            .catch(err => {
              console.error("Error fetching subject count:", err);
              const statusCell = row.querySelector('.status-badge');
              if (statusCell) {
                statusCell.textContent = '0 Subjects Assigned';
              }
            });

          // VIEW CLASS SUBJECTS
          row.querySelector(".view-subjects-btn").addEventListener("click", () => {
            showClassSubjectsModal(c.id, c.block, c.year_level);
          });

          // EDIT CLASS
          row.querySelector(".edit-btn").addEventListener("click", () => {
            const addClassModal = document.getElementById("addClassModal");
            addClassModal.style.display = "flex";
            addClassModal.style.zIndex = "10001";
            addClassModal.style.position = "fixed";
            addClassModal.dataset.isEditing = "true";
            addClassModal.dataset.editId = c.id;
            addClassModal.dataset.existingAssignments = "{}";
            addClassModal.dataset.existingSubjectIds = "[]";

            document.getElementById("class-year").value = c.year_level;
            document.getElementById("class-block").value = c.block || "";
            setClassSubjectMessage("Loading subjects...");

            const facultyBox = document.getElementById("faculty-list");
            if (facultyBox) {
              facultyBox.dataset.facultyData = JSON.stringify({});
              facultyBox.innerHTML = '<h4><i class="ph ph-user"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
            }

            loadSubjectsByYear(c.year_level, () => {
              fetch(`classes_crud.php?action=get&class_id=${c.id}`)
                .then(res => res.json())
                .then(subjects => {
                  if (!Array.isArray(subjects)) return;
                  const existingAssignments = {};
                  const existingSubjectIds = [];
                  subjects.forEach(subject => {
                    const sid = String(subject.subject_id);
                    existingSubjectIds.push(sid);
                    existingAssignments[sid] = Number(subject.faculty_id) || 0;
                    const checkbox = document.querySelector(`#subject-checkbox-list input[type='checkbox'][value='${subject.subject_id}']`);
                    if (checkbox) {
                      checkbox.checked = true;
                      const labelText = `${subject.subject_code} - ${subject.subject_desc}`;
                      loadFacultyBySubject(subject.subject_id, labelText, true, subject.faculty_id);
                    }
                  });
                  addClassModal.dataset.existingAssignments = JSON.stringify(existingAssignments);
                  addClassModal.dataset.existingSubjectIds = JSON.stringify(existingSubjectIds);
                })
                .catch(err => {
                  console.error("Error loading class edit subjects:", err);
                });
            });
          });

          row.querySelector(".delete-btn").addEventListener("click", () => {
            openDeleteModal("class", c.section_name, row);
          });

          classesTableBody.appendChild(row);
        });
        applyManageSearchFilter();
      })
      .catch(err => {
        console.error("Error loading classes:", err);
        if (classesTableBody) {
          classesTableBody.innerHTML = `<tr><td colspan='5'>Error loading classes: ${err.message}</td></tr>`;
        }
      });
  }

  // ========================= Manage Search (Subjects / Classes) =========================
  const manageSearchInput = document.getElementById("manage-search");
  function applyManageSearchFilter() {
    const term = (manageSearchInput?.value || "").toLowerCase().trim();
    const subjectsVisible = document.getElementById("subjects")?.style.display !== "none";
    const targetTbody = subjectsVisible ? subjectsTableBody : classesTableBody;
    if (!targetTbody) return;
    if (subjectsVisible && (
      document.getElementById("manage-subjects-search") ||
      document.getElementById("manage-subjects-semester-filter") ||
      document.getElementById("manage-subjects-year-filter")
    )) {
      applyManageSubjectFilters();
      return;
    }
    [...targetTbody.rows].forEach((row) => {
      if (!term) {
        row.style.display = "";
        return;
      }
      if (!subjectsVisible) {
        const key = (row.dataset.searchKey || "").toLowerCase();
        row.style.display = key.includes(term) ? "" : "none";
        return;
      }
      row.style.display = row.textContent.toLowerCase().includes(term) ? "" : "none";
    });
  }

  manageSearchInput?.addEventListener("input", (e) => {
    applyManageSearchFilter();
  });

  // ========================= CLOSE CLASS MODAL =========================
  document.getElementById("addClassModal")?.querySelector(".close-btn")?.addEventListener("click", () => {
    document.getElementById("addClassModal").style.display = "none";
  });

  // ========================= SAVE CLASS =========================
  saveClassBtn?.addEventListener("click", () => {
    const year = document.getElementById("class-year").value.trim();
    const block = document.getElementById("class-block").value.trim();
    const addClassModalEl = document.getElementById("addClassModal");

    if (!year || !block) {
      showGlobalNotification("Please fill all fields", "warning");
      return;
    }
    if (!currentProgramId) {
      showGlobalNotification("Please select program first", "warning");
      return;
    }

    const isEditing = addClassModalEl.dataset.isEditing === "true";
    const editId = addClassModalEl.dataset.editId;
    const existingAssignments = JSON.parse(addClassModalEl.dataset.existingAssignments || "{}");
    const existingSubjectIds = JSON.parse(addClassModalEl.dataset.existingSubjectIds || "[]");
    const checked = [...document.querySelectorAll("#subject-checkbox-list input:checked")]
      .map(cb => String(cb.value));

    // Preserve original subjects if edit screen still loading and user did not uncheck anything
    if (isEditing && checked.length === 0 && existingSubjectIds.length > 0) {
      checked.push(...existingSubjectIds);
    }
    if (checked.length === 0) {
      showGlobalNotification("Please select at least one subject", "warning");
      return;
    }

    const assignments = {};
    checked.forEach(subjectId => {
      const select = document.querySelector(`#faculty-list select[data-subject-id="${subjectId}"]`);
      if (select && select.value) {
        assignments[subjectId] = select.value;
      } else if (isEditing && Object.prototype.hasOwnProperty.call(existingAssignments, subjectId)) {
        assignments[subjectId] = existingAssignments[subjectId];
      } else {
        assignments[subjectId] = 0; // No faculty assigned yet
      }
    });

    const action = isEditing ? "edit" : "add";
    let body = `action=${action}&program_id=${currentProgramId}&year_level=${encodeURIComponent(year)}&block=${encodeURIComponent(block)}`;
    body += `&subjects=${encodeURIComponent(JSON.stringify(checked))}`;
    body += `&faculty_assignments=${encodeURIComponent(JSON.stringify(assignments))}`;

    if (isEditing) {
      body += `&id=${encodeURIComponent(editId)}`;
    }

    console.log("Saving class - Action:", action, "ID:", editId);
    console.log("Checked subjects:", checked);
    console.log("Faculty assignments:", assignments);
    console.log("Full body being sent:", body);

    fetch("classes_crud.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body
    })
      .then(r => r.json())
      .then(res => {
        console.log("Class save response:", res);
        if (res.status === "success" || res === "success") {
          addClassModalEl.style.display = "none";
          loadClasses();
          showNotification(
            isEditing ? "Class updated successfully!" : "Class added successfully!",
            "#4caf50"
          );

          // Clear editing state
          delete addClassModalEl.dataset.editId;
          delete addClassModalEl.dataset.isEditing;
          delete addClassModalEl.dataset.existingAssignments;
          delete addClassModalEl.dataset.existingSubjectIds;
        } else {
          const msg = (res.message || "").toLowerCase();
          if (msg.includes("already exist in this section") || msg.includes("already exists in this section")) {
            showNotification("The faculty or subject already exist in this section", "#f44336", 3500);
          } else {
            showNotification("Error " + (isEditing ? "updating" : "adding") + " class: " + (res.message || "Unknown error"), "#f44336", 5000);
          }
        }
      })
      .catch(err => {
        console.error("Error saving class:", err);
        alert("Something went wrong while " + (isEditing ? "updating" : "adding") + " class.");
      });
  });

  // ========================= SHOW CLASS SUBJECTS MODAL =========================
  function showClassSubjectsModal(classId, sectionName, yearLevel) {
    const modal = document.getElementById("viewClassSubjectsModal");
    const modalTitle = modal.querySelector("h3");
    const className = modal.querySelector("#viewClassName");
    const subjectsList = document.getElementById("viewClassSubjectsList");

    modalTitle.innerHTML = `Class Subjects`;
    className.textContent = `Loading...`;
    subjectsList.innerHTML = "<div class='loading'>Loading subjects...</div>";
    modal.style.display = "flex";

    // Fetch program information to get program code
    fetch(`getProgram.php`)
      .then(r => r.json())
      .then(programs => {
        const currentProgram = programs.find(p => p.id == currentProgramId);
        const programCode = currentProgram ? currentProgram.program_code : 'PROGRAM';
        const yearDigit = yearLevel.match(/\d+/);
        className.textContent = `${programCode}- ${yearDigit ? yearDigit[0] : ''}${sectionName}`;
      })
      .catch(err => {
        console.error("Error fetching program:", err);
        const yearDigit = yearLevel.match(/\d+/);
        className.textContent = `${yearDigit ? yearDigit[0] : ''}${sectionName}`;
      });

    // Fetch subjects for this class
    fetch(`classes_crud.php?action=get&class_id=${classId}`)
      .then(r => r.json())
      .then(data => {
        console.log("Class subjects data:", data);

        if (!data || data.length === 0) {
          subjectsList.innerHTML = "<div class='no-subjects'>No subjects assigned to this class</div>";
          return;
        }

        let html = "<div class='subjects-grid'>";
        data.forEach(subject => {
          html += `
          <div class='subject-card' data-subject-id='${subject.subject_id}' data-class-id='${classId}'>
            <div class='subject-header'>
              <strong>${subject.subject_code}</strong>
              <span class='year-badge'>${subject.year_level || ''}</span>
            </div>
            <p class='subject-desc'>${subject.subject_desc || ''}</p>
            <div style='margin:8px 0 0; font-size:0.95rem; color:#4b5563;'>
              <i class='ph ph-user' style='margin-right:5px; color:#1565c0; font-size:1rem; vertical-align:middle;'></i>
              Instructor: ${subject.faculty_name || 'Unassigned'}
            </div>
          </div>
        `;
        });
        html += "</div>";

        subjectsList.innerHTML = html;
      })
      .catch(err => {
        console.error("Error fetching class subjects:", err);
        subjectsList.innerHTML = "<div class='error'>Error loading subjects</div>";
      });
  }

  // ========================= DELETE SUBJECT FROM CLASS MODAL =========================
  function openDeleteSubjectFromClassModal(classId, subjectId, subjectCode, subjectCard) {
    console.log("openDeleteSubjectFromClassModal called with:", { classId, subjectId, subjectCode });

    const modal = document.getElementById("deleteModal");
    const deleteMessage = document.getElementById("deleteMessage");
    const deleteWarning = document.getElementById("deleteWarning");

    deleteMessage.innerHTML = `Do you want to remove <strong>${subjectCode}</strong> from this class?`;
    deleteWarning.textContent = "This subject will be removed from class but will not be deleted from the system.";

    // Store data for deletion
    deleteTarget = {
      classId: classId,
      subjectId: subjectId,
      subjectCard: subjectCard,
      type: "class_subject"
    };
    deleteType = "class_subject";

    console.log("Set deleteType to:", deleteType);
    console.log("Set deleteTarget to:", deleteTarget);

    modal.style.display = "flex";
    modal.style.zIndex = "10001";
    modal.style.position = "fixed";
  }

  // ========================= EDIT SUBJECT IN CLASS MODAL =========================
  function openEditSubjectInClassModal(classId, subjectId, currentFacultyId, subjectCode, subjectCard) {
    // For now, we'll show a simple faculty assignment modal
    // This could be expanded to a full modal later
    const modal = document.getElementById("editSubjectInClassModal");
    if (!modal) {
      // Create modal if it doesn't exist
      createEditSubjectInClassModal();
      return openEditSubjectInClassModal(classId, subjectId, currentFacultyId, subjectCode, subjectCard);
    }

    const modalTitle = modal.querySelector("h3");
    const subjectName = modal.querySelector("#editSubjectName");
    const facultySelect = modal.querySelector("#editFacultySelect");

    modalTitle.textContent = "Edit Subject Assignment";
    subjectName.textContent = subjectCode;

    // Load available faculty for this subject
    facultySelect.innerHTML = '<option value="">Loading faculty...</option>';
    fetch(`classes_crud.php?action=get_faculty_by_subject&subject_id=${subjectId}`)
      .then(r => r.json())
      .then(faculty => {
        facultySelect.innerHTML = '<option value="0">Unassigned</option>';
        faculty.forEach(f => {
          const option = document.createElement('option');
          option.value = f.id;
          option.textContent = f.name;
          if (f.id == currentFacultyId) {
            option.selected = true;
          }
          facultySelect.appendChild(option);
        });
      })
      .catch(err => {
        console.error("Error loading faculty:", err);
        facultySelect.innerHTML = '<option value="">Error loading faculty</option>';
      });

    // Store data for saving
    modal.dataset.classId = classId;
    modal.dataset.subjectId = subjectId;
    modal.dataset.subjectCard = JSON.stringify(subjectCard.outerHTML);

    modal.style.display = "flex";
    modal.style.zIndex = "10001";
    modal.style.position = "fixed";
  }

  function createEditSubjectInClassModal() {
    const modalHTML = `
    <div id="editSubjectInClassModal" class="modal" style="display:none;">
      <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
          <h3>Edit Subject Assignment</h3>
          <span class="close-btn">&times;</span>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <label>Subject</label>
            <div id="editSubjectName" style="padding: 8px; background: #f3f4f6; border-radius: 4px; font-weight: bold;"></div>
          </div>
          <div class="form-row">
            <label for="editFacultySelect">Assign Faculty</label>
            <select id="editFacultySelect">
              <option value="">Loading...</option>
            </select>
          </div>
          <button id="saveSubjectAssignmentBtn" class="submit-btn">Save Assignment</button>
        </div>
      </div>
    </div>
  `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Add event listeners
    const modal = document.getElementById("editSubjectInClassModal");
    modal.querySelector(".close-btn").addEventListener("click", () => {
      modal.style.display = "none";
    });

    modal.querySelector("#saveSubjectAssignmentBtn").addEventListener("click", saveSubjectAssignment);
  }

  function saveSubjectAssignment() {
    const modal = document.getElementById("editSubjectInClassModal");
    const classId = modal.dataset.classId;
    const subjectId = modal.dataset.subjectId;
    const facultyId = document.getElementById("editFacultySelect").value;

    // First remove the existing assignment, then add new one
    fetch(`classes_crud.php?action=delete_subject`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `class_id=${classId}&subject_id=${subjectId}`
    })
      .then(r => r.json())
      .then(deleteRes => {
        if (deleteRes.status === "success") {
          // Add the subject back with new faculty assignment
          fetch(`classes_crud.php?action=add_subject_to_class`, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `class_id=${classId}&subject_id=${subjectId}&faculty_id=${facultyId}`
          })
            .then(r => r.json())
            .then(addRes => {
              if (addRes.status === "success") {
                modal.style.display = "none";
                showNotification("Faculty assignment updated successfully!", "#4caf50");
                // Refresh class subjects - get current class info from modal
                const classModal = document.getElementById("viewClassSubjectsModal");
                const className = classModal.querySelector("#viewClassName").textContent;
                showClassSubjectsModal(classId, className, ""); // This will refresh the modal
              } else {
                const assignMsg = (addRes.message || "").toLowerCase();
                if (assignMsg.includes("already exist in this section") || assignMsg.includes("already exists in this section")) {
                  showNotification("The faculty or subject already exist in this section", "#f44336", 3500);
                } else {
                  showNotification("Error updating assignment: " + (addRes.message || "Unknown error"), "#f44336");
                }
              }
            })
            .catch(err => {
              console.error("Error adding subject:", err);
              showNotification("Error updating assignment", "#f44336");
            });
        } else {
          showNotification("Error updating assignment: " + (deleteRes.message || "Unknown error"), "#f44336");
        }
      })
      .catch(err => {
        console.error("Error deleting subject:", err);
        showNotification("Error updating assignment", "#f44336");
      });
  }

  // ========================= Forms ==========================================================================================
  const openModal = (m, h, b) => {
    m.querySelector("h3").innerText = h;
    m.querySelector(".submit-btn").innerText = b;
    m.style.display = "flex";
    const fid = m.querySelector("#faculty-number"), fem = m.querySelector("#faculty-email"),
      sid = m.querySelector("#student-number"), sem = m.querySelector("#student-email");
    if (fid && fem) { fid.disabled = fem.disabled = h.includes("EDIT FACULTY"); }
    if (sid && sem) { sid.disabled = sem.disabled = h.includes("EDIT STUDENT"); }
  };
  const closeModal = m => m.style.display = "none";

  function showYearSectionRow(row) {
    if (!row) return;

    row.style.setProperty('display', 'grid', 'important');
    row.style.setProperty('grid-template-columns', 'minmax(0, 1fr) minmax(0, 1fr)', 'important');
    row.style.setProperty('gap', 'clamp(8px, 3vw, 20px)', 'important');
    row.style.setProperty('align-items', 'start', 'important');
    row.style.setProperty('width', '100%', 'important');
    row.style.setProperty('min-width', '0', 'important');

    Array.from(row.children).forEach(field => {
      field.style.setProperty('min-width', '0', 'important');
      field.style.setProperty('width', '100%', 'important');
      field.style.setProperty('margin', '0', 'important');
    });
  }

  // ================= Faculty ==========================================================================================
  const facultyTbody = document.querySelector("#faculties-section tbody");
  const archivedFacultyTbody = document.querySelector(".archived-faculties-table tbody");
  const facultyStatsContainer = document.querySelector("#faculties-section .faculty-stats-container");
  const facultyTableWrapper = document.querySelector("#faculties-section .table-wrapper");
  const facultyToolbar = document.querySelector("#faculties-section .faculty-toolbar");
  const archivedFacultySection = document.querySelector("#faculties-section .archived-faculty-section");

  function normalizeStatusValue(status) {
    return String(status || "active").trim().toLowerCase();
  }

  function statusLabel(status) {
    const normalized = normalizeStatusValue(status);
    if (normalized === "on leave") return "On Leave";
    if (normalized === "archived") return "Archived";
    return normalized === "inactive" ? "Inactive" : "Active";
  }

  function statusPillHtml(status) {
    const normalized = normalizeStatusValue(status);
    const cssStatus = normalized.replace(/\s+/g, "-");
    return `<span class="inline-status ${cssStatus}"><span class="status-dot"></span><span>${statusLabel(normalized)}</span></span>`;
  }

  function statusMenuHtml(type, currentStatus) {
    const options = type === "faculty" ? ["active", "inactive", "on leave"] : ["active", "inactive"];
    const current = normalizeStatusValue(currentStatus);
    return `
    <div class="status-action-menu">
      <button class="settings-btn" title="Change Status"><i class="ph ph-gear-six"></i></button>
      <div class="status-dropdown-menu">
        ${options.map(status => `
          <button type="button" class="status-option ${current === status ? "selected" : ""}" data-status="${status}">
            ${statusLabel(status)}
          </button>
        `).join("")}
      </div>
    </div>
  `;
  }

  function restoreStatusDropdown(menu) {
    const dropdown = menu.querySelector(".status-dropdown-menu") || document.body.querySelector(`.status-dropdown-menu.floating[data-owner-menu="${menu.dataset.statusMenuId}"]`);
    if (!dropdown) return;

    dropdown.classList.remove("floating");
    dropdown.style.left = "";
    dropdown.style.top = "";
    dropdown.style.width = "";
    dropdown.style.visibility = "";
    dropdown.removeAttribute("data-owner-menu");

    if (dropdown.parentElement !== menu) {
      menu.appendChild(dropdown);
    }
  }

  function closeStatusMenus(exceptMenu = null) {
    document.querySelectorAll(".status-action-menu.open").forEach(menu => {
      if (menu !== exceptMenu) {
        restoreStatusDropdown(menu);
        menu.classList.remove("open");
      }
    });
  }

  function positionStatusMenu(statusMenu) {
    const dropdown = statusMenu.querySelector(".status-dropdown-menu");
    const trigger = statusMenu.querySelector(".settings-btn");
    if (!dropdown || !trigger) return;

    if (!statusMenu.dataset.statusMenuId) {
      statusMenu.dataset.statusMenuId = `status-menu-${Date.now()}-${Math.random().toString(36).slice(2)}`;
    }

    dropdown.classList.add("floating");
    dropdown.dataset.ownerMenu = statusMenu.dataset.statusMenuId;
    dropdown.style.left = "0px";
    dropdown.style.top = "0px";
    dropdown.style.width = "";
    dropdown.style.visibility = "hidden";
    document.body.appendChild(dropdown);

    const triggerRect = trigger.getBoundingClientRect();
    const menuRect = dropdown.getBoundingClientRect();
    const gutter = 8;
    const left = Math.min(
      Math.max(gutter, triggerRect.right - menuRect.width),
      window.innerWidth - menuRect.width - gutter
    );
    let top = triggerRect.bottom + 6;

    if (top + menuRect.height > window.innerHeight - gutter) {
      top = Math.max(gutter, triggerRect.top - menuRect.height - 6);
    }

    dropdown.style.left = `${left}px`;
    dropdown.style.top = `${top}px`;
    dropdown.style.visibility = "";
  }

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".status-action-menu") && !e.target.closest(".status-dropdown-menu")) {
      closeStatusMenus();
    }
  });

  window.addEventListener("scroll", () => closeStatusMenus(), true);
  window.addEventListener("resize", () => closeStatusMenus());

  function showFacultyListView() {
    if (facultyStatsContainer) {
      facultyStatsContainer.hidden = false;
      facultyStatsContainer.style.display = "";
    }
    if (facultyToolbar) {
      facultyToolbar.hidden = false;
      facultyToolbar.style.display = "";
    }
    if (facultyTableWrapper) {
      facultyTableWrapper.hidden = false;
      facultyTableWrapper.style.display = "";
    }
    if (archivedFacultySection) {
      archivedFacultySection.hidden = true;
      archivedFacultySection.style.display = "none";
      // Clear archived search on close
      const archivedSearch = document.getElementById("archived-faculty-search");
      if (archivedSearch) {
        archivedSearch.value = "";
        if (archivedFacultyTbody) {
          [...archivedFacultyTbody.rows].forEach(row => row.style.display = "");
        }
      }
    }
  }

  function showArchivedFacultyView() {
    if (facultyStatsContainer) {
      facultyStatsContainer.hidden = true;
      facultyStatsContainer.style.display = "none";
    }
    if (facultyToolbar) {
      facultyToolbar.hidden = true;
      facultyToolbar.style.display = "none";
    }
    if (facultyTableWrapper) {
      facultyTableWrapper.hidden = true;
      facultyTableWrapper.style.display = "none";
    }
    if (archivedFacultySection) {
      archivedFacultySection.hidden = false;
      archivedFacultySection.style.display = "";

      // Wire up archived search (only once)
      const archivedSearch = document.getElementById("archived-faculty-search");
      if (archivedSearch && !archivedSearch._searchBound) {
        archivedSearch._searchBound = true;
        const clearBtn = document.getElementById("archived-faculty-clear-btn");

        function runArchivedFacultyFilter() {
          const term = archivedSearch.value.toLowerCase().trim();
          if (!archivedFacultyTbody) return;
          [...archivedFacultyTbody.rows].forEach(row => {
            const idText = (row.querySelector("td:nth-child(2)")?.textContent || "").toLowerCase();
            const nameText = (row.querySelector("td:nth-child(3)")?.textContent || "").toLowerCase();
            row.style.display = (!term || idText.includes(term) || nameText.includes(term)) ? "" : "none";
          });
        }

        archivedSearch.addEventListener("input", runArchivedFacultyFilter);

        if (clearBtn) {
          clearBtn.addEventListener("click", () => {
            archivedSearch.value = "";
            runArchivedFacultyFilter();
            archivedSearch.focus();
          });
        }
      }
    }
  }

  function refreshSectionStats() {
    fetch("get_section_stats.php?cb=" + Date.now(), { cache: "no-store" })
      .then(r => r.json())
      .then(res => {
        if (!res.success || !res.data) return;
        const faculty = res.data.faculty || {};
        const students = res.data.students || {};

        const setText = (selector, value) => {
          const el = document.querySelector(selector);
          if (el) el.textContent = value ?? 0;
        };

        setText(".faculty-stat-card.total-faculty .stat-number", faculty.total_faculty);
        setText(".faculty-stat-card.active-faculty .stat-number", faculty.active_faculty);
        setText(".faculty-stat-card.pending-evaluation .stat-number", faculty.pending_evaluation);
        setText(".student-stat-card.total-students .stat-number", students.total_students);
        setText(".student-stat-card.active-students .stat-number", students.active_students);
        setText(".student-stat-card.regular-students .stat-number", students.regular_students);
        setText(".student-stat-card.irregular-students .stat-number", students.irregular_students);
      })
      .catch(err => console.error("Error refreshing section stats:", err));
  }

  async function updateStatus(type, id, status) {
    const url = type === "faculty" ? "faculty_crud.php" : "student_crud.php";
    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "update_status", id, status })
    });
    return response.json();
  }

  // ------------------- Load Faculty -------------------
  function loadFaculty() {
    console.log("Loading faculty data...");
    fetch("getFaculty.php?t=" + Date.now())
      .then(r => r.json())
      .then(data => {
        console.log("Raw faculty data received:", data);
        console.log("Data type:", typeof data);
        console.log("Data length:", data ? data.length : "null/undefined");

        // Check if there's an error in the response
        if (data && data.error) {
          console.error("Server returned error:", data.error);
          return;
        }
        facultyTbody.innerHTML = "";
        if (archivedFacultyTbody) archivedFacultyTbody.innerHTML = "";

        // Check if no data or empty array
        if (!data || data.length === 0) {
          facultyTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">No faculty found</td></tr>';
          if (archivedFacultyTbody) {
            archivedFacultyTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">No archived faculty found</td></tr>';
          }
          return;
        }

        data.forEach(f => {
          console.log("Processing faculty:", f);
          console.log("Photo data:", f.photo);
          console.log("Subjects data:", f.subjects);
          const row = document.createElement("tr");
          row.id = "faculty-" + f.faculty_id;
          Object.assign(row.dataset, f);
          row.dataset.faculty_id = f.faculty_id;
          row.dataset.searchKey = `${(f.faculty_id || "").toLowerCase()} ${(f.firstname || "").toLowerCase()} ${(f.lastname || "").toLowerCase()} ${(f.suffix || "").toLowerCase()}`;
          row.dataset.status = normalizeStatusValue(f.status);

          // Display subject codes or show "No subjects yet"
          console.log("Faculty subjects data:", f.subjects);
          console.log("Faculty subject_codes:", f.subject_codes);

          const subjectsDisplay = f.subject_codes && f.subject_codes.length > 0
            ? `<div class="faculty-subject-grid">${f.subject_codes.map((code) =>
              `<span class="subject-item">${code}</span>`
            ).join('')}</div>`
            : '<span class="no-subjects">No subjects yet</span>';

          console.log("Subjects display:", subjectsDisplay);

          // Build initials avatar fallback
          const initials = ((f.firstname || "").charAt(0) + (f.lastname || "").charAt(0)).toUpperCase() || "?";
          const avatarHtml = f.photo
            ? `<img src="${f.photo}" class="table-avatar" alt="${f.firstname} ${f.lastname}">`
            : `<div class="table-avatar placeholder faculty-initials-avatar">${initials}</div>`;

          if (normalizeStatusValue(f.status) === "archived") {
            row.innerHTML = `
            <td>${avatarHtml}</td>
            <td><strong>${f.faculty_id}</strong></td>
            <td>
              <div>
                <div style="font-weight: 600; color: var(--primary-900);">${f.firstname} ${f.lastname} ${f.suffix || ""}</div>
                <small style="color: var(--neutral-500); font-weight: 500;">${f.email}</small>
              </div>
            </td>
            <td>
              <span class="subject-count-badge">${f.subjects ? f.subjects.length : 0}</span>
            </td>
            <td>${statusPillHtml(f.status)}</td>`;

            if (archivedFacultyTbody) archivedFacultyTbody.appendChild(row);
            return;
          }

          row.innerHTML = `
          <td>${avatarHtml}</td>
          <td><strong>${f.faculty_id}</strong></td>
          <td>
            <div>
              <div style="font-weight: 600; color: var(--primary-900);">${f.firstname} ${f.lastname} ${f.suffix || ""}</div>
              <small style="color: var(--neutral-500); font-weight: 500;">${f.email}</small>
            </div>
          </td>
          <td>
            <span class="subject-count-badge">${f.subjects ? f.subjects.length : 0}</span>
          </td>
          <td>${statusPillHtml(f.status)}</td>
          <td class="action-cell"><div class="action-buttons">
            <button class="view-btn"><i class="ph ph-eye"></i></button>
            <button class="edit-btn"><i class="ph ph-pencil-simple"></i></button>
            ${statusMenuHtml("faculty", f.status)}
            <button class="archive-btn" title="Archive Faculty">Archived</button>
          </div></td>`;

          facultyTbody.appendChild(row);

          // EDIT ----------------------
          row.querySelector(".edit-btn").onclick = () => {
            const facultyIdInput = document.getElementById("faculty-number");
            facultyIdInput.value = f.faculty_id || "";
            facultyIdInput.disabled = true;

            const emailInput = document.getElementById("faculty-email");
            emailInput.value = f.email || "";
            emailInput.disabled = false;

            document.getElementById("faculty-firstname").value = f.firstname || "";
            document.getElementById("faculty-lastname").value = f.lastname || "";
            document.getElementById("faculty-suffix").value = f.suffix || "";

            const preview = document.getElementById("faculty-photo-preview");
            preview.src = f.photo || "";
            preview.hidden = !f.photo;

            // Load subjects and check faculty's existing subjects
            loadAllFacultySubjects().then(() => {
              if (f.subjects && f.subjects.length > 0) {
                const assignedSubjectIds = f.subjects.map(subject => String(subject.subject_id || subject.id || ""));
                const checkboxes = document.querySelectorAll('#faculty-subjects-list input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                  checkbox.checked = assignedSubjectIds.includes(String(checkbox.value));
                });
              }
            });

            addFacultyModal.dataset.editRow = f.id;
            openModal(addFacultyModal, "EDIT FACULTY", "UPDATE FACULTY");
          };

          // VIEW ----------------------
          row.querySelector(".view-btn").onclick = () => {
            // Set faculty name in modal
            document.getElementById("viewFacultyName").textContent = `${f.firstname} ${f.lastname} ${f.suffix || ""}`;

            // Show faculty subjects in modal
            const subjectsList = document.getElementById("viewFacultySubjectsList");
            subjectsList.innerHTML = renderGroupedFacultySubjects(f.subjects || []);

            // Show modal
            viewFacultySubjectsModal.style.display = "flex";
            viewFacultySubjectsModal.style.zIndex = "10001";
            viewFacultySubjectsModal.style.position = "fixed";
          };

          const statusMenu = row.querySelector(".status-action-menu");
          statusMenu.querySelector(".settings-btn").addEventListener("click", (e) => {
            e.stopPropagation();
            const willOpen = !statusMenu.classList.contains("open");
            closeStatusMenus(statusMenu);
            statusMenu.classList.toggle("open", willOpen);
            if (willOpen) positionStatusMenu(statusMenu);
            else restoreStatusDropdown(statusMenu);
          });
          statusMenu.querySelectorAll(".status-option").forEach(option => {
            option.addEventListener("click", async (e) => {
              e.stopPropagation();
              const newStatus = option.dataset.status;
              try {
                const result = await updateStatus("faculty", f.id, newStatus);
                if (result.success) {
                  row.dataset.status = newStatus;
                  row.querySelector("td:nth-child(5)").innerHTML = statusPillHtml(newStatus);
                  (option.closest(".status-dropdown-menu") || statusMenu).querySelectorAll(".status-option").forEach(btn => {
                    btn.classList.toggle("selected", btn.dataset.status === newStatus);
                  });
                  restoreStatusDropdown(statusMenu);
                  statusMenu.classList.remove("open");
                  closeStatusMenus();
                  showNotification(`Faculty status updated to ${statusLabel(newStatus)}`, "#4caf50");
                  refreshSectionStats();
                  filterFaculty();
                } else {
                  showNotification("Failed to update status: " + (result.message || "Unknown error"), "#f44336");
                }
              } catch (error) {
                console.error("Status update error:", error);
                showNotification("Error updating status", "#f44336");
              }
            });
          });

          row.querySelector(".archive-btn").addEventListener("click", async () => {
            const currentStatus = normalizeStatusValue(row.dataset.status);
            if (currentStatus === "archived") {
              showNotification("Faculty is already archived", "#64748b");
              return;
            }

            openArchiveModal(row, f, statusMenu);
          });
        });
      })
      .then(() => {
        if (facultyTbody && facultyTbody.rows.length === 0) {
          facultyTbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;">No faculty found</td></tr>';
        }
        if (archivedFacultyTbody && archivedFacultyTbody.rows.length === 0) {
          archivedFacultyTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">No archived faculty found</td></tr>';
        }
      })
      .then(() => refreshSectionStats())
      .catch(err => console.error("Load error:", err));
  }

  // ADD BUTTON -------------------
  document.querySelector(".add-faculty-btn").onclick = () => {
    const facultyIdInput = document.getElementById("faculty-number");
    facultyIdInput.value = "";
    facultyIdInput.readOnly = false;

    // Enable email field for adding - multiple approaches
    const emailInput = document.getElementById("faculty-email");
    emailInput.disabled = false;
    emailInput.readOnly = false;
    emailInput.removeAttribute('disabled');
    emailInput.removeAttribute('readonly');
    console.log("Email field enabled for add. Disabled state:", emailInput.disabled);

    addFacultyModal.querySelectorAll("input").forEach(i => {
      if (i.id !== "faculty-number") i.value = "";
    });

    document.getElementById("faculty-subjects-list").innerHTML = '<p style="color: #6b7280; font-size: 0.9em;">No subjects available</p>';

    loadAllFacultySubjects();

    document.getElementById("faculty-photo-preview").hidden = true;
    delete addFacultyModal.dataset.editRow;
    openModal(addFacultyModal, "ADD FACULTY", "SAVE FACULTY");

    setTimeout(() => {
      const emailInput = document.getElementById("faculty-email");
      emailInput.disabled = false;
      emailInput.readOnly = false;
      emailInput.style.pointerEvents = 'auto';
      emailInput.style.userSelect = 'auto';
      emailInput.style.opacity = '1';
      emailInput.setAttribute('contenteditable', 'true');
      emailInput.focus();
      console.log("Email field re-enabled after modal open. Disabled state:", emailInput.disabled);

      emailInput.addEventListener('input', function (e) {
        e.target.disabled = false;
        e.target.readOnly = false;
      });

      Object.defineProperty(emailInput, 'disabled', {
        get: function () { return false; },
        set: function (value) { return false; }
      });
    }, 100);
  };

  // SUBMIT (ADD/EDIT) ----------
  addFacultyModal.querySelector(".submit-btn").onclick = async () => {
    const n = document.getElementById("faculty-number").value.trim(),
      e = document.getElementById("faculty-email").value.trim(),
      f = document.getElementById("faculty-firstname").value.trim(),
      l = document.getElementById("faculty-lastname").value.trim(),
      s = document.getElementById("faculty-suffix").value.trim(),
      photo = document.getElementById("faculty-photo-preview").hidden ? "" : document.getElementById("faculty-photo-preview").src;

    if (!n || !e || !f || !l) return showNotification("Required fields missing!", "#f44336");

    // Get selected subjects
    const selectedSubjects = [];
    document.querySelectorAll('#faculty-subjects-list input[type="checkbox"]:checked').forEach(checkbox => {
      selectedSubjects.push(parseInt(checkbox.value));
    });

    const action = addFacultyModal.dataset.editRow ? "edit" : "add";
    const facultyData = {
      action: action,
      faculty_id: n,
      email: e,
      firstname: f,
      lastname: l,
      suffix: s,
      photo: photo,
      subjects: selectedSubjects
    };

    // Add ID for edit
    if (action === "edit") {
      facultyData.id = addFacultyModal.dataset.editRow;
    }

    try {
      // Add timeout to prevent hanging
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

      const response = await fetch("faculty_crud.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(facultyData),
        signal: controller.signal
      });

      clearTimeout(timeoutId);
      const result = await response.json();
      console.log("Faculty creation response:", result);

      if (result.success) {
        console.log("Faculty added successfully, reloading faculty list...");
        loadFaculty();
        closeModal(addFacultyModal);
        showNotification(result.message, "#10b981");

        // Force a refresh after a short delay to ensure photo data is loaded
        setTimeout(() => {
          console.log("Force refreshing faculty list...");
          loadFaculty();
        }, 500);
      } else {
        showNotification(result.message, "#f44336");
      }
    } catch (err) {
      console.error("Save error:", err);
      showNotification("Network error. Please try again.", "#f44336");
    }
  };

  // PHOTO PREVIEW ----------------
  document.getElementById("faculty-photo").onchange = e => {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = ev => {
        const p = document.getElementById("faculty-photo-preview");
        p.src = ev.target.result;
        p.hidden = false;
      };
      reader.readAsDataURL(file);
    }
  };

  // SEARCH AND STATUS FILTER -----------------------
  function filterFaculty() {
    const searchTerm = document.getElementById("faculty-search").value.toLowerCase();
    const statusFilter = document.getElementById("faculty-status-filter").value;

    [...facultyTbody.rows].forEach(r => {
      const idCell = r.querySelector("td:nth-child(2)");
      const nameCell = r.querySelector("td:nth-child(3)");

      let matchesSearch = true;
      let matchesStatus = true;

      // Check search term against ID and name only
      if (searchTerm) {
        const idText = idCell ? idCell.textContent.toLowerCase() : "";
        const nameText = nameCell ? nameCell.textContent.toLowerCase() : "";
        matchesSearch = idText.includes(searchTerm) || nameText.includes(searchTerm);
      }

      const statusText = normalizeStatusValue(r.dataset.status);

      // Check status filter against the row status value. Archived faculty stay out of the normal list.
      if (statusFilter && statusFilter !== "") {
        matchesStatus = statusText === statusFilter.toLowerCase();
      } else {
        matchesStatus = statusText !== "archived";
      }

      // Show row only if both conditions are met
      r.style.display = (matchesSearch && matchesStatus) ? "" : "none";
    });
  }

  document.getElementById("faculty-search").oninput = filterFaculty;
  document.getElementById("faculty-status-filter").onchange = filterFaculty;

  const showArchivedFacultyBtn = document.querySelector(".show-archived-faculty-btn");
  if (showArchivedFacultyBtn) {
    showArchivedFacultyBtn.addEventListener("click", () => {
      showArchivedFacultyView();
    });
  }

  const backToFacultyBtn = document.querySelector(".back-to-faculty-btn");
  if (backToFacultyBtn) {
    backToFacultyBtn.addEventListener("click", () => {
      showFacultyListView();
    });
  }

  const showArchivedStudentBtn = document.querySelector(".show-archived-student-btn");
  if (showArchivedStudentBtn) {
    showArchivedStudentBtn.addEventListener("click", () => {
      showArchivedStudentView();
    });
  }

  const backToStudentBtn = document.querySelector(".back-to-student-btn");
  if (backToStudentBtn) {
    backToStudentBtn.addEventListener("click", () => {
      showStudentListView();
    });
  }
  // ========================= Report Section =========================
  let reportPeriodFiltersLoaded = false;

  function getReportPeriodParams() {
    const ay = document.getElementById("report-ay-filter")?.value || "";
    const semester = document.getElementById("report-semester-filter")?.value || "";
    const params = new URLSearchParams();
    if (ay) params.set("academic_year", ay);
    if (semester) params.set("semester", semester);
    return params;
  }

  async function loadReportPeriodFilters() {
    if (reportPeriodFiltersLoaded) return;
    const aySelect = document.getElementById("report-ay-filter");
    if (!aySelect) return;

    try {
      const res = await fetch("periods_api.php?action=list", { cache: "no-store", credentials: "same-origin" });
      const data = await res.json();
      const periods = (data && data.success && Array.isArray(data.periods)) ? data.periods : [];
      const years = [...new Set(periods.map(period => period.ay).filter(Boolean))].sort().reverse();
      years.forEach(year => {
        const option = document.createElement("option");
        option.value = year;
        option.textContent = year;
        aySelect.appendChild(option);
      });
      reportPeriodFiltersLoaded = true;
    } catch (err) {
      console.error("Failed to load report period filters:", err);
    }
  }

  function loadEvaluations() {
    console.log('Loading evaluations...');
    const reportParams = getReportPeriodParams();
    const query = reportParams.toString();
    fetch(`get_evaluations.php${query ? `?${query}` : ""}`)
      .then(r => r.json())
      .then(data => {
        console.log('Evaluations data received:', data);
        const tbody = document.getElementById("evaluationTableBody");
        tbody.innerHTML = "";

        if (data.success) {
          if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No evaluation data available</td></tr>';
            return;
          }

          data.data.forEach(evaluation => {
            const pct = parseFloat(evaluation.percentage_score || 0);
            const pctText = `${pct.toFixed(1)}%`;

            const row = document.createElement("tr");
            row.innerHTML = `
            <td>
              <strong>${evaluation.name}</strong>
              ${evaluation.subject_label ? `<small style="display:block;color:#64748b;margin-top:4px;">${evaluation.subject_label}${evaluation.class_label ? ` (${evaluation.class_label})` : ''}</small>` : ''}
            </td>
            <td>${escapeHtml(evaluation.period_label || 'Unassigned Period')}</td>
            <td>
              <div class="score-cell">
                <span class="score-pct">${pctText}</span>
                <div class="score-mini-track">
                  <div class="score-mini-fill ${evaluation.rating_class}" style="width:${Math.min(pct, 100)}%"></div>
                </div>
                <span class="score-raw">${Number(evaluation.average_score).toFixed(2)} / 5.00</span>
              </div>
            </td>
            <td>${evaluation.total_responses}</td>
            <td>
              <span class="badge ${evaluation.rating_class}">${evaluation.rating}</span>
            </td>
            <td>
              <button class="view-btn" data-faculty-id="${evaluation.id}" data-subject-id="${evaluation.subject_id || 0}" data-class-id="${evaluation.class_id || 0}" data-period-id="${evaluation.period_id || 0}">
                <i class="ph ph-eye"></i> View
              </button>
            </td>
          `;

            // Add event listener to view button
            const viewBtn = row.querySelector('.view-btn');
            viewBtn.addEventListener('click', () => {
              viewEvaluationDetails(evaluation.id, evaluation.subject_id || 0, evaluation.class_id || 0, evaluation.period_id || 0);
            });

            tbody.appendChild(row);
          });

          // Add search functionality
          document.getElementById("searchInput").oninput = e => {
            const term = e.target.value.toLowerCase();
            [...tbody.rows].forEach(r =>
              r.style.display = r.textContent.toLowerCase().includes(term) ? "" : "none"
            );
          };
        } else {
          console.error("Error loading evaluations:", data.message);
          tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: red;">Error loading evaluation data</td></tr>';
        }
      })
      .catch(err => {
        console.error("Network error:", err);
        const tbody = document.getElementById("evaluationTableBody");
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: red;">Network error loading data</td></tr>';
      });
  }

  function buildEvaluationReportPdfUrl(downloadMode = false) {
    const searchInput = document.getElementById("searchInput");
    const search = encodeURIComponent((searchInput?.value || "").trim());
    const mode = downloadMode ? "download" : "view";
    const reportParams = getReportPeriodParams();
    reportParams.set("mode", mode);
    reportParams.set("search", search);
    reportParams.set("t", Date.now());
    return `generate_evaluation_summary_pdf.php?${reportParams.toString()}`;
  }

  function generateEvaluationReport() {
    const url = buildEvaluationReportPdfUrl(true);
    fetch(url, { cache: "no-store" })
      .then(async (res) => {
        if (!res.ok) {
          throw new Error(`HTTP ${res.status}`);
        }
        const contentType = res.headers.get("content-type") || "";
        if (!contentType.toLowerCase().includes("application/pdf")) {
          const text = await res.text();
          throw new Error(text || "Server did not return a PDF file.");
        }
        return res.blob();
      })
      .then((blob) => {
        const filename = `Faculty_Performance_Summary_Report_${new Date().toISOString().slice(0, 10)}.pdf`;
        const downloadUrl = URL.createObjectURL(blob);

        // Download only (no auto-open)
        const link = document.createElement("a");
        link.href = downloadUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(() => URL.revokeObjectURL(downloadUrl), 60000);
      })
      .catch((err) => {
        console.error("Generate report error:", err);
        showNotification("Failed to generate PDF report: " + err.message, "#f44336", 5000);
      });
  }

  function viewEvaluationReportPDF() {
    const url = buildEvaluationReportPdfUrl(false);
    window.open(url, "_blank");
  }

  function downloadEvaluationReportPDF() {
    const url = buildEvaluationReportPdfUrl(true);
    const link = document.createElement("a");
    link.href = url;
    link.download = "";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }

  document.getElementById("report-ay-filter")?.addEventListener("change", loadEvaluations);
  document.getElementById("report-semester-filter")?.addEventListener("change", loadEvaluations);

  // Ensure admin Generate Report is callable from inline HTML and click bindings
  window.generateEvaluationReport = generateEvaluationReport;
  document.addEventListener("DOMContentLoaded", function () {
    const reportBtn = document.querySelector(".generate-report-btn");
    if (reportBtn) {
      reportBtn.onclick = function (e) {
        e.preventDefault();
        generateEvaluationReport();
      };
    }
  });

  function generateReportContent(rows) {
    let content = "Faculty Name,Faculty ID,Overall Rating,Responses,Status\n";

    Array.from(rows).forEach(row => {
      const cells = row.getElementsByTagName("td");
      if (cells.length >= 5) {
        const facultyName = cells[0].textContent.replace(/\s+/g, ' ').trim();
        const facultyId = cells[0].querySelector('small')?.textContent || '';
        const overallRating = cells[1].textContent;
        const responses = cells[2].textContent;
        const status = cells[3].querySelector('.badge')?.textContent || '';

        content += `"${facultyName}","${facultyId}","${overallRating}","${responses}","${status}"\n`;
      }
    });

    return content;
  }

  function downloadReport(content, filename) {
    const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.display = 'none';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    URL.revokeObjectURL(url);
    showNotification("Report generated successfully!", "#4caf50", 3000);
  }

  // Global variable to store current faculty data for PDF
  let currentFacultyData = null;

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function getSubjectYearOrder(yearLevel) {
    const value = String(yearLevel || "").trim().toLowerCase();
    if (value.startsWith("1")) return 1;
    if (value.startsWith("2")) return 2;
    if (value.startsWith("3")) return 3;
    if (value.startsWith("4")) return 4;
    return 99;
  }

  function formatSubjectYearLevel(yearLevel) {
    const order = getSubjectYearOrder(yearLevel);
    if (order === 1) return "1st Year";
    if (order === 2) return "2nd Year";
    if (order === 3) return "3rd Year";
    if (order === 4) return "4th Year";
    return yearLevel ? String(yearLevel) : "No year level";
  }

  function renderGroupedFacultySubjects(subjects) {
    if (!Array.isArray(subjects) || subjects.length === 0) {
      return '<p style="text-align: center; color: #6b7280; padding: 40px;">No subjects assigned yet</p>';
    }

    const grouped = subjects.reduce((groups, subject) => {
      const programCode = String(subject.program_code || "N/A").trim() || "N/A";
      const programName = String(subject.program_name || "Unassigned Program").trim() || "Unassigned Program";
      const key = `${programCode}__${programName}`;

      if (!groups[key]) {
        groups[key] = { programCode, programName, subjects: [] };
      }

      groups[key].subjects.push(subject);
      return groups;
    }, {});

    return Object.values(grouped)
      .sort((a, b) => a.programCode.localeCompare(b.programCode) || a.programName.localeCompare(b.programName))
      .map(group => {
        const cards = group.subjects
          .sort((a, b) => {
            const yearDiff = getSubjectYearOrder(a.year_level) - getSubjectYearOrder(b.year_level);
            if (yearDiff !== 0) return yearDiff;
            return String(a.subject_code || "").localeCompare(String(b.subject_code || ""));
          })
          .map(subject => `
          <div class="subject-card">
            <div class="subject-header">
              <strong>${escapeHtml(subject.subject_code || "")}</strong>
              <span class="year-badge">${escapeHtml(formatSubjectYearLevel(subject.year_level))}</span>
            </div>
            <p class="subject-desc">${escapeHtml(subject.subject_desc || "")}</p>
          </div>
        `).join("");

        return `
        <div class="faculty-program-group">
          <div class="faculty-program-header">
            <span class="faculty-program-code">${escapeHtml(group.programCode)}</span>
            <span class="faculty-program-name">${escapeHtml(group.programName)}</span>
          </div>
          <div class="subjects-grid">${cards}</div>
        </div>
      `;
      }).join("");
  }

  function formatFeedbackForHtml(feedback) {
    const text = String(feedback || "No feedback available").trim() || "No feedback available";
    return escapeHtml(text).replace(/\n/g, "<br>");
  }

  function renderFeedbackComments(reportData) {
    const feedbackEl = document.getElementById("reportFeedbackText");
    const feedbackCountEl = document.getElementById("reportFeedbackCount");
    if (!feedbackEl) return;

    const comments = Array.isArray(reportData.feedback_comments)
      ? reportData.feedback_comments.map(item => String(item || "").trim()).filter(Boolean)
      : String(reportData.all_feedback || reportData.feedback || "")
        .split(/\n\s*\n/)
        .map(item => item.trim())
        .filter(Boolean);

    const hasComments = comments.length > 0 && !(comments.length === 1 && comments[0] === "No feedback available");
    if (feedbackCountEl) {
      feedbackCountEl.textContent = `${hasComments ? comments.length : 0} ${hasComments && comments.length === 1 ? "comment" : "comments"}`;
    }

    if (!hasComments) {
      feedbackEl.innerHTML = '<div class="feedback-loading">No feedback given by students yet.</div>';
      return;
    }

    feedbackEl.innerHTML = comments.map(comment => `
    <div class="feedback-comment">${formatFeedbackForHtml(comment)}</div>
  `).join("");
  }

  function renderEvaluationDetailsTable(reportData) {
    const tbody = document.getElementById("reportEvaluationDetailsBody");
    const categoryCountEl = document.getElementById("reportCategoryCount");
    const categoryListEl = document.getElementById("reportCategoryList");
    if (!tbody) return;

    const details = Array.isArray(reportData.evaluation_details)
      ? reportData.evaluation_details
      : Array.isArray(reportData.category_totals)
        ? reportData.category_totals
        : [];

    // Check if weights are present
    const hasWeights = details.some(d => parseFloat(d.normalised_weight || d.weight || 0) > 0);

    if (categoryCountEl) {
      categoryCountEl.textContent = `${details.length} ${details.length === 1 ? "category" : "categories"}`;
    }
    if (categoryListEl) {
      categoryListEl.innerHTML = details.length
        ? details.map((detail) => {
          const categoryName = detail.category || detail.category_name || "Uncategorized";
          const rawScore = parseFloat(detail.average_score ?? detail.overall_rating ?? detail.score ?? 0);
          const scoreValue = Number.isFinite(rawScore) ? rawScore.toFixed(2) : String(detail.average_score ?? detail.overall_rating ?? "0.00");
          const scorePercent = Number.isFinite(rawScore) ? Math.min(100, Math.max(0, rawScore * 20)) : 0;
          const percentText = `${scorePercent.toFixed(0)}%`;
          const ratingText = detail.rating || detail.status || "N/A";
          const ratingClass = String((detail.rating_class || ratingText || "poor")).toLowerCase().replace(/\s+/g, "-");
          const normW = parseFloat(detail.normalised_weight || detail.weight || 0);
          const weightTag = hasWeights ? `<span class="category-weight-tag" title="Category weight">${normW.toFixed(1)}%</span>` : '';
          return `
            <div class="category-pill ${escapeHtml(ratingClass)}">
              <div class="category-pill-name">${escapeHtml(categoryName)} ${weightTag}</div>
              <div class="category-pill-meter" aria-label="${escapeHtml(categoryName)} ${percentText}">
                <div class="category-pill-fill" style="width: ${scorePercent}%;"></div>
              </div>
              <div class="category-pill-percent">${percentText}</div>
              <div class="category-pill-rating">${escapeHtml(scoreValue)} / 5.00</div>
            </div>
          `;
        }).join("")
        : '<span class="category-list-empty">No categories available</span>';
    }

    if (details.length === 0) {
      tbody.innerHTML = `
      <tr>
        <td colspan="${hasWeights ? 5 : 4}" class="empty-report-cell">No categories available</td>
      </tr>
    `;
      return;
    }

    // Update thead if weight column needed
    const thead = tbody.closest('table')?.querySelector('thead tr');
    if (thead && hasWeights && !thead.querySelector('.weight-th')) {
      const weightTh = document.createElement('th');
      weightTh.className = 'weight-th';
      weightTh.textContent = 'Weight';
      thead.insertBefore(weightTh, thead.children[1]); // after Category name
    }

    const rowsHtml = details.map((detail) => {
      const categoryName = detail.category || detail.category_name || 'Uncategorized';
      const rawScore = parseFloat(detail.average_score ?? detail.overall_rating ?? detail.score ?? 0);
      const scoreValue = Number.isFinite(rawScore) ? rawScore.toFixed(2) : String(detail.average_score ?? detail.overall_rating ?? '0.00');
      const scorePercent = Number.isFinite(rawScore) ? Math.min(100, Math.max(0, rawScore * 20)) : 0;
      const ratingText = detail.rating || detail.status || 'N/A';
      const ratingClass = String((detail.rating_class || ratingText || 'poor')).toLowerCase().replace(/\s+/g, '-');
      const responses = Number.parseInt(detail.responses ?? detail.total_responses ?? 0, 10) || 0;
      const normW = parseFloat(detail.normalised_weight || detail.weight || 0);
      const weightCell = hasWeights
        ? `<td class="category-weight-cell"><span class="category-weight-tag">${normW.toFixed(1)}%</span></td>`
        : '';

      return `
      <tr>
        <td class="category-name-cell">${escapeHtml(categoryName)}</td>
        ${weightCell}
        <td class="category-rating-cell">
          <div class="category-score-cell">
            <span class="score-text">${escapeHtml(scoreValue)} / 5.00</span>
            <div class="score-bar"><div class="score-fill" style="width: ${scorePercent}%;"></div></div>
          </div>
        </td>
        <td class="category-response-cell">${escapeHtml(responses)}</td>
        <td class="category-status-cell"><span class="report-badge ${escapeHtml(ratingClass)}">${escapeHtml(ratingText)}</span></td>
      </tr>
    `;
    }).join("");

    tbody.innerHTML = rowsHtml;
  }

  // Add event listener for PDF button when DOM is loaded
  document.addEventListener('DOMContentLoaded', function () {
    // Add click event listener to PDF button
    const pdfButton = document.querySelector('.download-pdf-btn');
    if (pdfButton) {
      console.log('PDF button found, adding click listener');
      pdfButton.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('PDF button clicked!');
        downloadFacultyReportPDF();
      });
    } else {
      console.log('PDF button not found on page load');
    }
  });

  function viewEvaluationDetails(facultyId, subjectId = 0, classId = 0, periodId = 0) {
    console.log('View button clicked for faculty ID:', facultyId);

    const modal = document.getElementById('facultyReportModal');
    const detailsBody = document.getElementById("reportEvaluationDetailsBody");
    const feedbackEl = document.getElementById("reportFeedbackText");
    const statusElement = document.getElementById('reportOverallStatus');
    const overallPercentElement = document.getElementById('reportOverallPercentage');
    const overallProgressFill = document.getElementById('reportOverallProgressFill');
    const categoryCountEl = document.getElementById("reportCategoryCount");
    const feedbackCountEl = document.getElementById("reportFeedbackCount");
    const categoryListEl = document.getElementById("reportCategoryList");

    if (detailsBody) {
      detailsBody.innerHTML = '<tr><td colspan="4" class="empty-report-cell">Loading categories...</td></tr>';
    }
    if (feedbackEl) {
      feedbackEl.innerHTML = '<div class="feedback-loading">Loading feedback...</div>';
    }
    if (statusElement) {
      statusElement.textContent = '-';
    }
    if (overallPercentElement) overallPercentElement.textContent = '0%';
    if (overallProgressFill) overallProgressFill.style.width = '0%';
    if (categoryCountEl) categoryCountEl.textContent = '0 categories';
    if (feedbackCountEl) feedbackCountEl.textContent = '0 comments';
    if (categoryListEl) categoryListEl.innerHTML = '<span class="category-list-empty">Loading categories...</span>';

    const reportParams = new URLSearchParams({
      faculty_id: facultyId,
      subject_id: subjectId || 0,
      class_id: classId || 0,
      period_id: periodId || 0
    });

    fetch(`get_faculty_report.php?${reportParams.toString()}`)
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          // Store faculty data globally for PDF use
          currentFacultyData = data.data;

          // Populate modal with faculty data
          const nameElement = document.getElementById('reportFacultyName');
          const idElement = document.getElementById('reportFacultyId');
          const ratingElement = document.getElementById('reportOverallRating');
          const overallPercentElement = document.getElementById('reportOverallPercentage');
          const overallProgressFill = document.getElementById('reportOverallProgressFill');
          const responsesElement = document.getElementById('reportTotalResponses');
          const periodElement = document.getElementById('reportEvaluationPeriod');
          const overallScore = parseFloat(data.data.overall_rating || 0);
          const pctScore = data.data.percentage_score
            ? parseFloat(data.data.percentage_score)
            : Math.min(100, Math.max(0, overallScore * 20));
          const overallPercent = pctScore;

          if (nameElement) nameElement.textContent = data.data.name;
          if (idElement) {
            const subjectSuffix = data.data.subject_label
              ? ` | ${data.data.subject_label}${data.data.class_label ? ` (${data.data.class_label})` : ''}`
              : '';
            idElement.textContent = `ID: ${data.data.faculty_id || data.data.id || '-'}${subjectSuffix}`;
          }
          if (ratingElement) ratingElement.textContent = `${data.data.overall_rating || '0.00'}`;
          if (overallPercentElement) overallPercentElement.textContent = `${pctScore.toFixed(1)}%`;
          if (overallProgressFill) overallProgressFill.style.width = `${Math.min(pctScore, 100)}%`;
          if (responsesElement) responsesElement.textContent = data.data.total_responses || 0;
          if (periodElement) periodElement.textContent = data.data.evaluation_period || 'All evaluation periods';
          if (statusElement) {
            const statusText = data.data.overall_status || data.data.rating || 'No Responses';
            const statusClass = String(data.data.overall_status_class || statusText).toLowerCase().replace(/\s+/g, '-');
            statusElement.innerHTML = `<span class="report-badge ${escapeHtml(statusClass)}">${escapeHtml(statusText)}</span>`;
          }

          renderEvaluationDetailsTable(data.data);
          renderFeedbackComments(data.data);

          // Show modal
          console.log('Modal element found:', modal);
          if (modal) {
            modal.style.display = 'flex';
            modal.style.zIndex = '10001';
            modal.style.position = 'fixed';
            console.log('Modal should be visible now');

            // Add click event listener to PDF button after modal is shown
            setTimeout(() => {
              const pdfButton = modal.querySelector('.download-pdf-btn');
              if (pdfButton) {
                console.log('PDF button found in modal, adding click listener');
                // Remove existing listeners to avoid duplicates
                pdfButton.replaceWith(pdfButton.cloneNode(true));
                const newPdfButton = modal.querySelector('.download-pdf-btn');
                newPdfButton.addEventListener('click', function (e) {
                  e.preventDefault();
                  e.stopPropagation();
                  console.log('PDF button clicked from modal!');
                  downloadFacultyReportPDF();
                });
                console.log('PDF button listener added');
              } else {
                console.log('PDF button not found in modal');
              }
            }, 100);
          } else {
            console.error('Modal element not found!');
          }
        } else {
          showNotification('Error loading faculty report: ' + data.message, '#f44336', 5000);
        }
      })
      .catch(err => {
        console.error('Error fetching faculty report:', err);
        console.error('Error details:', err.message);
        showNotification('Network error while loading faculty report: ' + err.message, '#f44336', 5000);
      });
  }

  function closeFacultyReportModal() {
    const modal = document.getElementById('facultyReportModal');
    modal.style.display = 'none';
  }

  function printReport() {
    window.print();
  }

  // Test function to verify modal works
  function testModal() {
    console.log('Testing modal display...');
    const modal = document.getElementById('facultyReportModal');
    if (modal) {
      modal.style.display = 'flex';
      console.log('Modal should be visible now');
    } else {
      console.error('Modal not found!');
    }
  }

  function downloadFacultyReportPDF() {
    // Check if we have faculty data available
    if (!currentFacultyData) {
      showNotification('No faculty data available. Please try again.', '#f44336', 3000);
      return;
    }

    // Create the PDF content using stored faculty data (matching instructor format)
    const pdfContent = {
      facultyName: currentFacultyData.name,
      facultyId: currentFacultyData.id, // Use database ID, not faculty_id
      subjectLabel: currentFacultyData.subject_label || '',
      classLabel: currentFacultyData.class_label || '',
      evaluationPeriod: currentFacultyData.evaluation_period || '',
      overallRating: currentFacultyData.overall_rating,
      totalResponses: currentFacultyData.total_responses,
      evaluationDetails: currentFacultyData.evaluation_details || [],
      feedback: '',
      allFeedback: currentFacultyData.all_feedback || 'No feedback available'
    };

    // Send to server for PDF generation
    console.log('Sending PDF request with data:', pdfContent);

    fetch('generate_evaluation_pdf.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(pdfContent)
    })
      .then(response => {
        console.log('PDF response status:', response.status);
        console.log('PDF response headers:', response.headers);

        if (!response.ok) {
          return response.text().then(text => {
            console.error('PDF generation error:', text);
            throw new Error(text);
          });
        }
        return response.blob();
      })
      .then(blob => {
        console.log('PDF blob received, size:', blob.size);
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `Individual_Performance_Report_${currentFacultyData.name.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.pdf`;
        document.body.appendChild(a);
        a.click();

        // Clean up immediately
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        showNotification('PDF downloaded successfully!', '#4caf50', 3000);
      })
      .catch(err => {
        console.error('Error generating PDF:', err);
        showNotification('Error generating PDF: ' + err.message, '#f44336', 5000);
      });
  }

  // ========================= Dashboard Statistics ======================================================================================================================================================
  window.topPerformanceState = { allRatings: [], sortedRatings: [], modalOpen: false };

  function loadDashboardStats() {
    console.log("Loading dashboard stats...");
    fetch("get_dashboard_stats.php?cb=" + Date.now(), { cache: "no-store" })
      .then(r => {
        console.log("Response status:", r.status);
        if (!r.ok) {
          throw new Error(`HTTP error! status: ${r.status}`);
        }
        return r.json();
      })
      .then(data => {
        console.log("Dashboard data received:", data);
        if (data.success) {
          console.log("totalStudents element:", document.getElementById("totalStudents"));
          // Update dashboard cards
          const totalFacultyEl = document.getElementById("totalFaculty");
          const totalStudentsEl = document.getElementById("totalStudents");
          const totalEvaluationsEl = document.getElementById("totalEvaluations");

          console.log("Elements found:", { totalFacultyEl, totalStudentsEl, totalEvaluationsEl });

          if (totalFacultyEl) totalFacultyEl.textContent = data.data.totalFaculty;
          if (totalStudentsEl) totalStudentsEl.textContent = data.data.totalStudents;
          if (totalEvaluationsEl) totalEvaluationsEl.textContent = data.data.activeStudents;

          // Update evaluation progress bar: stay at 0 until there are submissions
          const progressFillEl = document.getElementById("evaluationProgressFill");
          const progressTextEl = document.getElementById("evaluationProgressText");
          if (progressFillEl && progressTextEl) {
            const submittedEvaluations = Number(data.data.totalEvaluationsSubmitted) || 0;
            const totalActiveStudents = Number(data.data.activeStudents) || 0;

            if (submittedEvaluations <= 0 || totalActiveStudents <= 0) {
              progressFillEl.style.width = "0%";
              progressTextEl.textContent = "No submissions yet";
            } else {
              const progressPercent = Math.min(
                100,
                Math.round((submittedEvaluations / totalActiveStudents) * 100)
              );
              progressFillEl.style.width = `${progressPercent}%`;
              progressTextEl.textContent = `${submittedEvaluations} submitted`;
            }
          }

          // Update overall faculty rating
          const overallRatingEl = document.getElementById("overallRating");
          if (overallRatingEl && data?.data && data.data.overallRating !== undefined && data.data.overallRating !== null) {
            const oldValue = overallRatingEl.textContent;
            const numericRating = Number(data.data.overallRating);
            overallRatingEl.textContent = Number.isFinite(numericRating) ? numericRating.toFixed(2) : String(data.data.overallRating);
            console.log("Updated overallRating from", oldValue, "to:", data.data.overallRating);
            // Add visual feedback for rating change
            overallRatingEl.style.transition = "color 0.5s, transform 0.3s";
            overallRatingEl.style.color = "#f59e0b";
            overallRatingEl.style.transform = "scale(1.1)";
            setTimeout(() => {
              overallRatingEl.style.color = "";
              overallRatingEl.style.transform = "";
            }, 800);
          }

          // Update faculty ratings table
          const ratingsBody = document.getElementById("ratings-body");
          if (ratingsBody) {
            ratingsBody.innerHTML = "";
            data.data.ratings.forEach(rating => {
              const row = document.createElement("tr");
              row.innerHTML = `
              <td>${rating.name}</td>
              <td>${formatRatingPercentage(rating.rating)}</td>
            `;
              ratingsBody.appendChild(row);
            });
          }

          // Update faculty ranking table
          const rankingBody = document.getElementById("ranking-body");
          if (rankingBody) {
            rankingBody.innerHTML = "";
            data.data.ratings.forEach((rating, index) => {
              const row = document.createElement("tr");
              row.innerHTML = `
              <td>${index + 1}</td>
              <td>${rating.name}</td>
              <td>${formatRatingPercentage(rating.rating)}</td>
            `;
              rankingBody.appendChild(row);
            });
          }

          // Update Top 5 Faculty Performance bar chart
          window.topPerformanceState.allRatings = data.data.ratings || [];
          window.topPerformanceState.sortedRatings = [...window.topPerformanceState.allRatings].sort((a, b) => Number(b.rating) - Number(a.rating));
          updateTopPerformanceChart();

          // If detailed modal is open, refresh it to reflect latest ranks
          if (window.topPerformanceState.modalOpen) {
            populateTopPerformanceModal();
          }

          // Update department graph (if canvas exists)
          updateDepartmentGraph(data.data.departments);

        } else {
          console.error("Error loading dashboard stats:", data.message);
        }
      })
      .catch(err => console.error("Network error:", err));
  }

  function updateDepartmentGraph(departments) {
    const canvas = document.getElementById("departmentGraph");
    if (!canvas) return;

    const ctx = canvas.getContext("2d");

    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    if (departments.length === 0) return;

    // Simple bar chart
    const maxValue = Math.max(...departments.map(d => d.count));
    const barWidth = canvas.width / (departments.length * 1.5);
    const barSpacing = barWidth * 0.5;
    const chartHeight = canvas.height - 40;

    departments.forEach((dept, index) => {
      const barHeight = (dept.count / maxValue) * chartHeight;
      const x = index * (barWidth + barSpacing) + barSpacing;
      const y = canvas.height - barHeight - 20;

      // Draw bar
      ctx.fillStyle = "#3b82f6";
      ctx.fillRect(x, y, barWidth, barHeight);

      // Draw label
      ctx.fillStyle = "#374151";
      ctx.font = "12px Arial";
      ctx.textAlign = "center";
      ctx.fillText(dept.name, x + barWidth / 2, canvas.height - 5);

      // Draw count
      ctx.fillText(dept.count, x + barWidth / 2, y - 5);
    });
  }

  function getRatingPercentageValue(rating) {
    const numericRating = Number(rating);
    if (!Number.isFinite(numericRating)) return 0;
    return Math.max(0, Math.min(100, (numericRating / 5) * 100));
  }

  function formatRatingPercentage(rating) {
    const percentage = getRatingPercentageValue(rating);
    return `${percentage.toFixed(0)}%`;
  }

  function getFacultyInitial(name) {
    const cleanName = String(name || "").trim();
    const match = cleanName.match(/[A-Za-z0-9]/);
    return match ? match[0].toUpperCase() : "?";
  }

  function updateTopPerformanceChart() {
    const progressList = document.getElementById("topPerformanceChart");
    if (!progressList) return;
    const wrapper = progressList.closest(".top-performance-chart-wrapper");

    const sorted = window.topPerformanceState.sortedRatings || [];
    const chartSource = sorted.slice(0, 5);

    if (window.topPerformanceChartInstance) {
      window.topPerformanceChartInstance.destroy();
      window.topPerformanceChartInstance = null;
    }

    if (wrapper && !wrapper.querySelector("#topPerformanceChart")) {
      wrapper.innerHTML = '<div id="topPerformanceChart" class="top-performance-progress-list" role="button" tabindex="0" aria-label="View all faculty performance rankings"></div>';
    }

    const currentProgressList = document.getElementById("topPerformanceChart");
    if (!currentProgressList) return;

    currentProgressList.title = "Click here to view all ranked faculties.";
    currentProgressList.onclick = showAllFacultyPerformance;
    currentProgressList.onkeydown = function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        showAllFacultyPerformance();
      }
    };

    if (chartSource.length === 0) {
      currentProgressList.innerHTML = '<div class="top-performance-empty">No faculty ratings available.</div>';
      return;
    }

    currentProgressList.innerHTML = chartSource.map((item, index) => {
      const pct = item.percentage !== undefined
        ? parseFloat(item.percentage)
        : getRatingPercentageValue(item.rating);
      const displayPct = `${pct.toFixed(1)}%`;
      const facultyName = item.name || "Unknown Faculty";
      const progressColorClass = index % 2 === 0 ? "is-blue" : "is-green";
      return `
      <div class="top-performance-progress-item">
        <div class="top-performance-rank">${index + 1}.</div>
        <div class="top-performance-avatar" aria-hidden="true">${escapeHtml(getFacultyInitial(facultyName))}</div>
        <div class="top-performance-main">
          <div class="top-performance-progress-meta">
            <span class="top-performance-name">${escapeHtml(facultyName)}</span>
            <span class="top-performance-percent"><i class="ph ph-star"></i>${displayPct}</span>
          </div>
          <div class="top-performance-progress-track" aria-label="${escapeHtml(facultyName)} performance ${displayPct}">
            <div class="top-performance-progress-fill ${progressColorClass}" style="width: ${Math.min(pct, 100)}%;"></div>
          </div>
        </div>
      </div>
    `;
    }).join("");
  }

  function showAllFacultyPerformance() {
    window.topPerformanceState.modalOpen = true;
    const modal = document.getElementById("topPerformanceModal");
    if (!modal) return;
    populateTopPerformanceModal();
    modal.classList.add("show");
  }

  function populateTopPerformanceModal() {
    const tableBody = document.getElementById("top-performance-modal-body");
    const summaryText = document.getElementById("top-performance-modal-summary");
    if (!tableBody || !summaryText) return;

    const sorted = window.topPerformanceState.sortedRatings || [];
    tableBody.innerHTML = "";

    if (sorted.length === 0) {
      const emptyRow = document.createElement("tr");
      emptyRow.innerHTML = `<td colspan="3" style="text-align:center;padding:18px;color:#64748b;">No faculty ratings available.</td>`;
      tableBody.appendChild(emptyRow);
      summaryText.textContent = "No ranked faculty data available.";
      return;
    }

    summaryText.textContent = `Showing ${sorted.length} ranked faculty members in descending order.`;

    sorted.forEach((item, index) => {
      const pct = item.percentage !== undefined
        ? parseFloat(item.percentage)
        : getRatingPercentageValue(item.rating);
      const row = document.createElement("tr");
      row.innerHTML = `
      <td>${index + 1}</td>
      <td>${item.name}</td>
      <td>${pct.toFixed(1)}%</td>
    `;
      tableBody.appendChild(row);
    });
  }

  function closeTopPerformanceModal() {
    window.topPerformanceState.modalOpen = false;
    const modal = document.getElementById("topPerformanceModal");
    if (modal) modal.classList.remove("show");
  }

  // Initialize modal close button and background click
  function initializeTopPerformanceModal() {
    const modal = document.getElementById("topPerformanceModal");
    if (!modal) return;

    // Close button click
    const closeBtn = modal.querySelector(".modal-close-btn");
    if (closeBtn) {
      closeBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        closeTopPerformanceModal();
      });
    }

    // Close when clicking modal background (outside content)
    modal.addEventListener("click", function (e) {
      if (e.target === modal) {
        closeTopPerformanceModal();
      }
    });
  }

  loadFaculty();
  loadDashboardStats();
  // Only load evaluations if report section is initially visible
  const currentSection = document.querySelector('.section:not([style*="display: none"])');
  if (currentSection && currentSection.id === 'report-section') {
    loadEvaluations();
  }
  // Ensure dashboard stats are loaded after a short delay
  setTimeout(() => {
    loadDashboardStats();
  }, 1000);

  // ========================= Students  ==========================================================================================
  const studentTbody = document.querySelector("#students-section table tbody"),
    programSelect = document.getElementById("student-program");
  const archivedStudentTbody = document.querySelector(".archived-students-table tbody");
  const studentStatsContainer = document.querySelector("#students-section .student-stats-container");
  const studentTableWrapper = document.querySelector("#students-section .table-wrapper");
  const studentToolbarBox = document.querySelector("#students-section .student-toolbar-box");
  const archivedStudentSection = document.querySelector("#students-section .archived-student-section");

  function showStudentListView() {
    if (studentStatsContainer) {
      studentStatsContainer.hidden = false;
      studentStatsContainer.style.display = "";
    }
    if (studentToolbarBox) {
      studentToolbarBox.hidden = false;
      studentToolbarBox.style.display = "";
    }
    if (studentTableWrapper) {
      studentTableWrapper.hidden = false;
      studentTableWrapper.style.display = "";
    }
    if (archivedStudentSection) {
      archivedStudentSection.hidden = true;
      archivedStudentSection.style.display = "none";
      // Clear archived search on close
      const archivedSearch = document.getElementById("archived-student-search");
      if (archivedSearch) {
        archivedSearch.value = "";
        if (archivedStudentTbody) {
          [...archivedStudentTbody.rows].forEach(row => row.style.display = "");
        }
      }
    }
  }

  function showArchivedStudentView() {
    if (studentStatsContainer) {
      studentStatsContainer.hidden = true;
      studentStatsContainer.style.display = "none";
    }
    if (studentToolbarBox) {
      studentToolbarBox.hidden = true;
      studentToolbarBox.style.display = "none";
    }
    if (studentTableWrapper) {
      studentTableWrapper.hidden = true;
      studentTableWrapper.style.display = "none";
    }
    if (archivedStudentSection) {
      archivedStudentSection.hidden = false;
      archivedStudentSection.style.display = "";

      // Wire up archived search (only once)
      const archivedSearch = document.getElementById("archived-student-search");
      if (archivedSearch && !archivedSearch._searchBound) {
        archivedSearch._searchBound = true;
        const clearBtn = document.getElementById("archived-student-clear-btn");

        function runArchivedFilter() {
          const term = archivedSearch.value.toLowerCase().trim();
          if (!archivedStudentTbody) return;
          [...archivedStudentTbody.rows].forEach(row => {
            const idText = (row.querySelector("td:nth-child(1)")?.textContent || "").toLowerCase();
            const nameText = (row.querySelector("td:nth-child(2)")?.textContent || "").toLowerCase();
            row.style.display = (!term || idText.includes(term) || nameText.includes(term)) ? "" : "none";
          });
        }

        archivedSearch.addEventListener("input", runArchivedFilter);

        if (clearBtn) {
          clearBtn.addEventListener("click", () => {
            archivedSearch.value = "";
            runArchivedFilter();
            archivedSearch.focus();
          });
        }
      }
    }
  }

  // ------------------- Helpers -------------------
  function val(id) { return document.getElementById("student-" + id).value.trim(); }

  // Function to format year level (1 -> 1st Year, 2 -> 2nd Year, etc.)
  function getFormattedYearLevel(yearLevel) {
    if (!yearLevel) return '';

    const year = parseInt(yearLevel);
    if (isNaN(year)) return yearLevel;

    const suffix = year === 1 ? 'st' : year === 2 ? 'nd' : year === 3 ? 'rd' : 'th';
    return `${year}${suffix} Year`;
  }

  function normalizeStudentYearlevel(yearLevel) {
    const value = (yearLevel || "").toString().trim().toLowerCase();
    if (!value) return "";
    if (value === "irregular") return "irregular";

    const year = parseInt(value, 10);
    return Number.isNaN(year) ? value : year.toString();
  }

  function normalizeFilterText(value) {
    return (value || "").toString().trim().toLowerCase();
  }

  function getProgramByStudentValue(programValue) {
    const value = (programValue || "").toString().trim();
    if (!value || !Array.isArray(programs)) return null;

    return programs.find(program =>
      String(program.id) === value ||
      normalizeFilterText(program.name) === normalizeFilterText(value) ||
      normalizeFilterText(program.program_name) === normalizeFilterText(value)
    ) || null;
  }

  // Function to get program name from program ID
  function getProgramName(programId) {
    if (!programId) return '';

    const program = getProgramByStudentValue(programId);
    return program ? (program.name || program.program_name || programId) : programId;
  }

  function resetSubmitBtn(text) {
    let btn = addStudentModal.querySelector(".submit-btn");
    btn.replaceWith(btn.cloneNode(true));
    btn = addStudentModal.querySelector(".submit-btn");
    btn.textContent = text;
    return btn;
  }

  // Populate Program Dropdown
  function populateProgramDropdown() {
    const dropdown = document.getElementById("student-program-filter");
    if (!dropdown) {
      console.error("Program dropdown not found");
      return;
    }

    console.log("Populating dropdown with programs:", programs);
    const currentValue = dropdown.value;

    // Clear existing options except "All Programs"
    dropdown.innerHTML = '<option value="">All Programs</option>';

    // Add programs to dropdown
    if (programs && Array.isArray(programs)) {
      programs.forEach(program => {
        const option = document.createElement("option");
        option.value = String(program.id || '');
        option.textContent = program.name || program.program_name || '';
        dropdown.appendChild(option);
        console.log("Added program to dropdown:", program.name || program.program_name);
      });
      if ([...dropdown.options].some(option => option.value === currentValue)) {
        dropdown.value = currentValue;
      }
    } else {
      console.log("No programs available or programs is not an array");
    }
  }


  //  Dynamic Program -------------------
  let programs = [];
  function addProgramToDropdown(programName) {
    if (programName && !programs.includes(programName)) {
      programs.push(programName);
      programSelect.insertAdjacentHTML("beforeend", `<option value="${programName}">${programName}</option>`);
    }
  }

  //  Load Programs -------------------
  function loadStudentPrograms() {
    console.log("Loading student programs...");
    return fetch("get_StudentProgram.php")
      .then(r => {
        console.log("Response status:", r.status);
        return r.json();
      })
      .then(data => {
        console.log("Programs data received:", data);
        programs = data; // Store full program objects with id and name
        programSelect.innerHTML = `<option value="" disabled selected>-- Select Program --</option>`;
        data.forEach(program => {
          programSelect.insertAdjacentHTML("beforeend", `<option value="${program.id}">${program.name}</option>`);
        });
        console.log("Programs stored in variable:", programs);
        return data; // Return data for chaining
      })
      .catch(err => {
        console.error("Error loading programs:", err);
        throw err; // Re-throw to maintain Promise rejection
      });
  }

  // Load All Subjects for Faculty Form -------------------
  function facultySubjectSearchHtml() {
    return `
    <h4><i class="ph ph-book"></i> Assigned Subjects</h4>
    <div class="subject-filters">
      <input type="text" id="faculty-subject-search" placeholder="Search subjects, program, year level..." class="subject-search-input">
    </div>
  `;
  }

  function facultySubjectSearchText(subject) {
    return [
      subject.program_code,
      subject.program_name,
      subject.subject_code,
      subject.subject_desc,
      subject.year_level,
      `year ${subject.year_level || ""}`,
      `${subject.year_level || ""} year`
    ].join(" ").toLowerCase();
  }

  function groupSubjectsByProgram(subjects) {
    return subjects.reduce((groups, subject) => {
      const programCode = String(subject.program_code || "N/A").trim() || "N/A";
      const programName = String(subject.program_name || "Unassigned Program").trim() || "Unassigned Program";
      const key = `${programCode}__${programName}`;

      if (!groups[key]) {
        groups[key] = { programCode, programName, subjects: [] };
      }

      groups[key].subjects.push(subject);
      return groups;
    }, {});
  }

  function renderFacultySubjectList(subjects) {
    const subjectsList = document.getElementById("faculty-subjects-list");
    if (!subjectsList) return;

    if (!subjects || subjects.length === 0) {
      subjectsList.innerHTML = `${facultySubjectSearchHtml()}<small>No subjects available</small>`;
      return;
    }

    const grouped = groupSubjectsByProgram(subjects);
    const rows = Object.values(grouped)
      .sort((a, b) => a.programCode.localeCompare(b.programCode) || a.programName.localeCompare(b.programName))
      .map(group => {
        const subjectRows = group.subjects
          .sort((a, b) => {
            const yearDiff = getSubjectYearOrder(a.year_level) - getSubjectYearOrder(b.year_level);
            if (yearDiff !== 0) return yearDiff;
            const semDiff = String(a.semester || "").localeCompare(String(b.semester || ""));
            if (semDiff !== 0) return semDiff;
            return String(a.subject_code || "").localeCompare(String(b.subject_code || ""));
          })
          .map(subject => `
          <label class="subject-checkbox-item faculty-subject-item" data-search="${escapeHtml(facultySubjectSearchText(subject))}">
            <input type="checkbox" value="${subject.id}" class="faculty-subject-checkbox">
            <span class="subject-info">
              <span class="subject-code">${escapeHtml(subject.subject_code)}</span>
              <span class="subject-desc">${escapeHtml(subject.subject_desc)}</span>
              <span class="subject-year-level">${escapeHtml(formatSubjectYearLevel(subject.year_level))}</span>
            </span>
          </label>
        `).join("");

        return `
        <div class="faculty-subject-program-group">
          <div class="faculty-subject-program-header">
            <div class="faculty-subject-program-title">
              <span class="faculty-subject-program-code">${escapeHtml(group.programCode)}</span>
              <span class="faculty-subject-program-name">${escapeHtml(group.programName)}</span>
            </div>
            <span class="faculty-subject-program-count">${group.subjects.length}</span>
          </div>
          <div class="faculty-subject-program-items">
            ${subjectRows}
          </div>
        </div>
      `;
      }).join("");

    subjectsList.innerHTML = `${facultySubjectSearchHtml()}<div class="faculty-subjects-checkboxes">${rows}</div>`;
    attachFacultySubjectSearch();
  }

  function attachFacultySubjectSearch() {
    const searchInput = document.getElementById("faculty-subject-search");
    const subjectsList = document.getElementById("faculty-subjects-list");
    if (!searchInput || !subjectsList) return;

    searchInput.addEventListener("input", () => {
      const term = searchInput.value.trim().toLowerCase();
      subjectsList.querySelectorAll(".faculty-subject-item").forEach(item => {
        item.hidden = Boolean(term && !item.dataset.search.includes(term));
      });
      subjectsList.querySelectorAll(".faculty-subject-program-group").forEach(group => {
        const hasVisibleSubject = Array.from(group.querySelectorAll(".faculty-subject-item"))
          .some(item => !item.hidden);
        const headerText = group.querySelector(".faculty-subject-program-header")?.textContent.toLowerCase() || "";
        group.hidden = Boolean(term && !hasVisibleSubject && !headerText.includes(term));
      });
    });
  }

  function loadAllFacultySubjects() {
    return fetch("subject_crud.php?action=get_all")
      .then(r => r.json())
      .then(data => {
        renderFacultySubjectList(data);
        return data;
      })
      .catch(err => {
        console.error("Error loading faculty subjects:", err);
        throw err;
      });
  }

  const subjectsList = document.getElementById("faculty-subjects-list");
  loadAllFacultySubjects().then(data => {
    if (data.length === 0) {
      subjectsList.innerHTML = `${facultySubjectSearchHtml()}<p style="color: #6b7280; font-size: 0.9em;">No subjects found</p>`;
    } else {
      renderFacultySubjectList(data);
    }
  })
    .catch(err => {
      console.error("Error fetching faculty subjects:", err);
      subjectsList.innerHTML = '<p style="color: #dc2626; font-size: 0.9em;">Error loading subjects</p>';
    });

  //  Load Students -------------------
  function loadStudents() {
    console.log("Loading students...");

    // First load programs, then load students
    loadStudentPrograms().then(() => {
      // Populate program dropdown
      populateProgramDropdown();

      fetch("get_students.php")
        .then(r => {
          console.log("Response status:", r.status);
          if (!r.ok) {
            throw new Error(`HTTP error! status: ${r.status}`);
          }
          return r.json();
        })
        .then(data => {
          console.log("Students data received:", data);

          // Check for error response from PHP
          if (data.error) {
            console.error("Database error:", data.error);
            studentTbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px; color: red;">Error loading students</td></tr>';
            return;
          }

          studentTbody.innerHTML = "";
          if (archivedStudentTbody) archivedStudentTbody.innerHTML = "";

          // Check if data is empty or not an array
          if (!data || !Array.isArray(data) || data.length === 0) {
            console.log("No students found");
            studentTbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No students found</td></tr>';
            if (archivedStudentTbody) {
              archivedStudentTbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No archived students found</td></tr>';
            }
            return;
          }

          data.forEach(stu => {
            console.log("Processing student:", stu);
            console.log("Student subjects:", stu.subjects);
            const row = document.createElement("tr");
            row.dataset.id = stu.id;
            row.dataset.yearlevel = normalizeStudentYearlevel(stu.yearlevel);
            const studentProgram = getProgramByStudentValue(stu.program);
            row.dataset.programId = studentProgram ? String(studentProgram.id) : String(stu.program || "");
            row.dataset.programName = normalizeFilterText(studentProgram ? (studentProgram.name || studentProgram.program_name) : stu.program);
            row.dataset.status = (stu.status || "active").toLowerCase();

            if (normalizeStatusValue(stu.status) === "archived") {
              row.innerHTML = `
            <td>${stu.student_number || ''}</td>
            <td><div>${stu.firstname || ''} ${stu.lastname || ''} ${stu.suffix || ""}</div>
                <small style="color:#6b7280;">${stu.email || ''}</small>
            </td>
            <td><div>${stu.yearlevel === 'irregular' ? 'irregular' : (stu.yearlevel || '') + (stu.section || '')}</div>
                <small style="color:#6b7280;">${getProgramName(stu.program)}</small>
            </td>
            <td>${statusPillHtml(stu.status)}</td>`;
              if (archivedStudentTbody) archivedStudentTbody.appendChild(row);
              return;
            }

            row.innerHTML = `
          <td>${stu.student_number || ''}</td>
          <td><div>${stu.firstname || ''} ${stu.lastname || ''} ${stu.suffix || ""}</div>
              <small style="color:#6b7280;">${stu.email || ''}</small>
          </td>
          <td><div>${stu.yearlevel === 'irregular' ? 'irregular' : (stu.yearlevel || '') + (stu.section || '')}</div>
              <small style="color:#6b7280;">${getProgramName(stu.program)}</small>
          </td>
          <td>${statusPillHtml(stu.status)}</td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="view-subjects-btn" title="View Subjects"><i class="ph ph-eye"></i></button>
              <button class="edit-btn"><i class="ph ph-pencil-simple"></i></button>
              ${statusMenuHtml("student", stu.status)}
              <button class="archive-btn" title="Archive Student">Archived</button>
            </div>
          </td>`;

            // Edit button event
            const editBtn = row.querySelector(".edit-btn");
            if (editBtn) {
              editBtn.onclick = () => {
                console.log("Edit button clicked for student:", stu);
                openModal(addStudentModal, "EDIT STUDENT", "UPDATE STUDENT");

                const numberInput = document.getElementById("student-number");
                numberInput.value = stu.student_number || "";
                numberInput.disabled = true;

                const emailInput = document.getElementById("student-email");
                emailInput.value = stu.email || "";
                emailInput.disabled = false;

                // Other fields
                document.getElementById("student-firstname").value = stu.firstname || "";
                document.getElementById("student-lastname").value = stu.lastname || "";
                document.getElementById("student-suffix").value = stu.suffix || "";
                document.getElementById("student-section").value = stu.section || "";

                // Handle student type and year level
                const studentTypeRadios = document.querySelectorAll('input[name="student-type"]');
                const yearlevelRow = document.getElementById('yearlevel-row');
                const yearlevelSelect = document.getElementById('student-yearlevel');
                const sectionSelect = document.getElementById('student-section');
                const subjectsSection = document.querySelector('.section-box:has(#add-subject-btn)');

                if (stu.yearlevel === 'irregular') {
                  document.querySelector('input[name="student-type"][value="irregular"]').checked = true;
                  yearlevelRow.style.setProperty('display', 'none', 'important');
                  yearlevelSelect.required = false;
                  yearlevelSelect.value = "";
                  sectionSelect.required = false;
                  sectionSelect.value = "";
                  if (subjectsSection) subjectsSection.style.display = 'block';
                } else {
                  document.querySelector('input[name="student-type"][value="regular"]').checked = true;
                  showYearSectionRow(yearlevelRow);
                  yearlevelSelect.required = true;
                  sectionSelect.required = true;
                  document.getElementById("student-yearlevel").value = stu.yearlevel || "";
                  document.getElementById("student-section").value = stu.section || "";
                  if (subjectsSection) subjectsSection.style.display = 'none';
                }

                // Store student data for update
                addStudentModal.dataset.editId = stu.id;
                addStudentModal.dataset.studentData = JSON.stringify(stu);

                // Load existing subjects for this student
                selectedSubjects = [];
                if (stu.subjects && Array.isArray(stu.subjects)) {
                  selectedSubjects = stu.subjects.map(s => ({
                    ...s,
                    id: parseInt(s.id, 10),
                    class_id: s.class_id ? parseInt(s.class_id, 10) : 0,
                    class_year_level: s.class_year_level || "",
                    class_section: s.class_section || "",
                    instructor_name: s.instructor_name || "Unassigned"
                  }));
                  console.log("Loading subjects for edit:", selectedSubjects);
                  console.log("Number of subjects loaded:", selectedSubjects.length);
                } else {
                  console.log("No subjects found for this student or subjects is not an array");
                }
                updateSelectedSubjectsDisplay();

                // Set program value immediately after loading programs
                loadStudentPrograms().then(() => {
                  console.log("Programs loaded, setting program to:", stu.program);

                  // Re-attach student type radio button event listeners after edit form setup
                  const studentTypeRadios = document.querySelectorAll('input[name="student-type"]');
                  const yearlevelRow = document.getElementById('yearlevel-row');
                  const yearlevelSelect = document.getElementById('student-yearlevel');
                  const sectionSelect = document.getElementById('student-section');
                  const subjectsSection = document.querySelector('.section-box:has(#add-subject-btn)');

                  // Remove existing listeners to avoid duplicates
                  studentTypeRadios.forEach(radio => {
                    radio.replaceWith(radio.cloneNode(true));
                  });

                  // Get fresh reference after cloning and attach new listeners
                  const freshRadios = document.querySelectorAll('input[name="student-type"]');
                  freshRadios.forEach(radio => {
                    radio.addEventListener('change', function () {
                      console.log('Edit form radio changed to:', this.value);

                      if (!yearlevelRow || !yearlevelSelect || !sectionSelect) {
                        console.error('Required elements not found in edit form');
                        return;
                      }

                      if (this.value === 'irregular') {
                        console.log('Hiding year level and section for irregular in edit form');
                        yearlevelRow.style.setProperty('display', 'none', 'important');
                        yearlevelSelect.required = false;
                        yearlevelSelect.value = '';
                        sectionSelect.required = false;
                        sectionSelect.value = '';
                        if (subjectsSection) subjectsSection.style.display = 'block';
                      } else {
                        console.log('Showing year level and section for regular in edit form');
                        showYearSectionRow(yearlevelRow);
                        yearlevelSelect.required = true;
                        sectionSelect.required = true;
                        if (subjectsSection) subjectsSection.style.display = 'none';
                      }
                    });
                  });
                  programSelect.value = stu.program || "";
                  console.log("Program set to:", programSelect.value);
                });

                let submitBtn = resetSubmitBtn("UPDATE STUDENT");
                submitBtn.onclick = async e => {
                  e.preventDefault();
                  submitBtn.disabled = true;

                  const studentData = JSON.parse(addStudentModal.dataset.studentData || "{}");

                  const studentType = document.querySelector('input[name="student-type"]:checked').value;
                  // Clear subjects for regular students, keep only for irregular
                  const subjectIds = (studentType === 'irregular')
                    ? selectedSubjects.map(s => ({
                      id: s.id,
                      class_id: s.class_id || 0,
                      class_year_level: s.class_year_level || "",
                      class_section: s.class_section || ""
                    }))
                    : [];
                  let yearlevelValue;
                  if (studentType === 'irregular') {
                    yearlevelValue = 'irregular';
                  } else {
                    yearlevelValue = document.getElementById("student-yearlevel").value;
                    if (!yearlevelValue) {
                      showGlobalNotification("Please select year level for regular student", "warning");
                      submitBtn.disabled = false;
                      return;
                    }
                  }

                  const res = await fetch("student_crud.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                      action: "edit",
                      id: studentData.id,
                      student_number: studentData.student_number,
                      email: document.getElementById("student-email").value.trim(),
                      firstname: document.getElementById("student-firstname").value.trim(),
                      lastname: document.getElementById("student-lastname").value.trim(),
                      suffix: document.getElementById("student-suffix").value.trim(),
                      yearlevel: yearlevelValue,
                      program: document.getElementById("student-program").value,
                      section: document.getElementById("student-section").value.trim(),
                      subjects: subjectIds,
                      student_type: document.querySelector('input[name="student-type"]:checked')?.value || 'regular'
                    })
                  });

                  const d = await res.json();
                  if (d.success) {
                    loadStudents();
                    closeModal(addStudentModal);
                    addProgramToDropdown(val("program"));
                    showNotification("Student edited successfully!", "#4caf50");
                    loadDashboardStats(); // Update dashboard stats in real-time
                  } else {
                    showNotification(d.message || "Error updating student.", "#f44336");
                  }
                  submitBtn.disabled = false;
                };
              };
            } else {
              console.error("Edit button not found for student row:", stu);
            }

            const statusMenu = row.querySelector(".status-action-menu");
            statusMenu.querySelector(".settings-btn").addEventListener("click", (e) => {
              e.stopPropagation();
              const willOpen = !statusMenu.classList.contains("open");
              closeStatusMenus(statusMenu);
              statusMenu.classList.toggle("open", willOpen);
              if (willOpen) positionStatusMenu(statusMenu);
              else restoreStatusDropdown(statusMenu);
            });
            statusMenu.querySelectorAll(".status-option").forEach(option => {
              option.addEventListener("click", async (e) => {
                e.stopPropagation();
                const newStatus = option.dataset.status;
                try {
                  const result = await updateStatus("student", stu.id, newStatus);
                  if (result.success) {
                    row.dataset.status = newStatus;
                    row.querySelector("td:nth-child(4)").innerHTML = statusPillHtml(newStatus);
                    (option.closest(".status-dropdown-menu") || statusMenu).querySelectorAll(".status-option").forEach(btn => {
                      btn.classList.toggle("selected", btn.dataset.status === newStatus);
                    });
                    restoreStatusDropdown(statusMenu);
                    statusMenu.classList.remove("open");
                    closeStatusMenus();
                    showNotification(`Student status updated to ${statusLabel(newStatus)}`, "#4caf50");
                    refreshSectionStats();
                  } else {
                    showNotification("Failed to update status: " + (result.message || "Unknown error"), "#f44336");
                  }
                } catch (error) {
                  console.error("Student status update error:", error);
                  showNotification("Error updating student status", "#f44336");
                }
              });
            });

            row.querySelector(".archive-btn").addEventListener("click", async () => {
              const currentStatus = normalizeStatusValue(row.dataset.status);
              if (currentStatus === "archived") {
                showNotification("Student is already archived", "#64748b");
                return;
              }

              openArchiveModal(row, stu, statusMenu, "student");
            });

            // View Subjects
            const viewBtn = row.querySelector(".view-subjects-btn");
            if (viewBtn) {
              viewBtn.onclick = () => {
                console.log("View subjects button clicked for student:", stu);
                viewStudentSubjects(stu);
              };
            } else {
              console.error("View subjects button not found for student row:", stu);
            }
            studentTbody.appendChild(row);
          });
          if (studentTbody.rows.length === 0) {
            studentTbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 20px;">No students found</td></tr>';
          }
          if (archivedStudentTbody && archivedStudentTbody.rows.length === 0) {
            archivedStudentTbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No archived students found</td></tr>';
          }
          // Search functionality
          const searchInput = document.getElementById("student-search");
          const programFilter = document.getElementById("student-program-filter");
          const yearlevelFilter = document.getElementById("student-yearlevel-filter");

          if (searchInput) {
            searchInput.oninput = e => {
              filterStudents();
            };
          }

          if (programFilter) {
            programFilter.onchange = e => {
              setStudentFilterActiveStates();
              filterStudents();
            };
          }

          if (yearlevelFilter) {
            yearlevelFilter.onchange = e => {
              setStudentFilterActiveStates();
              filterStudents();
            };
          }

          setStudentFilterActiveStates();

          function setStudentFilterActiveStates() {
            [programFilter, yearlevelFilter].forEach(filter => {
              if (!filter) return;

              const wrapper = filter.closest(".program-filter-wrapper");
              if (!wrapper) return;

              wrapper.classList.toggle("is-active", filter.value !== "");
            });
          }

          // Combined filter function
          function filterStudents() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : "";
            const selectedProgram = programFilter ? programFilter.value : "";
            const selectedYearlevel = yearlevelFilter ? yearlevelFilter.value.toLowerCase() : "";

            [...studentTbody.rows].forEach(row => {
              // Get ID and name only (columns 1 and 2)
              const idCell = row.querySelector("td:nth-child(1)");
              const nameCell = row.querySelector("td:nth-child(2)");
              const programCell = row.querySelector("td:nth-child(3) small");

              let matchesSearch = true;
              let matchesProgram = true;
              let matchesYearlevel = true;

              // Check search term against ID and name only
              if (searchTerm) {
                const idText = idCell ? idCell.textContent.toLowerCase() : "";
                const nameText = nameCell ? nameCell.textContent.toLowerCase() : "";
                matchesSearch = idText.includes(searchTerm) || nameText.includes(searchTerm);
              }

              // Check program filter
              if (selectedProgram && selectedProgram !== "") {
                const programText = programCell ? normalizeFilterText(programCell.textContent) : "";
                matchesProgram = row.dataset.programId === selectedProgram ||
                  row.dataset.programName === normalizeFilterText(selectedProgram) ||
                  programText === normalizeFilterText(selectedProgram);
              }

              if (selectedYearlevel && selectedYearlevel !== "") {
                matchesYearlevel = (row.dataset.yearlevel || "") === selectedYearlevel;
              }

              // Show row only if all conditions are met
              row.style.display = (matchesSearch && matchesProgram && matchesYearlevel) ? "" : "none";
            });
          }
        })
        .then(() => refreshSectionStats())
        .catch(err => console.error("Error loading students:", err));
    });
  }

  // ========================= Subject Selection for Students =========================
  let selectedSubjects = [];
  let allSubjects = [];
  let subjectClassOptions = {};

  async function loadClassOptionsForSubjects(programId, subjects) {
    subjectClassOptions = {};
    const requests = subjects.map(subject =>
      fetch(`classes_crud.php?action=get_subject_class_options&program_id=${programId}&subject_id=${subject.id}`)
        .then(r => r.json())
        .then(rows => {
          subjectClassOptions[subject.id] = Array.isArray(rows) ? rows : [];
        })
        .catch(() => {
          subjectClassOptions[subject.id] = [];
        })
    );
    await Promise.all(requests);
  }

  // Load all subjects for selection
  function loadAllSubjects() {
    fetch("subject_crud.php?action=get_all")
      .then(r => r.json())
      .then(data => {
        allSubjects = data;
        renderSubjectsTable();
      })
      .catch(err => console.error("Error loading subjects:", err));
  }

  // Render subjects table with filters
  function renderSubjectsTable() {
    const tbody = document.getElementById("subjects-selection-tbody");
    const searchTerm = document.getElementById("subject-search").value.toLowerCase();

    let filteredSubjects = allSubjects.filter(subject => {
      const matchesSearch = !searchTerm ||
        subject.subject_code.toLowerCase().includes(searchTerm) ||
        subject.subject_desc.toLowerCase().includes(searchTerm);

      return matchesSearch;
    });

    tbody.innerHTML = "";

    if (filteredSubjects.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No subjects found</td></tr>';
      return;
    }

    filteredSubjects.forEach(subject => {
      const row = document.createElement("tr");
      const selectedSubject = selectedSubjects.find(s => s.id === subject.id);
      const isSelected = !!selectedSubject;
      const options = subjectClassOptions[subject.id] || [];
      let selectedClassId = selectedSubject?.class_id ? String(selectedSubject.class_id) : "";

      // Recover existing class selection during edit if class_id is missing but class year/section exists.
      if (!selectedClassId && selectedSubject && options.length > 0) {
        const matched = options.find(opt =>
          String(opt.year_level || "").trim().toLowerCase() === String(selectedSubject.class_year_level || "").trim().toLowerCase() &&
          String(opt.section || "").trim().toLowerCase() === String(selectedSubject.class_section || "").trim().toLowerCase()
        );
        if (matched) {
          selectedClassId = String(matched.class_id);
          selectedSubject.class_id = parseInt(matched.class_id, 10);
          selectedSubject.class_year_level = matched.year_level;
          selectedSubject.class_section = matched.section;
          selectedSubject.instructor_name = matched.instructor_name || selectedSubject.instructor_name || "Unassigned";
        }
      }
      const optionHtml = options.length > 0
        ? options.map(opt => {
          const label = `${opt.year_level} - ${opt.section}`;
          const sel = selectedClassId && selectedClassId === String(opt.class_id) ? "selected" : "";
          return `<option value="${opt.class_id}" ${sel}>${label}</option>`;
        }).join("")
        : '<option value="">No class found</option>';

      row.innerHTML = `
      <td><input type="checkbox" value="${subject.id}" ${isSelected ? 'checked' : ''}></td>
      <td>${subject.subject_code}</td>
      <td>${subject.subject_desc}</td>
      <td>
        <select class="subject-class-select" data-subject-id="${subject.id}">
          <option value="">Select Year & Section</option>
          ${optionHtml}
        </select>
      </td>
    `;

      tbody.appendChild(row);
    });
  }

  // Function to load subjects for program popup modal
  function loadSubjectsForProgramPopup(programId) {
    // Clear search input
    document.getElementById("subject-search").value = "";

    fetch(`subject_crud.php?action=get_all_by_program&program_id=${programId}`)
      .then(r => r.json())
      .then(async data => {
        allSubjects = data;
        await loadClassOptionsForSubjects(programId, allSubjects);
        renderSubjectsTable();
      })
      .catch(err => {
        console.error("Error loading subjects for program:", err);
        allSubjects = [];
        renderSubjectsTable();
      });
  }

  // Add Subject button - opens popup modal
  document.getElementById("add-subject-btn")?.addEventListener("click", () => {
    const program = document.getElementById("student-program").value;

    if (!program) {
      showGlobalNotification("Please select a program first to view available subjects", "warning");
      return;
    }

    // Load subjects for the selected program and open popup
    loadSubjectsForProgramPopup(program);
    subjectSelectionModal.style.display = "flex";
    subjectSelectionModal.style.zIndex = "100000";
    subjectSelectionModal.style.position = "fixed";
    subjectSelectionModal.style.top = "0";
    subjectSelectionModal.style.left = "0";
    subjectSelectionModal.style.width = "100%";
    subjectSelectionModal.style.height = "100%";
    subjectSelectionModal.style.backgroundColor = "rgba(0,0,0,0.5)";
    subjectSelectionModal.style.alignItems = "center";
    subjectSelectionModal.style.justifyContent = "center";

    // Add close button event listeners directly when modal opens
    setTimeout(() => {
      const closeBtn = document.querySelector("#subjectSelectionModal .close-btn");
      const cancelBtn = document.getElementById("cancel-subject-selection");

      console.log("Adding close handlers directly");
      console.log("Close button:", closeBtn);
      console.log("Cancel button:", cancelBtn);

      // Remove existing listeners to avoid duplicates
      if (closeBtn) {
        closeBtn.replaceWith(closeBtn.cloneNode(true));
        const newCloseBtn = document.querySelector("#subjectSelectionModal .close-btn");
        newCloseBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          console.log("Close X clicked directly");
          subjectSelectionModal.style.display = "none";
          // Clear checkboxes to prevent repeated clicking issue
          const allCheckboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']");
          allCheckboxes.forEach(cb => cb.checked = false);
        });
      }

      if (cancelBtn) {
        cancelBtn.replaceWith(cancelBtn.cloneNode(true));
        const newCancelBtn = document.getElementById("cancel-subject-selection");
        newCancelBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();
          console.log("Cancel clicked directly");
          subjectSelectionModal.style.display = "none";
          // Clear checkboxes to prevent repeated clicking issue
          const allCheckboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']");
          allCheckboxes.forEach(cb => cb.checked = false);
        });
      }
    }, 100);
  });

  // Close modal handlers
  document.addEventListener("DOMContentLoaded", () => {
    // Subject Selection Modal close handlers
    const closeBtn = document.querySelector("#subjectSelectionModal .close-btn");
    const cancelBtn = document.getElementById("cancel-subject-selection");

    console.log("Setting up subject modal close handlers");
    console.log("Close button found:", closeBtn);
    console.log("Cancel button found:", cancelBtn);

    if (closeBtn) {
      closeBtn.addEventListener("click", (e) => {
        e.preventDefault();
        console.log("Close X button clicked");
        subjectSelectionModal.style.display = "none";
        // Clear checkboxes to prevent repeated clicking issue
        const allCheckboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']");
        allCheckboxes.forEach(cb => cb.checked = false);
      });
    }

    if (cancelBtn) {
      cancelBtn.addEventListener("click", (e) => {
        e.preventDefault();
        console.log("Cancel button clicked");
        subjectSelectionModal.style.display = "none";
        // Clear checkboxes to prevent repeated clicking issue
        const allCheckboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']");
        allCheckboxes.forEach(cb => cb.checked = false);
      });
    }

    // Student modal close button
    document.querySelector("#addStudentModal .close-btn")?.addEventListener("click", () => {
      addStudentModal.style.display = "none";
      // Clear selected subjects when closing modal to prevent carryover
      selectedSubjects = [];
      updateSelectedSubjectsDisplay();
    });

    // View student subjects modal close button
    document.querySelector("#viewStudentSubjectsModal .close-btn")?.addEventListener("click", () => {
      viewStudentSubjectsModal.style.display = "none";
    });

    // View faculty subjects modal close button
    document.querySelector("#viewFacultySubjectsModal .close-btn")?.addEventListener("click", () => {
      viewFacultySubjectsModal.style.display = "none";
    });


    // Add Faculty modal close button
    const facultyCloseBtn = document.querySelector("#addFacultyModal .close-btn");
    if (facultyCloseBtn) {
      facultyCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById("addFacultyModal").style.display = "none";
        console.log("Faculty modal closed by X button");
      };
    }

    // Add Program modal close button
    const programCloseBtn = document.querySelector("#addProgramModal .close-btn");
    if (programCloseBtn) {
      programCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById("addProgramModal").style.display = "none";
        console.log("Program modal closed by X button");
      };
    }

    // Add Category modal close button
    const categoryCloseBtn = document.querySelector("#addCategoryModal .close-btn");
    if (categoryCloseBtn) {
      categoryCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        addCategoryModal.style.display = "none";
        console.log("Category modal closed by X button");
      };
    }

    // Add Question modal close button
    const questionCloseBtn = document.querySelector("#addQuestionModal .close-btn");
    if (questionCloseBtn) {
      questionCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        addQuestionModal.style.display = "none";
        console.log("Question modal closed by X button");
      };
    }

    // Clear error message when user starts typing in question textarea
    const questionTextarea = document.getElementById("question-text");
    if (questionTextarea) {
      questionTextarea.addEventListener("input", () => {
        const errorMsg = document.getElementById("questionErrorMsg");
        if (errorMsg && errorMsg.style.display !== "none") {
          errorMsg.style.display = "none";
        }
      });
    }

    // Add Subject modal close button
    const subjectCloseBtn = document.querySelector("#addSubjectModal .close-btn");
    if (subjectCloseBtn) {
      subjectCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        addSubjectModal.style.display = "none";
        console.log("Subject modal closed by X button");
      };
    }

    // Add Subject Main modal close button
    const subjectMainCloseBtn = document.querySelector("#addSubjectMainModal .close-btn");
    if (subjectMainCloseBtn) {
      subjectMainCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        addSubjectMainModal.style.display = "none";
        console.log("Subject Main modal closed by X button");
      };
    }

    // Add Class modal close button
    const classCloseBtn = document.querySelector("#addClassModal .close-btn");
    if (classCloseBtn) {
      classCloseBtn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        document.getElementById("addClassModal").style.display = "none";
        console.log("Class modal closed by X button");
      };
    }
  });

  // Global close button handler for all modals
  document.addEventListener("click", function (e) {
    if (e.target.classList.contains("close-btn")) {
      e.preventDefault();
      e.stopPropagation();

      // Find the closest modal parent
      let modal = e.target.closest(".modal");
      if (modal) {
        modal.style.display = "none";
        console.log("Modal closed by global close handler:", modal.id);
      }
    }
  });

  // Close any open modal when clicking the backdrop/outside modal-content.
  document.addEventListener("mousedown", function (e) {
    const visibleModals = document.querySelectorAll(".modal");
    visibleModals.forEach((modal) => {
      const isVisible = window.getComputedStyle(modal).display !== "none";
      if (!isVisible) return;
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });
  });

  // Filter handlers
  document.getElementById("subject-search")?.addEventListener("input", renderSubjectsTable);

  // ... (rest of the code remains the same)
  // Select all checkbox
  document.getElementById("select-all-subjects")?.addEventListener("change", (e) => {
    const checkboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']");
    checkboxes.forEach(cb => cb.checked = e.target.checked);
  });

  // Confirm subject selection
  document.getElementById("confirm-subject-selection")?.addEventListener("click", () => {
    const checkboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']:checked");
    let hasMissingClass = false;

    console.log("Confirming subject selection. Checked checkboxes:", checkboxes.length);

    checkboxes.forEach(cb => {
      const subject = allSubjects.find(s => s.id == cb.value);
      if (subject) {
        const classSelect = document.querySelector(`.subject-class-select[data-subject-id="${subject.id}"]`);
        const classId = classSelect ? parseInt(classSelect.value || "0", 10) : 0;
        const classOptions = subjectClassOptions[subject.id] || [];
        const selectedClass = classOptions.find(opt => parseInt(opt.class_id, 10) === classId);

        if (!classId || !selectedClass) {
          showGlobalNotification(`Please select year level and section for subject ${subject.subject_code}`, "warning");
          hasMissingClass = true;
          return;
        }

        const subjectWithClass = {
          ...subject,
          class_id: parseInt(selectedClass.class_id, 10),
          class_year_level: selectedClass.year_level,
          class_section: selectedClass.section,
          instructor_name: selectedClass.instructor_name || "Unassigned"
        };

        // Check if subject is already in selectedSubjects to avoid duplicates
        const existingIndex = selectedSubjects.findIndex(s => s.id === subject.id);
        if (existingIndex === -1) {
          selectedSubjects.push(subjectWithClass);
          console.log("Added subject:", subjectWithClass);
        } else {
          selectedSubjects[existingIndex] = subjectWithClass;
          console.log("Updated subject class selection:", subjectWithClass);
        }
      }
    });

    if (hasMissingClass) return;

    console.log("Final selectedSubjects:", selectedSubjects);
    updateSelectedSubjectsDisplay();
    subjectSelectionModal.style.display = "none";

    // Clear all checkboxes to prevent repeated clicking issue
    setTimeout(() => {
      const allCheckboxes = document.querySelectorAll("#subjects-selection-tbody input[type='checkbox']");
      allCheckboxes.forEach(cb => cb.checked = false);
    }, 100);
  });

  // Update selected subjects display
  function updateSelectedSubjectsDisplay() {
    const container = document.getElementById("selected-subjects");

    console.log("Updating selected subjects display. selectedSubjects:", selectedSubjects);
    console.log("Container found:", container);

    if (!container) {
      console.error("selected-subjects container not found!");
      return;
    }

    if (selectedSubjects.length === 0) {
      container.innerHTML = '<p style="color: #6b7280; font-size: 0.9em;">No subjects selected</p>';
      console.log("No subjects selected, showing default message");
    } else {
      console.log(`Displaying ${selectedSubjects.length} subjects`);
      let html = '<div class="selected-subjects-list">';
      selectedSubjects.forEach(subject => {
        console.log("Adding subject to display:", subject);
        html += `
        <div class="selected-subject-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; margin-bottom: 5px; background: #f3f4f6; border-radius: 4px; border: 1px solid #d1d5db;">
          <span>${subject.subject_code} - ${subject.subject_desc} (${subject.class_year_level || subject.year_level}${subject.class_section ? " / " + subject.class_section : ""})${subject.instructor_name ? " - " + subject.instructor_name : ""}</span>
          <button type="button" class="remove-subject" data-id="${subject.id}" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
            <i class="ph ph-x"></i>
          </button>
        </div>
      `;
      });
      html += '</div>';
      container.innerHTML = html;

      // Add remove handlers
      container.querySelectorAll(".remove-subject").forEach(btn => {
        btn.addEventListener("click", (e) => {
          const subjectId = parseInt(e.currentTarget.dataset.id);
          console.log("Removing subject with ID:", subjectId);
          selectedSubjects = selectedSubjects.filter(s => s.id !== subjectId);
          updateSelectedSubjectsDisplay();
        });
      });
    }
  }

  // Function to display available subjects for selection
  function updateAvailableSubjectsDisplay(availableSubjects) {
    const container = document.getElementById("selected-subjects");

    if (availableSubjects.length === 0) {
      container.innerHTML = '<p style="color: #6b7280; font-size: 0.9em;">Select both program and year level to see available subjects</p>';
    } else {
      let html = '<div class="available-subjects-section">';
      html += '<h5 style="margin-bottom: 10px; color: #374151;">Available Subjects:</h5>';
      html += '<div class="available-subjects-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px;">';

      availableSubjects.forEach(subject => {
        const isSelected = selectedSubjects.some(s => s.id === subject.id);
        html += `
        <div class="available-subject-item" style="display: flex; align-items: center; justify-content: space-between; padding: 8px; margin-bottom: 5px; background: ${isSelected ? '#dbeafe' : '#f9fafb'}; border-radius: 4px; border: 1px solid ${isSelected ? '#bfdbfe' : '#e5e7eb'};">
          <div style="flex: 1;">
            <strong>${subject.subject_code}</strong> - ${subject.subject_desc}
          </div>
          <button type="button" class="select-subject-btn" data-id="${subject.id}" data-code="${subject.subject_code}" data-desc="${subject.subject_desc}" data-year="${subject.year_level}" style="background: ${isSelected ? '#dc2626' : '#2563eb'}; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;">
            ${isSelected ? 'Remove' : 'Add'}
          </button>
        </div>
      `;
      });

      html += '</div>';

      if (selectedSubjects.length > 0) {
        html += '<h5 style="margin-top: 15px; margin-bottom: 10px; color: #374151;">Selected Subjects:</h5>';
        html += '<div class="selected-subjects-list">';
        selectedSubjects.forEach(subject => {
          html += `
          <div class="selected-subject-item" style="display: flex; align-items: center; justify-content: space-between; padding: 6px; margin-bottom: 3px; background: #dbeafe; border-radius: 4px;">
            <span>${subject.subject_code} - ${subject.subject_desc}</span>
            <button type="button" class="remove-subject" data-id="${subject.id}" style="background: #dc2626; color: white; border: none; padding: 2px 6px; border-radius: 3px; cursor: pointer; font-size: 11px;">
              <i class="ph ph-x"></i>
            </button>
          </div>
        `;
        });
        html += '</div>';
      }

      html += '</div>';
      container.innerHTML = html;

      // Add event listeners for select/remove buttons
      container.querySelectorAll(".select-subject-btn").forEach(btn => {
        btn.addEventListener("click", (e) => {
          const subjectId = parseInt(e.currentTarget.dataset.id);
          const subjectCode = e.currentTarget.dataset.code;
          const subjectDesc = e.currentTarget.dataset.desc;
          const subjectYear = e.currentTarget.dataset.year;

          const subject = { id: subjectId, subject_code: subjectCode, subject_desc: subjectDesc, year_level: subjectYear };

          const existingIndex = selectedSubjects.findIndex(s => s.id === subjectId);
          if (existingIndex >= 0) {
            // Remove from selected
            selectedSubjects.splice(existingIndex, 1);
          } else {
            // Add to selected
            selectedSubjects.push(subject);
          }

          // Refresh the display
          updateAvailableSubjectsDisplay(availableSubjects);
        });
      });

      // Add remove handlers for selected subjects
      container.querySelectorAll(".remove-subject").forEach(btn => {
        btn.addEventListener("click", (e) => {
          const subjectId = parseInt(e.currentTarget.dataset.id);
          selectedSubjects = selectedSubjects.filter(s => s.id !== subjectId);
          updateAvailableSubjectsDisplay(availableSubjects);
        });
      });
    }
  }

  // ========================= Categories & Questions dagdag========================= //
  const addCategoryBtn = document.getElementById("addCategoryBtn"),
    saveCategoryBtn = document.getElementById("saveCategoryBtn"),
    questionCategoryName = document.getElementById("questionCategoryName"),
    saveQuestionBtn = document.getElementById("saveQuestionBtn");

  // --- Criteria lock: disable/enable buttons when period is active ---
  function updateCriteriaLockUI() {
    const locked = !!window.hasActivePeriod;

    // Add Category button
    const btn = document.getElementById("addCategoryBtn");
    if (btn) {
      btn.disabled = locked;
      btn.classList.toggle("criteria-btn-locked", locked);
    }

    // All per-category and per-question add/edit/delete buttons
    document.querySelectorAll(
      ".criteria-category .add-btn, .criteria-category .edit-btn, .criteria-category .delete-btn, " +
      ".question-item .edit-btn, .question-item .delete-btn"
    ).forEach(b => {
      b.disabled = locked;
      b.classList.toggle("criteria-btn-locked", locked);
    });
  }

  /**
   * Get the sum of weights currently stored on category cards,
   * optionally excluding the category being edited (by numeric id string).
   */
  function getCurrentWeightSum(excludeCatId = null) {
    let sum = 0;
    document.querySelectorAll(".criteria-category").forEach(c => {
      if (excludeCatId && c.id === `cat-${excludeCatId}`) return;
      sum += parseFloat(c.dataset.weight || 0);
    });
    return Math.round(sum * 100) / 100;
  }

  // --- OPEN CATEGORY form ---
  addCategoryBtn.onclick = () => {
    if (window.hasActivePeriod) return;

    // Block adding a new category if weights are already fully allocated
    const usedWeight = getCurrentWeightSum(null);
    if (usedWeight >= 100) {
      showNotification(
        `Cannot add a new category — existing categories already use ${usedWeight}% (100%). ` +
        `Edit existing category weights to free up percentage first.`,
        "#ef4444", 6000
      );
      return;
    }

    addCategoryModal.style.display = "flex";
    delete addCategoryModal.dataset.editId;
    document.getElementById("categoryModalTitle").innerText = "ADD CATEGORY";
    ["category-name", "section-number", "category-weight"].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = "";
    });
    updateWeightHint(null);
  };

  //  CLOSE form =========================

  //  SAVE CATEGORY ========================= //
  saveCategoryBtn.onclick = () => {
    if (window.hasActivePeriod) return;

    const name = document.getElementById("category-name").value.trim();
    const sec = document.getElementById("section-number").value.trim();
    const wInput = document.getElementById("category-weight");
    const weight = wInput ? parseFloat(wInput.value) || 0 : 0;

    if (!name || !sec) {
      showGlobalNotification("Please fill all fields", "warning");
      return;
    }

    // --- Weight validation ---
    const editId = addCategoryModal.dataset.editId
      ? addCategoryModal.dataset.editId.replace("cat-", "")
      : null;

    const usedWeight = getCurrentWeightSum(editId);   // sum excluding self when editing
    const newTotal = Math.round((usedWeight + weight) * 100) / 100;
    const catCount = document.querySelectorAll(".criteria-category").length;
    const isNew = !editId;

    // Allow weight = 0 (means "equal split, no fixed percentage")
    if (weight > 0) {
      if (newTotal > 100) {
        const available = Math.round((100 - usedWeight) * 100) / 100;
        showNotification(
          `Weight too high — other categories already use ${usedWeight}%. ` +
          `Maximum you can assign here is ${available}%.`,
          "#ef4444", 6000
        );
        return;
      }
      if (newTotal < 100) {
        // Warn but allow — they may plan to set the rest later
        const remaining = Math.round((100 - newTotal) * 100) / 100;
        showNotification(
          `Note: Total weight will be ${newTotal}% after this save (${remaining}% unassigned). ` +
          `Scores will be auto-normalised until all categories sum to 100%.`,
          "#f59e0b", 5000
        );
      }
    }

    const payload = { category_name: name, section_number: sec, weight: weight };
    let url = "add_category.php";

    if (editId) {
      payload.id = editId;
      url = "edit_category.php";
    }

    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          const isEditing = !!editId;
          loadCategories();
          addCategoryModal.style.display = "none";
          delete addCategoryModal.dataset.editId;
          if (newTotal === 100 || weight === 0) {
            showNotification(isEditing ? "Category updated successfully!" : "Category added successfully!", "#10b981");
          }
          // else the yellow warning above already showed
        } else {
          showNotification("Failed: " + (d.message || "Unknown error"), "#ef4444");
        }
      })
      .catch(err => {
        console.error("Error saving category:", err);
        showNotification("Network error occurred", "#ef4444");
      });
  };

  //  SAVE QUESTION ========================= //
  saveQuestionBtn.onclick = () => {
    if (window.hasActivePeriod) return;

    const q = document.getElementById("question-text").value.trim();
    const errorMsg = document.getElementById("questionErrorMsg");
    const errorText = errorMsg?.querySelector(".error-text");

    if (!q) {
      // Show error message inside the modal
      if (errorMsg && errorText) {
        errorText.textContent = "Please enter a question";
        errorMsg.style.display = "flex";

        // Auto-hide after 3 seconds
        setTimeout(() => {
          errorMsg.style.display = "none";
        }, 3000);
      }
      return;
    }

    // Hide error message if shown
    if (errorMsg) {
      errorMsg.style.display = "none";
    }

    const catId = addQuestionModal.dataset.targetId.replace("cat-", "");

    // Check if this is a new question (not editing)
    if (!addQuestionModal.dataset.editId) {
      // Count existing questions for this category
      fetch(`get_question.php?category_id=${catId}`)
        .then(r => r.json())
        .then(questions => {
          if (questions.length >= 5) {
            // Close the add question modal first
            const addQuestionModal = document.getElementById("addQuestionModal");
            if (addQuestionModal) {
              addQuestionModal.style.display = "none";
            }

            // Show notification after a short delay so user can see it
            setTimeout(() => {
              showGlobalNotification("Maximum of 5 questions per category reached!", "warning");
            }, 100);
            return;
          }

          // Proceed with adding the question
          proceedToAddQuestion(catId, q);
        })
        .catch(err => {
          console.error("Error checking question count:", err);
          // Still proceed if there's an error checking count
          proceedToAddQuestion(catId, q);
        });
    } else {
      // Editing existing question, proceed directly
      proceedToAddQuestion(catId, q);
    }
  };

  function proceedToAddQuestion(catId, q) {
    const payload = { category_id: catId, question_text: q };
    let url = addQuestionModal.dataset.editId ? "edit_question.php" : "add_question.php";

    if (addQuestionModal.dataset.editId) payload.id = addQuestionModal.dataset.editId.replace("q-", "");

    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          const isEditing = addQuestionModal.dataset.editId ? true : false;
          loadCategories();
          addQuestionModal.style.display = "none";
          delete addQuestionModal.dataset.editId;
          document.getElementById("question-text").value = "";
          showNotification(isEditing ? "Question edited successfully!" : "Question added successfully!", "#10b981");
        } else {
          showNotification("Failed: " + (d.message || "Unknown error"), "#ef4444");
        }
      })
      .catch(err => {
        console.error("Error saving question:", err);
        showNotification("Network error occurred", "#ef4444");
      });
  }

  //  CATEGORY ACTIONS ========================= //
  function bindCategoryActions(cat) {
    cat.querySelector(".add-btn").onclick = () => {
      if (window.hasActivePeriod) return;
      questionCategoryName.innerText = cat.querySelector(".category-name").innerText;

      // Clear error message and input field when opening modal
      const errorMsg = document.getElementById("questionErrorMsg");
      if (errorMsg) errorMsg.style.display = "none";
      document.getElementById("question-text").value = "";

      addQuestionModal.style.display = "flex";
      addQuestionModal.dataset.targetId = cat.id;
      delete addQuestionModal.dataset.editId;
      document.getElementById("questionModalTitle").innerText = "ADD QUESTION";
    };

    cat.querySelector(".edit-btn").onclick = () => {
      if (window.hasActivePeriod) return;
      document.getElementById("category-name").value = cat.querySelector(".category-name").innerText;
      document.getElementById("section-number").value = cat.querySelector(".section-number").innerText.replace("SECTION ", "");
      const wInput = document.getElementById("category-weight");
      if (wInput) wInput.value = parseFloat(cat.dataset.weight || 0).toFixed(2);
      addCategoryModal.style.display = "flex";
      addCategoryModal.dataset.editId = cat.id;
      document.getElementById("categoryModalTitle").innerText = "EDIT CATEGORY";
      updateWeightHint(cat.id.replace("cat-", ""));
    };

    cat.querySelector(".delete-btn").onclick = () => {
      if (window.hasActivePeriod) return;
      openDeleteModal("category", cat.querySelector(".category-name").innerText, cat);
    };
  }

  function bindQuestionActions(item) {
    const cat = item.closest(".criteria-category");

    item.querySelector(".edit-btn").onclick = () => {
      if (window.hasActivePeriod) return;

      // Clear error message when opening edit modal
      const errorMsg = document.getElementById("questionErrorMsg");
      if (errorMsg) errorMsg.style.display = "none";

      document.getElementById("question-text").value = item.querySelector(".question-text").innerText;
      questionCategoryName.innerText = cat.querySelector(".category-name").innerText;
      addQuestionModal.style.display = "flex";
      addQuestionModal.dataset.targetId = cat.id;
      addQuestionModal.dataset.editId = item.id;
      document.getElementById("questionModalTitle").innerText = "EDIT QUESTION";
    };

    item.querySelector(".delete-btn").onclick = () => {
      if (window.hasActivePeriod) return;
      openDeleteModal("question", item.querySelector(".question-text").innerText, item);
    };
  }
  //  LOAD CATEGORIES ========================= //
  function loadCategories() {
    fetch("get_category.php")
      .then(r => r.json())
      .then(cats => {
        const container = document.getElementById("categoriesColumn");
        container.innerHTML = "";
        document.getElementById("total-categories").innerText = cats.length;
        let totalQuestions = 0;

        // Normalise weights for display (same logic as PHP helper)
        const totalW = cats.reduce((s, c) => s + parseFloat(c.weight || 0), 0);
        const useEqual = totalW <= 0;
        const equalW = cats.length > 0 ? (100 / cats.length) : 0;

        // Update summary panel weight row
        const weightSumRow = document.getElementById("weight-sum-row");
        const weightDisplay = document.getElementById("total-weight-display");
        const weightStatus = document.getElementById("weight-sum-status");
        if (cats.length > 0 && weightSumRow) {
          weightSumRow.style.display = "block";
          const displayTotal = useEqual ? 100 : Math.round(totalW * 100) / 100;
          if (weightDisplay) weightDisplay.textContent = displayTotal + "%";
          if (weightStatus) {
            if (useEqual) {
              weightStatus.textContent = "(equal split)";
              weightStatus.style.color = "#64748b";
            } else if (Math.abs(totalW - 100) < 0.1) {
              weightStatus.textContent = "✓";
              weightStatus.style.color = "#10b981";
            } else {
              weightStatus.textContent = "(auto-normalised to 100%)";
              weightStatus.style.color = "#f59e0b";
            }
          }
        } else if (weightSumRow) {
          weightSumRow.style.display = "none";
        }

        cats.forEach(c => {
          const normW = useEqual
            ? parseFloat(equalW.toFixed(2))
            : parseFloat(((parseFloat(c.weight || 0) / totalW) * 100).toFixed(2));

          const cat = document.createElement("div");
          cat.className = "criteria-category";
          cat.id = `cat-${c.id}`;
          cat.dataset.category_id = c.id;
          cat.dataset.weight = parseFloat(c.weight || 0);

          cat.innerHTML = `
          <div class="category-header">
            <div class="title-block">
              <i class="ph ph-chalkboard-teacher"></i>
              <div class="text-block">
                <div class="section-number">SECTION ${c.section_number}</div>
                <div class="category-name">${c.category_name}</div>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <span class="category-weight-badge" title="Category weight (contribution to overall score)">${normW}%</span>
              <div class="action-buttons">
                <button class="add-btn"><i class="ph ph-plus"></i></button>
                <button class="edit-btn"><i class="ph ph-pencil-simple"></i></button>
                <button class="delete-btn"><i class="ph ph-trash"></i></button>
              </div>
            </div>
          </div>
          <div class="category-table-header"><div>Question</div><div>Action</div></div>
          <div class="questions-list"></div>
        `;
          container.appendChild(cat);
          bindCategoryActions(cat);

          fetch(`get_question.php?category_id=${c.id}`)
            .then(r => r.json())
            .then(qs => {
              const list = cat.querySelector(".questions-list");
              qs.forEach(q => {
                const item = document.createElement("div");
                item.className = "question-item";
                item.id = `q-${q.id}`;
                item.dataset.question_id = q.id;
                item.innerHTML = `
                <span class="question-text">${q.question_text}</span>
                <div class="actions">
                  <button class="edit-btn"><i class="ph ph-pencil-simple"></i></button>
                  <button class="delete-btn"><i class="ph ph-trash"></i></button>
                </div>
              `;
                list.appendChild(item);
                bindQuestionActions(item);
              });
              totalQuestions += qs.length;
              document.getElementById("total-questions").innerText = totalQuestions;
            })
            .catch(err => console.error("Failed to load questions:", err));
        });
        document.getElementById("criteria-section").style.display = "block";
        updateCriteriaLockUI();
      })
      .catch(err => console.error("Failed to load categories:", err));
  }

  /**
   * Update the weight hint in the add/edit category modal.
   * editCatId: the numeric id of the category being edited, or null for new.
   */
  function updateWeightHint(editCatId) {
    const hintEl = document.getElementById("weightHint");
    const badgeEl = document.getElementById("weightSumBadge");
    const wInput = document.getElementById("category-weight");
    if (!hintEl) return;

    const usedWeight = getCurrentWeightSum(editCatId);
    const remaining = Math.max(0, Math.round((100 - usedWeight) * 100) / 100);
    const enteredWeight = wInput ? (parseFloat(wInput.value) || 0) : 0;
    const newTotal = Math.round((usedWeight + enteredWeight) * 100) / 100;

    // Hint text
    if (usedWeight >= 100) {
      hintEl.textContent = `⚠ All 100% is already allocated to other categories. Edit existing weights first.`;
      hintEl.style.color = "#ef4444";
    } else {
      hintEl.textContent = `Other categories use ${usedWeight}% — ${remaining}% available. ` +
        (remaining === 100 ? "Set 0 to split equally." : `Set 0 to auto-split.`);
      hintEl.style.color = newTotal > 100 ? "#ef4444" : "#64748b";
    }

    // Badge on the label
    if (badgeEl) {
      if (usedWeight >= 100) {
        badgeEl.textContent = "100% used";
        badgeEl.style.background = "#fecaca";
        badgeEl.style.color = "#dc2626";
      } else {
        badgeEl.textContent = `${usedWeight}% used`;
        badgeEl.style.background = usedWeight > 100 ? "#fecaca" : "#e2e8f0";
        badgeEl.style.color = usedWeight > 100 ? "#dc2626" : "#475569";
      }
    }

    // Colour the weight input itself
    if (wInput) {
      if (enteredWeight > 0 && newTotal > 100) {
        wInput.style.borderColor = "#ef4444";
      } else if (enteredWeight > 0 && newTotal === 100) {
        wInput.style.borderColor = "#10b981";
      } else {
        wInput.style.borderColor = "";
      }
    }
  }

  // Update hint as admin types in weight field
  document.addEventListener("input", e => {
    if (e.target.id === "category-weight") {
      const editId = addCategoryModal.dataset.editId
        ? addCategoryModal.dataset.editId.replace("cat-", "")
        : null;
      updateWeightHint(editId);
    }
  });

  document.addEventListener("DOMContentLoaded", loadCategories);

  // ========================= View Student Subjects =========================
  function viewStudentSubjects(student) {
    console.log("viewStudentSubjects called with student:", student);

    const modal = document.getElementById("viewStudentSubjectsModal");
    const subjectsList = document.getElementById("viewStudentSubjectsList");
    const studentNameElement = document.getElementById("viewStudentName");

    // Set student name in header
    studentNameElement.textContent = `${student.firstname} ${student.lastname} ${student.suffix || ""}`.trim();

    subjectsList.innerHTML = "<div class='loading'>Loading subjects...</div>";
    modal.style.display = "flex";

    console.log("Student subjects:", student.subjects);
    console.log("Is subjects array?", Array.isArray(student.subjects));

    // Display subjects that are already loaded with student data
    if (student.subjects && Array.isArray(student.subjects)) {
      if (student.subjects.length === 0) {
        subjectsList.innerHTML = "<div class='no-subjects'>No subjects assigned to this student</div>";
      } else {
        let html = "<div class='subjects-grid'>";
        student.subjects.forEach(subject => {
          html += `
          <div class='subject-card'>
            <div class='subject-header'>
              <strong>${subject.subject_code}</strong>
              <span class='year-badge'>${subject.year_level}</span>
            </div>
            <p class='subject-desc'>${subject.subject_desc}</p>
            ${subject.class_year_level || subject.class_section ? `<p class='subject-desc'><strong>Class:</strong> ${subject.class_year_level || ''}${subject.class_section ? ' - ' + subject.class_section : ''}</p>` : ''}
            <p class='subject-desc'><strong>Instructor:</strong> ${subject.instructor_name || 'Unassigned'}</p>
            ${subject.program_name ? `<span class='program-badge'>${subject.program_name}</span>` : ''}
          </div>
        `;
        });
        html += "</div>";
        subjectsList.innerHTML = html;
      }
    } else {
      console.log("Subjects not preloaded, fetching from server...");
      // If subjects aren't preloaded, fetch them
      fetch(`get_students.php`)
        .then(r => r.json())
        .then(students => {
          console.log("Fetched students:", students);
          const currentStudent = students.find(s => s.id == student.id);
          console.log("Found current student:", currentStudent);

          if (currentStudent && currentStudent.subjects) {
            console.log("Current student subjects:", currentStudent.subjects);
            if (currentStudent.subjects.length === 0) {
              subjectsList.innerHTML = "<div class='no-subjects'>No subjects assigned to this student</div>";
            } else {
              console.log("Displaying", currentStudent.subjects.length, "subjects");
              let html = "<div class='subjects-grid'>";
              currentStudent.subjects.forEach(subject => {
                console.log("Adding subject to display:", subject);
                html += `
                <div class='subject-card'>
                  <div class='subject-header'>
                    <strong>${subject.subject_code}</strong>
                    <span class='year-badge'>${subject.year_level}</span>
                  </div>
                  <p class='subject-desc'>${subject.subject_desc}</p>
                  ${subject.class_year_level || subject.class_section ? `<p class='subject-desc'><strong>Class:</strong> ${subject.class_year_level || ''}${subject.class_section ? ' - ' + subject.class_section : ''}</p>` : ''}
                  <p class='subject-desc'><strong>Instructor:</strong> ${subject.instructor_name || 'Unassigned'}</p>
                  ${subject.program_name ? `<span class='program-badge'>${subject.program_name}</span>` : ''}
                </div>
              `;
              });
              html += "</div>";
              subjectsList.innerHTML = html;
            }
          } else {
            console.log("No current student found or no subjects");
            subjectsList.innerHTML = "<div class='no-subjects'>No subjects assigned to this student</div>";
          }
        })
        .catch(err => {
          console.error("Error fetching student subjects:", err);
          subjectsList.innerHTML = "<div class='error'>Error loading subjects</div>";
        });
    }
  }

  // ========================= Forms Close ==========================================================================================
  // Removed global close button handler to prevent closing all modals

  // ------------------- Add Student -------------------
  function initAddStudentButton() {
    console.log("Initializing add student button...");
    const addStudentBtn = document.querySelector(".add-student-btn");
    console.log("Add student button found:", addStudentBtn);

    if (addStudentBtn) {
      addStudentBtn.onclick = () => {
        console.log("Add Student button clicked!");
        console.log("addStudentModal:", addStudentModal);
        openModal(addStudentModal, "ADD STUDENT", "SAVE STUDENT");
        console.log("Modal display style:", addStudentModal.style.display);

        // Force modal to be visible with higher z-index
        addStudentModal.style.display = "flex";
        addStudentModal.style.zIndex = "99999";
        addStudentModal.style.position = "fixed";
        addStudentModal.style.top = "0";
        addStudentModal.style.left = "0";
        addStudentModal.style.width = "100%";
        addStudentModal.style.height = "100%";
        addStudentModal.style.backgroundColor = "rgba(0,0,0,0.5)";
        addStudentModal.style.alignItems = "center";
        addStudentModal.style.justifyContent = "center";

        // Add close button event listener directly for Add Students modal
        setTimeout(() => {
          const closeBtn = document.querySelector("#addStudentModal .close-btn");
          console.log("Adding close handler for Add Students modal");
          console.log("Add Students close button:", closeBtn);

          if (closeBtn) {
            closeBtn.replaceWith(closeBtn.cloneNode(true));
            const newCloseBtn = document.querySelector("#addStudentModal .close-btn");
            newCloseBtn.addEventListener("click", (e) => {
              e.preventDefault();
              e.stopPropagation();
              console.log("Add Students close X clicked directly");
              addStudentModal.style.display = "none";
            });
          }
        }, 100);

        console.log("Modal forced display - current styles:", {
          display: addStudentModal.style.display,
          zIndex: addStudentModal.style.zIndex,
          position: addStudentModal.style.position
        });
        ["number", "email", "firstname", "lastname", "suffix", "yearlevel", "program", "section"].forEach(f => {
          const el = document.getElementById("student-" + f);
          if (el) {
            el.value = "";
            if (f === "number") el.disabled = false;
          }
        });
        const errorDiv = document.getElementById("student-number-error");
        errorDiv.textContent = "";

        // Reset student type to regular
        const regularRadio = document.querySelector('input[name="student-type"][value="regular"]');
        if (regularRadio) {
          regularRadio.checked = true;
        }

        // Load programs to ensure dropdown is populated
        loadStudentPrograms();

        // Handle student type radio buttons
        const studentTypeRadios = document.querySelectorAll('input[name="student-type"]');
        const yearlevelRow = document.getElementById('yearlevel-row');
        const yearlevelSelect = document.getElementById('student-yearlevel');
        const sectionSelect = document.getElementById('student-section');
        const subjectsSection = document.querySelector('.section-box:has(#add-subject-btn)');

        console.log('Found radio buttons:', studentTypeRadios.length);
        console.log('Found yearlevel row:', yearlevelRow);
        console.log('Found section select:', sectionSelect);

        // Remove existing listeners to avoid duplicates
        studentTypeRadios.forEach(radio => {
          radio.replaceWith(radio.cloneNode(true));
        });

        // Get fresh reference after cloning
        const freshRadios = document.querySelectorAll('input[name="student-type"]');

        freshRadios.forEach(radio => {
          radio.addEventListener('change', function () {
            console.log('Radio changed to:', this.value);

            // Ensure elements exist before trying to manipulate them
            if (!yearlevelRow || !yearlevelSelect || !sectionSelect) {
              console.error('Required elements not found');
              return;
            }

            if (this.value === 'irregular') {
              // Hide both year level and section for irregular students
              console.log('Hiding year level and section for irregular');
              yearlevelRow.style.setProperty('display', 'none', 'important');
              yearlevelSelect.required = false;
              yearlevelSelect.value = '';
              sectionSelect.required = false;
              sectionSelect.value = '';
              if (subjectsSection) subjectsSection.style.display = 'block';
            } else {
              // Show both year level and section for regular students
              console.log('Showing year level and section for regular');
              showYearSectionRow(yearlevelRow);
              yearlevelSelect.required = true;
              sectionSelect.required = true;
              if (subjectsSection) subjectsSection.style.display = 'none';
            }
          });
        });

        // Initially, since regular is checked, show year level and section, hide subjects
        showYearSectionRow(yearlevelRow);
        yearlevelSelect.required = true;
        sectionSelect.required = true;
        if (subjectsSection) subjectsSection.style.display = 'none';

        // Clear selected subjects to prevent edit checking issue
        selectedSubjects = [];
        updateSelectedSubjectsDisplay();

        let submitBtn = resetSubmitBtn("SAVE STUDENT");
        submitBtn.onclick = async e => {
          e.preventDefault();
          submitBtn.disabled = true;

          // GC- validation
          const numberVal = val("number");
          if (!numberVal.startsWith("GC-")) {
            errorDiv.textContent = "Student ID must start with GC-";
            submitBtn.disabled = false;
            return;
          } else {
            errorDiv.textContent = "";
          }

          const n = val("number"), eMail = val("email"), fName = val("firstname"), lName = val("lastname"),
            p = val("program"), section = val("section");

          const studentType = document.querySelector('input[name="student-type"]:checked').value;
          let y;
          if (studentType === 'irregular') {
            y = 'irregular';
          } else {
            y = val("yearlevel");
            if (!y) {
              showGlobalNotification("Please select year level for regular student", "warning");
              submitBtn.disabled = false;
              return;
            }
          }

          if (!n || !eMail || !fName || !lName || !p) {
            showGlobalNotification("Please fill all required fields", "warning");
            submitBtn.disabled = false;
            return;
          }

          // Clear subjects for regular students, keep only for irregular
          const subjectIds = (studentType === 'irregular')
            ? selectedSubjects.map(s => ({
              id: s.id,
              class_id: s.class_id || 0,
              class_year_level: s.class_year_level || "",
              class_section: s.class_section || ""
            }))
            : [];

          const res = await fetch("student_crud.php", {
            method: "POST", headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              action: "add",
              student_number: n,
              email: eMail,
              firstname: fName,
              lastname: lName,
              suffix: val("suffix"),
              yearlevel: y,
              program: p,
              section: section,
              subjects: subjectIds,
              student_type: studentType
            })
          });
          const d = await res.json();
          if (d.success) {
            loadStudents();
            // Force dashboard stats update regardless of current section
            setTimeout(() => {
              loadDashboardStats();
              console.log("Dashboard stats updated after student add");
            }, 500); // Increased delay to ensure DB commit
            closeModal(addStudentModal);
            showNotification(d.message || "Student added successfully. Password sent to email.", "#4caf50");
            addProgramToDropdown(p); // live add program
          } else {
            alert(d.message || "Error adding student.");
          }
          submitBtn.disabled = false;
        };
      };
      console.log("Add student button event listener attached successfully!");
    } else {
      console.error("Add student button not found!");
    }
  }

  // Initialize on DOM content loaded
  document.addEventListener("DOMContentLoaded", initAddStudentButton);

  // Also try to initialize immediately in case DOM is already loaded
  initAddStudentButton();

  // Test function for debugging dashboard updates
  window.testDashboardUpdate = function () {
    console.log("Testing dashboard update...");
    loadDashboardStats();
  };

  // Test function for delete dashboard update
  window.testDeleteDashboardUpdate = function () {
    console.log("Testing delete dashboard update...");
    deleteType = "student"; // Simulate student delete context
    loadDashboardStats();
  };

  // ========================= MANAGE PERIODS =========================

  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function parseDateOnly(dateString) {
    const [year, month, day] = String(dateString || "").split("-").map(Number);
    if (!year || !month || !day) return null;
    return new Date(year, month - 1, day);
  }

  function todayDateOnly() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
  }

  function isPastDate(dateString) {
    const date = parseDateOnly(dateString);
    return !!date && date < todayDateOnly();
  }

  function setPeriodSubmitMode(mode) {
    const addBtn = document.getElementById("add-period-btn");
    if (!addBtn) return;
    const isEdit = mode === "edit";
    addBtn.innerHTML = `<i class="ph ${isEdit ? "ph-floppy-disk" : "ph-plus"}"></i>${isEdit ? "Update Period" : "Add Period"}`;
  }

  function resetPeriodForm() {
    editingPeriodId = null;
    document.getElementById("period-ay").value = selectedAcademicYear || "";
    document.getElementById("period-sem").value = selectedClassSemester || "";
    document.getElementById("period-start").value = "";
    document.getElementById("period-end").value = "";
    setPeriodSubmitMode("add");
  }

  function formatPeriodName(semester, ay) {
    const sem = (semester || "").toString();
    let prefix = sem;
    if (sem.toLowerCase().includes("1st")) prefix = "1st";
    else if (sem.toLowerCase().includes("2nd")) prefix = "2nd";
    return `${prefix} ${ay || ""}`.trim();
  }

  function dashboardPeriodLabel() {
    if (!selectedAcademicYear || !selectedClassSemester) return "";
    return formatPeriodName(selectedClassSemester, selectedAcademicYear);
  }

  function updateDashboardPeriodBox(fallbackLabel = "") {
    const activeBox = document.getElementById("activePeriodBox");
    if (!activeBox) return;
    activeBox.textContent = fallbackLabel || "No Active Period";
  }

  function formatRange(startDate, endDate) {
    const start = parseDateOnly(startDate);
    const end = parseDateOnly(endDate);
    const startStr = start.toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" });
    const endStr = end.toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" });
    return `${startStr} - ${endStr}`;
  }

  async function refreshPeriodCard() {
    try {
      const res = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
      const data = await res.json();
      if (!data || !data.success) return;

      const activeBox = document.getElementById("activePeriodBox");
      const statusText = document.getElementById("evaluationStatusText");
      const btnClose = document.querySelector(".btn-close");

      updateDashboardPeriodBox(data.active_period_name || "");
      if (statusText) {
        statusText.textContent = data.evaluation_open ? "Evaluation is Open" : "Evaluation is Closed";
      }
      if (btnClose) {
        const hasActive = !!data.active_period_id;
        btnClose.style.display = (data.evaluation_open || hasActive) ? "inline-block" : "none";
        if (!hasActive && !data.evaluation_open) btnClose.style.display = "none";
      }

      // Store active period state globally for criteria lock
      window.hasActivePeriod = !!data.active_period_id;
      updateCriteriaLockUI();
    } catch (_) {
      // ignore
    }
  }

  function initPeriodCardControls() {
    const btnClose = document.querySelector(".btn-close");

    if (btnClose) {
      btnClose.addEventListener("click", async () => {
        try {
          // Closing the period should also remove active period
          await fetch("periods_api.php?action=deactivate", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({})
          });
        } finally {
          refreshPeriodCard();
        }
      });
    }
  }

  function initManagePeriodsButton() {
    const manageBtn = document.querySelector(".btn-manage");
    if (manageBtn) {
      manageBtn.addEventListener("click", () => {
        openManagePeriodsModal();
      });
    }
  }

  function openManagePeriodsModal() {
    resetPeriodForm();
    if (!selectedAcademicYear || !selectedClassSemester) {
      showNotification("Set academic year and semester on the dashboard first", "#f44336");
    }

    // Load existing periods
    loadPeriods();

    // Show modal
    managePeriodsModal.style.display = "flex";
    managePeriodsModal.style.zIndex = "9999";
  }

  async function loadPeriods() {
    const tbody = document.getElementById("periods-tbody");
    if (!tbody) return;

    tbody.innerHTML = `
    <tr>
      <td colspan="4" style="text-align:center; padding: 18px; color:#6b7280;">Loading...</td>
    </tr>
  `;

    try {
      const res = await fetch("periods_api.php?action=list", { cache: "no-store", credentials: "same-origin" });
      const data = await res.json();
      const periods = (data && data.success && Array.isArray(data.periods)) ? data.periods : [];

      if (periods.length === 0) {
        tbody.innerHTML = `
        <tr>
          <td colspan="4" style="text-align:center; padding: 18px; color:#6b7280;">No periods yet</td>
        </tr>
      `;
        return;
      }

      tbody.innerHTML = "";
      periods.forEach(p => {
        const tr = document.createElement("tr");
        const periodName = escapeHtml(p.semester || formatPeriodName(p.semester, p.ay));
        const ay = escapeHtml(p.ay);
        const duration = formatRange(p.start_date, p.end_date);

        const statusHtml = p.is_active
          ? `<button type="button" class="period-status-btn active" data-action="deactivate" data-id="${p.id}">Active</button>`
          : `<button type="button" class="period-status-btn inactive" data-action="set-active" data-id="${p.id}">Inactive</button>`;

        tr.innerHTML = `
        <td>
          <div class="period-name-cell">
            <span class="period-semester">${periodName}</span>
            <span class="period-ay">${ay}</span>
          </div>
        </td>
        <td>${duration}</td>
        <td>${statusHtml}</td>
        <td class="action-cell">
          <div class="action-buttons">
            <button class="edit-btn" data-action="edit" data-id="${p.id}" title="Edit Period">
              <i class="ph ph-pencil-simple"></i>
            </button>
          </div>
        </td>
      `;

        tbody.appendChild(tr);
      });

      // Delegate click handlers
      tbody.querySelectorAll("[data-action='set-active']").forEach(btn => {
        btn.addEventListener("click", async (e) => {
          const id = parseInt(e.currentTarget.dataset.id, 10);
          if (!id) return;
          const res = await fetch("periods_api.php?action=set_active", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
          });
          const result = await res.json().catch(() => null);
          if (!result || !result.success) {
            showAlertModal((result && result.message) || "Unable to activate period.");
          } else {
            showNotification(result.message || "Period activated.", "#10b981", 5000);
          }
          await loadPeriods();
          await refreshPeriodCard();
        });
      });

      tbody.querySelectorAll("[data-action='deactivate']").forEach(btn => {
        btn.addEventListener("click", async () => {
          await fetch("periods_api.php?action=deactivate", {
            method: "POST",
            credentials: "same-origin",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({})
          });
          await loadPeriods();
          await refreshPeriodCard();
        });
      });

      tbody.querySelectorAll("[data-action='edit']").forEach(btn => {
        btn.addEventListener("click", (e) => {
          const id = parseInt(e.currentTarget.dataset.id, 10);
          if (!id) return;
          const period = periods.find(item => Number(item.id) === id);
          if (period) editPeriod(period);
        });
      });
    } catch (err) {
      console.error("Failed to load periods:", err);
      tbody.innerHTML = `
      <tr>
        <td colspan="4" style="text-align:center; padding: 18px; color:#ef4444;">Failed to load periods</td>
      </tr>
    `;
    }
  }

  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  }

  function editPeriod(period) {
    document.getElementById("period-ay").value = period.ay;
    document.getElementById("period-sem").value = period.semester;
    document.getElementById("period-start").value = period.start_date;
    document.getElementById("period-end").value = period.end_date;
    editingPeriodId = Number(period.id);
    setPeriodSubmitMode("edit");
    document.querySelector("#managePeriodsModal .period-creation-section")?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function addPeriod() {
    const ay = document.getElementById("period-ay").value.trim();
    const sem = document.getElementById("period-sem").value;
    const start = document.getElementById("period-start").value;
    const end = document.getElementById("period-end").value;

    if (!ay || !sem || !start || !end) {
      showGlobalNotification("Please fill all fields", "warning");
      return;
    }
    if (editingPeriodId) {
      updatePeriod(editingPeriodId);
      return;
    }

    // Validate YYYY-MM-DD format
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(start) || !dateRegex.test(end)) {
      showAlertModal("Invalid date format.");
      return;
    }

    if (parseDateOnly(start) >= parseDateOnly(end)) {
      showAlertModal("End date must be after start date.");
      return;
    }
    if (isPastDate(start)) {
      showAlertModal("Start date cannot be in the past.");
      return;
    }

    // Persist
    fetch("periods_api.php?action=create", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ ay, semester: sem, start_date: start, end_date: end })
    })
      .then(r => r.json())
      .then(d => {
        if (d && d.success) {
          showNotification("Period added successfully!", "#4caf50");
          resetPeriodForm();
          loadPeriods();
        } else {
          alert((d && d.message) || "Error adding period.");
        }
      })
      .catch(() => alert("Error adding period."));
  }

  function updatePeriod(periodId) {
    const ay = document.getElementById("period-ay").value.trim();
    const sem = document.getElementById("period-sem").value;
    const start = document.getElementById("period-start").value;
    const end = document.getElementById("period-end").value;

    if (!ay || !sem || !start || !end) {
      showGlobalNotification("Please fill all fields", "warning");
      return;
    }

    // Validate YYYY-MM-DD format
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(start) || !dateRegex.test(end)) {
      showAlertModal("Invalid date format.");
      return;
    }

    if (parseDateOnly(start) >= parseDateOnly(end)) {
      showAlertModal("End date must be after start date.");
      return;
    }
    // Update via API
    fetch("periods_api.php?action=update", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: periodId, ay, semester: sem, start_date: start, end_date: end })
    })
      .then(r => r.json())
      .then(d => {
        if (d && d.success) {
          showNotification("Period updated successfully!", "#4caf50");
          resetPeriodForm();
          loadPeriods();
        } else {
          alert((d && d.message) || "Error updating period.");
        }
      })
      .catch(() => alert("Error updating period."));
  }

  // Add period button event
  document.getElementById("add-period-btn")?.addEventListener("click", addPeriod);

  // Close modal event
  managePeriodsModal?.querySelector(".close-btn")?.addEventListener("click", () => {
    managePeriodsModal.style.display = "none";
  });

  // Tooltip functionality for A.Y. field
  function initAYTooltip() {
    const ayInput = document.getElementById("period-ay");
    const tooltip = document.getElementById("ay-tooltip");

    if (ayInput && tooltip) {
      ayInput.addEventListener("focus", () => {
        tooltip.style.display = "block";
      });

      ayInput.addEventListener("blur", () => {
        tooltip.style.display = "none";
      });

      ayInput.addEventListener("input", () => {
        if (ayInput.value) {
          tooltip.style.display = "none";
        }
      });
    }
  }

  // Simple Calendar Click Handler
  function initCalendarClickHandlers() {
    const hideCalendars = () => {
      document.querySelectorAll('.calendar-picker').forEach(cal => {
        cal.style.display = 'none';
        cal.classList.remove('active');
      });
    };

    const openCalendar = (targetId) => {
      const calendar = document.getElementById(targetId + '-calendar');
      const input = document.getElementById(targetId);
      if (!calendar || !input) return;

      hideCalendars();

      // Keep the picker outside modal overflow/transform clipping.
      if (calendar.parentElement !== document.body) {
        document.body.appendChild(calendar);
      }

      const inputRect = input.getBoundingClientRect();
      const pickerWidth = 292;
      const left = Math.min(inputRect.left, window.innerWidth - pickerWidth - 12);
      const top = inputRect.bottom + 8;

      calendar.style.position = 'fixed';
      calendar.style.top = `${top}px`;
      calendar.style.left = `${Math.max(12, left)}px`;
      calendar.style.right = 'auto';
      calendar.style.zIndex = '100000';
      calendar.style.display = 'block';
      calendar.classList.add('active');

      const now = new Date();
      calendar.dataset.year = now.getFullYear();
      calendar.dataset.month = now.getMonth();
      generateCalendarDays(calendar, targetId);
    };

    document.querySelectorAll('.calendar-icon').forEach(icon => {
      icon.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openCalendar(icon.dataset.target);
      });
    });

    document.querySelectorAll('#period-start, #period-end').forEach(input => {
      input.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openCalendar(input.id);
      });
    });

    document.querySelectorAll('.calendar-nav').forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();

        const calendar = button.closest('.calendar-picker');
        if (!calendar) return;

        const targetId = calendar.id.replace('-calendar', '');
        const direction = button.dataset.direction === 'next' ? 1 : -1;
        const currentYear = Number(calendar.dataset.year || new Date().getFullYear());
        const currentMonth = Number(calendar.dataset.month || new Date().getMonth());
        const nextDate = new Date(currentYear, currentMonth + direction, 1);

        calendar.dataset.year = nextDate.getFullYear();
        calendar.dataset.month = nextDate.getMonth();
        generateCalendarDays(calendar, targetId);
      });
    });

    // Close calendar when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.classList.contains('calendar-icon') &&
        !e.target.closest('.calendar-picker')) {
        hideCalendars();
      }
    });
  }

  // Generate calendar days
  function generateCalendarDays(calendar, targetId) {
    const grid = calendar.querySelector('.calendar-grid');
    const monthYear = calendar.querySelector('.calendar-month-year');

    // Clear existing days (keep headers)
    const existingDays = grid.querySelectorAll('.calendar-day');
    existingDays.forEach(day => day.remove());

    const now = new Date();
    const year = Number(calendar.dataset.year || now.getFullYear());
    const month = Number(calendar.dataset.month || now.getMonth());

    // Update header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'];
    monthYear.textContent = `${monthNames[month]} ${year}`;

    // Get first day and days in month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Add empty cells
    for (let i = 0; i < firstDay; i++) {
      const emptyDay = document.createElement('div');
      emptyDay.className = 'calendar-day disabled';
      grid.appendChild(emptyDay);
    }

    // Add days
    for (let day = 1; day <= daysInMonth; day++) {
      const dayElement = document.createElement('div');
      dayElement.className = 'calendar-day';
      dayElement.textContent = day;
      const formattedDate = year + '-' +
        String(month + 1).padStart(2, '0') + '-' +
        String(day).padStart(2, '0');
      const isPastStartDate = targetId === "period-start" && isPastDate(formattedDate);

      // Highlight today
      if (day === now.getDate() && month === now.getMonth() && year === now.getFullYear()) {
        dayElement.classList.add('today');
      }

      if (isPastStartDate) {
        dayElement.classList.add('disabled');
        grid.appendChild(dayElement);
        continue;
      }

      // Add click event
      dayElement.addEventListener('click', () => {
        document.getElementById(targetId).value = formattedDate;
        calendar.style.display = 'none';
        calendar.classList.remove('active');
      });

      grid.appendChild(dayElement);
    }
  }

  // Initialize manage periods + period card controls (runs inside main DOMContentLoaded handler)
  initManagePeriodsButton();
  initAYTooltip();
  initCalendarClickHandlers();
  initPeriodCardControls();
  refreshPeriodCard();
  setInterval(() => {
    refreshPeriodCard();
    if (managePeriodsModal?.style.display === "flex") {
      loadPeriods();
    }
  }, 60000);

});

// Rating Distribution Donut Chart
function initRatingDistributionChart() {
  const canvas = document.getElementById('ratingDistributionChart');
  const chartWrapper = document.querySelector('.rating-chart-wrapper');
  const legendContainer = document.getElementById('ratingLegend');

  // Always show chart and legend
  if (chartWrapper) {
    chartWrapper.style.display = 'flex';
  }
  if (legendContainer) {
    legendContainer.style.display = 'flex';
  }

  // Remove "No ratings yet" text if it exists
  const existingNoRatings = document.querySelector('.no-ratings-text');
  if (existingNoRatings) {
    existingNoRatings.remove();
  }

  // Draw the chart with sample data
  if (canvas) {
    // Sample data for rating distribution
    const ratingData = [
      { label: 'Excellent', value: 15, color: '#1d4ed8' },
      { label: 'Very Good', value: 35, color: '#ab7dfa' },
      { label: 'Good', value: 25, color: '#f59e0b' },
      { label: 'Fair', value: 20, color: '#ffe16a' },
      { label: 'Poor', value: 5, color: '#ef4444' }
    ];

    drawRatingDonutChart(canvas, ratingData);
  }
}

function drawRatingDonutChart(canvas, data) {
  // Destroy existing chart instance if it exists
  if (window.ratingChartInstance) {
    window.ratingChartInstance.destroy();
  }

  const ctx = canvas.getContext('2d');

  // Prepare data for Chart.js
  const chartData = {
    labels: data.map(item => item.label),
    datasets: [{
      data: data.map(item => item.value),
      backgroundColor: data.map(item => item.color),
      borderWidth: 2,
      borderColor: '#ffffff'
    }]
  };

  // Create new Chart.js donut chart
  window.ratingChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: chartData,
    options: {
      responsive: false,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false // Hide default legend as we have custom legend
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.label || '';
              const value = context.parsed || 0;
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const percentage = Math.round((value / total) * 100);
              return `${label}: ${value} (${percentage}%)`;
            }
          }
        }
      },
      cutout: '60%', // Creates the donut effect
      animation: {
        animateRotate: true,
        animateScale: false
      }
    }
  });
}

// Initialize rating chart when page loads
document.addEventListener('DOMContentLoaded', function () {
  setTimeout(initRatingDistributionChart, 100);
});
