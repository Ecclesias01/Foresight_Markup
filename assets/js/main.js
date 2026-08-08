const counters = document.querySelectorAll(".counter");

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {

        if (!entry.isIntersecting) return;

        const counter = entry.target;
        const target = parseInt(counter.dataset.target);
        let current = 0;
        const duration = 2000; // 2 seconds
        const step = Math.ceil(target / (duration / 16));

        function update() {
            current += step;

            if (current < target) {

                if (target === 50000) {
                    counter.textContent = current.toLocaleString();
                } else {
                    counter.textContent = current;
                }

                requestAnimationFrame(update);

            } else {

                if (target === 50000) {
                    counter.textContent = "50K+";
                } else if (target === 12) {
                    counter.textContent = "₦12B+";
                } else if (target === 98) {
                    counter.textContent = "98%";
                }

            }
        }

        update();
        observer.unobserve(counter);

    });
}, { threshold: 0.5 });

counters.forEach(counter => observer.observe(counter));

document.addEventListener("DOMContentLoaded", () => {
  const track = document.getElementById("testimonialTrack");
  
  // 1. Clone cards to create a seamless infinite loop
  const cards = Array.from(track.children);
  cards.forEach((card) => {
    const clone = card.cloneNode(true);
    track.appendChild(clone);
  });

  // 2. Auto-scroll configuration
  let speed = 1; // Speed in pixels per frame (increase/decrease to adjust)
  let isHovered = false;
  let animationFrameId;

  function scroll() {
    if (!isHovered) {
      track.scrollLeft += speed;

      // When reaching half the track width (the start of cloned elements), reset to top seamlessly
      if (track.scrollLeft >= track.scrollWidth / 2) {
        track.scrollLeft = 0;
      }
    }
    animationFrameId = requestAnimationFrame(scroll);
  }

  // 3. Pause auto-scroll on hover so users can comfortably read reviews
  track.addEventListener("mouseenter", () => {
    isHovered = true;
  });

  track.addEventListener("mouseleave", () => {
    isHovered = false;
  });

  // 4. Mobile touch drag support
  let isDown = false;
  let startX;
  let scrollLeft;

  track.addEventListener("touchstart", () => {
    isHovered = true;
  });

  track.addEventListener("touchend", () => {
    isHovered = false;
  });

  // Start the auto-scroll animation
  scroll();
});

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("applyForm");
  const submitBtn = document.getElementById("submitBtn");

  form.addEventListener("submit", (e) => {
    e.preventDefault(); // Prevent page reload

    // Change button state to simulate sending
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = `<span>Submitting...</span>`;
    submitBtn.style.opacity = "0.7";
    submitBtn.disabled = true;

    // Simulate API call / Network Delay
    setTimeout(() => {
      // Success feedback
      submitBtn.innerHTML = `✓ Submitted Successfully!`;
      submitBtn.style.background = "#10b981"; // Green color
      submitBtn.style.opacity = "1";

      // Reset form fields
      form.reset();

      // Reset button back to default after 3 seconds
      setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.style.background = "var(--btn-gradient)";
        submitBtn.disabled = false;
      }, 3000);
    }, 1500);
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const searchForm = document.getElementById("errorSearchForm");
  const searchInput = document.getElementById("searchInput");

  searchForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const query = searchInput.value.trim();

    if (query) {
      // Redirect to your site's search results page or homepage with query
      window.location.href = `/?search=${encodeURIComponent(query)}`;
    }
  });
});
