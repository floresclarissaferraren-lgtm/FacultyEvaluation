

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
  document.body.classList.add("modal-open");
  document.getElementById("passwordForm").classList.add("show");
}
function closePasswordForm(){
  document.body.classList.remove("modal-open");
  document.getElementById("passwordForm").classList.remove("show");
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
  
  // Fetch additional student data from database
  fetchStudentProfileData(studentId).then(additionalData => {
    // Create profile modal content with real data
    const profileContent = `
      <div class="profile-header">
        <h3>Student Profile</h3>
      </div>
      <div class="profile-body">
        <div class="profile-header-info">
          <div class="profile-avatar">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" alt="Profile Picture">
          </div>
          <div class="profile-name-section">
            <h4>${studentName || 'Student'}</h4>
            <p>Student</p>
          </div>
        </div>
        <div class="profile-fields">
          <div class="field-group">
            <div class="field-label">Student ID</div>
            <div class="field-value">${studentNumber || 'N/A'}</div>
          </div>
          <div class="field-group">
            <div class="field-label">Program</div>
            <div class="field-value">${studentProgram || 'N/A'}</div>
          </div>
          <div class="field-group">
            <div class="field-label">Year Level</div>
            <div class="field-value">${studentYearLevel || 'N/A'}</div>
          </div>
        </div>
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
    
    // Add click outside to close functionality
    profileModal.addEventListener('click', function(event) {
      if (event.target === profileModal) {
        closeProfileModal();
      }
    });
  }).catch(error => {
    console.error('Error fetching student profile data:', error);
  });
}

// Function to fetch additional student profile data from database
async function fetchStudentProfileData(studentId) {
  try {
    const response = await fetch('get_student_profile.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `student_id=${studentId}`
    });
    
    if (!response.ok) {
      throw new Error('Failed to fetch student profile data');
    }
    
    const data = await response.json();
    return data.success ? data.data : {};
  } catch (error) {
    console.error('Error:', error);
    return {};
  }
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
  if ((document.getElementById("studentStatus")?.value || "active").toLowerCase() !== "active") {
    alert("Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.");
    return;
  }

  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    if (!status || !status.success || !status.evaluation_open) {
      alert("Evaluation is closed");
      return;
    }
  } catch (_) {
    // If status check fails, be safe and block entry.
    alert("Evaluation is closed");
    return;
  }

  document.getElementById("mainPage").style.display = "none";
  document.getElementById("evaluateSection").style.display = "block";
  document.getElementById("facultyCards").style.display = "block";
  document.getElementById("evaluationContainer").style.display = "none";

  await loadStudentFacultyCards();
}
function goBackToMain() {
  document.getElementById("evaluateSection").style.display = "none";
  document.getElementById("evaluationContainer").style.display = "none";
  document.getElementById("facultyCards").style.display = "none";
  document.getElementById("mainPage").style.display = "flex";
}

async function refreshStudentPeriodAccess() {
  const evaluateSection = document.getElementById("evaluateSection");
  const isEvaluating = evaluateSection && evaluateSection.style.display !== "none";
  if (!isEvaluating) return;

  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    if (!status || !status.success || !status.evaluation_open) {
      goBackToMain();
      alert("Evaluation period has ended or is closed.");
    }
  } catch (_) {
    goBackToMain();
    alert("Evaluation is closed");
  }
}

setInterval(refreshStudentPeriodAccess, 60000);
// ================= LOAD CATEGORIES + QUESTIONS =================
let currentCriteria = 0;
let criteriaTables = [];

async function loadStudentFacultyCards() {
  const studentId = document.getElementById('studentId')?.value.trim();
  const yearLevel = document.getElementById('studentYearLevel')?.value.trim();
  const container = document.getElementById('facultyContainer');

  if (!container) return;

  // Block evaluate when evaluation is closed
  const studentActive = (document.getElementById("studentStatus")?.value || "active").toLowerCase() === "active";
  let evaluationOpen = false;
  try {
    const statusRes = await fetch("periods_api.php?action=status", { cache: "no-store", credentials: "same-origin" });
    const status = await statusRes.json();
    evaluationOpen = studentActive && !!(status && status.success && status.evaluation_open);
  } catch (_) {
    evaluationOpen = false;
  }

  if (!studentId && !yearLevel) {
    container.innerHTML = '<div class="no-faculty"><i class="ph ph-chalkboard-teacher"></i><span>No Faculty Available</span></div>';
    return;
  }

  const params = new URLSearchParams();
  if (studentId) params.set('student_id', studentId);
  else if (yearLevel) params.set('year_level', yearLevel);

  try {
    const res = await fetch(`getFaculty.php?${params.toString()}`);
    const facultyList = await res.json();

    container.innerHTML = '';

    if (Array.isArray(facultyList) && facultyList.length > 0) {
      facultyList.forEach(faculty => {
        const subjectLabels = Array.isArray(faculty.subjects)
          ? faculty.subjects.map(sub => sub.subject_code || sub.subject_desc).join(', ')
          : '';

        const nameParts = [
          faculty.firstname || '',
          faculty.lastname || '',
          faculty.suffix ? faculty.suffix : ''
        ].filter(Boolean);

        const fullName = nameParts.join(' ');

        const card = document.createElement('div');
        card.className = 'faculty-card';
        card.onclick = () => {
          if (!studentActive) return alert("Your account has been set to inactive by an admin. You cannot evaluate until your account is active again.");
          if (!evaluationOpen) return alert("Evaluation is closed");
          selectFaculty(faculty.id, fullName, subjectLabels);
        };
         
        card.innerHTML = `
          <div class="faculty-header">
            <div class="faculty-icon">
              <i class="ph ph-user-circle"></i>
            </div>
            <div class="faculty-name-bg">
              <h3 class="faculty-name">${fullName}</h3>
            </div>
          </div>
          <div class="faculty-info">
            <p class="faculty-subjects-label">Subjects:</p>
            <div class="faculty-subjects">${subjectLabels || 'No subjects assigned'}</div>
          </div>
          <div class="evaluate-action">
            <button class="evaluate-faculty-btn" ${evaluationOpen ? "" : "disabled"}>Evaluate</button>
          </div>
        `;
        
        container.appendChild(card);
      });
    } else {
      container.innerHTML = '<div class="no-faculty"><i class="ph ph-chalkboard-teacher"></i><span>No Faculty Available</span></div>';
    }
  } catch (err) {
    console.error('Error loading faculty cards:', err);
    container.innerHTML = '<div class="no-faculty"><i class="ph ph-warning-circle"></i><span>Unable to Load Faculty</span></div>';
  }
}

function selectFaculty(facultyId, facultyName, subjects) {
  // Store selected faculty data
  window.selectedFaculty = {
    id: facultyId,
    name: facultyName,
    subjects: subjects
  };
  
  // Hide faculty cards and show evaluation form
  document.getElementById('facultyCards').style.display = 'none';
  document.getElementById('evaluationContainer').style.display = 'block';
  
  // Load evaluation categories and questions
  loadFacultyCategories();
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

    // Create main evaluation container with header
    const mainEvaluationContainer = document.createElement("div");
    mainEvaluationContainer.className = "main-evaluation-container";
    
    // Create header section
    const headerSection = document.createElement("div");
    headerSection.className = "evaluation-header-section";
    headerSection.innerHTML = `
      <h2 class="performance-evaluation-title">Performance Evaluation</h2>
      <div class="rating-legends-mini">
        <ul>
          <li><span class="dot dot5"></span>5 - Outstanding</li>
          <li><span class="dot dot4"></span>4 - Very Good</li>
          <li><span class="dot dot3"></span>3 - Good</li>
          <li><span class="dot dot2"></span>2 - Fair</li>
          <li><span class="dot dot1"></span>1 - Poor</li>
        </ul>
      </div>
    `;
    
    // Create form content area
    const formContentArea = document.createElement("div");
    formContentArea.className = "evaluation-form-area";

    criteriaTables = [];
    currentCriteria = 0;
    
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
        questions.forEach((q, index) => {
          const tr = document.createElement("tr");
          tr.className = index % 2 === 0 ? "row-even" : "row-odd";
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

      formContentArea.appendChild(table);
      criteriaTables.push(table);
    }
    
    // Add feedback container (hidden initially)
    const feedbackSubmitContainer = document.createElement("div");
    feedbackSubmitContainer.id = "feedbackSubmitContainer";
    feedbackSubmitContainer.style.display = "none";
    feedbackSubmitContainer.innerHTML = `
      <div class="feedback-container">
        <div class="feedback-box">
          <label for="studentFeedback">OPTIONAL COMMENTS</label>
          <textarea id="studentFeedback" placeholder="Type your feedback here..."></textarea>
          <div class="feedback-badwords-msg" id="feedbackBadwordsMsg" aria-live="polite">
            Bad words is not allowed
          </div>
        </div>
      </div>
    `;
    
    formContentArea.appendChild(feedbackSubmitContainer);

    // Bad words filtering for feedback
    const feedbackTextarea = document.getElementById("studentFeedback");
    const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
    const badWordsList = [
      "putangina",
      "puta",
      "tangina",
      "tang ina",
      "gago",
      "tanga",
      "bobo",
      "ulol",
      "tarantado",
      "inutil",
      "leche",
      "bwiset",
      "bwisit",
      "punyeta",
      "fuck you",
      "fuck",
      "shit",
      "bitch",
      "asshole",
      "dick",
      "cunt",
      "faggot",
      "nigger"
    ];

    function normalizeFeedbackText(text) {
      return (" " + String(text || "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, " ")
        .replace(/\s+/g, " ")
        .trim() + " ");
    }

    function feedbackHasBadWords(text) {
      const normalized = normalizeFeedbackText(text);
      return badWordsList.some(w => normalized.includes(` ${w} `));
    }

    function setFeedbackBadwordsState(hasBadWords) {
      const feedbackBox = document.querySelector(".feedback-box");
      if (feedbackBox) feedbackBox.classList.toggle("has-badwords", hasBadWords);
      if (badwordsMsg) badwordsMsg.style.display = hasBadWords ? "block" : "none";
    }

    if (feedbackTextarea) {
      setFeedbackBadwordsState(false);
      feedbackTextarea.addEventListener("input", () => {
        setFeedbackBadwordsState(feedbackHasBadWords(feedbackTextarea.value));
      });
    }
    
    // Create navigation section
    const navigationSection = document.createElement("div");
    navigationSection.className = "evaluation-navigation";
    navigationSection.innerHTML = `
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
    
    // Assemble the main container
    mainEvaluationContainer.appendChild(headerSection);
    mainEvaluationContainer.appendChild(formContentArea);
    mainEvaluationContainer.appendChild(navigationSection);
    
    container.appendChild(mainEvaluationContainer);
    
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
    nextBtn.disabled = false;
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
  const studentId = document.getElementById('studentId')?.value?.trim() || '';
  const facultyId = window.selectedFaculty?.id || '';
  const feedbackText = document.getElementById('studentFeedback')?.value.trim() || '';

  // Prevent submission when feedback has bad words
  const normalizedFeedback = (" " + feedbackText.toLowerCase().replace(/[^a-z0-9]+/g, " ").replace(/\s+/g, " ").trim() + " ");
  const hasBadWords = [
    "putangina","puta","tangina","tang ina","gago","tanga","bobo","ulol","tarantado","inutil","leche","bwiset","bwisit","punyeta",
    "fuck you","fuck","shit","bitch","asshole","dick","cunt","faggot","nigger"
  ].some(w => normalizedFeedback.includes(` ${w} `));

  if (hasBadWords) {
    const feedbackBox = document.querySelector(".feedback-box");
    const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
    if (feedbackBox) feedbackBox.classList.add("has-badwords");
    if (badwordsMsg) badwordsMsg.style.display = "block";
    alert("Bad words is not allowed");
    updatePaginationButtons();
    return;
  }

  const feedbackBox = document.querySelector(".feedback-box");
  const badwordsMsg = document.getElementById("feedbackBadwordsMsg");
  if (feedbackBox) feedbackBox.classList.remove("has-badwords");
  if (badwordsMsg) badwordsMsg.style.display = "none";

  if (!facultyId) {
    alert('Please select a faculty member to evaluate.');
    updatePaginationButtons();
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
    updatePaginationButtons();
    return;
  }

  const nextBtn = document.getElementById("nextBtn");
  if (nextBtn) {
    nextBtn.disabled = true;
    nextBtn.textContent = "Submitting...";
  }

  const facultyLabel = window.selectedFaculty?.name || '';
  const submission = {
    student_id: studentId,
    faculty_id: facultyId,
    faculty_name: facultyLabel,
    answers: data,
    feedback: feedbackText
  };

  fetch("submit_evaluation.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(submission)
  })
    .then(r => r.json())
    .then(async res => {
      if (!res.success) {
        alert(res.message || "Failed to submit evaluation.");
        updatePaginationButtons();
        return;
      }
      alert(`Evaluation submitted! Your overall rating for this instructor: ${res.overall_rating}/5.00`);
      document.querySelectorAll('input[type="radio"]:checked').forEach(el => { el.checked = false; });
      const fb = document.getElementById('studentFeedback');
      if (fb) fb.value = '';
      
      // Refresh faculty cards to remove evaluated faculty
      await loadStudentFacultyCards();

      document.getElementById("evaluateSection").style.display = "block";
      document.getElementById("facultyCards").style.display = "block";
      document.getElementById("evaluationContainer").style.display = "none";
      window.selectedFaculty = null;
      
      // Reset to first criteria page
      currentCriteria = 0;
      criteriaTables.forEach((table, index) => {
        table.classList.toggle("active", index === 0);
      });
      updatePaginationButtons();
    })
    .catch(err => {
      console.error("Submit error:", err);
      alert("Error submitting evaluation. Please try again.");
      updatePaginationButtons();
    });
}

// Add event listener for password form back button
document.addEventListener("DOMContentLoaded", function() {
  const backBtn = document.getElementById("closePasswordForm");
  if (backBtn) {
    backBtn.addEventListener("click", function(e) {
      e.preventDefault();
      closePasswordForm();
    });
  }
});

// ================= EVALUATION HISTORY =================
async function showEvaluationHistory(event) {
  if (event) event.preventDefault();
  const dropdown = document.getElementById("dropdownMenu");
  if (dropdown) dropdown.style.display = "none";

  const studentId = document.getElementById('studentId')?.value.trim();
  
  if (!studentId) {
    alert('Student ID not found');
    return;
  }
  
  try {
    const response = await fetch('get_student_evaluation_history.php');
    const data = await response.json();
    
    if (!data.success) {
      alert(data.message || 'Failed to load evaluation history');
      return;
    }
    
    displayEvaluationHistory(data.data);
  } catch (error) {
    console.error('Error fetching evaluation history:', error);
    alert('Error loading evaluation history');
  }
}

function displayEvaluationHistory(history) {
  let historyModal = document.getElementById('historyModal');
  if (!historyModal) {
    historyModal = document.createElement('div');
    historyModal.id = 'historyModal';
    historyModal.className = 'historyform';
    document.body.appendChild(historyModal);
  }
  
  let historyContent = '';
  
  if (history.length === 0) {
    historyContent = `
      <div class="history-content">
        <div class="history-header">
          <h3>Evaluation History</h3>
          <button class="close-history-btn" onclick="closeHistoryModal()">×</button>
        </div>
        <div class="history-body">
          <p class="no-history">No evaluations submitted yet.</p>
        </div>
      </div>
    `;
  } else {
    const historyItems = history.map(item => `
      <div class="history-item">
        <div class="history-faculty">
          <i class="ph ph-user-circle"></i>
          <span class="faculty-name">${item.faculty_name}</span>
        </div>
        <div class="history-details">
          <div class="history-rating">
            <span class="rating-label">${item.rating_label}</span>
            <span class="rating-score">${item.overall_rating}/5.00</span>
          </div>
          <div class="history-date">${item.date_evaluated}</div>
        </div>
        ${item.feedback ? `<div class="history-feedback"><strong>Feedback:</strong> ${item.feedback}</div>` : ''}
      </div>
    `).join('');
    
    historyContent = `
      <div class="history-content">
        <div class="history-header">
          <h3>Evaluation History</h3>
          <button class="close-history-btn" onclick="closeHistoryModal()">×</button>
        </div>
        <div class="history-body">
          <div class="history-list">
            ${historyItems}
          </div>
        </div>
      </div>
    `;
  }
  
  historyModal.innerHTML = `
    <div class="logout-content">
      ${historyContent}
    </div>
  `;
  
  historyModal.style.display = 'flex';
  
  historyModal.addEventListener('click', function(event) {
    if (event.target === historyModal) {
      closeHistoryModal();
    }
  });
}

function closeHistoryModal() {
  const historyModal = document.getElementById('historyModal');
  if (historyModal) {
    historyModal.style.display = 'none';
  }
}

function displayEvaluationHistory(history) {
  let historyModal = document.getElementById('historyModal');
  if (!historyModal) {
    historyModal = document.createElement('div');
    historyModal.id = 'historyModal';
    historyModal.className = 'historyform';
    document.body.appendChild(historyModal);
  }

  const escapeHistoryHtml = value => String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

  const normalizeRatingLabel = label => {
    const value = String(label || "").trim();
    return value.toLowerCase() === "outstanding" ? "Excellent" : value;
  };

  const historyItems = history.length === 0
    ? `<p class="no-history">No evaluations submitted yet.</p>`
    : history.map(item => {
      const facultyName = escapeHistoryHtml(item.faculty_name);
      const initial = facultyName.trim().charAt(0).toUpperCase() || "F";
      const label = escapeHistoryHtml(normalizeRatingLabel(item.rating_label));
      const ratingClass = label.toLowerCase().replace(/\s+/g, "-");
      const rating = Number(item.overall_rating || 0).toFixed(2);
      const date = escapeHistoryHtml(item.date_evaluated);
      const feedback = escapeHistoryHtml(item.feedback || "");

      return `
        <div class="history-item" data-history-name="${facultyName.toLowerCase()}">
          <div class="history-item-top">
            <div class="history-main">
              <div class="history-avatar">${initial}</div>
              <div class="history-faculty-info">
                <div class="faculty-name">${facultyName}</div>
                <div class="history-date">${date}</div>
              </div>
            </div>
            <div class="history-rating">
              <div class="rating-label rating-${ratingClass}">${label}</div>
              <div class="rating-score">${rating}/5.00</div>
            </div>
          </div>
          ${feedback ? `<div class="history-feedback"><span>Feedback:</span> ${feedback}</div>` : ""}
        </div>
      `;
    }).join('');

  historyModal.innerHTML = `
    <div class="history-content">
      <button class="close-history-btn" onclick="closeHistoryModal()" aria-label="Close history">
        <i class="ph ph-x"></i>
      </button>
      <div class="history-header">
        <h3>Evaluation History</h3>
        <p>You will see your evaluation history here</p>
      </div>
      <div class="history-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="search" id="historySearchInput" placeholder="Search faculty">
      </div>
      <div class="history-body">
        <div class="history-list" id="historyList">
          ${historyItems}
        </div>
        <p class="no-history history-no-results" id="historyNoResults" style="display:none;">No matching evaluations found.</p>
      </div>
    </div>
  `;

  const searchInput = historyModal.querySelector("#historySearchInput");
  searchInput?.addEventListener("input", () => {
    const query = searchInput.value.trim().toLowerCase();
    const items = historyModal.querySelectorAll(".history-item");
    let visibleCount = 0;

    items.forEach(item => {
      const isVisible = item.dataset.historyName.includes(query);
      item.style.display = isVisible ? "flex" : "none";
      if (isVisible) visibleCount++;
    });

    const noResults = historyModal.querySelector("#historyNoResults");
    if (noResults) noResults.style.display = items.length && visibleCount === 0 ? "block" : "none";
  });

  historyModal.style.display = 'flex';
  historyModal.onclick = event => {
    if (event.target === historyModal) closeHistoryModal();
  };
}
