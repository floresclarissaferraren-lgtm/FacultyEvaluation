

//walang password open ================= DROPDOWN =================
function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
}
window.showLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "flex";
  document.getElementById("dropdownMenu").style.display = "none";
};
window.closeLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "none";
};

window.confirmLogout = () => location.href = "EvalMain.php?logout=true";
window.logout = e => { 
  e.preventDefault(); 
  showLogoutModal(); 
};

document.addEventListener("click", e => {
  const m = document.getElementById("dropdownMenu"),
        t = document.querySelector(".student-box");
  if (m.style.display === "block" && !t.contains(e.target) && !m.contains(e.target)) {
    m.style.display = "none";
  }
});
document.getElementById("logoutModal").addEventListener("click", e => {
  if (e.target === document.getElementById("logoutModal")) closeLogoutModal();
});

function showPasswordForm(){
  document.getElementById("passwordForm").style.display="flex";
}
function closePasswordForm(){
  document.getElementById("passwordForm").style.display="none";
}
function showProfile(){
  // Close dropdown first
  document.getElementById("dropdownMenu").style.display = "none";
  
  // Get student information from hidden fields
  const studentId = document.getElementById("studentId")?.value;
  const studentName = document.getElementById("studentName")?.value;
  const studentNumber = document.getElementById("studentNumber")?.value;
  const studentYearLevel = document.getElementById("studentYearLevel")?.value;
  const studentProgram = document.getElementById("studentProgram")?.value;
  
  // Create profile modal content
  const profileContent = `
    <div class="profile-header">
      <div class="profile-icon">
        <i class="ph ph-user-circle"></i>
      </div>
      <h3>Student Profile</h3>
    </div>
    <div class="profile-info">
      <p><strong>Full Name:</strong> ${studentName || 'N/A'}</p>
      <p><strong>Student Number:</strong> ${studentNumber || 'N/A'}</p>
      <p><strong>Year Level:</strong> ${studentYearLevel || 'N/A'}</p>
      <p><strong>Program:</strong> ${studentProgram || 'N/A'}</p>
    </div>
    <div class="profile-buttons">
      <button class="btn close-profile-btn" onclick="closeProfileModal()">Close</button>
    </div>
  `;
  
  // Create modal if it doesn't exist
  let profileModal = document.getElementById("profileModal");
  if (!profileModal) {
    profileModal = document.createElement("div");
    profileModal.id = "profileModal";
    profileModal.className = "profileform";
    document.body.appendChild(profileModal);
  }
  
  profileModal.innerHTML = `
    <div class="logout-content">
      ${profileContent}
    </div>
  `;
  
  profileModal.style.display = "flex";
}

window.closeProfileModal = () => {
  document.getElementById("profileModal").style.display = "none";
};
function updatePassword() {
  const studentId = document.getElementById("studentId").value.trim();
  const oldPass = document.getElementById("oldPass").value.trim();
  const newPass = document.getElementById("newPass").value.trim();
  const confirmPass = document.getElementById("confirmPass").value.trim();

  // Validation
  if (!studentId || !oldPass || !newPass || !confirmPass) {
    alert("Please fill in all fields!");
    return;
  }

  if (newPass !== confirmPass) {
    alert("New passwords do not match!");
    return;
  }

  // Send to PHP backend
  fetch("update_studentPassForm.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      student_id: studentId,
      old_password: oldPass,
      new_password: newPass,
    }),
  })
    .then((res) => {
      if (!res.ok) throw new Error("Network response was not ok");
      return res.json();
    })
    .then((data) => {
      if (data.success) {
        alert("Password updated successfully!");
        closePasswordForm();
      } else {
        alert(data.message || "Failed to update password.");
      }
    })
    .catch((err) => {
      console.error("Error updating password:", err);
      alert("Something went wrong. Please try again.");
    });
}

// Close modal by clicking overlay
document.getElementById("passwordForm").addEventListener("click", (e) => {
  if (e.target.id === "passwordForm") closePasswordForm();
});

function closePasswordForm() {
  document.getElementById("passwordForm").style.display = "none";
}

