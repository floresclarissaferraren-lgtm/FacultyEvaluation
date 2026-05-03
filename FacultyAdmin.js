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
  // ------------------- Notification==========================================================================================
function showNotification(message, color="#4caf50", duration=3000){
    const notif = document.getElementById("notification");
    notif.style.backgroundColor = color;
    notif.textContent = message;
    notif.style.display = "block";
    notif.style.opacity = 1;

    setTimeout(()=>{
        notif.style.transition = "opacity 0.5s";
        notif.style.opacity = 0;
        setTimeout(()=>{ notif.style.display = "none"; notif.style.transition = ""; }, 500);
    }, duration);
}

// Custom Notification Modal Functions
function showNotificationModal() {
    const modal = document.getElementById("notificationModal");
    modal.style.display = "flex";
    
    // Ensure OK button is clickable when modal is shown
    setTimeout(() => {
        const okBtn = document.getElementById("notificationModalOkBtn");
        if (okBtn) {
            okBtn.onclick = function() {
                closeNotificationModal();
            };
        }
    }, 100);
}

function closeNotificationModal() {
    const modal = document.getElementById("notificationModal");
    modal.style.display = "none";
    
    // Also close the add question modal
    const addQuestionModal = document.getElementById("addQuestionModal");
    if (addQuestionModal) {
        addQuestionModal.style.display = "none";
    }
}

// Setup notification modal event listeners
document.addEventListener("DOMContentLoaded", () => {
    const notificationModal = document.getElementById("notificationModal");
    const okBtn = document.getElementById("notificationModalOkBtn");
    
    if (notificationModal) {
        // Click outside to close
        notificationModal.addEventListener("click", (e) => {
            // Check if click is on modal backdrop (outside modal content)
            if (e.target === notificationModal) {
                closeNotificationModal();
            }
        });
        
        // Ensure modal has proper z-index
        notificationModal.style.zIndex = "10002";
    }
    
    if (okBtn) {
        // OK button click to close
        okBtn.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeNotificationModal();
        });
        
        // Ensure button is clickable
        okBtn.style.pointerEvents = "auto";
        okBtn.style.cursor = "pointer";
    }
});
  // ================= Sidebar Toggle ==========================================================================================
window.toggleSidebar=()=>{
  const s=document.getElementById("sidebar"),m=document.querySelector("main"),navbar=document.querySelector(".navbar");
  if(window.innerWidth>768){
    s.classList.toggle("collapsed");
    m.classList.toggle("full");
    
    // Directly manipulate navbar styles
    if(s.classList.contains("collapsed")){
      navbar.style.left="85px";
      navbar.style.width="calc(100% - 85px)";
    }else{
      navbar.style.left="260px";
      navbar.style.width="calc(100% - 260px)";
    }
  }
  else{s.classList.remove("collapsed");s.classList.toggle("active");m.style.marginLeft="0";m.style.width="100%";}
};

window.addEventListener("resize",()=>{
  const s=document.getElementById("sidebar"),m=document.querySelector("main");
  if(innerWidth>768){s.classList.remove("active","collapsed");m.style.marginLeft="";m.style.width="";}
  else{s.classList.remove("collapsed");m.classList.remove("full");}
});

document.addEventListener("click",e=>{
  const s=document.getElementById("sidebar");
  if(s.classList.contains("active") && !s.contains(e.target) && !e.target.closest(".hamburger")){
    s.classList.remove("active");
  }
  if(e.target.closest("#sidebar a")) s.classList.remove("active");
});


  // ================= Section Switching May nabago==========================================================================================
window.showSection = (id, e) => {
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
  const sectionTitles = {
    "dashboard-section": "Dashboard",
    "programs-section": "Program",
    "faculties-section": "Faculty List",
    "students-section": "Student List",
    "criteria-section": "Evaluation Criteria List",
    "report-section": "Evaluation Report",
    "subjects-section": "Subjects Management",
    "manage-section": "Manage Program"
  };
  
  if (navbarTitle && sectionTitles[id]) {
    navbarTitle.textContent = sectionTitles[id];
  }

  // =========================
  // AUTO LOAD SECTIONS (DATA ONLY)
  // =========================
  if (id === "dashboard-section") loadDashboardStats?.();
  if (id === "programs-section") loadPrograms?.();
  if (id === "faculties-section") loadFaculty?.();
  if (id === "criteria-section") loadCategories?.();
  if (id === "report-section") loadEvaluations?.();
  if (id === "students-section") loadStudents?.();
  if (id === "subjects-section") loadAllSubjects?.();

  // =========================
  // Dashboard details toggle
  // =========================
  const dashboardDetails = document.getElementById("dashboard-details");
  if (dashboardDetails) {
    dashboardDetails.style.display =
      id === "dashboard-section" ? "flex" : "none";
  }

  // =========================
  // Hide evaluation table from other sections
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

 // ================= Delete Forms ==========================================================================================
function openDeleteModal(type,name,el){
  console.log("openDeleteModal called with:", {type, name, el});
  
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
  modalsToClose.forEach(m => { if(m) m.style.display = "none"; });
  
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
  console.log("Modal display set to flex");
}

const closeDeleteModal=()=>{document.getElementById("deleteModal").style.display="none";deleteTarget=null;deleteType="";};
const confirmDelete = () => {
  if (!deleteTarget || !deleteType) return closeDeleteModal();

const cfg = {
  faculty: { url: "deleteFaculty.php", key: "faculty_id", type: "form" },
  program: { url: "deleteProgram.php", key: "program_code", type: "form" },
  category: { url: "delete_category.php", key: "category_id", type: "form" },   
  question: { url: "delete_question.php", key: "question_id", type: "form" },
  student: { url: "student_crud.php", key: "id", type: "json" }, // student uses JSON
  subject: { url: "subject_crud.php", key: "id", type: "form" }, // subject uses form
  class: { url: "classes_crud.php", key: "id", type: "form" } // class uses form
}[deleteType];

if (!cfg) return closeDeleteModal();

const val = deleteTarget.dataset[cfg.key] || deleteTarget.id.replace(`${deleteType}-`, "");

let fetchOptions;
if (cfg.type === "json") {
  // For students, send JSON with action + id
  fetchOptions = {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action: "delete", id: val })
  };
} else {
  // For other entities, send form-encoded
  let body = `${cfg.key}=${encodeURIComponent(val)}`;
  
  // Add action parameter for subjects
  if (deleteType === "subject") {
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
      deleteTarget.remove();
      closeDeleteModal();
      openDeleteSuccess();
      if (deleteType === "student") {
        // Delay dashboard update to ensure DB transaction is committed
        setTimeout(() => {
          loadDashboardStats();
          console.log("Dashboard stats updated after student delete");
        }, 500);
      }
    } else {
      alert("Error deleting: " + (resp.error || resp.message));
      closeDeleteModal();
    }
  })
  .catch(err => {
    console.error(err);
    alert("Unexpected error");
    closeDeleteModal();
  });

};

function openDeleteSuccess(){
  document.getElementById("deleteSuccessModal").style.display = "flex";
  // Also update dashboard when success modal is shown (for immediate feedback)
  if (deleteType === "student") {
    setTimeout(() => {
      loadDashboardStats();
      console.log("Dashboard stats updated on delete success modal");
    }, 100);
  }
}
function closeDeleteSuccess(){
  document.getElementById("deleteSuccessModal").style.display = "none";
  // Update dashboard when success modal is closed
  if (deleteType === "student") {
    loadDashboardStats();
    console.log("Dashboard stats updated on delete success modal close");
  }
  // Reset delete type
  deleteType = "";
}
document.getElementById("success-ok-btn").onclick = closeDeleteSuccess;

