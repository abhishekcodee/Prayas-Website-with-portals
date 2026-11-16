<div class="card summary-card">

    <div class="section-title d-flex justify-content-between align-items-center">

        <span>
            <i class="fa-solid fa-gears text-primary"></i>
            Skills
        </span>

        <button
            type="button"
            id="editSkills"
            class="btn btn-warning">

            <i class="fa-solid fa-pen"></i>

            Edit

        </button>

    </div>

    <div class="card-body">

        <div id="skillsView">

        <?php if(count($skills)>0){ ?>

<div class="row">

<?php foreach($skills as $skill){ ?>

<div class="col-md-6 mb-3">

    <?php

    $badge = "secondary";

    switch($skill['skill_level']){

        case "Beginner":
            $badge = "warning";
            break;

        case "Intermediate":
            $badge = "info";
            break;

        case "Advanced":
            $badge = "success";
            break;

    }

    ?>

    <div class="card border shadow-sm">

        <div class="card-body py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h6 class="mb-1">

                        <?= htmlspecialchars($skill['skill_name']) ?>

                    </h6>

                    <small class="text-muted">

                        Technical Skill

                    </small>

                </div>

                <span class="badge bg-<?= $badge ?>">

                    <?= htmlspecialchars($skill['skill_level']) ?>

                </span>

            </div>

        </div>

    </div>

</div>

<?php } ?>

</div>

<?php }else{ ?>

<div class="text-center py-4">

    <i class="fa-solid fa-gears fa-3x text-secondary mb-3"></i>

    <h5>No Skills Added</h5>

    <p class="text-muted">

        Click <strong>Edit</strong> to add your skills.

    </p>

</div>

<?php } ?>

</div>

<div id="skillsEdit" style="display:none;">

<form action="update-skills.php" method="POST">

<div id="skillContainer">

<?php if(count($skills)>0){ ?>

<?php foreach($skills as $skill){ ?>

<div class="row mb-3 skillRow">

    <div class="col-md-5">

        <label class="form-label">Skill</label>

        <input
            type="text"
            name="skill_name[]"
            class="form-control"
            value="<?= htmlspecialchars($skill['skill_name']) ?>"
            list="skillList"
            autocomplete="off"
            required>

    </div>

    <div class="col-md-5">

        <label class="form-label">Skill Level</label>

        <select
            name="skill_level[]"
            class="form-select">

            <option value="Beginner"
            <?= $skill['skill_level']=="Beginner"?"selected":"" ?>>
            Beginner
            </option>

            <option value="Intermediate"
            <?= $skill['skill_level']=="Intermediate"?"selected":"" ?>>
            Intermediate
            </option>

            <option value="Advanced"
            <?= $skill['skill_level']=="Advanced"?"selected":"" ?>>
            Advanced
            </option>

        </select>

    </div>

    <div class="col-md-2 d-flex align-items-end">

        <button
            type="button"
            class="btn btn-danger removeSkill">

            <i class="fa-solid fa-trash"></i>

        </button>

    </div>

</div>

<?php } ?>

<?php } ?>

</div>

<datalist id="skillList">

<option>Carpenter</option>
    <option>Draughtsman Civil</option>
    <option>Electrician</option>
    <option>Fitter</option>
    <option>Maintenance Mechanic</option>
    <option>Plumber</option>
     <option>Surveyor</option>
     <option>Tools & Die maker</option>
    <option>Turner</option>
    <option>Welder</option>
    <option>Wireman</option>

</datalist>

<div class="mt-3">

<button
type="button"
id="addSkill"
class="btn btn-primary">

<i class="fa-solid fa-plus"></i>

Add Skill

</button>

</div>

<div class="text-end mt-4">

<button
type="submit"
class="btn btn-success">

<i class="fa-solid fa-floppy-disk"></i>

Save Changes

</button>

<button
type="button"
id="cancelSkills"
class="btn btn-secondary ms-2">

Cancel

</button>

</div>

</form>

</div>