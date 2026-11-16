document.addEventListener("DOMContentLoaded", function () {

    /* ==========================================
       1. MOBILE SIDEBAR & BACKDROP OVERLAY
       ========================================== */
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.querySelector(".admin-sidebar");
    
    // Create overlay backdrop if it doesn't exist
    let overlay = document.querySelector(".sidebar-overlay");
    if (!overlay) {
        overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        document.body.appendChild(overlay);
    }

    function toggleSidebar() {
        if (sidebar) {
            const isOpen = sidebar.classList.toggle("show");
            overlay.classList.toggle("show", isOpen);
        }
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    if (overlay) {
        overlay.addEventListener("click", function () {
            if (sidebar && sidebar.classList.contains("show")) {
                sidebar.classList.remove("show");
                overlay.classList.remove("show");
            }
        });
    }


    /* ==========================================
       2. ANIMATED COUNT-UP NUMBERS
       ========================================== */
    const countElements = document.querySelectorAll(".stat-number, .pipeline-count");
    
    function animateCounter(element) {
        const target = parseInt(element.getAttribute("data-target"), 10);
        if (isNaN(target) || target === 0) {
            element.textContent = "0";
            return;
        }

        const duration = 1200; // ms
        const startTime = performance.now();
        const startValue = 0;

        function updateCounter(currentTime) {
            const elapsedTime = currentTime - startTime;
            const progress = Math.min(elapsedTime / duration, 1);
            
            // Ease-out cubic formula
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const currentValue = Math.floor(startValue + (target - startValue) * easeProgress);

            element.textContent = currentValue.toLocaleString();

            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target.toLocaleString();
            }
        }

        requestAnimationFrame(updateCounter);
    }

    // Trigger count-up animation after slight delay for smooth entrance
    setTimeout(() => {
        countElements.forEach(element => animateCounter(element));
    }, 250);


    /* ==========================================
       3. PIPELINE PROGRESS BARS ANIMATION
       ========================================== */
    const progressFills = document.querySelectorAll(".pipeline-bar-fill");
    
    setTimeout(() => {
        progressFills.forEach(fill => {
            const percentage = fill.getAttribute("data-percentage") || 0;
            fill.style.width = percentage + "%";
        });
    }, 450);

});