const modal=document.getElementById("deleteModal");
modal.querySelector(".cancel-btn").onclick=closeDeleteModal;
modal.querySelector(".submit-btn").onclick=confirmDelete;

// ===================== Logout Modal ==========================================================================================
const logoutModal = document.getElementById("logoutModal");
const logoutContent = logoutModal.querySelector(".logout-content");
const cancelBtn = logoutModal.querySelector(".cancel-btn");
const submitBtn = logoutModal.querySelector(".logout-btn");
const dropdownMenu = document.getElementById("dropdownMenu");
const dropdownToggle = document.getElementById("dropdownToggle");
const logoutLink = document.getElementById("logoutLink");

function showLogoutModal(e) {
  e.preventDefault();
  e.stopPropagation();
  logoutModal.style.display = "flex";
  dropdownMenu.classList.remove("show");}
function closeLogoutModal() {
  logoutModal.style.display = "none";}
function confirmLogout() {
  window.location.href = "EvalMain.php";}

logoutContent.addEventListener("click", e => e.stopPropagation());
logoutModal.addEventListener("click", closeLogoutModal);
cancelBtn.addEventListener("click", closeLogoutModal);
submitBtn.addEventListener("click", confirmLogout);

dropdownToggle.addEventListener("click", e => {
  e.stopPropagation();
  dropdownMenu.classList.toggle("show");
});

logoutLink.addEventListener("click", showLogoutModal);
dropdownMenu.addEventListener("click", e => e.stopPropagation());
document.addEventListener("click", () => {
  if (dropdownMenu.classList.contains("show")) dropdownMenu.classList.remove("show");
});
 // ========================= Programs ==========================================================================================
const addProgramModal=document.getElementById("addProgramModal"),
      programSubmitBtn=document.getElementById("save-program-btn"),
      programHeader=addProgramModal.querySelector("h3"),
      programCodeInput=document.getElementById("program-code"),
      programNameInput=document.getElementById("program-name");
let editRowProgram=null;

// OPEN ADD MODAL
document.querySelector(".add-program-btn")?.addEventListener("click",()=>{
  programHeader.innerText="ADD PROGRAM";programSubmitBtn.innerText="SAVE PROGRAM";
  programCodeInput.value="";programNameInput.value="";editRowProgram=null;
  addProgramModal.style.display="flex";
});

// SAVE / UPDATE
programSubmitBtn.addEventListener("click",()=>{
  const code=programCodeInput.value.trim(),name=programNameInput.value.trim();
  if(!code||!name)return alert("Please fill in both Program Code and Program Name.");

  const url=editRowProgram?"edit_program.php":"add_program.php";
  const body=editRowProgram?`id=${editRowProgram.dataset.id}&program_code=${encodeURIComponent(code)}
  &program_name=${encodeURIComponent(name)}`:`program_code=${encodeURIComponent(code)}&program_name=${encodeURIComponent(name)}`;

  fetch(url,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body})
  .then(r=>r.text()).then(t=>{try{return JSON.parse(t);}catch{return t;}})
  .then(res=>{
    const ok=(typeof res==="object"&&res.status==="success")||(typeof res==="string"&&res.toLowerCase().includes("success"));
    if(ok){loadPrograms();closeProgramModal(); 
    showNotification("Program edited successfully!", "#4caf50"); 
    }else alert(res.message||res||"Error updating Program.", "#f44336");
  }).catch(err=>{console.error("FETCH ERROR:",err);alert("Something went wrong.");});
});

// ATTACH ROW EVENTS
function attachProgramRowEvents(row){
  row.querySelector(".manage-btn")?.addEventListener("click",()=>{
    currentProgramId = row.dataset.id;
    showSection("manage-section");
    document.getElementById("manageTitle").innerText=row.cells[1].innerText;
    showTable("subjects");
    loadSubjects();
  });
  row.querySelector(".edit-btn")?.addEventListener("click",()=>{
    programHeader.innerText="EDIT PROGRAM";programSubmitBtn.innerText="UPDATE PROGRAM";
    programCodeInput.value=row.cells[0].innerText;programNameInput.value=row.cells[1].innerText;
    editRowProgram=row;addProgramModal.style.display="flex";
  });
  row.querySelector(".delete-btn")?.addEventListener("click",()=>openDeleteModal("program",row.cells[1].innerText,row));
}

// LOAD PROGRAMS
function loadPrograms(){
  fetch("getProgram.php")
    .then(r => r.json())
    .then(data => {
      const tbody = document.querySelector(".programs-table tbody");
      tbody.innerHTML = "";

      // Check if no data or empty array
      if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;">No programs found</td></tr>';
        return;
      }

      // Sort data alphabetically by program_code
      data.sort((a, b) => a.program_code.localeCompare(b.program_code));

      data.forEach(p => {
        const tr = document.createElement("tr");
        tr.dataset.id = p.id;
        tr.dataset.program_code = p.program_code;
        tr.innerHTML = `
          <td>${p.program_code}</td>
          <td>${p.program_name}</td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="manage-btn"><i class="fas fa-sliders-h"></i></button>
              <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </div>
          </td>`;
        tbody.appendChild(tr);
        attachProgramRowEvents(tr);
      });
    })
    .catch(err => console.error("FETCH ERROR:", err));
}
// CLOSE MODAL
function closeProgramModal(){programCodeInput.value="";programNameInput.value="";editRowProgram=null;addProgramModal.style.display="none";}

document.getElementById("program-search")?.addEventListener("input",e=>{
  const term=e.target.value.toLowerCase();
  const rows=document.querySelectorAll(".programs-table tbody tr");
  rows.forEach(r=>{
    r.style.display=r.textContent.toLowerCase().includes(term)?"":"none";
  });
});

document.addEventListener("DOMContentLoaded",loadPrograms);

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
    "deleteSuccessModal"
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

// ========================= MAIN SUBJECTS SECTION =========================
const addSubjectMainModal = document.getElementById("addSubjectMainModal"),
      saveMainSubjectBtn = document.getElementById("save-main-subject-btn"),
      subjectsMainTbody = document.getElementById("subjects-main-tbody");

