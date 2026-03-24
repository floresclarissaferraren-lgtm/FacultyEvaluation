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
  if(window.innerWidth>768){s.classList.remove("active","collapsed");m.style.marginLeft="";m.style.width="";}
  else{s.classList.remove("collapsed");m.classList.remove("full");}
});


  // ================= Section Switching ==========================================================================================
  window.showSection = (id,e) => {
    document.querySelectorAll(".section").forEach(s=>s.style.display="none");
    const target=document.getElementById(id); if(target) target.style.display="block";
    document.querySelectorAll(".sidebar a").forEach(l=>l.classList.remove("active"));
    const link=e?.target.closest("a")||document.querySelector(`.sidebar a[data-section="${id}"]`);
    if(link){link.classList.add("active");currentActiveLink=link;}
  };
  document.querySelectorAll(".sidebar a").forEach(l=>l.addEventListener("click",e=>showSection(l.dataset.section,e)));

 // ================= Delete Forms ==========================================================================================
function openDeleteModal(type,name,el){
  deleteTarget=el;deleteType=type;
  document.getElementById("deleteMessage").innerHTML=`Do you want to delete <strong>${type}</strong>?<div class="delete-item-name">${name}</div>`;
  document.getElementById("deleteWarning").innerText=`Warning: All details about this ${type} will be deleted.`;
  document.getElementById("deleteModal").style.display="flex";
}

const closeDeleteModal=()=>{document.getElementById("deleteModal").style.display="none";deleteTarget=null;deleteType="";};
const confirmDelete=()=>{if(deleteTarget)deleteTarget.remove();closeDeleteModal();};

const modal=document.getElementById("deleteModal");
modal.querySelector(".cancel-btn").onclick=closeDeleteModal;
modal.querySelector(".submit-btn").onclick=confirmDelete;


  // ================= User Menu Toggle ==========================================================================================
  window.toggleUserMenu = () => {
    const menu=document.getElementById("userDropdown");
    menu.style.display=menu.style.display==="block"?"none":"block";
  };
  window.logout = () => {
  };
 // ========================= Programs ==========================================================================================
const addProgramModal=document.getElementById("addProgramModal"),
      programSubmitBtn=document.getElementById("save-program-btn"),
      programHeader=addProgramModal.querySelector("h3"),
      programCodeInput=document.getElementById("program-code"),
      programNameInput=document.getElementById("program-name");

document.querySelector(".add-program-btn")?.addEventListener("click",()=>{
  programHeader.innerText="ADD PROGRAM";programSubmitBtn.innerText="SAVE PROGRAM";
  programCodeInput.value=programNameInput.value="";editRowProgram=null;
  addProgramModal.style.display="flex";
});

programSubmitBtn.addEventListener("click",()=>{
  const code=programCodeInput.value.trim(),name=programNameInput.value.trim();
  if(!code||!name)return alert("Please fill in both Program Code and Program Name.");
  if(editRowProgram){editRowProgram.cells[0].innerText=code;editRowProgram.cells[1].innerText=name;}
  else{
    const row=document.createElement("tr");
    row.innerHTML=`<td>${code}</td><td>${name}</td><td class="action-cell"><div class="action-buttons">
      <button class="manage-btn"><i class="fas fa-sliders-h"></i></button>
      <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
      <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
    </div></td>`;
    document.querySelector(".programs-table tbody").appendChild(row);
    attachProgramRowEvents(row);
  }
  programCodeInput.value=programNameInput.value="";addProgramModal.style.display="none";editRowProgram=null;
});

function attachProgramRowEvents(row){
  row.querySelector(".manage-btn")?.addEventListener("click",()=>{
    showSection("manage-section");
    document.getElementById("manageTitle").innerText=`Subjects for ${row.cells[1].innerText}`;
    showTable("subjects");
  });
  row.querySelector(".edit-btn")?.addEventListener("click",()=>{
    programHeader.innerText="EDIT PROGRAM";programSubmitBtn.innerText="UPDATE PROGRAM";
    programCodeInput.value=row.cells[0].innerText;programNameInput.value=row.cells[1].innerText;
    editRowProgram=row;addProgramModal.style.display="flex";
  });
  row.querySelector(".delete-btn").addEventListener("click",()=>openDeleteModal("program",row.cells[1].innerText,row));
}

