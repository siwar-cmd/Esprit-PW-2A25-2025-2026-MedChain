function openModal() {
    const modal = document.getElementById("modal");
    if (modal) {
        modal.style.display = "block";
    }
}

function closeModal() {
    const modal = document.getElementById("modal");
    if (modal) {
        modal.style.display = "none";
    }
}

/* ================= DELETE MODAL ================= */

function openDeleteModal(id) {
    const input = document.getElementById("deleteId");
    const modal = document.getElementById("deleteModal");

    if (input) {
        input.value = id;
    }

    if (modal) {
        modal.style.display = "block";
    }
}

function closeDeleteModal() {
    const modal = document.getElementById("deleteModal");
    if (modal) {
        modal.style.display = "none";
    }
}

/* ================= CLOSE WHEN CLICK OUTSIDE ================= */

window.onclick = function(event) {
    const modal = document.getElementById("modal");
    const deleteModal = document.getElementById("deleteModal");

    // close add/edit modal
    if (event.target === modal) {
        modal.style.display = "none";
    }

    // close delete modal
    if (event.target === deleteModal) {
        deleteModal.style.display = "none";
    }
};