function togglePassword(id, icon) {
  const input = document.getElementById(id);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("ph-eye-slash");
    icon.classList.add("ph-eye");
  } else {
    input.type = "password";
    icon.classList.remove("ph-eye");
    icon.classList.add("ph-eye-slash");
  }
}

// ================= switch section =================

async function showEvaluateSection() {
  document.getElementById("mainPage").style.display = "none";
  document.getElementById("evaluateSection").style.display = "block";
  document.getElementById("ratingLegends").style.display = "block";
  document.getElementById("evaluationContainer").style.display = "block";

  await loadStudentFacultyDropdown();
  loadFacultyCategories(); 
}
function goBackToMain() {
  document.getElementById("evaluateSection").style.display = "none";
  document.getElementById("ratingLegends").style.display = "none";
  document.getElementById("evaluationContainer").style.display = "none";
  document.getElementById("mainPage").style.display = "flex";
}
// ================= LOAD CATEGORIES + QUESTIONS =================
let currentCriteria = 0;
let criteriaTables = [];

async function loadStudentFacultyDropdown() {
  const studentId = document.getElementById('studentId')?.value.trim();
  const yearLevel = document.getElementById('studentYearLevel')?.value.trim();
  const dropdown = document.getElementById('facultyDropdown');

  if (!dropdown) return;

  if (!studentId && !yearLevel) {
    dropdown.innerHTML = '<option value="">-- No faculty available --</option>';
    return;
  }

  const params = new URLSearchParams();
  if (studentId) params.set('student_id', studentId);
  else if (yearLevel) params.set('year_level', yearLevel);

  try {
    const res = await fetch(`getFaculty.php?${params.toString()}`);
    const facultyList = await res.json();

    dropdown.innerHTML = '';

    if (Array.isArray(facultyList) && facultyList.length > 0) {
      dropdown.innerHTML = '<option value="">-- Select Faculty --</option>';
      facultyList.forEach(faculty => {
        const subjectLabels = Array.isArray(faculty.subjects)
          ? faculty.subjects.map(sub => sub.subject_code || sub.subject_desc).join(', ')
          : '';

        const labelParts = [
          faculty.firstname || '',
          faculty.lastname || '',
          faculty.suffix ? faculty.suffix : ''
        ].filter(Boolean);

        const label = `${labelParts.join(' ')}${subjectLabels ? ' (' + subjectLabels + ')' : ''}`;

        const option = document.createElement('option');
        option.value = faculty.id;
        option.textContent = label;
        option.dataset.subjects = subjectLabels;
        dropdown.appendChild(option);
      });
    } else {
      dropdown.innerHTML = `<option value="">-- No faculty available for ${yearLevel} --</option>`;
    }
  } catch (err) {
    console.error('Error loading faculty dropdown:', err);
    dropdown.innerHTML = '<option value="">-- Unable to load faculty --</option>';
  }
}

