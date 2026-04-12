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

window.confirmLogout = () => location.href = "EvalMain.php";
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
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  }
}

// ================= switch section =================

function showEvaluateSection() {
  document.getElementById("mainPage").style.display = "none";
  document.getElementById("evaluateSection").style.display = "block";
  document.getElementById("ratingLegends").style.display = "block";
  document.getElementById("evaluationContainer").style.display = "block";
  document.querySelector(".feedback-container").style.display = "block"; 

  const submitBtn = document.getElementById("submitEvaluation");
  if (submitBtn) {
    submitBtn.style.display = "inline-flex";
  }

  loadFacultyCategories(); 
}
function goBackToMain() {
  document.getElementById("evaluateSection").style.display = "none";
  document.getElementById("ratingLegends").style.display = "none";
  document.getElementById("evaluationContainer").style.display = "none";
  document.getElementById("submitEvaluation").style.display = "none";
  document.getElementById("mainPage").style.display = "flex";
    document.querySelector(".feedback-container").style.display = "none"; 
}
// ================= LOAD CATEGORIES + QUESTIONS =================
async function loadFacultyCategories() {
  try {
    const res = await fetch("/FacultyEvaluation/get_category.php");
    const categories = await res.json();
    console.log("Categories:", categories);

    const container = document.getElementById("evaluationContainer");
    container.innerHTML = "";

    for (const c of categories) {
      const table = document.createElement("table");
      table.className = "evaluationform";
      table.innerHTML = `
  <thead>
    <tr><th><i class="fa-solid fa-graduation-cap section-icon"></i> 
  ${c.category_name.toUpperCase()}</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr>
  </thead>
  <tbody></tbody>
`;
      const tbody = table.querySelector("tbody");
      const qRes = await fetch(`/FacultyEvaluation/get_question.php?category_id=${c.id}`);
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
          tbody.appendChild(tr);});
        } else { const tr = document.createElement("tr");
        tr.innerHTML = `<td colspan="6">No questions available</td>`;
        tbody.appendChild(tr);}

      container.appendChild(table);}
  } catch (err) {
    console.error("Error loading faculty categories:", err);
  }
}

// ================= SUBMIT =================
function submitEvaluation() {
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

  console.log("Submitted:", data);
  alert("Evaluation submitted!");
}
