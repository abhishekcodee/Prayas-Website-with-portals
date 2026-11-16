let languageIndex = 0;

const container = document.getElementById("languageContainer");
const addBtn = document.getElementById("addLanguage");

// Add First Language Row
addLanguage();

addBtn.addEventListener("click", function () {
    addLanguage();
});

function addLanguage() {

    let html = `

    <div class="card mb-3 languageRow shadow-sm">

        <div class="card-body">

            <div class="row align-items-center">

                <div class="col-md-5">

                    <label class="form-label">
                        Language
                    </label>

                    <select
                        class="form-select languageSelect"
                        name="language_id[]"
                        required>

                        <option value="">
                            Select Language
                        </option>

                        ${languageOptions()}

                    </select>

                </div>

                <div class="col-md-5">

                    <label class="form-label d-block">
                        Proficiency
                    </label>

                    <div class="form-check form-check-inline">

                        <input
                        class="form-check-input"
                        type="checkbox"
                        name="can_read_${languageIndex}">

                        <label class="form-check-label">
                            Read
                        </label>

                    </div>

                    <div class="form-check form-check-inline">

                        <input
                        class="form-check-input"
                        type="checkbox"
                        name="can_write_${languageIndex}">

                        <label class="form-check-label">
                            Write
                        </label>

                    </div>

                    <div class="form-check form-check-inline">

                        <input
                        class="form-check-input"
                        type="checkbox"
                        name="can_speak_${languageIndex}">

                        <label class="form-check-label">
                            Speak
                        </label>

                    </div>

                </div>

                <div class="col-md-2 text-end">

                    <button
                    type="button"
                    class="btn btn-danger removeBtn">

                        Remove

                    </button>

                </div>

            </div>

        </div>

    </div>

    `;

    container.insertAdjacentHTML("beforeend", html);

    languageIndex++;

}

container.addEventListener("click", function(e){

    if(e.target.classList.contains("removeBtn")){

        if(document.querySelectorAll(".languageRow").length==1){

            alert("Minimum one language is required.");

            return;

        }

        e.target.closest(".languageRow").remove();

    }

});

function languageOptions(){

    let html="";

    languages.forEach(function(lang){

        html+=`
        <option value="${lang.id}">
            ${lang.name}
        </option>
        `;

    });

    return html;

}

document.getElementById("profileForm").addEventListener("submit",function(e){

    let rows=document.querySelectorAll(".languageRow");

    let selected=[];

    for(let row of rows){

        let language=row.querySelector(".languageSelect").value;

        if(language==""){

            alert("Please select language.");

            e.preventDefault();

            return;

        }

        if(selected.includes(language)){

            alert("Duplicate language selected.");

            e.preventDefault();

            return;

        }

        selected.push(language);

        let checks=row.querySelectorAll("input[type=checkbox]");

        let ok=false;

        checks.forEach(c=>{

            if(c.checked){

                ok=true;

            }

        });

        if(!ok){

            alert("Select Read, Write or Speak.");

            e.preventDefault();

            return;

        }

    }

});