// C:\laragon\www\destek_as\assets\js\app.js

document.addEventListener("DOMContentLoaded", () => {
    // Modal controls
    const modals = document.querySelectorAll(".modal");
    const modalCloses = document.querySelectorAll(".modal-close, [data-dismiss='modal']");

    modalCloses.forEach(btn => {
        btn.addEventListener("click", () => {
            const modal = btn.closest(".modal");
            if (modal) {
                modal.classList.remove("show");
            }
        });
    });

    window.addEventListener("click", (e) => {
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.classList.remove("show");
            }
        });
    });

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll(".alert-dismissible");
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add("show");
    }
}

function hideModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove("show");
    }
}
