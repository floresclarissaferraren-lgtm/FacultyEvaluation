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