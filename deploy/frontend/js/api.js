async function apiCall(path, { method = "GET", body = null, auth = true } = {}) {
  const headers = { "Content-Type": "application/json" };
  if (auth) {
    const token = localStorage.getItem("hal_token");
    if (token) headers["Authorization"] = "Bearer " + token;
  }

  let res;
  try {
    res = await fetch(API_BASE + path, {
      method,
      headers,
      body: body ? JSON.stringify(body) : null,
    });
  } catch (err) {
    throw new Error(
      "Could not reach the server. Check your internet connection or that the backend is running."
    );
  }

  let data;
  try {
    data = await res.json();
  } catch {
    throw new Error("Unexpected response from server (status " + res.status + ")");
  }

  if (res.status === 401) {
    localStorage.removeItem("hal_token");
    localStorage.removeItem("hal_username");
    window.location.href = "index.html";
    return;
  }

  if (!res.ok || data.success === false) {
    throw new Error(data.message || "Request failed");
  }

  return data;
}
