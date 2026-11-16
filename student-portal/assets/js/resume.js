const uploadBox = document.querySelector(".upload-box");
const fileInput = document.getElementById("resume");
const chooseBtn = document.getElementById("chooseResume");
const fileName = document.getElementById("fileName");

// Choose button
chooseBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    fileInput.click();
});

// Upload box click
uploadBox.addEventListener("click", function () {
    fileInput.click();
});

// Drag over
uploadBox.addEventListener("dragover", function (e) {
    e.preventDefault();
    uploadBox.style.borderColor = "#0d6efd";
});

// Drag leave
uploadBox.addEventListener("dragleave", function () {
    uploadBox.style.borderColor = "#cfd8dc";
});

// Drop
uploadBox.addEventListener("drop", function (e) {
    e.preventDefault();

    fileInput.files = e.dataTransfer.files;

    showFile();
});

// File change
fileInput.addEventListener("change", showFile);

function showFile() {

    if (fileInput.files.length > 0) {

        fileName.innerHTML = "Selected File : " + fileInput.files[0].name;

    }

}