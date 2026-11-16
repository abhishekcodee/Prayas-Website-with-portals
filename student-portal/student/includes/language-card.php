<div class="card summary-card">

    <div class="section-title">

        <span>

            <i class="fa-solid fa-language text-info"></i>

            Languages

        </span>

        <button
            type="button"
            id="editLanguage"
            class="btn btn-warning">

            <i class="fa-solid fa-pen"></i>

            Edit

        </button>

    </div>

    <div class="card-body">

        <div id="languageView">

            <table class="table table-bordered">

                <thead class="table-info">

                    <tr>

                        <th>Language</th>
                        <th>Read</th>
                        <th>Write</th>
                        <th>Speak</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach($languages as $lang){ ?>

                <tr>

                    <td><?= htmlspecialchars($lang['language_name']) ?></td>

                    <td><?= $lang['can_read'] ? "✔" : "-" ?></td>

                    <td><?= $lang['can_write'] ? "✔" : "-" ?></td>

                    <td><?= $lang['can_speak'] ? "✔" : "-" ?></td>

                </tr>

                <?php } ?>

                </tbody>

            </table>

        </div>

        <div id="languageEdit" style="display:none;">

            <form action="update-languages.php" method="POST">

            <?php foreach($allLanguages as $row){ ?>

<?php
$selected = isset($selectedLanguages[$row['language_name']]);
?>

<div class="border rounded p-3 mb-3">

    <div class="row align-items-center">

        <div class="col-md-4">

            <label class="fw-bold">

                <input
                    type="checkbox"
                    name="language_id[<?= $row['id'] ?>]"
                    value="<?= $row['id'] ?>"
                    <?= $selected ? "checked" : "" ?>>

                <?= htmlspecialchars($row['language_name']) ?>

            </label>

        </div>

        <div class="col-md-8">

            <label class="me-3">

                <input
                    type="checkbox"
                    name="can_read[<?= $row['id'] ?>]"
                    <?= !empty($selectedLanguages[$row['language_name']]['read']) ? "checked" : "" ?>>

                Read

            </label>

            <label class="me-3">

                <input
                    type="checkbox"
                    name="can_write[<?= $row['id'] ?>]"
                    <?= !empty($selectedLanguages[$row['language_name']]['write']) ? "checked" : "" ?>>

                Write

            </label>

            <label>

                <input
                    type="checkbox"
                    name="can_speak[<?= $row['id'] ?>]"
                    <?= !empty($selectedLanguages[$row['language_name']]['speak']) ? "checked" : "" ?>>

                Speak

            </label>

        </div>

    </div>

</div>

<?php } ?>

<div class="mt-4 text-end">

    <button
        type="submit"
        class="btn btn-success">

        <i class="fa-solid fa-floppy-disk"></i>

        Save Languages

    </button>

    <button
        type="button"
        id="cancelLanguage"
        class="btn btn-secondary ms-2">

        <i class="fa-solid fa-xmark"></i>

        Cancel

    </button>

</div>              

</form>

</div>

</div>

</div>