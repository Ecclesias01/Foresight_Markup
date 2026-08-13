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

document.addEventListener("DOMContentLoaded", () => {

    /* =========================================
       MISSION / VISION
       10 SECOND LOOP
    ========================================= */

    const slides =
        document.querySelectorAll(".purpose-slide");

    if (slides.length > 0) {

        let currentSlide = 0;

        const slideDuration = 10000;


        slides.forEach((slide, index) => {

            slide.classList.toggle(
                "active",
                index === 0
            );

        });


        setInterval(() => {

            slides[currentSlide]
                .classList
                .remove("active");


            currentSlide =
                (currentSlide + 1) % slides.length;


            slides[currentSlide]
                .classList
                .add("active");

        }, slideDuration);

    }



    /* =========================================
       IMAGE POSITION ROTATION
    ========================================= */

    const images =
        document.querySelectorAll(".purpose-image");

    if (images.length === 3) {

        /* Initial arrangement */

        images[0].classList.add("position-left");

        images[1].classList.add("position-center");

        images[2].classList.add("position-right");


        /* Rotate positions every 5 seconds */

        // setInterval(() => {

        //     const leftImage =
        //         document.querySelector(
        //             ".purpose-image.position-left"
        //         );

        //     const centerImage =
        //         document.querySelector(
        //             ".purpose-image.position-center"
        //         );

        //     const rightImage =
        //         document.querySelector(
        //             ".purpose-image.position-right"
        //         );


        //     /*
        //      * LEFT → RIGHT
        //      * CENTER → LEFT
        //      * RIGHT → CENTER
        //      */

        //     leftImage.classList.remove(
        //         "position-left"
        //     );

        //     leftImage.classList.add(
        //         "position-right"
        //     );


        //     centerImage.classList.remove(
        //         "position-center"
        //     );

        //     centerImage.classList.add(
        //         "position-left"
        //     );


        //     rightImage.classList.remove(
        //         "position-right"
        //     );

        //     rightImage.classList.add(
        //         "position-center"
        //     );

        // }, 5000);

    }

});

// About us Lightweight Scroll Animation Trigger (Add inside <script> tag before </body>)
document.addEventListener("DOMContentLoaded", function () {

  const loader = document.getElementById("page-loader");
  const progress = document.getElementById("loader-progress");
  const percent = document.getElementById("loader-percent");

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

});
