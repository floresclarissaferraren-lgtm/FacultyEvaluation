document.addEventListener("DOMContentLoaded", () => {
  let currentActiveLink = null;
  let currentRow = null; 

  // ================= Sidebar Toggle =================
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
      sidebar.classList.remove("active","collapsed");
      main.style.marginLeft = "";
      main.style.width = "";
    } else {
      sidebar.classList.remove("collapsed");
      main.classList.remove("full");
    }
  });

  // ================= Section Switching =================
  window.showSection = (id,e) => {
    document.querySelectorAll(".section").forEach(s=>s.style.display="none");
    const target=document.getElementById(id); if(target) target.style.display="block";
    document.querySelectorAll(".sidebar a").forEach(l=>l.classList.remove("active"));
    const link=e?.target.closest("a")||document.querySelector(`.sidebar a[data-section="${id}"]`);
    if(link){link.classList.add("active");currentActiveLink=link;}
  };
  document.querySelectorAll(".sidebar a").forEach(l=>l.addEventListener("click",e=>showSection(l.dataset.section,e)));

  // ================= User Menu Toggle =================
  window.toggleUserMenu = () => {
    const menu=document.getElementById("userDropdown");
    menu.style.display=menu.style.display==="block"?"none":"block";
  };
  window.logout = () => {
  };
  // ========================= Programs =========================
  const addProgramModal = document.getElementById("addProgramModal");
  const programSubmitBtn = document.getElementById("save-program-btn");
  const programHeader = addProgramModal.querySelector("h3");
  const programCodeInput = document.getElementById("program-code");
  const programNameInput = document.getElementById("program-name");

  document.querySelector(".add-program-btn")?.addEventListener("click", () => {
    programHeader.innerText = "ADD PROGRAM";
    programSubmitBtn.innerText = "SAVE PROGRAM";
    programCodeInput.value = programNameInput.value = "";
    editRowProgram = null;
    addProgramModal.style.display = "flex";
  });

  programSubmitBtn.addEventListener("click", () => {
    const code = programCodeInput.value.trim();
    const name = programNameInput.value.trim();
    if (!code || !name) return alert("Please fill in both Program Code and Program Name.");

    if(editRowProgram) {
      editRowProgram.cells[0].innerText = code;
      editRowProgram.cells[1].innerText = name;
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

    programCodeInput.value = programNameInput.value = "";
    addProgramModal.style.display = "none";
    editRowProgram = null;
  });

  function attachProgramRowEvents(row) {
    row.querySelector(".manage-btn")?.addEventListener("click", () => {
      showSection("manage-section");
      document.getElementById("manageTitle").innerText = `Subjects for ${row.cells[1].innerText}`;
      showTable('subjects');
    });
    row.querySelector(".edit-btn")?.addEventListener("click", () => {
      programHeader.innerText = "EDIT PROGRAM";
      programSubmitBtn.innerText = "UPDATE PROGRAM";
      programCodeInput.value = row.cells[0].innerText;
      programNameInput.value = row.cells[1].innerText;
      editRowProgram = row;
      addProgramModal.style.display = "flex";
    });
    row.querySelector(".delete-btn")?.addEventListener("click", () => row.remove());
  }
  document.querySelectorAll(".programs-table tbody tr").forEach(attachProgramRowEvents);

  // ========================= Manage Section (Subjects & Classes) =========================
  function showTable(tab) {
    const s = document.getElementById("subjects");
    const c = document.getElementById("classes");
    const sb = document.querySelector(".subject-btn");
    const cb = document.querySelector(".classes-btn");
    const title = document.getElementById("manageTitle");

    if(tab === 'subjects') {
      s.style.display = "block";
      c.style.display = "none";
      sb.classList.add("active");
      cb.classList.remove("active");
    } else {
      s.style.display = "none";
      c.style.display = "block";
      sb.classList.remove("active");
      cb.classList.add("active");
    }
  }

  document.querySelector(".back-btn").addEventListener("click", () => showSection("programs-section"));
  document.querySelector(".subject-btn").addEventListener("click", () => showTable('subjects'));
  document.querySelector(".classes-btn").addEventListener("click", () => showTable('classes'));

  const addManageBtn = document.querySelector(".add-manage-btn");
  const addSubjectModal = document.getElementById("addSubjectModal");
  const addClassModal = document.getElementById("addClassModal");
  const saveSubjectBtn = document.getElementById("save-subject-btn");
  const saveClassBtn = document.getElementById("save-class-btn");
  const subjectsTableBody = document.querySelector("#subjects tbody");
  const classesTableBody = document.querySelector("#classes tbody");

  addManageBtn.addEventListener("click", () => {
    if(document.querySelector(".subject-btn").classList.contains("active")) {
      addSubjectModal.style.display = "flex";
      addSubjectModal.querySelector("h3").innerText = "ADD SUBJECT";
      saveSubjectBtn.innerText = "SAVE SUBJECT";
      ["subject-code","subject-desc","subject-year"].forEach(id => document.getElementById(id).value = "");
      editingSubjectRow = null;
    } else if(document.querySelector(".classes-btn").classList.contains("active")) {
      addClassModal.style.display = "flex";
      addClassModal.querySelector("h3").innerText = "ADD CLASS";
      saveClassBtn.innerText = "SAVE CLASS";
      ["class-name","class-year","class-status"].forEach(id => document.getElementById(id).value = "");
      editingClassRow = null;
    }
  });

  saveSubjectBtn.addEventListener("click", () => {
    const code = document.getElementById("subject-code").value.trim();
    const desc = document.getElementById("subject-desc").value.trim();
    const year = document.getElementById("subject-year").value.trim();
    if(!code || !desc || !year) return alert("Please fill in all fields.");

    if(editingSubjectRow) {
      editingSubjectRow.cells[0].innerText = code;
      editingSubjectRow.cells[1].innerText = desc;
      editingSubjectRow.cells[2].innerText = year;
      editingSubjectRow = null;
    } else {
      const row = document.createElement("tr");
      row.innerHTML = `<td>${code}</td><td>${desc}</td><td>${year}</td><td class="action-cell">
        <div class="action-buttons">
          <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
          <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
        </div></td>`;
      subjectsTableBody.appendChild(row);
      row.querySelector(".edit-btn").addEventListener("click", () => {
        editingSubjectRow = row;
        addSubjectModal.style.display = "flex";
        addSubjectModal.querySelector("h3").innerText = "EDIT SUBJECT";
        saveSubjectBtn.innerText = "UPDATE SUBJECT";
        document.getElementById("subject-code").value = row.cells[0].innerText;
        document.getElementById("subject-desc").value = row.cells[1].innerText;
        document.getElementById("subject-year").value = row.cells[2].innerText;
      });
      row.querySelector(".delete-btn").addEventListener("click", () => row.remove());
    }
    addSubjectModal.style.display = "none";
  });

  saveClassBtn.addEventListener("click", () => {
    const name = document.getElementById("class-name").value.trim();
    const year = document.getElementById("class-year").value.trim();
    const status = document.getElementById("class-status").value.trim();
    if(!name || !year || !status) return alert("Please fill in all fields.");

    if(editingClassRow) {
      editingClassRow.cells[0].innerText = name;
      editingClassRow.cells[1].innerText = year;
      editingClassRow.cells[2].innerText = status;
      editingClassRow = null;
    } else {
      const row = document.createElement("tr");
      row.innerHTML = `<td>${name}</td><td>${year}</td><td>${status}</td>
        <td class="action-cell"><div class="action-buttons">
          <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
          <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
        </div></td>`;
      classesTableBody.appendChild(row);
      row.querySelector(".edit-btn").addEventListener("click", () => {
        editingClassRow = row;
        addClassModal.style.display = "flex";
        addClassModal.querySelector("h3").innerText = "EDIT CLASS";
        saveClassBtn.innerText = "UPDATE CLASS";
        document.getElementById("class-name").value = row.cells[0].innerText;
        document.getElementById("class-year").value = row.cells[1].innerText;
        document.getElementById("class-status").value = row.cells[2].innerText;
      });
      row.querySelector(".delete-btn").addEventListener("click", () => row.remove());
    }
    addClassModal.style.display = "none";
  });
// ========================= Forms ===================================
const openModal = (m,h,b) => {
  m.querySelector("h3").innerText = h;
  m.querySelector(".submit-btn").innerText = b;
  m.style.display = "flex";

  const fid = m.querySelector("#faculty-number"),
        fem = m.querySelector("#faculty-email"),
        sid = m.querySelector("#student-number"),
        sem = m.querySelector("#student-email");

  if(fid && fem){ 
    fid.disabled = h.includes("EDIT FACULTY"); 
    fem.disabled = h.includes("EDIT FACULTY"); 
  }
  if(sid && sem){ 
    sid.disabled = h.includes("EDIT STUDENT"); 
    sem.disabled = h.includes("EDIT STUDENT"); 
  }
}
const closeModal = m => m.style.display = "none";

// ========================= Faculty =========================
const addFacultyModal = document.getElementById("addFacultyModal"),
      facultyTbody = document.querySelector("#faculties-section tbody");

document.querySelector(".add-faculty-btn").addEventListener("click", () => {
  // Clear form for new faculty
  addFacultyModal.querySelectorAll("input").forEach(i=>{ i.value=""; i.disabled=false; });
  document.getElementById("faculty-photo-preview").hidden = true;
  openModal(addFacultyModal,"ADD FACULTY","SAVE FACULTY");
});

// Handle Save / Update
addFacultyModal.querySelector(".submit-btn").addEventListener("click", () => {
  const n = document.getElementById("faculty-number").value.trim(),
        e = document.getElementById("faculty-email").value.trim(),
        f = document.getElementById("faculty-firstname").value.trim(),
        l = document.getElementById("faculty-lastname").value.trim(),
        s = document.getElementById("faculty-suffix").value.trim(),
        photo = document.getElementById("faculty-photo-preview").src;

  if(!n||!e||!f||!l) return alert("Please fill in all required fields.");

  // Check if updating existing row
  if(addFacultyModal.dataset.editRow){
    const row = document.getElementById(addFacultyModal.dataset.editRow);
    row.querySelector("td:nth-child(1)").innerHTML = photo?`<img src="${photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`:"";
    row.querySelector("td:nth-child(3)").innerHTML = `<div>${f} ${l} ${s}</div><small style="color:#6b7280;">${e}</small>`;
    row.dataset.firstname = f;
    row.dataset.lastname = l;
    row.dataset.suffix = s;
    row.dataset.photo = photo;
    delete addFacultyModal.dataset.editRow;
  } else {
    // New row
    const row = document.createElement("tr");
    row.id = "faculty-" + Date.now();
    row.dataset.id = n;
    row.dataset.email = e;
    row.dataset.firstname = f;
    row.dataset.lastname = l;
    row.dataset.suffix = s;
    row.dataset.photo = photo;

    row.innerHTML = `
  <td>${photo?`<img src="${photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`:``}</td>
  <td><strong class="faculty-id">${n}</strong></td>
  <td>
    <div><strong class="faculty-name">${f} ${l} ${s}</strong></div>
    <small class="faculty-email">${e}</small>
  </td>
  <td><div class="faculty-subjects">No subjects yet</div></td>
  <td class="action-cell">
    <div class="action-buttons">
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
      <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
    </div>
  </td>`;


    facultyTbody.appendChild(row);

    // Edit button
    row.querySelector(".edit-btn").addEventListener("click", () => {
      document.getElementById("faculty-number").value = row.dataset.id;
      document.getElementById("faculty-email").value = row.dataset.email;
      document.getElementById("faculty-firstname").value = row.dataset.firstname;
      document.getElementById("faculty-lastname").value = row.dataset.lastname;
      document.getElementById("faculty-suffix").value = row.dataset.suffix;
      if(row.dataset.photo){ 
        const p = document.getElementById("faculty-photo-preview"); 
        p.src = row.dataset.photo; 
        p.hidden = false; 
      }
      addFacultyModal.dataset.editRow = row.id;
      openModal(addFacultyModal,"EDIT FACULTY","UPDATE FACULTY");
    });

    // Delete button
    row.querySelector(".delete-btn").addEventListener("click", () => row.remove());
  }

  closeModal(addFacultyModal);
});

// Photo preview
document.getElementById("faculty-photo").addEventListener("change", e => {
  const f = e.target.files[0]; 
  if(f){ 
    const r = new FileReader();
    r.onload = ev => { 
      const p = document.getElementById("faculty-photo-preview"); 
      p.src = ev.target.result; 
      p.hidden = false; 
    }
    r.readAsDataURL(f);
  }
});


  // ========================= Students =========================
  const addStudentModal = document.getElementById("addStudentModal"),
        studentTbody = document.querySelector("#students-section tbody");

  document.querySelector(".add-student-btn").addEventListener("click", () => openModal(addStudentModal,"ADD STUDENT","SAVE STUDENT"));
  addStudentModal.querySelector(".submit-btn").addEventListener("click", () => {
    const n = document.getElementById("student-number").value.trim(),
          e = document.getElementById("student-email").value.trim(),
          f = document.getElementById("student-firstname").value.trim(),
          l = document.getElementById("student-lastname").value.trim(),
          s = document.getElementById("student-suffix").value.trim(),
          y = document.getElementById("student-yearlevel").value,
          sec = document.getElementById("student-yearsection").value,
          p = document.getElementById("student-program").value;
    if(!n||!e||!f||!l||!y||!sec||!p) return alert("Please fill all required fields.");
    const row = document.createElement("tr");
    row.innerHTML = `<td>${n}</td>
      <td><div>${f} ${l} ${s}</div><small style="color:#6b7280;">${e}</small></td>
      <td>${p}-${y.replace(" Year","")}${sec}</td>
      <td class="action-cell"><div class="action-buttons">
        <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
        <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
      </div></td>`;
    studentTbody.appendChild(row);
    row.querySelector(".edit-btn").addEventListener("click", () => openModal(addStudentModal,"EDIT STUDENT","UPDATE STUDENT"));
    row.querySelector(".delete-btn").addEventListener("click", () => row.remove());
    closeModal(addStudentModal);
  });

  // ========================= Categories =========================
  const addCategoryModal = document.getElementById("addCategoryModal"),
        addCategoryBtn = document.getElementById("addCategoryBtn"),
        saveCategoryBtn = document.getElementById("saveCategoryBtn"),
        categoriesColumn = document.getElementById("categoriesColumn"),
        addQuestionModal = document.getElementById("addQuestionModal"),
        questionCategoryName = document.getElementById("questionCategoryName"),
        saveQuestionBtn = document.getElementById("saveQuestionBtn");

  addCategoryBtn.addEventListener("click", () => addCategoryModal.style.display = "flex");

  function bindCategoryActions(category) {
    category.querySelector(".add-btn").addEventListener("click", () => {
      const fullText = category.querySelector("h3").innerText;      
      const nameOnly = fullText.split(": ")[1] || fullText;        
      questionCategoryName.innerText = nameOnly;                   
      addQuestionModal.style.display = "flex";
      addQuestionModal.dataset.targetCategory = category;
      document.getElementById("question-text").placeholder = `Enter a question here...`;
    });
    category.querySelector(".edit-btn").addEventListener("click", () => {});
    category.querySelector(".delete-btn").addEventListener("click", () => category.remove());
  }

  saveCategoryBtn.addEventListener("click", () => {
    const name = document.getElementById("category-name").value.trim(),
          sec = document.getElementById("section-number").value.trim();
    if(!name || !sec) return alert("Please fill in all fields.");
    const cat = document.createElement("div");
    cat.className = "criteria-category";
    cat.innerHTML = `<div class="category-header">
      <h3>SECTION ${sec}: ${name}</h3>
      <div class="action-buttons">
        <button class="add-btn" title="Add"><i class="fa-solid fa-plus"></i></button>
        <button class="edit-btn" title="Edit"><i class="fas fa-pen-to-square"></i></button>
        <button class="delete-btn" title="Delete"><i class="fas fa-trash-can"></i></button>
      </div>
    </div>
    <div class="questions-list"></div>`;
    categoriesColumn.appendChild(cat);
    bindCategoryActions(cat);

    addCategoryModal.style.display = "none";
    document.getElementById("category-name").value = "";
    document.getElementById("section-number").value = "";
  });

  saveQuestionBtn.addEventListener("click", () => {
    const q = document.getElementById("question-text").value.trim();
    if(!q) return alert("Please enter a question.");
    const target = addQuestionModal.dataset.targetCategory;
    const list = target.querySelector(".questions-list");
    const item = document.createElement("div");
    item.className = "question-item";
    item.innerHTML = `<span class="question-text">${q}</span>
      <button class="edit-btn" title="Edit"><i class="fas fa-pen-to-square"></i></button>
      <button class="delete-btn" title="Delete"><i class="fas fa-trash-alt"></i></button>`;
    list.appendChild(item);
    item.querySelector(".edit-btn").addEventListener("click", () => {});
    item.querySelector(".delete-btn").addEventListener("click", () => item.remove());
    addQuestionModal.style.display = "none";
    document.getElementById("question-text").value = "";
  });

  // ========================= Modal Close =========================
  document.querySelectorAll(".close-btn").forEach(b => b.addEventListener("click", () => {
    [addProgramModal, addSubjectModal, addClassModal, addFacultyModal, addStudentModal, addCategoryModal, addQuestionModal].forEach(m => m.style.display = "none");
  }));
  window.addEventListener("click", e => {
    [addProgramModal, addSubjectModal, addClassModal, addFacultyModal, addStudentModal, addCategoryModal, addQuestionModal].forEach(m => { if(e.target === m) m.style.display = "none"; });
  });
  // ========================= Initialize =========================
  showSection("dashboard-section");
  showTable('subjects');
});