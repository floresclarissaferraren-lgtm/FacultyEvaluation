// ================= dropdown =================
function toggleDropdown() {
  const menu = document.getElementById("dropdownMenu");
  menu.style.display = (menu.style.display === "flex") ? "none" : "flex";
}

document.addEventListener("click", e => {
  const dropdown = document.getElementById("dropdownMenu");
  const studentBox = document.querySelector(".student-box");
  if (!studentBox.contains(e.target)) dropdown.style.display = "none";
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
