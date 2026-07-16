function requireLogin() {
  const token = localStorage.getItem("hal_token");
  if (!token) {
    window.location.href = "index.html";
    return false;
  }
  return true;
}

function currentUsername() {
  return localStorage.getItem("hal_username") || "";
}

function logout() {
  localStorage.removeItem("hal_token");
  localStorage.removeItem("hal_username");
  window.location.href = "index.html";
}
