


document.addEventListener("DOMContentLoaded", () => {
  let currentActiveLink = null;
  let currentRow = null; 
  let deleteTarget = null;
  let deleteType = "";

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


  // ================= Section Switching ==========================================================================================
  window.showSection = (id,e) => {
  document.querySelectorAll(".section").forEach(s=>s.style.display="none");

  const target = document.getElementById(id);
  if(target) target.style.display = "block";

  document.querySelectorAll(".sidebar a").forEach(l=>l.classList.remove("active"));
  const link = e?.target.closest("a") || document.querySelector(`.sidebar a[data-section="${id}"]`);
  if(link){
    link.classList.add("active");
    currentActiveLink = link;
  }
  if(id === "programs-section"){
    loadPrograms();
  }
  if(id === "faculties-section"){
  loadFaculty(); // load data from DB
}
};
 // ================= Delete Forms ==========================================================================================
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
  const cfg = { faculty:{url:"deleteFaculty.php",key:"faculty_id"}, program:{url:"deleteProgram.php",key:"program_code"} }[deleteType];
  if (!cfg) return closeDeleteModal();

  const val = deleteTarget.dataset[cfg.key] || deleteTarget.id.replace(`${deleteType}-`, "");
  fetch(cfg.url, {
    method:"POST", headers:{ "Content-Type":"application/x-www-form-urlencoded" },
    body:`${cfg.key}=${encodeURIComponent(val)}`
  })
  .then(r=>r.text())
  .then(resp=>{
    if(resp==="success"){
      const row = deleteTarget.closest("tr");
      if(row) row.remove();
      closeDeleteModal();
      openDeleteSuccess();
    } else {
      alert("Error deleting: "+resp);
      closeDeleteModal();
    }
  })
  .catch(err=>{
    console.error(err);
    alert("Unexpected error");
    closeDeleteModal();
  });
};

function openDeleteSuccess(){
  document.getElementById("deleteSuccessModal").style.display = "flex";
}
function closeDeleteSuccess(){
  document.getElementById("deleteSuccessModal").style.display = "none";
}
document.getElementById("success-ok-btn").onclick = closeDeleteSuccess;

const modal=document.getElementById("deleteModal");
modal.querySelector(".cancel-btn").onclick=closeDeleteModal;
modal.querySelector(".submit-btn").onclick=confirmDelete;

 // ================= User Menu Toggle & Logout ==========================================================================================
window.toggleDropdown = () => {
  const m = document.getElementById("dropdownMenu");
  m.style.display = m.style.display === "block" ? "none" : "block";
};
// Show/close form
window.showLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "flex";
  document.getElementById("dropdownMenu").style.display = "none";
};
window.closeLogoutModal = () => document.getElementById("logoutModal").style.display = "none";

// Confirm/logout
window.confirmLogout = () => location.href = "EvalMain.html";
window.logout = e => { e.preventDefault(); showLogoutModal(); };

// Close dropdown when clicking outside
document.addEventListener("click", e => {
  const m = document.getElementById("dropdownMenu"),
        t = document.querySelector(".admin-box");
  if (m.style.display === "block" && !t.contains(e.target) && !m.contains(e.target)) m.style.display = "none";
});

// Close form
document.getElementById("logoutModal").addEventListener("click", e => {
  if (e.target === document.getElementById("logoutModal")) closeLogoutModal();
});

