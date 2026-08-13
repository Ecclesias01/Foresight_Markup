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

  if (!track) {
    return;
  }

  // Clone cards to create a seamless infinite loop
  const cards = Array.from(track.children);

  cards.forEach((card) => {
    const clone = card.cloneNode(true);
    track.appendChild(clone);
  });

  let speed = 1;
  let isHovered = false;

  function scroll() {

    if (!isHovered) {

      track.scrollLeft += speed;

      if (track.scrollLeft >= track.scrollWidth / 2) {
        track.scrollLeft = 0;
      }

    }

    requestAnimationFrame(scroll);
  }

  track.addEventListener("mouseenter", () => {
    isHovered = true;
  });

  track.addEventListener("mouseleave", () => {
    isHovered = false;
  });

  track.addEventListener("touchstart", () => {
    isHovered = true;
  });

  track.addEventListener("touchend", () => {
    isHovered = false;
  });

  scroll();

});

document.addEventListener("DOMContentLoaded", () => {

  const form = document.getElementById("applyForm");
  const submitBtn = document.getElementById("submitBtn");

  if (!form || !submitBtn) {
    return;
  }

  form.addEventListener("submit", (e) => {

    e.preventDefault();

    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = `<span>Submitting...</span>`;
    submitBtn.style.opacity = "0.7";
    submitBtn.disabled = true;

    setTimeout(() => {

      submitBtn.innerHTML = `✓ Submitted Successfully!`;
      submitBtn.style.background = "#10b981";
      submitBtn.style.opacity = "1";

      form.reset();

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

/* =========================================
   SCROLL REVEAL ANIMATION ABOUT US
========================================= */

document.addEventListener("DOMContentLoaded", function () {

  const revealElements = document.querySelectorAll(".scroll-reveal");

  if (revealElements.length === 0) {
    return;
  }

  const revealObserver = new IntersectionObserver(
    function (entries, observer) {

      entries.forEach(function (entry) {

        if (entry.isIntersecting) {

          entry.target.classList.add("visible");

          observer.unobserve(entry.target);

        }

      });

    },
    {
      threshold: 0.15
    }
  );

  revealElements.forEach(function (element) {
    revealObserver.observe(element);
  });

});