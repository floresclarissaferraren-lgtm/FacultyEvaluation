
document.addEventListener("DOMContentLoaded", () => {
  let currentActiveLink = null;
  let currentRow = null; 
  let deleteTarget = null;
  let deleteType = "";
  // ------------------- Notification Helper -------------------
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
  // ================= Sidebar Toggle ==========================================================================================
window.toggleSidebar=()=>{
  const s=document.getElementById("sidebar"),m=document.querySelector("main");
  if(window.innerWidth>768){s.classList.toggle("collapsed");m.classList.toggle("full");}
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

  // =========================
  // AUTO LOAD SECTIONS (DATA ONLY)
  // =========================
  if (id === "programs-section") loadPrograms?.();
  if (id === "faculties-section") loadFaculty?.();
  if (id === "criteria-section") loadCategories?.();
  if (id === "students-section") loadStudents?.();

  // =========================
  // Dashboard details toggle
  // =========================
  const dashboardDetails = document.getElementById("dashboard-details");
  if (dashboardDetails) {
    dashboardDetails.style.display =
      id === "dashboard-section" ? "flex" : "none";
  }
};

 // ================= Delete Forms Binago==========================================================================================
function openDeleteModal(type,name,el){
  deleteTarget = el;
  deleteType = type;
  document.getElementById("deleteMessage").innerText = `Do you want to delete ${type}? \n${name}`;
  document.getElementById("deleteWarning").innerText = `Warning: All details about this ${type} will be deleted.`;
  document.getElementById("deleteModal").style.display = "flex";
}

const closeDeleteModal=()=>{document.getElementById("deleteModal").style.display="none";deleteTarget=null;deleteType="";};
const confirmDelete = () => {
  if (!deleteTarget || !deleteType) return closeDeleteModal();

const cfg = {
  faculty: { url: "deleteFaculty.php", key: "faculty_id", type: "form" },
  program: { url: "deleteProgram.php", key: "program_code", type: "form" },
  category: { url: "delete_category.php", key: "category_id", type: "form" },   
  question: { url: "delete_question.php", key: "question_id", type: "form" },
  student: { url: "student_crud.php", key: "id", type: "json" } // student uses JSON
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
  fetchOptions = {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `${cfg.key}=${encodeURIComponent(val)}`
  };
}

fetch(cfg.url, fetchOptions)
  .then(r => r.json())
  .then(resp => {
    if (resp.success) {
      deleteTarget.remove();
      closeDeleteModal();
      openDeleteSuccess();
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
  document.getElementById("deleteSuccessModal").style.display = "flex";}
function closeDeleteSuccess(){
  document.getElementById("deleteSuccessModal").style.display = "none";}
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
  window.location.href = "http://localhost/FacultyEvaluation/EvalMain.php";}

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
  row.querySelector(".manage-btn")?.addEventListener("click",()=>{showSection("manage-section");
    
    document.getElementById("manageTitle").innerText=row.cells[1].innerText;showTable("subjects");});
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


// ========================= GLOBAL STATE =========================
let currentProgramId = null;
let currentSubjectId = null;

let isEditingSubject = false;
let editSubjectId = null;

// ========================= ELEMENTS =========================
const addManageBtn = document.querySelector(".add-manage-btn"),
      addSubjectModal = document.getElementById("addSubjectModal"),
      addClassModal = document.getElementById("addClassModal"),
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

// ========================= PROGRAM → MANAGE =========================
function attachProgramRowEvents(row) {
  row.querySelector(".manage-btn")?.addEventListener("click", () => {
    currentProgramId = row.dataset.id;

    showSection("manage-section");
    showTable("subjects");

    loadSubjects();
  });

  row.querySelector(".edit-btn")?.addEventListener("click", () => {
    document.getElementById("program-code").value = row.cells[0].innerText;
    document.getElementById("program-name").value = row.cells[1].innerText;
    document.getElementById("addProgramModal").style.display = "flex";
  });

  row.querySelector(".delete-btn")?.addEventListener("click", () => {
    openDeleteModal("program", row.cells[1].innerText, row);
  });
}

// ========================= OPEN MODALS =========================
addManageBtn?.addEventListener("click", () => {
  if (document.querySelector(".subject-btn")?.classList.contains("active")) {
    // SUBJECT MODAL
    addSubjectModal.style.display = "flex";

    document.getElementById("subject-code").value = "";
    document.getElementById("subject-desc").value = "";
    document.getElementById("subject-year").value = "";

    isEditingSubject = false;
    editSubjectId = null;

  } else {
    // CLASS MODAL
    addClassModal.style.display = "flex";

    document.getElementById("class-name").value = "";
    document.getElementById("class-year").value = "";

    document.getElementById("subject-checkbox-list").innerHTML = "";
  }
});

// ========================= LOAD SUBJECTS =========================
function loadSubjects() {
  if (!currentProgramId) return;

  fetch(`subject_crud.php?action=get&program_id=${currentProgramId}`)
    .then(r => r.json())
    .then(data => {
      subjectsTableBody.innerHTML = "";

      data.forEach(s => {
        const row = document.createElement("tr");

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
          document.getElementById("subject-year").value = s.year_level;

          addSubjectModal.style.display = "flex";
        });

        // DELETE SUBJECT
        row.querySelector(".delete-btn").addEventListener("click", (e) => {
          e.stopPropagation();

          fetch("subject_crud.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=delete&id=${s.id}`
          }).then(() => loadSubjects());
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

  if (!code || !desc || !year) return alert("Fill all fields");
  if (!currentProgramId) return alert("Select program first");

  const url = isEditingSubject
    ? `subject_crud.php`
    : `subject_crud.php`;

  const body = isEditingSubject
    ? `action=edit&id=${editSubjectId}&subject_code=${code}&subject_desc=${desc}&year_level=${year}`
    : `action=add&program_id=${currentProgramId}&subject_code=${code}&subject_desc=${desc}&year_level=${year}`;

  fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body
  }).then(() => {
    isEditingSubject = false;
    editSubjectId = null;
    addSubjectModal.style.display = "none";
    loadSubjects();
  });
});

// ========================= LOAD SUBJECTS BY YEAR =========================
function loadSubjectsByYear(yearLevel) {

  const box = document.getElementById("subject-checkbox-list");
  box.innerHTML = "Loading...";

  fetch(`subject_crud.php?action=get_by_year&program_id=${currentProgramId}&year_level=${yearLevel}`)
    .then(res => res.json())
    .then(data => {

      box.innerHTML = "";

      if (!data || data.length === 0) {
        box.innerHTML = "<small>No subjects found</small>";
        return;
      }

      data.forEach(sub => {

        const label = document.createElement("label");

        label.innerHTML = `
          <input type="checkbox" value="${sub.id}">
          ${sub.subject_code} - ${sub.subject_desc}
        `;

        box.appendChild(label);
      });

    })
    .catch(err => {
      console.error(err);
      box.innerHTML = "<small>Error loading subjects</small>";
    });
}

// ========================= AUTO LOAD ON YEAR CHANGE =========================
document.getElementById("class-year")?.addEventListener("change", (e) => {

  const year = e.target.value;
  const box = document.getElementById("subject-checkbox-list");

  box.innerHTML = "";

  if (!year) return;

  if (!currentProgramId) {
    box.innerHTML = "<small>Select program first</small>";
    return;
  }

  fetchSubjectsByYear(year);
});

document.addEventListener("DOMContentLoaded", () => {

  const yearSelect = document.getElementById("class-year");

  yearSelect?.addEventListener("change", (e) => {

    const year = e.target.value;
    const box = document.getElementById("subject-checkbox-list");

    box.innerHTML = "";

    if (!year) return;

    if (!currentProgramId) {
      box.innerHTML = "<small>Please select program first</small>";
      return;
    }

    loadSubjectsByYear(year);
  });

});



function fetchSubjectsByYear(yearLevel) {

  const box = document.getElementById("subject-checkbox-list");
  box.innerHTML = "Loading...";

  fetch(`subject_crud.php?action=get_by_year&program_id=${currentProgramId}&year_level=${yearLevel}`)
    .then(res => res.json())
    .then(data => {

      box.innerHTML = "";

      if (!data.length) {
        box.innerHTML = "<small>No subjects found</small>";
        return;
      }

      data.forEach(sub => {

        const label = document.createElement("label");

        label.innerHTML = `
          <input type="checkbox" value="${sub.id}">
          ${sub.subject_code} - ${sub.subject_desc}
        `;

        box.appendChild(label);
      });

    })
    .catch(err => {
      console.error(err);
      box.innerHTML = "<small>Error loading subjects</small>";
    });
}
// ========================= LOAD CLASSES =========================
function loadClasses() {
  if (!currentSubjectId) return;

  fetch(`classes_crud.php?action=get&subject_id=${currentSubjectId}`)
    .then(r => r.json())
    .then(data => {
      classesTableBody.innerHTML = "";

      data.forEach(c => {
        const row = document.createElement("tr");

        row.innerHTML = `
          <td>${c.section_name}</td>
          <td>${c.year_level}</td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </div>
          </td>
        `;

        row.querySelector(".delete-btn").addEventListener("click", () => {
          fetch("classes_crud.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=delete&id=${c.id}`
          }).then(() => loadClasses());
        });

        classesTableBody.appendChild(row);
      });
    });
}

// ========================= SAVE CLASS =========================
saveClassBtn?.addEventListener("click", () => {
  const name = document.getElementById("class-name").value.trim(),
        year = document.getElementById("class-year").value.trim();

  if (!name || !year) return alert("Fill all fields");
  if (!currentSubjectId) return alert("Select subject first");

  const checked = [...document.querySelectorAll("#subject-checkbox-list input:checked")]
    .map(cb => cb.value);

  if (checked.length === 0) return alert("Select subjects");

  fetch("classes_crud.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=add&subject_id=${currentSubjectId}&section_name=${name}&year_level=${year}&subjects=${JSON.stringify(checked)}`
  }).then(() => {
    addClassModal.style.display = "none";
    loadClasses();
  });
});

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
const addFacultyModal = document.getElementById("addFacultyModal"),
      facultyTbody = document.querySelector("#faculties-section tbody");

// ------------------- Load Faculty -------------------
function loadFaculty() {
  fetch("getFaculty.php")
    .then(r => r.json())
    .then(data => {
      facultyTbody.innerHTML = "";
      data.forEach(f => {
        const row = document.createElement("tr");
        row.id = "faculty-" + f.faculty_id;
        Object.assign(row.dataset, f);
        row.dataset.faculty_id = f.faculty_id;

        row.innerHTML = `
          <td>${f.photo ? `<img src="${f.photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">` : ""}</td>
          <td><strong>${f.faculty_id}</strong></td>
          <td><div>${f.firstname} ${f.lastname} ${f.suffix||""}</div><small>${f.email}</small></td>
          <td>No subjects yet</td>
          <td class="action-cell"><div class="action-buttons">
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

          addFacultyModal.dataset.editRow = f.faculty_id;
          openModal(addFacultyModal, "EDIT FACULTY", "UPDATE FACULTY");};

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

  addFacultyModal.querySelectorAll("input").forEach(i => {
    if (i.id !== "faculty-number") i.value = "";
  });

  document.getElementById("faculty-photo-preview").hidden = true;
  delete addFacultyModal.dataset.editRow;
  openModal(addFacultyModal, "ADD FACULTY", "SAVE FACULTY");
};

// SUBMIT (ADD/EDIT) ----------
addFacultyModal.querySelector(".submit-btn").onclick = () => {
  const n = document.getElementById("faculty-number").value.trim(),
        e = document.getElementById("faculty-email").value.trim(),
        f = document.getElementById("faculty-firstname").value.trim(),
        l = document.getElementById("faculty-lastname").value.trim(),
        s = document.getElementById("faculty-suffix").value.trim(),
        photo = document.getElementById("faculty-photo-preview").hidden ? "" : document.getElementById("faculty-photo-preview").src;

  if (!n || !e || !f || !l) return showNotification("Required fields missing!", "#f44336");

  const form = new URLSearchParams({
    faculty_id: n, 
    email: e,
    firstname: f,
    lastname: l,
    suffix: s,
    photo
  });

  const url = addFacultyModal.dataset.editRow ? "editFaculty.php" : "add_faculty.php";

  fetch(url, {
    method: "POST",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: form.toString()
  })
  .then(r => r.text())
  .then(resp => {
    if (resp === "success") {
      loadFaculty();
      closeModal(addFacultyModal);
      showNotification(
        addFacultyModal.dataset.editRow ? "Faculty updated successfully!" : "Faculty added successfully!",
        "#4caf50"
      );
    } else if (resp === "duplicate") {
      showNotification("Faculty ID already exists!", "#f44336");
    } else {
      showNotification("Error: " + resp, "#f44336");
    }
  })
  .catch(err => console.error("Save error:", err));
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
document.addEventListener("DOMContentLoaded", loadFaculty);

// ========================= Students dagdag ==========================================================================================
const addStudentModal = document.getElementById("addStudentModal"),
      studentTbody   = document.querySelector("#students-section tbody"),
      programSelect  = document.getElementById("student-program");

// ------------------- Helpers -------------------
function val(id){ return document.getElementById("student-"+id).value.trim(); }

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
  fetch("get_StudentProgram.php")
    .then(r => r.json())
    .then(data => {
      programs = data;
      programs.sort(); 
      programSelect.innerHTML = `<option value="" disabled selected>-- Select Program --</option>`;
      programs.forEach(name => {
        programSelect.insertAdjacentHTML("beforeend", `<option value="${name}">${name}</option>`);
      });
    })
    .catch(err => console.error("Error loading programs:", err));
}

//  Load Students -------------------
function loadStudents(){
  fetch("get_students.php")
    .then(r => r.json())
    .then(data => {
      studentTbody.innerHTML = "";
      data.forEach(stu => {
        const row = document.createElement("tr");
        row.dataset.id = stu.id;
        row.innerHTML = `
          <td>${stu.student_number}</td>
          <td><div>${stu.firstname} ${stu.lastname} ${stu.suffix||""}</div>
              <small style="color:#6b7280;">${stu.email}</small>
          </td>
          <td><div>${stu.yearlevel}${stu.section}</div>
              <small style="color:#6b7280;">${stu.program}</small>
          </td>
          <td class="action-cell">
            <div class="action-buttons">
              <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
              <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
            </div>
          </td>`;
        studentTbody.appendChild(row);

row.querySelector(".edit-btn").onclick = () => {
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
  document.getElementById("student-yearlevel").value = stu.yearlevel || "";
  document.getElementById("student-yearsection").value = stu.section || "";
  document.getElementById("student-program").value = stu.program || "";

  let submitBtn = resetSubmitBtn("UPDATE STUDENT");
  submitBtn.onclick = async e => {
    e.preventDefault(); 
    submitBtn.disabled = true;

    const res = await fetch("student_crud.php", {
      method:"POST",
      headers:{ "Content-Type":"application/json" },
      body: JSON.stringify({ 
        action:"edit", 
        id: stu.id,
        student_number: stu.student_number,
        email: val("email"), 
        firstname: val("firstname"),
        lastname: val("lastname"),
        suffix: val("suffix"),
        yearlevel: val("yearlevel"),
        section: val("yearsection"),
        program: val("program") 
      })
    });

    const d = await res.json();
    if(d.success){
      loadStudents();
      closeModal(addStudentModal);
      addProgramToDropdown(val("program"));
      showNotification("Student edited successfully!", "#4caf50");
    } else {
      showNotification(d.message || "Error updating student.", "#f44336");
    }
    submitBtn.disabled = false;
  };
};
        // Delete
        row.querySelector(".delete-btn").onclick = () => 
          openDeleteModal("student", `${stu.firstname} ${stu.lastname}`, row);
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
}
// ------------------- Add Student -------------------
document.querySelector(".add-student-btn").onclick = () => {
  openModal(addStudentModal,"ADD STUDENT","SAVE STUDENT");
  ["number","email","firstname","lastname","suffix","yearlevel","yearsection","program"].forEach(f=>{
    document.getElementById("student-"+f).value = "";
  });
  const errorDiv = document.getElementById("student-number-error");
  errorDiv.textContent = "";

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
          y=val("yearlevel"), sec=val("yearsection"), p=val("program");

    if(!n||!eMail||!fName||!lName||!y||!sec||!p){ 
      alert("Please fill all required fields."); 
      submitBtn.disabled = false; 
      return; }

    const res = await fetch("student_crud.php", {
      method:"POST", headers:{ "Content-Type":"application/json" },
      body: JSON.stringify({ action:"add", student_number:n, email:eMail, firstname:fName, lastname:lName,
                             suffix:val("suffix"), yearlevel:y, section:sec, program:p })
    });
    const d = await res.json();
    if(d.success){
      loadStudents();
      closeModal(addStudentModal);
      alert(d.message || "Student added successfully. Password sent to email.");
      addProgramToDropdown(p); // live add program
    } else {
      alert(d.message || "Error adding student.");
    }
    submitBtn.disabled = false;
  };
};

// ------------------- Initialize -------------------
loadStudentPrograms();
loadStudents();

// ========================= Categories & Questions dagdag========================= //
const addCategoryModal = document.getElementById("addCategoryModal"),
      addCategoryBtn = document.getElementById("addCategoryBtn"),
      saveCategoryBtn = document.getElementById("saveCategoryBtn"),
      addQuestionModal = document.getElementById("addQuestionModal"),
      questionCategoryName = document.getElementById("questionCategoryName"),
      saveQuestionBtn = document.getElementById("saveQuestionBtn");

// --- OPEN CATEGORY form ---
addCategoryBtn.onclick = () => {
  addCategoryModal.style.display = "flex";
  delete addCategoryModal.dataset.editId;
  ["category-name","section-number"].forEach(id => document.getElementById(id).value = "");
};

//  CLOSE form =========================
document.querySelectorAll(".close-btn").forEach(btn =>
  btn.onclick = () => btn.closest(".modal").style.display = "none"
);

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
};

//  CATEGORY ACTIONS ========================= //
function bindCategoryActions(cat) {
  cat.querySelector(".add-btn").onclick = () => {
    questionCategoryName.innerText = cat.querySelector(".category-name").innerText;
    addQuestionModal.style.display = "flex";
    addQuestionModal.dataset.targetId = cat.id;
    delete addQuestionModal.dataset.editId;
  };

  cat.querySelector(".edit-btn").onclick = () => {
    document.getElementById("category-name").value = cat.querySelector(".category-name").innerText;
    document.getElementById("section-number").value = cat.querySelector(".section-number").innerText.replace("SECTION ", "");
    addCategoryModal.style.display = "flex";
    addCategoryModal.dataset.editId = cat.id;
  };

  cat.querySelector(".delete-btn").onclick = () =>
    openDeleteModal("category", cat.querySelector(".category-name").innerText, cat);
}

function bindQuestionActions(item) {
  const cat = item.closest(".criteria-category");

  item.querySelector(".edit-btn").onclick = () => {
    document.getElementById("question-text").value = item.querySelector(".question-text").innerText;
    addQuestionModal.style.display = "flex";
    addQuestionModal.dataset.targetId = cat.id;
    addQuestionModal.dataset.editId = item.id;
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

// ========================= Forms Close ==========================================================================================
document.querySelectorAll(".close-btn").forEach(b=>b.addEventListener("click",()=>{
  [addProgramModal,addSubjectModal,addClassModal,addFacultyModal,addStudentModal,addCategoryModal,addQuestionModal].forEach(m=>m.style.display="none");
}));
window.addEventListener("click",e=>{
  [addProgramModal,addSubjectModal,addClassModal,addFacultyModal,addStudentModal,addCategoryModal,addQuestionModal]
  .forEach(m=>{if(e.target===m)m.style.display="none";});
});
  // ========================= Initialize ==========================================================================================
  showSection("dashboard-section");
  showTable('subjects');
});