function initAddSubjectMainBtn() {
  const addBtn = document.querySelector(".add-subject-main-btn");
  if (addBtn) {
    addBtn.addEventListener("click",()=>{
      console.log("Add Subject button clicked");
      // Clear form
      document.getElementById("main-subject-code").value = "";
      document.getElementById("main-subject-desc").value = "";
      document.getElementById("main-program-select").selectedIndex = 0;
      document.getElementById("main-year-select").selectedIndex = 0;
      
      // Load programs for dropdown
      loadProgramsForDropdown();
      
      addSubjectMainModal.style.display = "flex";
      addSubjectMainModal.style.zIndex = "9999";
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
saveMainSubjectBtn?.addEventListener("click",()=>{
  const code = document.getElementById("main-subject-code").value.trim(),
        desc = document.getElementById("main-subject-desc").value.trim(),
        program = document.getElementById("main-program-select").value,
        year = document.getElementById("main-year-select").value;
  
  if(!code||!desc||!program||!year){
    alert("Please fill all fields");
    return;
  }
  
  fetch("subject_crud.php",{
    method:"POST",
    headers:{"Content-Type":"application/x-www-form-urlencoded"},
    body:`action=add&program_id=${program}&subject_code=${encodeURIComponent(code)}&subject_desc=${encodeURIComponent(desc)}&year_level=${encodeURIComponent(year)}`
  })
  .then(r=>r.json())
  .then(res=>{
    if(res.status === "success"){
      addSubjectMainModal.style.display = "none";
      loadAllSubjects();
      showNotification("Subject added successfully!", "#4caf50");
    }else{
      alert("Error: " + (res.message || "Failed to add subject"));
    }
  })
  .catch(err=>{
    console.error("Error:", err);
    alert("Something went wrong");
  });
});

// LOAD ALL SUBJECTS
function loadAllSubjects(){
  fetch("subject_crud.php?action=get_all")
    .then(r=>r.json())
    .then(data=>{
      subjectsMainTbody.innerHTML = "";
      
      if(!data || data.length === 0){
        subjectsMainTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">No subjects found</td></tr>';
        return;
      }
      
      data.forEach(s=>{
        const row = document.createElement("tr");
        row.dataset.id = s.id;
        row.innerHTML = `
          <td>${s.subject_code}</td>
          <td>${s.subject_desc}</td>
          <td>${s.program_name || 'N/A'}</td>
          <td>${s.year_level}</td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </div>
          </td>
        `;
        
        // EDIT BUTTON
        row.querySelector(".edit-btn").addEventListener("click",()=>{
          // TODO: Implement edit functionality
          alert("Edit functionality coming soon");
        });
        
        // DELETE BUTTON
        row.querySelector(".delete-btn").addEventListener("click",()=>{
          openDeleteModal("subject", s.subject_code, row);
        });
        
        subjectsMainTbody.appendChild(row);
      });
    })
    .catch(err=>{
      console.error("Error loading subjects:", err);
      subjectsMainTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">Error loading subjects</td></tr>';
    });
}

// SEARCH SUBJECTS
document.getElementById("subjects-search")?.addEventListener("input",e=>{
  const term = e.target.value.toLowerCase();
  const rows = subjectsMainTbody.querySelectorAll("tr");
  rows.forEach(r=>{
    r.style.display = r.textContent.toLowerCase().includes(term) ? "" : "none";
  });
});

// CLOSE MODAL
addSubjectMainModal?.querySelector(".close-btn")?.addEventListener("click",()=>{
  addSubjectMainModal.style.display = "none";
});

// ========================= GLOBAL STATE =========================
let currentProgramId = null;
let currentSubjectId = null;

let isEditingSubject = false;
let editSubjectId = null;

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
        cb = document.querySelector(".classes-btn");

  if (!s || !c) return;

  if (tab === "subjects") {
    s.style.display = "block";
    c.style.display = "none";
    sb?.classList.add("active");
    cb?.classList.remove("active");
  } else {
    s.style.display = "none";
    c.style.display = "block";
    sb?.classList.remove("active");
    cb?.classList.add("active");
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
    document.getElementById("subject-code").value = "";
    document.getElementById("subject-desc").value = "";
    document.getElementById("subject-year").selectedIndex = 0;
    isEditingSubject = false;
    editSubjectId = null;
  } else if (classesTab) {
    // Open class modal
    const addClassModal = document.getElementById("addClassModal");
    addClassModal.style.display = "flex";
    addClassModal.dataset.isEditing = "false";
    delete addClassModal.dataset.editId;
    document.getElementById("class-year").selectedIndex = 0;
    document.getElementById("class-block").value = "";
    document.getElementById("subject-checkbox-list").innerHTML = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4><small>Select year level first to load subjects</small>';
    const facultyBox = document.getElementById("faculty-list");
    if (facultyBox) {
      facultyBox.dataset.facultyData = JSON.stringify({});
      facultyBox.innerHTML = '<h4><i class="fas fa-user-tie"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
    }
  }
});

// ========================= LOAD SUBJECTS =========================
function loadSubjects() {
  if (!currentProgramId) return;

  fetch(`subject_crud.php?action=get&program_id=${currentProgramId}`)
    .then(r => r.json())
    .then(data => {
      subjectsTableBody.innerHTML = "";

      // Check if no data or empty array
      if (!data || data.length === 0) {
        subjectsTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;">No Subject found for this program</td></tr>';
        return;
      }

      data.forEach(s => {
        const row = document.createElement("tr");
        row.dataset.id = s.id;

        row.innerHTML = `
          <td>${s.subject_code}</td>
          <td>${s.subject_desc}</td>
          <td>${s.year_level}</td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </div>
          </td>
        `;

        // SELECT SUBJECT
        row.addEventListener("click", () => {
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
          
          // Set dropdown value for year level
          const yearSelect = document.getElementById("subject-year");
          for (let i = 0; i < yearSelect.options.length; i++) {
            if (yearSelect.options[i].value === s.year_level) {
              yearSelect.selectedIndex = i;
              break;
            }
          }

          addSubjectModal.style.display = "flex";
        });

        // DELETE SUBJECT
        row.querySelector(".delete-btn").addEventListener("click", (e) => {
          e.stopPropagation();
          openDeleteModal("subject", s.subject_code, row);
        });

        subjectsTableBody.appendChild(row);
      });
    });
}

// ========================= SAVE SUBJECT =========================
saveSubjectBtn?.addEventListener("click", () => {
  const code = document.getElementById("subject-code").value.trim(),
        desc = document.getElementById("subject-desc").value.trim(),
        year = document.getElementById("subject-year").value.trim();

  console.log("Saving subject:", { code, desc, year, currentProgramId, isEditingSubject });

  if (!code || !desc || !year) return alert("Fill all fields");
  if (!currentProgramId) return alert("Select program first");

  const url = "subject_crud.php";

  const body = isEditingSubject
    ? `action=edit&id=${editSubjectId}&subject_code=${code}&subject_desc=${desc}&year_level=${year}`
    : `action=add&program_id=${currentProgramId}&subject_code=${code}&subject_desc=${desc}&year_level=${year}`;

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
  box.innerHTML = "Loading...";

  console.log(`Fetching subjects for program_id: ${currentProgramId}, year_level: ${yearLevel}`);

  fetch(`classes_crud.php?action=get_subjects_by_program_year&program_id=${currentProgramId}&year_level=${yearLevel}`)
    .then(res => {
      console.log("Response status:", res.status);
      return res.json();
    })
    .then(data => {
      console.log("Received data:", data);

      box.innerHTML = "";

      if (!data || data.length === 0) {
        box.innerHTML = `<h4><i class="fas fa-book"></i> Assigned Subjects</h4><small>No subjects found for year ${yearLevel}</small>`;
        if (typeof callback === "function") callback([]);
        return;
      }

      // Clear existing content but keep the header
      box.innerHTML = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4>';
      
      data.forEach(sub => {
        const label = document.createElement("label");
        label.innerHTML = `
          <input type="checkbox" value="${sub.id}">
          ${sub.subject_code} - ${sub.subject_desc}
        `;
        box.appendChild(label);
      });

      if (typeof callback === "function") callback(data);
    })
    .catch(err => {
      console.error("Error loading subjects:", err);
      box.innerHTML = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4><small>Error loading subjects</small>';
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
    return;
  }
  
  if (!currentProgramId) {
    console.log("No program ID set");
    box.innerHTML = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4><small>Please select a program first from the programs list, then click Manage</small>';
    return;
  }
  
  const facultyBox = document.getElementById("faculty-list");
  if (facultyBox) {
    facultyBox.dataset.facultyData = JSON.stringify({});
    facultyBox.innerHTML = '<h4><i class="fas fa-user-tie"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
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
          selectedFacultyId: selectedFacultyId || (faculty.length > 0 ? faculty[0].id : null)
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
  
  facultyBox.innerHTML = '<h4><i class="fas fa-user-tie"></i> Available Faculty</h4>';
  
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
      select.innerHTML = `<option value="">Assign teacher for this subject</option>` +
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
        classesTableBody.innerHTML = "<tr><td colspan='4'>No classes found for this program</td></tr>";
        return;
      }

      data.forEach(c => {
        const row = document.createElement("tr");
        row.dataset.id = c.id;

        row.innerHTML = `
          <td>${c.block}</td>
          <td>${c.year_level}</td>
          <td><span class="status-badge active">Loading...</span></td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="view-subjects-btn" title="View Subjects"><i class="fas fa-eye"></i></button>
              <button class="edit-btn" title="Edit Class"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn" title="Delete Class"><i class="fas fa-trash-alt"></i></button>
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
          addClassModal.dataset.isEditing = "true";
          addClassModal.dataset.editId = c.id;

          document.getElementById("class-year").value = c.year_level;
          document.getElementById("class-block").value = c.block || "";
          document.getElementById("subject-checkbox-list").innerHTML = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4><small>Loading subjects...</small>';

          const facultyBox = document.getElementById("faculty-list");
          if (facultyBox) {
            facultyBox.dataset.facultyData = JSON.stringify({});
            facultyBox.innerHTML = '<h4><i class="fas fa-user-tie"></i> Available Faculty</h4><small>Select subjects to show available faculty and assign teachers</small>';
          }

          loadSubjectsByYear(c.year_level, () => {
            fetch(`classes_crud.php?action=get&class_id=${c.id}`)
              .then(res => res.json())
              .then(subjects => {
                if (!Array.isArray(subjects)) return;
                subjects.forEach(subject => {
                  const checkbox = document.querySelector(`#subject-checkbox-list input[type='checkbox'][value='${subject.subject_id}']`);
                  if (checkbox) {
                    checkbox.checked = true;
                    const labelText = `${subject.subject_code} - ${subject.subject_desc}`;
                    loadFacultyBySubject(subject.subject_id, labelText, true, subject.faculty_id);
                  }
                });
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
    })
    .catch(err => {
      console.error("Error loading classes:", err);
      if (classesTableBody) {
        classesTableBody.innerHTML = `<tr><td colspan='4'>Error loading classes: ${err.message}</td></tr>`;
      }
    });
}

// ========================= CLOSE CLASS MODAL =========================
document.getElementById("addClassModal")?.querySelector(".close-btn")?.addEventListener("click", () => {
  document.getElementById("addClassModal").style.display = "none";
});

// ========================= SAVE CLASS =========================
saveClassBtn?.addEventListener("click", () => {
  const year = document.getElementById("class-year").value.trim();
  const block = document.getElementById("class-block").value.trim();
  const addClassModalEl = document.getElementById("addClassModal");

  if (!year || !block) return alert("Fill all fields");
  if (!currentProgramId) return alert("Select program first");

  const checked = [...document.querySelectorAll("#subject-checkbox-list input:checked")]
    .map(cb => cb.value);

  if (checked.length === 0) return alert("Select at least one subject");

  const assignments = {};
  checked.forEach(subjectId => {
    const select = document.querySelector(`#faculty-list select[data-subject-id="${subjectId}"]`);
    if (select && select.value) {
      assignments[subjectId] = select.value;
    } else {
      assignments[subjectId] = 0; // No faculty assigned yet
    }
  });

  const isEditing = addClassModalEl.dataset.isEditing === "true";
  const editId = addClassModalEl.dataset.editId;

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
    } else {
      alert("Error " + (isEditing ? "updating" : "adding") + " class: " + (res.message || "Unknown error"));
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
          <div class='subject-card'>
            <div class='subject-header'>
              <strong>${subject.subject_code}</strong>
              <span class='year-badge'>${subject.year_level || ''}</span>
            </div>
            <p class='subject-desc'>${subject.subject_desc || ''}</p>
            <div style='margin:8px 0 0; font-size:0.95rem; color:#4b5563;'>
              <i class='fas fa-user-tie' style='margin-right:5px;'></i>
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

// ========================= Forms ==========================================================================================
const openModal=(m,h,b)=>{
  m.querySelector("h3").innerText=h;
  m.querySelector(".submit-btn").innerText=b;
  m.style.display="flex";
  const fid=m.querySelector("#faculty-number"),fem=m.querySelector("#faculty-email"),
        sid=m.querySelector("#student-number"),sem=m.querySelector("#student-email");
  if(fid&&fem){fid.disabled=fem.disabled=h.includes("EDIT FACULTY");}
  if(sid&&sem){sid.disabled=sem.disabled=h.includes("EDIT STUDENT");}
};
const closeModal=m=>m.style.display="none";

// ================= Faculty ==========================================================================================
const facultyTbody = document.querySelector("#faculties-section tbody");

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
      
      // Check if no data or empty array
      if (!data || data.length === 0) {
        facultyTbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;">No faculty found</td></tr>';
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

        // Display subject codes or show "No subjects yet"
        console.log("Faculty subjects data:", f.subjects);
        console.log("Faculty subject_codes:", f.subject_codes);
        
        const subjectsDisplay = f.subject_codes && f.subject_codes.length > 0 
          ? f.subject_codes.map((code, index) => 
              `<span class="subject-item">${code}</span>`
            ).join('') 
          : '<span class="no-subjects">No subjects yet</span>';
          
        console.log("Subjects display:", subjectsDisplay);

        row.innerHTML = `
          <td>${f.photo ? `<img src="${f.photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">` : `<div style="width:40px;height:40px;border-radius:50%;background:#f3f4f6;display:flex;align-items:center;justify-content:center;"><i class="fas fa-user-tie" style="color:#6b7280;"></i></div>`}</td>
          <td><strong>${f.faculty_id}</strong></td>
          <td><div>${f.firstname} ${f.lastname} ${f.suffix||""}</div><small>${f.email}</small></td>
          <td>${subjectsDisplay}</td>
          <td class="action-cell"><div class="action-buttons">
            <button class="view-btn"><i class="fas fa-eye"></i></button>
            <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
            <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
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
              const assignedSubjectCodes = f.subjects.map(subject => subject.subject_code);
              const checkboxes = document.querySelectorAll('#faculty-subjects-list input[type="checkbox"]');
              checkboxes.forEach(checkbox => {
                const labelText = checkbox.parentElement.textContent.trim();
                const subjectCode = labelText.split(' - ')[0].trim();
                if (assignedSubjectCodes.includes(subjectCode)) {
                  checkbox.checked = true;
                }
              });
            }
          });

          addFacultyModal.dataset.editRow = f.id;
          openModal(addFacultyModal, "EDIT FACULTY", "UPDATE FACULTY");};

        // VIEW ----------------------
        row.querySelector(".view-btn").onclick = () => {
          // Set faculty name in modal
          document.getElementById("viewFacultyName").textContent = `${f.firstname} ${f.lastname} ${f.suffix || ""}`;
          
          // Show faculty subjects in modal
          const subjectsList = document.getElementById("viewFacultySubjectsList");
          if (f.subjects && f.subjects.length > 0) {
            subjectsList.innerHTML = '<div class="subjects-grid">' + f.subjects.map(subject => `
              <div class="subject-card">
                <div class="subject-header">
                  <strong>${subject.subject_code}</strong>
                  <span class="year-badge">${subject.year_level || ''}</span>
                </div>
                <p class="subject-desc">${subject.subject_desc || ''}</p>
                <span class="program-badge">${subject.program_name || ''}</span>
              </div>
            `).join('') + '</div>';
          } else {
            subjectsList.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 40px;">No subjects assigned yet</p>';
          }
          
          // Show modal
          viewFacultySubjectsModal.style.display = "flex";
        };

        // DELETE --------------------
        row.querySelector(".delete-btn").onclick = () => {
          deleteTarget = row;
          deleteType = "faculty";
          openDeleteModal("faculty", f.firstname + " " + f.lastname, row);};});})
    .catch(err => console.error("Load error:", err));}

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
    
    emailInput.addEventListener('input', function(e) {
      e.target.disabled = false;
      e.target.readOnly = false;
    });
    
    Object.defineProperty(emailInput, 'disabled', {
      get: function() { return false; },
      set: function(value) { return false; }
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
      headers: {"Content-Type": "application/json"},
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

// SEARCH -----------------------
document.getElementById("faculty-search").oninput = e => {
  const term = e.target.value.toLowerCase();
  [...facultyTbody.rows].forEach(r => r.style.display = r.textContent.toLowerCase().includes(term) ? "" : "none");
};
// ========================= Report Section =========================
function loadEvaluations() {
  // Check if we're in the report section, if not, don't load the table
  const currentSection = document.querySelector('.section:not([style*="display: none"])');
  if (!currentSection || currentSection.id !== 'report-section') {
    return; // Don't load evaluations if not in report section
  }
  
  fetch("get_evaluations.php")
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const tbody = document.getElementById("evaluationTableBody");
        tbody.innerHTML = "";
        
        data.data.forEach(evaluation => {
          const row = document.createElement("tr");
          row.innerHTML = `
            <td>
              <strong>${evaluation.name}</strong><br>
              <small>${evaluation.faculty_id}</small>
            </td>
            <td>${evaluation.average_score}</td>
            <td>
              <span class="badge ${evaluation.rating_class}">${evaluation.rating}</span>
            </td>
            <td>${evaluation.total_responses}</td>
            <td>
              <button class="view-btn" onclick="viewEvaluationDetails('${evaluation.id}')">
                <i class="fas fa-eye"></i> View
              </button>
            </td>
          `;
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
      }
    })
    .catch(err => console.error("Network error:", err));
}

function viewEvaluationDetails(facultyId) {
  // TODO: Implement detailed view modal
  console.log("View details for faculty:", facultyId);
}

// ========================= Dashboard Statistics =========================
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
        if (totalStudentsEl) {
          const oldValue = totalStudentsEl.textContent;
          totalStudentsEl.textContent = data.data.totalStudents;
          console.log("Updated totalStudents from", oldValue, "to:", data.data.totalStudents);
          // Add visual feedback for decrease
          totalStudentsEl.style.transition = "background-color 0.3s";
          totalStudentsEl.style.backgroundColor = "#ffebee"; // Light red for decrease
          setTimeout(() => {
            totalStudentsEl.style.backgroundColor = "";
          }, 500);
        }
        if (totalEvaluationsEl) totalEvaluationsEl.textContent = data.data.totalEvaluations;
        
        // Update faculty ratings table
        const ratingsBody = document.getElementById("ratings-body");
        ratingsBody.innerHTML = "";
        
        data.data.ratings.forEach(rating => {
          const row = document.createElement("tr");
          row.innerHTML = `
            <td>${rating.name}</td>
            <td>${rating.rating}</td>
          `;
          ratingsBody.appendChild(row);
        });
        
        // Update faculty ranking table
        const rankingBody = document.getElementById("ranking-body");
        rankingBody.innerHTML = "";
        
        data.data.ratings.forEach((rating, index) => {
          const row = document.createElement("tr");
          row.innerHTML = `
            <td>${index + 1}</td>
            <td>${rating.name}</td>
            <td>${rating.rating}</td>
          `;
          rankingBody.appendChild(row);
        });
        
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
    ctx.fillText(dept.name, x + barWidth/2, canvas.height - 5);
    
    // Draw count
    ctx.fillText(dept.count, x + barWidth/2, y - 5);
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
const studentTbody   = document.querySelector("#students-section table tbody"),
      programSelect  = document.getElementById("student-program");

// ------------------- Helpers -------------------
function val(id){ return document.getElementById("student-"+id).value.trim(); }

// Function to format year level (1 -> 1st Year, 2 -> 2nd Year, etc.)
function getFormattedYearLevel(yearLevel) {
  if (!yearLevel) return '';
  
  const year = parseInt(yearLevel);
  if (isNaN(year)) return yearLevel;
  
  const suffix = year === 1 ? 'st' : year === 2 ? 'nd' : year === 3 ? 'rd' : 'th';
  return `${year}${suffix} Year`;
}

// Function to get program name from program ID
function getProgramName(programId) {
  if (!programId) return '';
  
  const program = programs.find(p => p.id == programId);
  return program ? program.name : programId;
}

function resetSubmitBtn(text){
  let btn = addStudentModal.querySelector(".submit-btn");
  btn.replaceWith(btn.cloneNode(true));  
  btn = addStudentModal.querySelector(".submit-btn"); 
  btn.textContent = text;
  return btn;
}

//  Dynamic Program -------------------
let programs = []; 
function addProgramToDropdown(programName){
  if(programName && !programs.includes(programName)){
    programs.push(programName);
    programSelect.insertAdjacentHTML("beforeend", `<option value="${programName}">${programName}</option>`);
  }
}

//  Load Programs -------------------
function loadStudentPrograms(){
  return fetch("get_StudentProgram.php")
    .then(r => r.json())
    .then(data => {
      programs = data; // Store full program objects with id and name
      programSelect.innerHTML = `<option value="" disabled selected>-- Select Program --</option>`;
      data.forEach(program => {
        programSelect.insertAdjacentHTML("beforeend", `<option value="${program.id}">${program.name}</option>`);
      });
      return data; // Return data for chaining
    })
    .catch(err => {
      console.error("Error loading programs:", err);
      throw err; // Re-throw to maintain Promise rejection
    });
}

// Load All Subjects for Faculty Form -------------------
function loadAllFacultySubjects(){
  return fetch("subject_crud.php?action=get_all")
    .then(r => r.json())
    .then(data => {
      const subjectsList = document.getElementById("faculty-subjects-list");
      if (data.length === 0) {
        subjectsList.innerHTML = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4><small>No subjects available</small>';
      } else {
        let html = '<h4><i class="fas fa-book"></i> Assigned Subjects</h4>';
        data.forEach(subject => {
          html += `
            <label class="subject-checkbox-item">
              <input type="checkbox" value="${subject.id}" class="faculty-subject-checkbox">
              <span class="checkmark"></span>
              ${subject.subject_code} - ${subject.subject_desc}
            </label>
          `;
        });
        subjectsList.innerHTML = html;
      }
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
      subjectsList.innerHTML = '<p style="color: #6b7280; font-size: 0.9em;">No subjects found</p>';
    } else {
      let html = '<div class="faculty-subjects-checkboxes">';
      data.forEach(subject => {
        html += `
          <label class="subject-checkbox-item">
            <input type="checkbox" value="${subject.id}" class="faculty-subject-checkbox">
            <span class="checkmark"></span>
            ${subject.subject_code} - ${subject.subject_desc}
          </label>
        `;
      });
      html += '</div>';
      subjectsList.innerHTML = html;
    }
  })
  .catch(err => {
    console.error("Error fetching faculty subjects:", err);
    subjectsList.innerHTML = '<p style="color: #dc2626; font-size: 0.9em;">Error loading subjects</p>';
  });

//  Load Students -------------------
function loadStudents(){
  console.log("Loading students...");
  
  // First load programs, then load students
  loadStudentPrograms().then(() => {
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
        studentTbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px; color: red;">Error loading students</td></tr>';
        return;
      }
      
      studentTbody.innerHTML = "";
      
      // Check if data is empty or not an array
      if (!data || !Array.isArray(data) || data.length === 0) {
        console.log("No students found");
        studentTbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No students found</td></tr>';
        return;
      }
      
      data.forEach(stu => {
        console.log("Processing student:", stu);
        console.log("Student subjects:", stu.subjects);
        const row = document.createElement("tr");
        row.dataset.id = stu.id;
        row.innerHTML = `
          <td>${stu.student_number || ''}</td>
          <td><div>${stu.firstname || ''} ${stu.lastname || ''} ${stu.suffix||""}</div>
              <small style="color:#6b7280;">${stu.email || ''}</small>
          </td>
          <td><div>${getFormattedYearLevel(stu.yearlevel)}</div>
              <small style="color:#6b7280;">${getProgramName(stu.program)}</small>
          </td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="view-subjects-btn" title="View Subjects"><i class="fas fa-eye"></i></button>
              <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </div>
          </td>`;

        // Edit button event
        const editBtn = row.querySelector(".edit-btn");
        if (editBtn) {
          editBtn.onclick = () => {
            console.log("Edit button clicked for student:", stu);
            openModal(addStudentModal,"EDIT STUDENT","UPDATE STUDENT");

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
            const subjectsSection = document.querySelector('.section-box:has(#add-subject-btn)');
            
            if (stu.yearlevel === 'irregular') {
              document.querySelector('input[name="student-type"][value="irregular"]').checked = true;
              yearlevelRow.style.display = 'none';
              yearlevelSelect.required = false;
              yearlevelSelect.value = "";
              if (subjectsSection) subjectsSection.style.display = 'block';
            } else {
              document.querySelector('input[name="student-type"][value="regular"]').checked = true;
              yearlevelRow.style.display = 'flex';
              yearlevelSelect.required = true;
              document.getElementById("student-yearlevel").value = stu.yearlevel || "";
              if (subjectsSection) subjectsSection.style.display = 'none';
            }
            
            // Store student data for update
            addStudentModal.dataset.editId = stu.id;
            addStudentModal.dataset.studentData = JSON.stringify(stu);

            // Load existing subjects for this student
            selectedSubjects = [];
            if (stu.subjects && Array.isArray(stu.subjects)) {
              selectedSubjects = stu.subjects;
              console.log("Loading subjects for edit:", selectedSubjects);
              console.log("Number of subjects loaded:", selectedSubjects.length);
            } else {
              console.log("No subjects found for this student or subjects is not an array");
            }
            updateSelectedSubjectsDisplay();

            // Set program value immediately after loading programs
            loadStudentPrograms().then(() => {
              console.log("Programs loaded, setting program to:", stu.program);
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
              const subjectIds = (studentType === 'irregular') ? selectedSubjects.map(s => s.id) : [];
              let yearlevelValue;
              if (studentType === 'irregular') {
                yearlevelValue = 'irregular';
              } else {
                yearlevelValue = document.getElementById("student-yearlevel").value;
                if (!yearlevelValue) {
                  alert("Please select year level for regular student.");
                  submitBtn.disabled = false;
                  return;
                }
              }

              const res = await fetch("student_crud.php", {
                method:"POST",
                headers:{ "Content-Type":"application/json" },
                body: JSON.stringify({ 
                  action:"edit", 
                  id: studentData.id,
                  student_number: studentData.student_number,
                  email: document.getElementById("student-email").value.trim(), 
                  firstname: document.getElementById("student-firstname").value.trim(),
                  lastname: document.getElementById("student-lastname").value.trim(),
                  suffix: document.getElementById("student-suffix").value.trim(),
                  yearlevel: yearlevelValue,
                  program: document.getElementById("student-program").value,
                  section: document.getElementById("student-section").value.trim(),
                  subjects: subjectIds
                })
              });

              const d = await res.json();
              if(d.success){
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
        
        // Delete
        const deleteBtn = row.querySelector(".delete-btn");
        if (deleteBtn) {
          deleteBtn.onclick = () => {
            console.log("Delete button clicked for student:", stu);
            openDeleteModal("student", `${stu.firstname} ${stu.lastname}`, row);
          };
        } else {
          console.error("Delete button not found for student row:", stu);
        }
        
        studentTbody.appendChild(row);
      });
      // Search
      const searchInput = document.getElementById("student-search");
      if(searchInput) searchInput.oninput = e => {
        const term = e.target.value.toLowerCase();
        [...studentTbody.rows].forEach(r => 
          r.style.display = r.textContent.toLowerCase().includes(term) ? "" : "none"
        );
      };
    })
    .catch(err => console.error("Error loading students:", err));
  });
}

// ========================= Subject Selection for Students =========================
let selectedSubjects = [];
let allSubjects = [];

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
    // Only check if subject is in selectedSubjects AND we're not in a fresh add mode
    const isSelected = selectedSubjects.some(s => s.id === subject.id);
    
    row.innerHTML = `
      <td><input type="checkbox" value="${subject.id}" ${isSelected ? 'checked' : ''}></td>
      <td>${subject.subject_code}</td>
      <td>${subject.subject_desc}</td>
      <td>${subject.year_level}</td>
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
    .then(data => {
      allSubjects = data;
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
    alert("Please select a program first to view available subjects");
    return;
  }
  
  // Load subjects for the selected program and open popup
  loadSubjectsForProgramPopup(program);
  subjectSelectionModal.style.display = "flex";
  subjectSelectionModal.style.zIndex = "999999";
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

  // Add Faculty modal close button
  const facultyCloseBtn = document.querySelector("#addFacultyModal .close-btn");
  if (facultyCloseBtn) {
    facultyCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById("addFacultyModal").style.display = "none";
      console.log("Faculty modal closed by X button");
    };
  }

  // Add Program modal close button
  const programCloseBtn = document.querySelector("#addProgramModal .close-btn");
  if (programCloseBtn) {
    programCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById("addProgramModal").style.display = "none";
      console.log("Program modal closed by X button");
    };
  }

  // Add Category modal close button
  const categoryCloseBtn = document.querySelector("#addCategoryModal .close-btn");
  if (categoryCloseBtn) {
    categoryCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      addCategoryModal.style.display = "none";
      console.log("Category modal closed by X button");
    };
  }

  // Add Question modal close button
  const questionCloseBtn = document.querySelector("#addQuestionModal .close-btn");
  if (questionCloseBtn) {
    questionCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      addQuestionModal.style.display = "none";
      console.log("Question modal closed by X button");
    };
  }

  // Add Subject modal close button
  const subjectCloseBtn = document.querySelector("#addSubjectModal .close-btn");
  if (subjectCloseBtn) {
    subjectCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      addSubjectModal.style.display = "none";
      console.log("Subject modal closed by X button");
    };
  }

  // Add Subject Main modal close button
  const subjectMainCloseBtn = document.querySelector("#addSubjectMainModal .close-btn");
  if (subjectMainCloseBtn) {
    subjectMainCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      addSubjectMainModal.style.display = "none";
      console.log("Subject Main modal closed by X button");
    };
  }

  // Add Class modal close button
  const classCloseBtn = document.querySelector("#addClassModal .close-btn");
  if (classCloseBtn) {
    classCloseBtn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      document.getElementById("addClassModal").style.display = "none";
      console.log("Class modal closed by X button");
    };
  }
});

