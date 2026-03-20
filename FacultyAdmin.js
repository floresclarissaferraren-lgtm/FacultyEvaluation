document.addEventListener("DOMContentLoaded", () => {
  let currentActiveLink = null, editRowProgram = null;

  // ========================= Sidebar Toggle =========================
window.toggleSidebar = () => {
  const sidebar = document.getElementById("sidebar");
  const main = document.querySelector("main");

  if (window.innerWidth > 768) {
    sidebar.classList.toggle("collapsed");
    main.classList.toggle("full");
  } else {
    sidebar.classList.remove("collapsed");
    sidebar.classList.toggle("active");
    main.style.marginLeft = "0";
    main.style.width = "100%";
  }
};

window.addEventListener("resize", () => {
  const sidebar = document.getElementById("sidebar");
  const main = document.querySelector("main");

  if (window.innerWidth > 768) {
    sidebar.classList.remove("active");
    sidebar.classList.remove("collapsed");
    main.style.marginLeft = "";
    main.style.width = "";
  } else {
    sidebar.classList.remove("collapsed");
    main.classList.remove("full");
  }
});

  // ========================= Section Switching =========================
  window.showSection = (id, event) => {
    document.querySelectorAll(".section").forEach(s => s.style.display = "none");
    const target = document.getElementById(id); if (target) target.style.display = "block";

    document.querySelectorAll(".sidebar a").forEach(l => l.classList.remove("active"));
    const link = event?.target.closest("a") || document.querySelector(`.sidebar a[data-section="${id}"]`);
    if (link) { link.classList.add("active"); currentActiveLink = link; }
  };

  // Sidebar link clicks
  document.querySelectorAll(".sidebar a").forEach(l => l.addEventListener("click", e => showSection(l.dataset.section, e)));

  // ========================= Program Modal =========================
  const addProgramModal = document.getElementById("addProgramModal"),
        programSubmitBtn = addProgramModal.querySelector(".submit-btn"),
        programHeader = addProgramModal.querySelector("h3"),
        programCodeInput = document.getElementById("program-code"),
        programNameInput = document.getElementById("program-name");

  // Open Add Program ============
  document.querySelector(".add-program-btn")?.addEventListener("click", () => {
    programHeader.innerText = "ADD PROGRAM";
    programSubmitBtn.innerText = "SAVE PROGRAM";
    programCodeInput.value = programNameInput.value = "";
    editRowProgram = null;
    addProgramModal.style.display = "flex";
  });

  // Close program modal ============
  document.querySelectorAll(".close-btn").forEach(b => b.addEventListener("click", () => addProgramModal.style.display = "none"));
  window.addEventListener("click", e => { if (e.target === addProgramModal) addProgramModal.style.display = "none"; });

  // Save Program ============
  programSubmitBtn.addEventListener("click", () => {
    const code = programCodeInput.value.trim(), name = programNameInput.value.trim();
    if (!code || !name) return alert("Please fill in both Program Code and Program Name.");
    if (editRowProgram) {
      editRowProgram.cells[0].innerText = code; editRowProgram.cells[1].innerText = name;
    } else {
      const row = document.createElement("tr");
      row.innerHTML = `<td>${code}</td><td>${name}</td><td class="action-cell">
        <div class="action-buttons">
          <button class="manage-btn"><i class="fas fa-sliders-h"></i></button>
          <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
          <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
        </div></td>`;
      document.querySelector(".programs-table tbody").appendChild(row);
      attachProgramRowEvents(row);
    }
    programCodeInput.value = programNameInput.value = ""; addProgramModal.style.display = "none"; editRowProgram = null;
  });

  //  Manage/Edit/Delete ====================================================
  function attachProgramRowEvents(row) {
    row.querySelector(".manage-btn")?.addEventListener("click", () => {
      showSection("manage-section");
      document.getElementById("manageTitle").innerText = `Subjects for ${row.cells[1].innerText}`;
      showTable('subjects');
    });
    row.querySelector(".edit-btn")?.addEventListener("click", () => {
      programHeader.innerText = "EDIT PROGRAM"; programSubmitBtn.innerText = "UPDATE PROGRAM";
      programCodeInput.value = row.cells[0].innerText; programNameInput.value = row.cells[1].innerText;
      editRowProgram = row; addProgramModal.style.display = "flex";
    });
    row.querySelector(".delete-btn")?.addEventListener("click", () => { if (confirm("Delete this program?")) row.remove(); });
  }
  document.querySelectorAll(".programs-table tbody tr").forEach(attachProgramRowEvents);

  // ========================= Manage Section Tabs =========================
  function showTable(tab) {
    const s = document.getElementById("subjects"), c = document.getElementById("classes"),
          sb = document.querySelector(".subject-btn"), cb = document.querySelector(".classes-btn"),
          title = document.getElementById("manageTitle");
    if (tab === 'subjects') {
      s.style.display = "block"; c.style.display = "none"; sb.classList.add("active"); cb.classList.remove("active");
      title.innerText = "Subjects for BSIT";
    } else {
      s.style.display = "none"; c.style.display = "block"; sb.classList.remove("active"); cb.classList.add("active");
      title.innerText = "Classes for BSIT";
    }
  }
  window.showTable = showTable;
  document.querySelector(".back-btn").addEventListener("click", () => showSection("programs-section"));

  // ========================= Add/Edit Subject =========================
  const addSubjectModal = document.getElementById("addSubjectModal"),
        saveSubjectBtn = document.getElementById("save-subject-btn"),
        addManageBtn = document.querySelector(".add-btn");

  addManageBtn.addEventListener("click", () => alert("Add form not yet available."));
  addSubjectModal.querySelector(".close-btn").addEventListener("click", () => addSubjectModal.style.display = "none");

  function defaultSaveSubject() { saveSubjectBtn.click(); }

  saveSubjectBtn.addEventListener("click", () => {
    const code = document.getElementById("subject-code").value.trim(),
          desc = document.getElementById("subject-desc").value.trim(),
          year = document.getElementById("subject-year").value.trim();
    if (!code || !desc || !year) return alert("Please fill in all fields.");
    const row = document.createElement("tr");
    row.innerHTML = `<td>${code}</td><td>${desc}</td><td>${year}</td>
      <td class="action-cell">
        <div class="action-buttons">
          <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
          <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
        </div>
      </td>`;
    document.querySelector("#subjects tbody").appendChild(row);

    row.querySelector(".edit-btn").addEventListener("click", () => editSubject(row));
    row.querySelector(".delete-btn").addEventListener("click", () => { if (confirm("Delete this subject?")) row.remove(); });

    document.getElementById("subject-code").value = document.getElementById("subject-desc").value = document.getElementById("subject-year").value = "";
    addSubjectModal.style.display = "none";
  });

  function editSubject(row) {
    addSubjectModal.querySelector("h3").innerText = "EDIT SUBJECT"; saveSubjectBtn.innerText = "UPDATE SUBJECT";
    ["code","desc","year"].forEach(id => document.getElementById(`subject-${id}`).value = row.cells[["code","desc","year"].indexOf(id)].innerText);
    addSubjectModal.style.display = "flex";
    saveSubjectBtn.onclick = () => {
      const code = document.getElementById("subject-code").value.trim(),
            desc = document.getElementById("subject-desc").value.trim(),
            year = document.getElementById("subject-year").value.trim();
      if (!code || !desc || !year) return alert("Please fill in all fields.");
      row.cells[0].innerText = code; row.cells[1].innerText = desc; row.cells[2].innerText = year;
      addSubjectModal.style.display = "none";
      addSubjectModal.querySelector("h3").innerText = "ADD SUBJECT"; saveSubjectBtn.innerText = "SAVE SUBJECT"; saveSubjectBtn.onclick = defaultSaveSubject;
    };
  }

  document.querySelector(".subject-btn").addEventListener("click", () => showTable('subjects'));
  document.querySelector(".classes-btn").addEventListener("click", () => showTable('classes'));

  // ========================= Initialize =========================
  showSection("dashboard-section"); showTable('subjects');

  // ========================= Add student =========================
  const openModal = (modal, header, btnText) => {
    modal.querySelector("h3").innerText = header;
    modal.querySelector(".submit-btn").innerText = btnText;
    modal.querySelectorAll("input, select").forEach(i => i.value = "");
    if(modal.querySelector(".subjects-list")) modal.querySelector(".subjects-list").innerHTML = "";
    modal.style.display = "flex";
  };

  // Utility: close modal
  const closeModal = modal => {
    modal.style.display = "none";
  };

  
// ================= FACULTY =================
const addFacultyModal = document.getElementById("addFacultyModal"),
      facultyTbody = document.querySelector("#faculties-section tbody");

document.querySelector(".add-faculty-btn").addEventListener("click", () => 
  openModal(addFacultyModal, "ADD FACULTY", "SAVE FACULTY")
);

addFacultyModal.querySelector(".close-btn").addEventListener("click", () => closeModal(addFacultyModal));
window.addEventListener("click", e => { if(e.target === addFacultyModal) closeModal(addFacultyModal); });

// Profile upload preview logic
document.getElementById("faculty-photo").addEventListener("change", e => {
  const file = e.target.files[0];
  if(file){
    const reader = new FileReader();
    reader.onload = ev => {
      const preview = document.getElementById("faculty-photo-preview");
      preview.src = ev.target.result;
      preview.hidden = false;
    };
    reader.readAsDataURL(file);
  }
});

addFacultyModal.querySelector(".submit-btn").addEventListener("click", () => {
  const n = document.getElementById("faculty-number").value.trim(),
        e = document.getElementById("faculty-email").value.trim(),
        f = document.getElementById("faculty-firstname").value.trim(),
        l = document.getElementById("faculty-lastname").value.trim(),
        s = document.getElementById("faculty-suffix").value.trim(),
        photoSrc = document.getElementById("faculty-photo-preview").src;

  if(!n || !e || !f || !l) return alert("Please fill in all required fields.");

  const row = document.createElement("tr");
 row.innerHTML = `
  <td>
    ${photoSrc ? `<img src="${photoSrc}" alt="Faculty Photo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">` : ``}
  </td>
  <td>${n}</td>
  <td>
    <div>${f} ${l} ${s}</div>
    <small style="color:#6b7280;">${e}</small>
  </td>
  <td><div class="faculty-subjects">No subjects yet</div></td>
  <td class="action-cell">
    <div class="action-buttons">
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
      <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
    </div>
  </td>
`;

  facultyTbody.appendChild(row);

  // Edit/Delete actions
  row.querySelector(".edit-btn").addEventListener("click", () => 
    openModal(addFacultyModal, "EDIT FACULTY", "UPDATE FACULTY")
  );
  row.querySelector(".delete-btn").addEventListener("click", () => { 
    if(confirm("Delete?")) row.remove(); 
  });

  closeModal(addFacultyModal);
});

 // ================= STUDENT =================
const addStudentModal = document.getElementById("addStudentModal"),
      studentTbody = document.querySelector("#students-section tbody");

document.querySelector(".add-student-btn").addEventListener("click", () => 
  openModal(addStudentModal, "ADD STUDENT", "SAVE STUDENT")
);

addStudentModal.querySelector(".close-btn").addEventListener("click", () => closeModal(addStudentModal));
window.addEventListener("click", e => { if(e.target === addStudentModal) closeModal(addStudentModal); });

addStudentModal.querySelector(".submit-btn").addEventListener("click", () => {
  const n   = document.getElementById("student-number").value.trim(),
        e   = document.getElementById("student-email").value.trim(),
        f   = document.getElementById("student-firstname").value.trim(),
        l   = document.getElementById("student-lastname").value.trim(),
        s   = document.getElementById("student-suffix").value.trim(),
        y   = document.getElementById("student-yearlevel").value,
        sec = document.getElementById("student-yearsection").value,
        p   = document.getElementById("student-program").value;

  if(!n||!e||!f||!l||!y||!sec||!p) return alert("Please fill all required fields.");

  const row = document.createElement("tr");
row.innerHTML = `
    <td>${n}</td>
    <td>
      <div>${f} ${l} ${s}</div>
      <small style="color:#6b7280;">${e}</small>
    </td>
    <td>${p}-${y.replace(" Year","")}${sec}</td>
    <td class="action-cell">
      <div class="action-buttons">
        <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
        <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
      </div>
    </td>
  `;
  studentTbody.appendChild(row);

  // Edit/Delete actions
  row.querySelector(".edit-btn").addEventListener("click", () => 
    openModal(addStudentModal, "EDIT STUDENT", "UPDATE STUDENT")
  );
  row.querySelector(".delete-btn").addEventListener("click", () => { 
    if(confirm("Delete?")) row.remove(); 
  });

  closeModal(addStudentModal);
});

});