// Tracking simples para ContentView
(function () {
  "use strict";

  // Função para obter parâmetros da URL
  function getURLParameter(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name) || "";
  }

  // Função para enviar evento ContentView
  function sendContentView() {
    const clickId = getURLParameter("click_id");

    // Só envia se tiver click_id
    if (!clickId) {
      console.log("Tracking: No click_id found, skipping ContentView event");
      return;
    }

    const payload = {
      click_id: clickId,
    };

    fetch("/api/track_content_view.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(payload),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log(
            "Tracking: ContentView sent successfully for click_id:",
            clickId
          );
        } else {
          console.error("Tracking: ContentView failed:", data.error);
        }
      })
      .catch((error) => {
        console.error("Tracking: Error sending ContentView:", error);
      });
  }

  // Enviar ContentView quando a página carregar
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", sendContentView);
  } else {
    sendContentView();
  }
})();