// Global close button handler for all modals
document.addEventListener("click", function(e) {
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
  
  console.log("Confirming subject selection. Checked checkboxes:", checkboxes.length);
  
  checkboxes.forEach(cb => {
    const subject = allSubjects.find(s => s.id == cb.value);
    if (subject) {
      // Check if subject is already in selectedSubjects to avoid duplicates
      const existingIndex = selectedSubjects.findIndex(s => s.id === subject.id);
      if (existingIndex === -1) {
        selectedSubjects.push(subject);
        console.log("Added subject:", subject);
      } else {
        console.log("Subject already exists, skipping:", subject);
      }
    }
  });
  
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
          <span>${subject.subject_code} - ${subject.subject_desc} (${subject.year_level})</span>
          <button type="button" class="remove-subject" data-id="${subject.id}" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-times"></i>
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
              <i class="fas fa-times"></i>
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

// --- OPEN CATEGORY form ---
addCategoryBtn.onclick = () => {
  addCategoryModal.style.display = "flex";
  delete addCategoryModal.dataset.editId;
  document.getElementById("categoryModalTitle").innerText = "ADD CATEGORY";
  ["category-name","section-number"].forEach(id => document.getElementById(id).value = "");
};

//  CLOSE form =========================

//  SAVE CATEGORY ========================= //
saveCategoryBtn.onclick = () => {
  const name = document.getElementById("category-name").value.trim(),
        sec  = document.getElementById("section-number").value.trim();
  if (!name || !sec) return alert("Fill all fields.");

  const payload = { category_name: name, section_number: sec };
  let url = "add_category.php";

  if (addCategoryModal.dataset.editId) {
    payload.id = addCategoryModal.dataset.editId.replace("cat-", "");
    url = "edit_category.php";
  }

  fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(d => d.success ? (
      loadCategories(),
      addCategoryModal.style.display = "none",
      delete addCategoryModal.dataset.editId
    ) : alert("Failed: " + d.message))
    .catch(err => console.error("Error saving category:", err));
};

//  SAVE QUESTION ========================= //
saveQuestionBtn.onclick = () => {
  const q = document.getElementById("question-text").value.trim();
  if (!q) return alert("Enter a question.");

  const catId = addQuestionModal.dataset.targetId.replace("cat-", "");
  
  // Check if this is a new question (not editing)
  if (!addQuestionModal.dataset.editId) {
    // Count existing questions for this category
    fetch(`get_question.php?category_id=${catId}`)
      .then(r => r.json())
      .then(questions => {
        if (questions.length >= 5) {
          showNotificationModal();
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
    .then(d => d.success ? (
      loadCategories(),
      addQuestionModal.style.display = "none",
      delete addQuestionModal.dataset.editId,
      document.getElementById("question-text").value = ""
    ) : alert("Failed: " + d.message))
    .catch(err => console.error("Error saving question:", err));
}

//  CATEGORY ACTIONS ========================= //
function bindCategoryActions(cat) {
  cat.querySelector(".add-btn").onclick = () => {
    questionCategoryName.innerText = cat.querySelector(".category-name").innerText;
    addQuestionModal.style.display = "flex";
    addQuestionModal.dataset.targetId = cat.id;
    delete addQuestionModal.dataset.editId;
    document.getElementById("questionModalTitle").innerText = "ADD QUESTION";
  };

  cat.querySelector(".edit-btn").onclick = () => {
    document.getElementById("category-name").value = cat.querySelector(".category-name").innerText;
    document.getElementById("section-number").value = cat.querySelector(".section-number").innerText.replace("SECTION ", "");
    addCategoryModal.style.display = "flex";
    addCategoryModal.dataset.editId = cat.id;
    document.getElementById("categoryModalTitle").innerText = "EDIT CATEGORY";
  };

  cat.querySelector(".delete-btn").onclick = () =>
    openDeleteModal("category", cat.querySelector(".category-name").innerText, cat);
}

function bindQuestionActions(item) {
  const cat = item.closest(".criteria-category");

  item.querySelector(".edit-btn").onclick = () => {
    document.getElementById("question-text").value = item.querySelector(".question-text").innerText;
    questionCategoryName.innerText = cat.querySelector(".category-name").innerText;
    addQuestionModal.style.display = "flex";
    addQuestionModal.dataset.targetId = cat.id;
    addQuestionModal.dataset.editId = item.id;
    document.getElementById("questionModalTitle").innerText = "EDIT QUESTION";
  };

  item.querySelector(".delete-btn").onclick = () =>
    openDeleteModal("question", item.querySelector(".question-text").innerText, item);
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

      cats.forEach(c => {
        const cat = document.createElement("div");
        cat.className = "criteria-category";
        cat.id = `cat-${c.id}`;
        cat.dataset.category_id = c.id;

        cat.innerHTML = `
          <div class="category-header">
            <div class="title-block">
              <i class="fas fa-chalkboard-teacher"></i>
              <div class="text-block">
                <div class="section-number">SECTION ${c.section_number}</div>
                <div class="category-name">${c.category_name}</div>
              </div>
            </div>
            <div class="action-buttons">
              <button class="add-btn"><i class="fa-solid fa-plus"></i></button>
              <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn"><i class="fas fa-trash-can"></i></button>
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
                  <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
                  <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
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
    })
    .catch(err => console.error("Failed to load categories:", err));
}

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
      openModal(addStudentModal,"ADD STUDENT","SAVE STUDENT");
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
      ["number","email","firstname","lastname","suffix","yearlevel","program","section"].forEach(f=>{
        document.getElementById("student-"+f).value = "";
      });
      const errorDiv = document.getElementById("student-number-error");
      errorDiv.textContent = "";
      
      // Load programs to ensure dropdown is populated
      loadStudentPrograms();
      
      // Handle student type radio buttons
      const studentTypeRadios = document.querySelectorAll('input[name="student-type"]');
      const yearlevelRow = document.getElementById('yearlevel-row');
      const yearlevelSelect = document.getElementById('student-yearlevel');
      const subjectsSection = document.querySelector('.section-box:has(#add-subject-btn)');
      
      studentTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
          if (this.value === 'irregular') {
            yearlevelRow.style.display = 'none';
            yearlevelSelect.required = false;
            if (subjectsSection) subjectsSection.style.display = 'block';
          } else {
            yearlevelRow.style.display = 'flex';
            yearlevelSelect.required = true;
            if (subjectsSection) subjectsSection.style.display = 'none';
          }
        });
      });
      
      // Initially, since regular is checked, show year level and hide subjects
      yearlevelRow.style.display = 'flex';
      yearlevelSelect.required = true;
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
        if(!numberVal.startsWith("GC-")){
          errorDiv.textContent = "Student ID must start with GC-";
          submitBtn.disabled = false;
          return;
        } else {
          errorDiv.textContent = "";
        }

        const n=val("number"), eMail=val("email"), fName=val("firstname"), lName=val("lastname"),
              p=val("program"), section=val("section");

        const studentType = document.querySelector('input[name="student-type"]:checked').value;
        let y;
        if (studentType === 'irregular') {
          y = 'irregular';
        } else {
          y = val("yearlevel");
          if (!y) {
            alert("Please select year level for regular student.");
            submitBtn.disabled = false;
            return;
          }
        }

        if(!n||!eMail||!fName||!lName||!p){ 
          alert("Please fill all required fields."); 
          submitBtn.disabled = false; 
          return; }

        // Clear subjects for regular students, keep only for irregular
        const subjectIds = (studentType === 'irregular') ? selectedSubjects.map(s => s.id) : [];

        const res = await fetch("student_crud.php", {
          method:"POST", headers:{ "Content-Type":"application/json" },
          body: JSON.stringify({ 
            action:"add", 
            student_number:n, 
            email:eMail, 
            firstname:fName, 
            lastname:lName,
            suffix:val("suffix"), 
            yearlevel:y, 
            program:p,
            section:section,
            subjects: subjectIds 
          })
        });
        const d = await res.json();
        if(d.success){
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
window.testDashboardUpdate = function() {
  console.log("Testing dashboard update...");
  loadDashboardStats();
};

// Test function for delete dashboard update
window.testDeleteDashboardUpdate = function() {
  console.log("Testing delete dashboard update...");
  deleteType = "student"; // Simulate student delete context
  loadDashboardStats();
};

// ========================= MANAGE PERIODS =========================
function initManagePeriodsButton() {
  const manageBtn = document.querySelector(".btn-manage");
  if (manageBtn) {
    manageBtn.addEventListener("click", () => {
      openManagePeriodsModal();
    });
  }
}

function openManagePeriodsModal() {
  // Clear form
  document.getElementById("period-ay").value = "";
  document.getElementById("period-sem").value = "1st Semester";
  document.getElementById("period-start").value = "";
  document.getElementById("period-end").value = "";
  
  // Load existing periods
  loadPeriods();
  
  // Show modal
  managePeriodsModal.style.display = "flex";
  managePeriodsModal.style.zIndex = "9999";
}

function loadPeriods() {
  const tbody = document.getElementById("periods-tbody");
  
  // Show empty table - no data
  tbody.innerHTML = `
    <tr>
      <td></td>
      <td></td>
      <td></td>
      <td class="action-cell">
        <div class="action-buttons">
        </div>
      </td>
    </tr>
  `;
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
  document.getElementById("period-sem").value = period.sem;
  document.getElementById("period-start").value = period.start;
  document.getElementById("period-end").value = period.end;
  
  // Change button text to update
  const addBtn = document.getElementById("add-period-btn");
  addBtn.textContent = "Update";
  addBtn.onclick = () => updatePeriod(period.id);
}

function addPeriod() {
  const ay = document.getElementById("period-ay").value.trim();
  const sem = document.getElementById("period-sem").value;
  const start = document.getElementById("period-start").value;
  const end = document.getElementById("period-end").value;
  
  if (!ay || !sem || !start || !end) {
    alert("Please fill all fields");
    return;
  }
  
  if (new Date(start) >= new Date(end)) {
    alert("End date must be after start date");
    return;
  }
  
  // For now, just show notification and reload
  // In production, you would make an API call here
  showNotification("Period added successfully!", "#4caf50");
  
  // Clear form and reload
  document.getElementById("period-ay").value = "";
  document.getElementById("period-sem").selectedIndex = 0;
  document.getElementById("period-start").value = "";
  document.getElementById("period-end").value = "";
  
  loadPeriods();
}

function updatePeriod(periodId) {
  const ay = document.getElementById("period-ay").value.trim();
  const sem = document.getElementById("period-sem").value;
  const start = document.getElementById("period-start").value;
  const end = document.getElementById("period-end").value;
  
  if (!ay || !sem || !start || !end) {
    alert("Please fill all fields");
    return;
  }
  
  if (new Date(start) >= new Date(end)) {
    alert("End date must be after start date");
    return;
  }
  
  // For now, just show notification and reload
  // In production, you would make an API call here
  showNotification("Period updated successfully!", "#4caf50");
  
  // Reset button and clear form
  const addBtn = document.getElementById("add-period-btn");
  addBtn.textContent = "Add";
  addBtn.onclick = addPeriod;
  
  document.getElementById("period-ay").value = "";
  document.getElementById("period-sem").selectedIndex = 0;
  document.getElementById("period-start").value = "";
  document.getElementById("period-end").value = "";
  
  loadPeriods();
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
  // Get calendar icons
  const calendarIcons = document.querySelectorAll('.calendar-icon');
  console.log('Found calendar icons:', calendarIcons.length); // Debug
  
  calendarIcons.forEach(icon => {
    icon.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log('Calendar icon clicked!'); // Debug
      
      const targetId = icon.dataset.target;
      const calendar = document.getElementById(targetId + '-calendar');
      
      console.log('Target ID:', targetId); // Debug
      console.log('Calendar element:', calendar); // Debug
      
      if (calendar) {
        // Hide all calendars first
        document.querySelectorAll('.calendar-picker').forEach(cal => {
          cal.style.display = 'none';
          cal.classList.remove('active');
        });
        
        // Show the clicked calendar
        calendar.style.display = 'block';
        calendar.classList.add('active');
        
        // Simple positioning - just below the input
        const input = document.getElementById(targetId);
        const inputRect = input.getBoundingClientRect();
        
        calendar.style.position = 'fixed';
        calendar.style.top = (inputRect.bottom + 5) + 'px';
        calendar.style.left = inputRect.left + 'px';
        calendar.style.zIndex = '10000';
        
        // Generate calendar days
        generateCalendarDays(calendar, targetId);
        
        console.log('Calendar should be visible now!'); // Debug
      }
    });
  });
  
  // Close calendar when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.classList.contains('calendar-icon') && 
        !e.target.closest('.calendar-picker')) {
      document.querySelectorAll('.calendar-picker').forEach(cal => {
        cal.style.display = 'none';
        cal.classList.remove('active');
      });
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
  
  // Current date
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth();
  
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
    
    // Highlight today
    if (day === now.getDate()) {
      dayElement.classList.add('today');
    }
    
    // Add click event
    dayElement.addEventListener('click', () => {
      const formattedDate = String(day).padStart(2, '0') + '/' + 
                           String(month + 1).padStart(2, '0') + '/' + year;
      document.getElementById(targetId).value = formattedDate;
      calendar.style.display = 'none';
      calendar.classList.remove('active');
    });
    
    grid.appendChild(dayElement);
  }
}

// Initialize manage periods button
document.addEventListener("DOMContentLoaded", () => {
  initManagePeriodsButton();
  initAYTooltip();
  initCalendarClickHandlers();
});
initManagePeriodsButton();
initAYTooltip();
initCalendarClickHandlers();

});