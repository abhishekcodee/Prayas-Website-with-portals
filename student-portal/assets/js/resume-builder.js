const container = document.getElementById("educationContainer");
const addBtn = document.getElementById("addEducation");

let educationCount = 1;

addBtn.addEventListener("click", function () {

    educationCount++;

    const year = new Date().getFullYear();

    let yearOptions = '<option value="">Select Passing Year</option>';

    for (let y = year; y >= 1980; y--) {
        yearOptions += `<option value="${y}">${y}</option>`;
    }

    const html = `
    <div class="education-item border rounded-3 p-4 mb-4 position-relative">

        <button type="button"
                class="btn btn-danger btn-sm position-absolute top-0 end-0 m-3 removeEducation">
            ✕
        </button>

        <h5 class="mb-4">
            Education ${educationCount}
        </h5>

        <div class="mb-3">

            <label class="form-label">
                Qualification
            </label>

            <select name="qualification[]" class="form-select qualification-select" required>

                <option value="">Select</option>

                <option>10th / दसवीं / दहावी</option>

                <option>ITI / आईटीआई / आयटीआय</option>

                <option>Other / अन्य / इतर</option>

            </select>

            <div class="other-qualification mt-3" style="display:none;">

                <input type="text"
                       name="other_qualification[]"
                       class="form-control other-input"
                       placeholder="Enter Qualification">

            </div>

        </div>

        <div class="mb-3">

            <label>
                Specialization
            </label>

            <input
                type="text"
                name="specialization[]"
                class="form-control">

        </div>

        <div class="mb-3">

            <label>
                Institute
            </label>

            <input
                type="text"
                name="institute[]"
                class="form-control"
                required>

        </div>

        <div class="mb-3">

            <label>
                Board / University
            </label>

            <input
                type="text"
                name="board[]"
                class="form-control">

        </div>

        <div class="row">

            <div class="col-md-6 mb-3">

                <label>
                    Passing Year
                </label>

                <select
                    name="passing_year[]"
                    class="form-select"
                    required>

                    ${yearOptions}

                </select>

            </div>

            <div class="col-md-6 mb-3">

                <label>
                    Percentage / CGPA
                </label>

                <input
                    type="text"
                    name="percentage[]"
                    class="form-control">

            </div>

        </div>

        <div class="mb-3">

            <label>
                Status
            </label>

            <select
                name="status[]"
                class="form-select">

                <option>Completed (पूर्ण | पूर्ण)</option>

                <option>Pursuing (अध्ययनरत | शिकत आहे)</option>

            </select>

        </div>

    </div>
    `;

    container.insertAdjacentHTML("beforeend", html);

    initializeOtherQualification();
});

document.addEventListener("click", function (e) {

    if (e.target.classList.contains("removeEducation")) {

        e.target.closest(".education-item").remove();

    }

});

function initializeOtherQualification() {

    document.querySelectorAll(".qualification-select").forEach(function (select) {

        select.removeEventListener("change", qualificationChange);

        select.addEventListener("change", qualificationChange);

    });

}

function qualificationChange() {

    const educationItem = this.closest(".education-item");

    const otherDiv = educationItem.querySelector(".other-qualification");

    const otherInput = educationItem.querySelector(".other-input");

    if (this.value.includes("Other")) {

        otherDiv.style.display = "block";
        otherInput.required = true;

    } else {

        otherDiv.style.display = "none";
        otherInput.required = false;
        otherInput.value = "";

    }

}

initializeOtherQualification();