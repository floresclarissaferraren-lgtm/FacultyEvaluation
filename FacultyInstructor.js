// DROPDOWN
function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");

  menu.style.display = (menu.style.display === "block") ? "none" : "block";}
function logout(e){
  e.preventDefault();
  document.getElementById("logoutModal").style.display = "flex";}

function closeLogoutModal(){
  document.getElementById("logoutModal").style.display = "none";}

function confirmLogout(){
  window.location.href = "EvalMain.php";}

function showPasswordForm(){
  document.getElementById("passwordForm").style.display = "flex";}

function closePasswordForm(){
  document.getElementById("passwordForm").style.display = "none";}

function showProfile(){
  // Close dropdown first
  document.getElementById("dropdownMenu").style.display = "none";
  
  // Get faculty information from hidden fields
  const facultyId = document.getElementById("facultyId")?.value;
  const facultyName = document.getElementById("facultyName")?.value;
  const facultyEmail = document.getElementById("facultyEmail")?.value;
  
  // Create profile modal content
  const profileContent = `
    <div class="profile-header">
      <div class="profile-icon">
        <i class="ph ph-user-circle"></i>
      </div>
      <h3>Faculty Profile</h3>
    </div>
    <div class="profile-info">
      <p><strong>Full Name:</strong> ${facultyName || 'N/A'}</p>
      <p><strong>Faculty ID:</strong> ${facultyId || 'N/A'}</p>
      <p><strong>Email:</strong> ${facultyEmail || 'N/A'}</p>
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

function togglePassword(fieldId, icon) {
  const field = document.getElementById(fieldId);
  if (field.type === "password") {
    field.type = "text";
    icon.classList.remove("ph-eye-slash");
    icon.classList.add("ph-eye");
  } else {
    field.type = "password";
    icon.classList.remove("ph-eye");
    icon.classList.add("ph-eye-slash");
  }
}

function updatePassword() {
  const facultyId = document.getElementById("facultyId").value;
  const oldPass = document.getElementById("oldPass").value;
  const newPass = document.getElementById("newPass").value;
  const confirmPass = document.getElementById("confirmPass").value;

  // Validation
  if (!oldPass || !newPass || !confirmPass) {
    alert("All fields are required.");
    return;
  }

  if (newPass !== confirmPass) {
    alert("New passwords do not match.");
    return;
  }

  if (newPass.length < 8) {
    alert("Password must be at least 8 characters long.");
    return;
  }

  // Send update request
  fetch("update_facultyPassForm.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      faculty_id: facultyId,
      old_password: oldPass,
      new_password: newPass
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Password updated successfully!");
      closePasswordForm();
      // Clear form fields
      document.getElementById("oldPass").value = "";
      document.getElementById("newPass").value = "";
      document.getElementById("confirmPass").value = "";
    } else {
      alert(data.message || "Failed to update password.");
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("An error occurred while updating password.");
  });
}
// ================= GLOBAL CLICK EVENTS =================

window.addEventListener("click", function(e){
  const modal = document.getElementById("passwordForm");
  if(e.target === modal){
    closePasswordForm();}});

document.addEventListener("click", function(e){
  const menu = document.getElementById("dropdownMenu");
  const trigger = document.querySelector(".instructor-box");

  if (menu.style.display === "block" &&
      !trigger.contains(e.target) &&
      !menu.contains(e.target)) {
    menu.style.display = "none";
  }
});
document.getElementById("logoutModal").addEventListener("click", function(e){
  if (e.target.id === "logoutModal") {
    closeLogoutModal();
  }
});

function loadFacultyStats() {
  fetch("get_faculty_stats.php")
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;
      const overallEl = document.getElementById("overallRatingValue");
      const responsesEl = document.getElementById("totalResponsesValue");

      if (overallEl) {
        overallEl.textContent = `${data.overall_rating} / 5.00 - ${data.rating_label}`;
      }
      if (responsesEl) {
        responsesEl.textContent = `${data.total_responses}`;
      }
    })
    .catch(err => {
      console.error("Failed to load faculty stats:", err);
    });
}

document.addEventListener("DOMContentLoaded", loadFacultyStats);
