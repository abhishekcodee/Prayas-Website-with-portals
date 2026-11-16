<?php

$siblingStmt = $pdo->prepare("
SELECT *
FROM student_siblings
WHERE user_id=?
ORDER BY id ASC
");

$siblingStmt->execute([$user_id]);

$siblings = $siblingStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="card summary-card">

    <div class="section-title d-flex justify-content-between align-items-center">

        <span>
            <i class="fa-solid fa-people-roof text-primary"></i>
            Family Details
        </span>

        <button
            type="button"
            id="editFamily"
            class="btn btn-warning">

            <i class="fa-solid fa-pen"></i>
            Edit

        </button>

    </div>

    <div class="card-body">

        <!-- =======================
             VIEW MODE
        ======================== -->

        <div id="familyView">

            <h5 class="text-primary mb-3">
                Father Details
            </h5>

            <table class="table table-bordered">

                <tr>
                    <th width="25%">Name</th>
                    <td><?= htmlspecialchars($profile['father_name']) ?></td>

                    <th width="25%">Age</th>
                    <td><?= $profile['father_age'] ?: '-' ?></td>
                </tr>

                <tr>

                    <th>Qualification</th>

                    <td><?= htmlspecialchars($profile['father_qualification']) ?></td>

                    <th>Occupation</th>

                    <td>
                        <?= $profile['father_occupation'] ?: '-' ?>
                    </td>

                </tr>

                <tr>

                    <th>Annual Income</th>

                    <td colspan="3">

                        <?= $profile['father_income'] ? "₹ ".number_format($profile['father_income']) : '-' ?>

                    </td>

                </tr>

            </table>


            <h5 class="text-success mt-4 mb-3">
                Mother Details
            </h5>

            <table class="table table-bordered">

                <tr>

                    <th width="25%">Name</th>

                    <td><?= htmlspecialchars($profile['mother_name']) ?></td>

                    <th width="25%">Age</th>

                    <td><?= $profile['mother_age'] ?: '-' ?></td>

                </tr>

                <tr>

                    <th>Qualification</th>

                    <td><?= htmlspecialchars($profile['mother_qualification']) ?></td>

                    <th>Occupation</th>

                    <td>
                        <?= $profile['mother_occupation'] ?: '-' ?>
                    </td>

                </tr>

                <tr>

                    <th>Annual Income</th>

                    <td colspan="3">

                        <?= $profile['mother_income'] ? "₹ ".number_format($profile['mother_income']) : '-' ?>

                    </td>

                </tr>

            </table>

        <hr>

<h5 class="text-info mt-4 mb-3">
    Brother / Sister Details
</h5>

<?php if(count($siblings) > 0){ ?>

<div class="table-responsive">

<table class="table table-bordered">

    <thead class="table-info">

        <tr>
            <th>Relation</th>
            <th>Name</th>
            <th>Age</th>
            <th>Qualification</th>
            <th>Occupation</th>
            <th>Annual Income</th>
        </tr>

    </thead>

    <tbody>

    <?php foreach($siblings as $row){ ?>

        <tr>

            <td><?= htmlspecialchars($row['relation']) ?></td>

            <td><?= htmlspecialchars($row['name']) ?></td>

            <td><?= $row['age'] ?: '-' ?></td>

            <td><?= $row['qualification'] ?: '-' ?></td>

            <td><?= $row['occupation'] ?: '-' ?></td>

            <td>
                <?= $row['annual_income'] ? '₹ '.number_format($row['annual_income']) : '-' ?>
            </td>

        </tr>

    <?php } ?>

    </tbody>

</table>

</div>
</div>
<?php } else { ?>

<div class="alert alert-warning">
    No Brother / Sister Details Added.
</div>

<?php } ?>

        <!-- =======================
             EDIT MODE
        ======================== -->

        <div id="familyEdit" style="display:none;">

            <form action="save-family.php" method="POST">

                <h5 class="text-primary mb-3">

                    Father Details

                </h5>

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <label>Name *</label>

                        <input
                        type="text"
                        name="father_name"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($profile['father_name']) ?>">

                    </div>

                    <div class="col-md-2 mb-3">

                        <label>Age</label>

                        <input
                        type="number"
                        name="father_age"
                        class="form-control"
                        value="<?= htmlspecialchars($profile['father_age']) ?>">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label>Qualification *</label>

                        <input
                        type="text"
                        name="father_qualification"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($profile['father_qualification']) ?>">

                    </div>

                    <div class="col-md-2 mb-3">

                        <label>Occupation</label>

                        <input
                        type="text"
                        name="father_occupation"
                        class="form-control"
                        value="<?= htmlspecialchars($profile['father_occupation']) ?>">

                    </div>

                    <div class="col-md-2 mb-3">

                        <label>Annual Income</label>

                        <input
                        type="number"
                        name="father_income"
                        class="form-control"
                        value="<?= htmlspecialchars($profile['father_income']) ?>">

                    </div>

                </div>

                                <hr>

                <h5 class="text-success mb-3">

                    Mother Details

                </h5>

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <label>Name *</label>

                        <input
                            type="text"
                            name="mother_name"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($profile['mother_name']) ?>">

                    </div>

                    <div class="col-md-2 mb-3">

                        <label>Age</label>

                        <input
                            type="number"
                            name="mother_age"
                            class="form-control"
                            value="<?= htmlspecialchars($profile['mother_age']) ?>">

                    </div>

                    <div class="col-md-3 mb-3">

                        <label>Qualification *</label>

                        <input
                            type="text"
                            name="mother_qualification"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($profile['mother_qualification']) ?>">

                    </div>

                    <div class="col-md-2 mb-3">

                        <label>Occupation</label>

                        <input
                            type="text"
                            name="mother_occupation"
                            class="form-control"
                            value="<?= htmlspecialchars($profile['mother_occupation']) ?>">

                    </div>

                    <div class="col-md-2 mb-3">

                        <label>Annual Income</label>

                        <input
                            type="number"
                            name="mother_income"
                            class="form-control"
                            value="<?= htmlspecialchars($profile['mother_income']) ?>">

                    </div>
</div>
                    <hr>

<h5 class="text-info mb-3">
    Brother / Sister Details
</h5>

<div id="siblingContainer">

<?php

foreach($siblings as $i=>$row){

?>

<div class="row mb-3 siblingRow">

    <div class="col-md-2">

        <label>Relation</label>

        <select
        name="siblings[<?= $i ?>][relation]"
        class="form-select">

            <option value="Brother"
            <?= $row['relation']=="Brother" ? "selected" : "" ?>>
            Brother
            </option>

            <option value="Sister"
            <?= $row['relation']=="Sister" ? "selected" : "" ?>>
            Sister
            </option>

        </select>

    </div>

    <div class="col-md-2">

        <label>Name</label>

        <input
        type="text"
        name="siblings[<?= $i ?>][name]"
        class="form-control"
        value="<?= htmlspecialchars($row['name']) ?>">

    </div>

    <div class="col-md-1">

        <label>Age</label>

        <input
        type="number"
        name="siblings[<?= $i ?>][age]"
        class="form-control"
        value="<?= $row['age'] ?>">

    </div>

    <div class="col-md-2">

        <label>Qualification</label>

        <input
        type="text"
        name="siblings[<?= $i ?>][qualification]"
        class="form-control"
        value="<?= htmlspecialchars($row['qualification']) ?>">

    </div>

    <div class="col-md-2">

        <label>Occupation</label>

        <input
        type="text"
        name="siblings[<?= $i ?>][occupation]"
        class="form-control"
        value="<?= htmlspecialchars($row['occupation']) ?>">

    </div>

    <div class="col-md-2">

        <label>Annual Income</label>

        <input
        type="number"
        name="siblings[<?= $i ?>][annual_income]"
        class="form-control"
        value="<?= $row['annual_income'] ?>">

    </div>

    <div class="col-md-1 d-flex align-items-end">

        <button
        type="button"
        class="btn btn-danger removeSibling">

            <i class="fa fa-trash"></i>

        </button>

    </div>

</div>

<?php } ?>

</div>

<button
type="button"
id="addSibling"
class="btn btn-success mb-4">

<i class="fa fa-plus"></i>

Add Brother / Sister

</button>

                
                

                <div class="mt-4">

                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="fa-solid fa-floppy-disk"></i>
                        Save

                    </button>

                    <button
                        type="button"
                        id="cancelFamily"
                        class="btn btn-secondary">

                        Cancel

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
<script>
    let siblingIndex = <?= count($siblings) ?>;

document.getElementById("addSibling").addEventListener("click",function(){

let html=`

<div class="row mb-3 siblingRow">

<div class="col-md-2">

<label>Relation</label>

<select
name="siblings[${siblingIndex}][relation]"
class="form-select">

<option value="Brother">Brother</option>

<option value="Sister">Sister</option>

</select>

</div>

<div class="col-md-2">

<label>Name</label>

<input
type="text"
name="siblings[${siblingIndex}][name]"
class="form-control">

</div>

<div class="col-md-1">

<label>Age</label>

<input
type="number"
name="siblings[${siblingIndex}][age]"
class="form-control">

</div>

<div class="col-md-2">

<label>Qualification</label>

<input
type="text"
name="siblings[${siblingIndex}][qualification]"
class="form-control">

</div>

<div class="col-md-2">

<label>Occupation</label>

<input
type="text"
name="siblings[${siblingIndex}][occupation]"
class="form-control">

</div>

<div class="col-md-2">

<label>Annual Income</label>

<input
type="number"
name="siblings[${siblingIndex}][annual_income]"
class="form-control">

</div>

<div class="col-md-1 d-flex align-items-end">

<button
type="button"
class="btn btn-danger removeSibling">

<i class="fa fa-trash"></i>

</button>

</div>

</div>

`;

document
.getElementById("siblingContainer")
.insertAdjacentHTML("beforeend",html);

siblingIndex++;

});

document.addEventListener("click",function(e){

if(e.target.closest(".removeSibling")){

e.target.closest(".siblingRow").remove();

}

});
</script>