document.querySelectorAll(".programs-table tbody tr").forEach(attachProgramRowEvents);

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

const addManageBtn=document.querySelector(".add-manage-btn"),
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

// ========================= Faculty ==========================================================================================
const addFacultyModal=document.getElementById("addFacultyModal"),
      facultyTbody=document.querySelector("#faculties-section tbody");

document.querySelector(".add-faculty-btn").addEventListener("click",()=>{
  addFacultyModal.querySelectorAll("input").forEach(i=>{i.value="";i.disabled=false;});
  document.getElementById("faculty-photo-preview").hidden=true;
  openModal(addFacultyModal,"ADD FACULTY","SAVE FACULTY");
});

addFacultyModal.querySelector(".submit-btn").addEventListener("click",()=>{
  const n=document.getElementById("faculty-number").value.trim(),
        e=document.getElementById("faculty-email").value.trim(),
        f=document.getElementById("faculty-firstname").value.trim(),
        l=document.getElementById("faculty-lastname").value.trim(),
        s=document.getElementById("faculty-suffix").value.trim(),
        photo=document.getElementById("faculty-photo-preview").src;
  if(!n||!e||!f||!l)return alert("Please fill in all required fields.");

  if(addFacultyModal.dataset.editRow){
    const row=document.getElementById(addFacultyModal.dataset.editRow);
    row.querySelector("td:nth-child(1)").innerHTML=photo?`<img src="${photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`:"";
    row.querySelector("td:nth-child(3)").innerHTML=`<div>${f} ${l} ${s}</div><small style="color:#6b7280;">${e}</small>`;
    Object.assign(row.dataset,{firstname:f,lastname:l,suffix:s,photo});
    delete addFacultyModal.dataset.editRow;
  }else{
    const row=document.createElement("tr");
    row.id="faculty-"+Date.now();
    Object.assign(row.dataset,{id:n,email:e,firstname:f,lastname:l,suffix:s,photo});
    row.innerHTML=`<td>${photo?`<img src="${photo}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`:``}</td>
      <td><strong class="faculty-id">${n}</strong></td>
      <td><div><strong class="faculty-name">${f} ${l} ${s}</strong></div><small class="faculty-email">${e}</small></td>
      <td><div class="faculty-subjects">No subjects yet</div></td>
      <td class="action-cell"><div class="action-buttons">
        <button class="edit-btn"><i class="fas fa-pen-to-square"></i></button>
        <button class="delete-btn"><i class="fas fa-trash-alt"></i></button>
      </div></td>`;
    facultyTbody.appendChild(row);

    row.querySelector(".edit-btn").addEventListener("click",()=>{
      ["faculty-number","faculty-email","faculty-firstname","faculty-lastname","faculty-suffix"].forEach((id,i)=>{
        document.getElementById(id).value=[row.dataset.id,row.dataset.email,row.dataset.firstname,row.dataset.lastname,row.dataset.suffix][i];
      });
      if(row.dataset.photo){const p=document.getElementById("faculty-photo-preview");p.src=row.dataset.photo;p.hidden=false;}
      addFacultyModal.dataset.editRow=row.id;
      openModal(addFacultyModal,"EDIT FACULTY","UPDATE FACULTY");
    });
    row.querySelector(".delete-btn").addEventListener("click",()=>openDeleteModal("faculty",`${row.dataset.firstname} ${row.dataset.lastname}`,row));
  }
  closeModal(addFacultyModal);
});

document.getElementById("faculty-photo").addEventListener("change",e=>{
  const f=e.target.files[0];
  if(f){const r=new FileReader();r.onload=ev=>{const p=document.getElementById("faculty-photo-preview");p.src=ev.target.result;p.hidden=false;};r.readAsDataURL(f);}
});


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