async function loadFacultyCategories() {
  try {
    const res = await fetch("get_category.php");
    const categories = await res.json();
    console.log("Categories:", categories);

    const container = document.getElementById("evaluationContainer");
    container.innerHTML = "";

    // Move evaluationContainer to content-container for equal heights
    const contentContainer = document.querySelector(".content-container");
    if (contentContainer && !contentContainer.contains(container)) {
      contentContainer.appendChild(container);
    }

    // Create pagination container
    const paginationContainer = document.createElement("div");
    paginationContainer.className = "pagination-container";
    paginationContainer.innerHTML = `
      <div class="pagination-buttons">
        <button class="pagination-btn arrow-left" onclick="previousCriteria()" id="prevBtn">
          Previous
        </button>
        <button class="pagination-btn arrow-right" onclick="nextCriteria()" id="nextBtn">
          Next
        </button>
      </div>
      <div class="pagination-info" id="pageInfo">1 / ${categories.length}</div>
    `;

    criteriaTables = [];
    
    for (let i = 0; i < categories.length; i++) {
      const c = categories[i];
      const table = document.createElement("table");
      table.className = "evaluationform";
      if (i === 0) table.classList.add("active");
      
      table.innerHTML = `
        <thead>
          <tr>
            <th><i class="ph ph-graduation-cap"></i> ${c.category_name.toUpperCase()}</th>
            <th>5</th><th>4</th><th>3</th><th>2</th><th>1</th>
          </tr>
        </thead>
        <tbody></tbody>
      `;
      
      const tbody = table.querySelector("tbody");
      const qRes = await fetch(`get_question.php?category_id=${c.id}`);
      const questions = await qRes.json();
      console.log("Questions for", c.id, questions);

      if (Array.isArray(questions) && questions.length > 0) {
        questions.forEach(q => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td class="question-list">${q.question_text}</td>
            <td><input type="radio" name="q_${q.id}" value="5"></td>
            <td><input type="radio" name="q_${q.id}" value="4"></td>
            <td><input type="radio" name="q_${q.id}" value="3"></td>
            <td><input type="radio" name="q_${q.id}" value="2"></td>
            <td><input type="radio" name="q_${q.id}" value="1"></td>
          `;
          tbody.appendChild(tr);
        });
      } else {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="6">No questions available</td>`;
        tbody.appendChild(tr);
      }

      container.appendChild(table);
      criteriaTables.push(table);
    }
    
    // Add feedback and submit button container (hidden initially)
    const feedbackSubmitContainer = document.createElement("div");
    feedbackSubmitContainer.id = "feedbackSubmitContainer";
    feedbackSubmitContainer.style.display = "none";
    feedbackSubmitContainer.innerHTML = `
      <div class="feedback-container">
        <div class="feedback-box">
          <label for="studentFeedback">OPTIONAL COMMENTS</label>
          <textarea id="studentFeedback" placeholder="Type your feedback here..."></textarea>
        </div>
      </div>
    `;
    
    // Add feedback container first, then pagination
    container.appendChild(feedbackSubmitContainer);
    container.appendChild(paginationContainer);
    
    updatePaginationButtons();
    
  } catch (err) {
    console.error("Error loading faculty categories:", err);
  }
}

function previousCriteria() {
  if (currentCriteria > 0) {
    criteriaTables[currentCriteria].classList.remove("active");
    currentCriteria--;
    criteriaTables[currentCriteria].classList.add("active");
    updatePaginationButtons();
  }
}

function nextCriteria() {
  if (currentCriteria < criteriaTables.length - 1) {
    criteriaTables[currentCriteria].classList.remove("active");
    currentCriteria++;
    criteriaTables[currentCriteria].classList.add("active");
    updatePaginationButtons();
  }
}

function updatePaginationButtons() {
  const pageInfo = document.getElementById("pageInfo");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const feedbackSubmitContainer = document.getElementById("feedbackSubmitContainer");
  
  if (pageInfo) {
    pageInfo.textContent = `${currentCriteria + 1} / ${criteriaTables.length}`;
  }
  
  if (prevBtn) {
    prevBtn.disabled = currentCriteria === 0;
  }
  
  if (nextBtn) {
    const isLastPage = currentCriteria === criteriaTables.length - 1;
    nextBtn.disabled = isLastPage;
    nextBtn.textContent = isLastPage ? "Submit" : "Next";
    nextBtn.onclick = isLastPage ? submitEvaluation : nextCriteria;
  }
  
  // Show feedback and submit button only on last page
  if (feedbackSubmitContainer) {
    feedbackSubmitContainer.style.display = currentCriteria === criteriaTables.length - 1 ? "block" : "none";
  }
}

// ================= SUBMIT =================
function submitEvaluation() {
  const facultyDropdown = document.getElementById('facultyDropdown');
  const facultyId = facultyDropdown ? facultyDropdown.value : '';

  if (!facultyId) {
    alert('Please select a faculty member to evaluate.');
    return;
  }

  const selected = document.querySelectorAll('input[type="radio"]:checked');
  const data = {};

  selected.forEach(r => {
    data[r.name] = r.value;
  });

  const totalQuestions = document.querySelectorAll('.question-list').length;

  if (Object.keys(data).length < totalQuestions) {
    alert("Please answer all questions!");
    return;
  }

  const facultyLabel = facultyDropdown.options[facultyDropdown.selectedIndex]?.text || '';
  const submission = {
    faculty_id: facultyId,
    faculty_name: facultyLabel,
    answers: data,
    feedback: document.getElementById('studentFeedback')?.value.trim() || ''
  };

  console.log("Submitted:", submission);
  alert("Evaluation submitted!");
}
