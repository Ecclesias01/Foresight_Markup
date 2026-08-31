document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("applyForm");
  const submitBtn = document.getElementById("submitBtn");

  if (!form || !submitBtn) {
    console.error("Contact form or submit button not found.");
    return;
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = `<span>Submitting...</span>`;
    submitBtn.style.opacity = "0.7";
    submitBtn.disabled = true;

    try {
      const formData = new FormData(form);

      const response = await fetch("process-contact.php", {
        method: "POST",
        body: formData
      });

      // Read the response as TEXT first
      const rawResponse = await response.text();

      console.log("HTTP Status:", response.status);
      console.log("Raw PHP Response:", rawResponse);

      // Try to convert the response to JSON
      let result;

      try {
        result = JSON.parse(rawResponse);
      } catch (jsonError) {
        throw new Error(
          "PHP did not return valid JSON.\n\nServer response:\n" +
          (rawResponse || "[EMPTY RESPONSE]")
        );
      }

      console.log("PHP JSON Response:", result);

      if (response.ok && result.status === "success") {

        submitBtn.innerHTML = `✓ Submitted Successfully!`;
        submitBtn.style.background = "#10b981";
        submitBtn.style.opacity = "1";

        form.reset();

        alert(result.message || "Message sent successfully!");

      } else {

        throw new Error(
          result.message || "Unable to send your message."
        );
      }

    } catch (error) {

      console.error("Contact Form Error:", error);

      submitBtn.innerHTML = `❌ Failed. Try Again`;
      submitBtn.style.background = "#ef4444";
      submitBtn.style.opacity = "1";

      alert(error.message);
    }

    setTimeout(() => {
      submitBtn.innerHTML = originalText;
      submitBtn.style.background = "var(--btn-gradient)";
      submitBtn.style.opacity = "1";
      submitBtn.disabled = false;
    }, 3000);
  });
});

/* =========================================
   FORESIGHT PAGE LOADER
========================================= */

(function () {

  const loader = document.getElementById("page-loader");
  const progress = document.getElementById("loader-progress");
  const percent = document.getElementById("loader-percent");

  if (!loader || !progress || !percent) {
    return;
  }

  let value = 0;

  const loading = setInterval(function () {

    value++;

    progress.style.width = value + "%";
    percent.textContent = value + "%";

    if (value >= 100) {

      clearInterval(loading);

      setTimeout(function () {
        loader.classList.add("hidden");
      }, 300);

    }

  }, 20);

})();
