<div class="card summary-card">

    <div class="section-title d-flex justify-content-between align-items-center">

        <span>
            
            Hobbies & Interests
        </span>

        <button
            type="button"
            id="editHobbies"
            class="btn btn-warning">

            <i class="fa-solid fa-pen"></i>
            Edit

        </button>

    </div>

    <div class="card-body">

        <!-- View -->
        <div id="hobbiesView">

        <?php if(!empty($hobbies)){ ?>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <div class="card border shadow-sm h-100">

                        <div class="card-body">

                            <h5 class="fw-bold">
                                <i class="fa-solid fa-heart text-danger"></i>
                                Hobbies
                            </h5>

                            <p class="mb-0">

                                <?= nl2br(htmlspecialchars($hobbies['hobbies'])) ?>

                            </p>

                        </div>

                    </div>

                </div>

                <div class="col-md-6 mb-3">

                    <div class="card border shadow-sm h-100">

                        <div class="card-body">

                            <h5 class="fw-bold">
                                <i class="fa-solid fa-star text-warning"></i>
                                Interests
                            </h5>

                            <p class="mb-0">

                                <?= nl2br(htmlspecialchars($hobbies['interests'])) ?>

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        <?php } else { ?>

            <div class="alert alert-warning">

                No Hobbies & Interests Added

            </div>

        <?php } ?>

        </div>

        <!-- Edit -->
        <div id="hobbiesEdit" style="display:none;">

            <form action="update-hobbies.php" method="POST">

                <div class="mb-3">

                    <label class="form-label">

                        Hobbies

                    </label>

                    <textarea
                        name="hobbies"
                        class="form-control"
                        rows="4"
                        placeholder="Please mention your hobbies"><?= htmlspecialchars($hobbies['hobbies'] ?? '') ?></textarea>

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Interests

                    </label>

                    <textarea
                        name="interests"
                        class="form-control"
                        rows="4"
                        placeholder="Please mention your interests"><?= htmlspecialchars($hobbies['interests'] ?? '') ?></textarea>

                </div>

                <div class="text-end">

                    <button
                        type="submit"
                        class="btn btn-success">

                        <i class="fa-solid fa-floppy-disk"></i>

                        Save Changes

                    </button>

                    <button
                        type="button"
                        id="cancelHobbies"
                        class="btn btn-secondary ms-2">

                        Cancel

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>