// ================= Logout Modal Buttons ==========================================================================================
document.addEventListener("DOMContentLoaded", () => {
  const logoutModal = document.getElementById("logoutModal");
  if (!logoutModal) return;

  const cancelBtn = logoutModal.querySelector(".cancel-btn");
  const submitBtn = logoutModal.querySelector(".submit-btn");

  if (cancelBtn) cancelBtn.addEventListener("click", closeLogoutModal);
  if (submitBtn) submitBtn.addEventListener("click", confirmLogout);
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
    if(ok){loadPrograms();closeProgramModal();}else alert(res.message||res||"Operation failed.");
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
  fetch("getProgram.php").then(r=>r.json()).then(data=>{
    const tbody=document.querySelector(".programs-table tbody");tbody.innerHTML="";
    data.forEach(p=>{
      const tr=document.createElement("tr");tr.dataset.id=p.id;tr.dataset.program_code=p.program_code;
      tr.innerHTML=
      `<td>${p.program_code}</td><td>${p.program_name}
      </td><td class="action-cell"><div class="action-buttons"><button class="manage-btn">
      <i class="fas fa-sliders-h"></i></button><button class="edit-btn">
      <i class="fas fa-pen-to-square"></i></button><button class="delete-btn">
      <i class="fas fa-trash-alt"></i></button></div></td>`;
      tbody.appendChild(tr);attachProgramRowEvents(tr);
    });
  }).catch(err=>console.error("FETCH ERROR:",err));
}

// CLOSE MODAL
function closeProgramModal(){programCodeInput.value="";programNameInput.value="";editRowProgram=null;addProgramModal.style.display="none";}

// INIT
document.addEventListener("DOMContentLoaded",loadPrograms);


// ========================= Manage Section (Subjects & Classes) ==========================================================================================
function showTable(tab){
  const s=document.getElementById("subjects"),c=document.getElementById("classes"),
        sb=document.querySelector(".subject-btn"),cb=document.querySelector(".classes-btn");
  if(tab==="subjects"){s.style.display="block";c.style.display="none";sb.classList.add("active");cb.classList.remove("active");}
  else{s.style.display="none";c.style.display="block";sb.classList.remove("active");cb.classList.add("active");}
}

document.querySelector(".back-btn").addEventListener("click",()=>showSection("programs-section"));
document.querySelector(".subject-btn").addEventListener("click",()=>showTable("subjects"));
document.querySelector(".classes-btn").addEventListener("click",()=>showTable("classes"));

const addManageBtn=document.querySelector  (".add-manage-btn"),
      addSubjectModal=document.getElementById("addSubjectModal"),
      addClassModal=document.getElementById("addClassModal"),
      saveSubjectBtn=document.getElementById("save-subject-btn"),
      saveClassBtn=document.getElementById("save-class-btn"),
      subjectsTableBody=document.querySelector("#subjects tbody"),
      classesTableBody=document.querySelector("#classes tbody");

addManageBtn.addEventListener("click",()=>{
  if(document.querySelector(".subject-btn").classList.contains("active")){
    addSubjectModal.style.display="flex";addSubjectModal.querySelector("h3").innerText="ADD SUBJECT";
    saveSubjectBtn.innerText="SAVE SUBJECT";["subject-code","subject-desc","subject-year"].forEach(id=>document.getElementById(id).value="");
    editingSubjectRow=null;
  }else{
    addClassModal.style.display="flex";addClassModal.querySelector("h3").innerText="ADD CLASS";
    saveClassBtn.innerText="SAVE CLASS";["class-name","class-year","class-status"].forEach(id=>document.getElementById(id).value="");
    editingClassRow=null;
  }
});

saveSubjectBtn.addEventListener("click",()=>{
  const code=document.getElementById("subject-code").value.trim(),
        desc=document.getElementById("subject-desc").value.trim(),
        year=document.getElementById("subject-year").value.trim();
  if(!code||!desc||!year)return alert("Please fill in all fields.");
  if(editingSubjectRow){editingSubjectRow.cells[0].innerText=code;editingSubjectRow.cells[1].innerText=desc;
    editingSubjectRow.cells[2].innerText=year;editingSubjectRow=null;}
  else{
    const row=document.createElement("tr");
    row.innerHTML=`<td>${code}</td><td>${desc}</td><td>${year}</td><td class="action-cell"><div class="action-buttons">
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button><button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
    </div></td>`;
    subjectsTableBody.appendChild(row);
    row.querySelector(".edit-btn").addEventListener("click",()=>{
      editingSubjectRow=row;addSubjectModal.style.display="flex";addSubjectModal.querySelector("h3").innerText="EDIT SUBJECT";
      saveSubjectBtn.innerText="UPDATE SUBJECT";
      document.getElementById("subject-code").value=row.cells[0].innerText;
      document.getElementById("subject-desc").value=row.cells[1].innerText;
      document.getElementById("subject-year").value=row.cells[2].innerText;
    });
    row.querySelector(".delete-btn").addEventListener("click",()=>openDeleteModal("subject",row.cells[1].innerText,row));
  }
  addSubjectModal.style.display="none";
});

saveClassBtn.addEventListener("click",()=>{
  const name=document.getElementById("class-name").value.trim(),
        year=document.getElementById("class-year").value.trim(),
        status=document.getElementById("class-status").value.trim();
  if(!name||!year||!status)return alert("Please fill in all fields.");
  if(editingClassRow){editingClassRow.cells[0].innerText=name;editingClassRow.cells[1].innerText=year;
    editingClassRow.cells[2].innerText=status;editingClassRow=null;}
  else{
    const row=document.createElement("tr");
    row.innerHTML=`<td>${name}</td><td>${year}</td><td>${status}</td><td class="action-cell"><div class="action-buttons">
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button><button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
    </div></td>`;
    classesTableBody.appendChild(row);
    row.querySelector(".edit-btn").addEventListener("click",()=>{
      editingClassRow=row;addClassModal.style.display="flex";addClassModal.querySelector("h3").innerText="EDIT CLASS";
      saveClassBtn.innerText="UPDATE CLASS";
      document.getElementById("class-name").value=row.cells[0].innerText;
      document.getElementById("class-year").value=row.cells[1].innerText;
      document.getElementById("class-status").value=row.cells[2].innerText;
    });
    row.querySelector(".delete-btn").addEventListener("click",()=>openDeleteModal("class",row.cells[0].innerText,row));
  }
  addClassModal.style.display="none";
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
const addFacultyModal=document.getElementById("addFacultyModal");
const facultyTbody=document.querySelector("#faculties-section tbody");

// LOAD FACULTY
function loadFaculty(){
  fetch("getFaculty.php").then(r=>r.json()).then(data=>{
    facultyTbody.innerHTML="";
    data.forEach(f=>{
      const row=document.createElement("tr");
      row.id="faculty-"+f.faculty_id;
      Object.assign(row.dataset,f);
      row.innerHTML=`
        <td>${f.photo?`<img src="${f.photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`:``}</td>
        <td><strong>${f.faculty_id}</strong></td>
        <td><div>${f.firstname} ${f.lastname} ${f.suffix||""}</div><small>${f.email}</small></td>
        <td>No subjects yet</td>
        <td class="action-cell"><div class="action-buttons">
          <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
          <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
        </div></td>`;
      facultyTbody.appendChild(row);

      // EDIT
      row.querySelector(".edit-btn").onclick=()=>{
        document.getElementById("faculty-number").value=f.faculty_id||"";
        document.getElementById("faculty-email").value=f.email||"";
        document.getElementById("faculty-firstname").value=f.firstname||"";
        document.getElementById("faculty-lastname").value=f.lastname||"";
        document.getElementById("faculty-suffix").value=f.suffix||"";
        const preview=document.getElementById("faculty-photo-preview");
        if(f.photo){preview.src=f.photo;preview.hidden=false;}else preview.hidden=true;
        addFacultyModal.dataset.editRow=f.faculty_id;
        openModal(addFacultyModal,"EDIT FACULTY","UPDATE FACULTY");
      };

      // DELETE
      row.querySelector(".delete-btn").onclick=()=>{
        if(confirm(`Delete ${f.firstname}?`)){
          fetch("deleteFaculty.php",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"faculty_id="+encodeURIComponent(f.faculty_id)})
          .then(r=>r.text()).then(()=>loadFaculty());
        }
      };
    });
  }).catch(err=>console.error("Load error:",err));
}

// ADD BUTTON
document.querySelector(".add-faculty-btn").onclick=()=>{
  addFacultyModal.querySelectorAll("input").forEach(i=>i.value="");
  const preview=document.getElementById("faculty-photo-preview");
  preview.hidden=true; preview.src="";
  delete addFacultyModal.dataset.editRow;
  openModal(addFacultyModal,"ADD FACULTY","SAVE FACULTY");
};

// SAVE (ADD/EDIT)
addFacultyModal.querySelector(".submit-btn").onclick=()=>{
  const n=document.getElementById("faculty-number").value.trim(),
        e=document.getElementById("faculty-email").value.trim(),
        f=document.getElementById("faculty-firstname").value.trim(),
        l=document.getElementById("faculty-lastname").value.trim(),
        s=document.getElementById("faculty-suffix").value.trim(),
        photo=document.getElementById("faculty-photo-preview").hidden?"":document.getElementById("faculty-photo-preview").src;
  if(!n||!e||!f||!l) return alert("Required fields missing!");
  const form=new URLSearchParams({faculty_id:n,email:e,firstname:f,lastname:l,suffix:s,photo});
  const url=addFacultyModal.dataset.editRow?"editFaculty.php":"add_faculty.php";
  fetch(url,{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:form.toString()})
  .then(r=>r.text()).then(resp=>{
    if(resp==="success"){loadFaculty();closeModal(addFacultyModal);}
    else if(resp==="duplicate") alert("Faculty ID already exists!");
    else alert("Error: "+resp);
  }).catch(err=>console.error("Save error:",err));
};

// PHOTO PREVIEW
document.getElementById("faculty-photo").onchange=e=>{
  const file=e.target.files[0];
  if(file){
    const reader=new FileReader();
    reader.onload=ev=>{
      const p=document.getElementById("faculty-photo-preview");
      p.src=ev.target.result; p.hidden=false;
    };
    reader.readAsDataURL(file);
  }
};

// SEARCH
document.getElementById("faculty-search").oninput=e=>{
  const term=e.target.value.toLowerCase();
  [...facultyTbody.rows].forEach(r=>r.style.display=r.textContent.toLowerCase().includes(term)?"":"none");
};

// INIT
document.addEventListener("DOMContentLoaded",loadFaculty);


  // ========================= Students ==========================================================================================
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
    row.querySelector(".delete-btn").addEventListener("click", () => {
    openDeleteModal("student", row.cells[1].innerText, row);
});    closeModal(addStudentModal);
  });

// ========================= Categories ==========================================================================================
const addCategoryModal=document.getElementById("addCategoryModal"),
      addCategoryBtn=document.getElementById("addCategoryBtn"),
      saveCategoryBtn=document.getElementById("saveCategoryBtn"),
      categoriesColumn=document.getElementById("categoriesColumn"),
      addQuestionModal=document.getElementById("addQuestionModal"),
      questionCategoryName=document.getElementById("questionCategoryName"),
      saveQuestionBtn=document.getElementById("saveQuestionBtn");

addCategoryBtn.addEventListener("click",()=>{addCategoryModal.style.display="flex";delete addCategoryModal.dataset.editId;});

function bindCategoryActions(cat){
  cat.querySelector(".add-btn").addEventListener("click",()=>{
    questionCategoryName.innerText=cat.querySelector(".category-name").innerText;
    addQuestionModal.style.display="flex";addQuestionModal.dataset.targetId=cat.id;delete addQuestionModal.dataset.editId;
    document.getElementById("question-text").placeholder="Enter a question here...";
  });
  cat.querySelector(".edit-btn").addEventListener("click",()=>{
    document.getElementById("category-name").value=cat.querySelector(".category-name").innerText;
    document.getElementById("section-number").value=cat.querySelector(".section-number").innerText.replace("SECTION ","");
    addCategoryModal.style.display="flex";addCategoryModal.dataset.editId=cat.id;
  });
  cat.querySelector(".delete-btn").addEventListener("click",()=>openDeleteModal("category",cat.querySelector(".category-name").innerText,cat));
}

saveCategoryBtn.addEventListener("click",()=>{
  const name=document.getElementById("category-name").value.trim(),
        sec=document.getElementById("section-number").value.trim();
  if(!name||!sec)return alert("Please fill in all fields.");
  if(addCategoryModal.dataset.editId){
    const cat=document.getElementById(addCategoryModal.dataset.editId);
    cat.querySelector(".category-name").innerText=name;
    cat.querySelector(".section-number").innerText=`SECTION ${sec}`;
    delete addCategoryModal.dataset.editId;
  }else{
    const cat=document.createElement("div");
    cat.className="criteria-category";cat.id=`cat-${Date.now()}`;
    cat.innerHTML=`<div class="category-header"><div class="title-block"><i class="fas fa-chalkboard-teacher"></i>
      <div class="text-block"><div class="section-number">SECTION ${sec}</div><div class="category-name">${name}</div></div></div>
      <div class="action-buttons"><button class="add-btn"><i class="fa-solid fa-plus"></i></button>
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button><button class="delete-btn"><i class="fas fa-trash-can"></i></button></div></div>
      <div class="category-table-header"><div>Question</div><div>Action</div></div><div class="questions-list"></div>`;
    categoriesColumn.appendChild(cat);bindCategoryActions(cat);
  }
  addCategoryModal.style.display="none";document.getElementById("category-name").value="";document.getElementById("section-number").value="";
});

saveQuestionBtn.addEventListener("click",()=>{
  const q=document.getElementById("question-text").value.trim();
  if(!q)return alert("Please enter a question.");
  const target=document.getElementById(addQuestionModal.dataset.targetId),list=target.querySelector(".questions-list");
  if(addQuestionModal.dataset.editId){
    document.getElementById(addQuestionModal.dataset.editId).querySelector(".question-text").innerText=q;
    delete addQuestionModal.dataset.editId;
  }else{
    const item=document.createElement("div");
    item.className="question-item";item.id=`q-${Date.now()}`;
    item.innerHTML=`<span class="question-text">${q}</span><div class="actions">
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button><button class="delete-btn"><i class="fas fa-trash-alt"></i></button></div>`;
    list.appendChild(item);
    item.querySelector(".edit-btn").addEventListener("click",()=>{
      document.getElementById("question-text").value=item.querySelector(".question-text").innerText;
      addQuestionModal.style.display="flex";addQuestionModal.dataset.targetId=target.id;addQuestionModal.dataset.editId=item.id;
    });
    item.querySelector(".delete-btn").addEventListener("click",()=>openDeleteModal("question",item.querySelector(".question-text").innerText,item));
  }
  addQuestionModal.style.display="none";document.getElementById("question-text").value="";
});
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

