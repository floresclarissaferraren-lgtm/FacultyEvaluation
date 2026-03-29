// ================= dropdown =================
function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "block") ? "none" : "block";
}

// Show/close form
window.showLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "flex";
  document.getElementById("dropdownMenu").style.display = "none";
};
window.closeLogoutModal = () => {
  document.getElementById("logoutModal").style.display = "none";
};

// Confirm/logout
window.confirmLogout = () => location.href = "EvalMain.html";
window.logout = e => { 
  e.preventDefault(); 
  showLogoutModal(); 
};

// Close dropdown when clicking outside
document.addEventListener("click", e => {
  const m = document.getElementById("dropdownMenu"),
        t = document.querySelector(".admin-box");
  if (m.style.display === "block" && !t.contains(e.target) && !m.contains(e.target)) {
    m.style.display = "none";
  }
});

// Close modal when clicking outside content
document.getElementById("logoutModal").addEventListener("click", e => {
  if (e.target === document.getElementById("logoutModal")) closeLogoutModal();
});

// ================= page switch =================
function showEvaluateSection() {
  document.getElementById("mainPage").style.display = "none";
  document.getElementById("evaluateSection").style.display = "block";
  document.getElementById("ratingLegends").style.display = "block";
}
function goBackToMain() {
  document.getElementById("evaluateSection").style.display = "none";
  document.getElementById("ratingLegends").style.display = "none";

  document.getElementById("mainPage").style.display = "flex";

}


function showPasswordForm(){
  document.getElementById("passwordForm").style.display="flex";
}
function closePasswordForm(){
  document.getElementById("passwordForm").style.display="none";
}
function updatePassword(){
  const inputs=document.querySelectorAll("#passwordForm input");
  if(inputs[1].value!==inputs[2].value){
    alert("New passwords do not match!");
    return;
  }
  alert("Password updated successfully!");
  closePasswordForm();
}
// Close when clicking outside
document.getElementById("passwordForm").addEventListener("click",e=>{
  if(e.target.id==="passwordForm") closePasswordForm();
});
