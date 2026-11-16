<div class="card summary-card">

<div class="section-title">

<span>

<i class="fa-solid fa-address-card text-success"></i>

Personal Details

</span>

<button
type="button"
id="editProfile"
class="btn btn-warning">

<i class="fa-solid fa-pen"></i>

Edit

</button>

</div>

<div class="card-body">

<div id="profileView">

<div class="row">

<div class="col-md-6">
<p><strong>Full Name :</strong>
<?= htmlspecialchars($user['full_name']) ?>
</p>

<p><strong>Have you ever changed your name?</strong>
<?= htmlspecialchars($profile['name_changed'] ?? 'No') ?>
</p>

<?php if(($profile['name_changed'] ?? 'No') == 'Yes'){ ?>

<p><strong>Previous Full Name :</strong>
<?= htmlspecialchars($profile['previous_name']) ?>
</p>

<?php } ?>

<p><strong>DOB :</strong> <?= htmlspecialchars($profile['dob']) ?></p>

<p><strong>Place of Birth :</strong>
<?= htmlspecialchars($profile['place_of_birth'] ?? '-') ?>
</p>

<p><strong>Gender :</strong> <?= htmlspecialchars($profile['gender']) ?></p>

<p><strong>Marital Status :</strong>
<?= htmlspecialchars($profile['marital_status'] ?? '-') ?>
</p> 

<p><strong>Height :</strong>

<?= htmlspecialchars($profile['height_feet']) ?>'

<?= htmlspecialchars($profile['height_inches']) ?>"

</p>

<p><strong>Weight :</strong>

<?= htmlspecialchars($profile['weight']) ?> Kg</p>

<p><strong>Blood Group :</strong>

<?= htmlspecialchars($profile['blood_group']) ?>

</p>

</div>

<div class="col-md-6">

<p><strong>Aadhaar :</strong>

<?= htmlspecialchars($profile['aadhaar_no']) ?>

</p>

<p><strong>PAN :</strong>

<?= htmlspecialchars($profile['pan_no']) ?>

</p>

<p><strong>Government ID Type :</strong>
<?= htmlspecialchars($profile['govt_id_type'] ?? '-') ?>
</p>

<p><strong>Government ID Number :</strong>
<?= htmlspecialchars($profile['govt_id_number'] ?? '-') ?>
</p>

<p>
    <strong>Government ID Card :</strong>

    <?php if(!empty($profile['govt_id_image'])){ ?>

        <a href="../uploads/govt-id/<?= htmlspecialchars($profile['govt_id_image']) ?>"
           target="_blank"
           class="btn btn-sm btn-primary">

            View ID Card

        </a>

    <?php } else { ?>

        Not Uploaded

    <?php } ?>

</p>

<p><strong>Passport :</strong>

<?= htmlspecialchars($profile['passport_no']) ?>

</p>

<p><strong>Alternate Contact Number :</strong>

<?= htmlspecialchars($profile['alternate_number'] ?? '-') ?>

</p>

<p><strong>Emergency Contact Number :</strong>

<?= htmlspecialchars($profile['emergency_contact'] ?? '-') ?>

</p>

</div>

</div>



<hr>

<p><strong>Address :</strong>

<?= htmlspecialchars($profile['address']) ?>

</p>

<p><strong>City :</strong>

<?= htmlspecialchars($profile['city']) ?>

</p>

<p><strong>State :</strong>

<?= htmlspecialchars($profile['state']) ?>

</p>

<p><strong>Pincode :</strong>

<?= htmlspecialchars($profile['pincode']) ?>

</p>

<hr>

<h5 class="text-primary">Permanent Address</h5>

<p><strong>Address :</strong>
<?= htmlspecialchars($profile['permanent_address'] ?? '') ?>
</p>

<p><strong>City :</strong>
<?= htmlspecialchars($profile['permanent_city'] ?? '') ?>
</p>

<p><strong>State :</strong>
<?= htmlspecialchars($profile['permanent_state'] ?? '') ?>
</p>

<p><strong>Pincode :</strong>
<?= htmlspecialchars($profile['permanent_pincode'] ?? '') ?>
</p>


<p><strong>Nationality :</strong>

<?= htmlspecialchars($profile['nationality'] ?? 'Indian') ?>

</p>

<p><strong>State / UT of Domicile :</strong>

<?= htmlspecialchars($profile['domicile_state'] ?? '-') ?>

</p>

<p>
    <strong>Religion :</strong>
    <?= htmlspecialchars($profile['religion'] ?? '-') ?>
</p>

<p>
    <strong>Category :</strong>
    <?= htmlspecialchars($profile['category'] ?? '-') ?>
</p>

<p><strong>Declaration :</strong>

<?= ($profile['declaration'] ?? 0) ? 'Accepted' : 'Not Accepted'; ?>

</p>

<p>
    <strong>PwBD :</strong>
    <?= htmlspecialchars($profile['pwbd'] ?? 'No') ?>
</p>

<?php if(($profile['pwbd'] ?? 'No') == 'Yes'){ ?>

<p>
    <strong>PwBD ID Number :</strong>
    <?= htmlspecialchars($profile['pwbd_id'] ?? '-') ?>
</p>

<?php } ?>

<?php if(!empty($profile['pwbd_certificate'])){ ?>

<p>
    <strong>PwBD Certificate :</strong>

    <a href="../uploads/pwbd/<?= htmlspecialchars($profile['pwbd_certificate']) ?>"
       target="_blank"
       class="btn btn-sm btn-primary">

        View Certificate

    </a>

</p>

<?php } ?>
</div>

<div id="profileEdit" style="display:none;">

<form action="update-profile.php"
      method="POST"
      enctype="multipart/form-data">

<div class="row">
    <div class="col-md-6 mb-3">

    <label class="form-label">
        Full Name
    </label>

  <input
    type="text"
    name="full_name"
    class="form-control"
    value="<?= htmlspecialchars($user['full_name']) ?>"
    required>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">
        Have you ever changed your name?
    </label>

    <select
        name="name_changed"
        id="name_changed"
        class="form-select">

        <option value="No"
        <?= (($profile['name_changed'] ?? 'No')=='No') ? 'selected' : '' ?>>
            No
        </option>

        <option value="Yes"
        <?= (($profile['name_changed'] ?? '')=='Yes') ? 'selected' : '' ?>>
            Yes
        </option>

    </select>

</div>

<div
class="col-md-12 mb-3"
id="previousNameBox"
style="<?= (($profile['name_changed'] ?? 'No')=='Yes') ? '' : 'display:none;' ?>">

    <label class="form-label">
        Previous Full Name
    </label>

    <input
        type="text"
        name="previous_name"
        class="form-control"
        value="<?= htmlspecialchars($profile['previous_name']) ?>">

</div>

    <!-- DOB -->
    <div class="col-md-6 mb-3">

        <label class="form-label">Date of Birth</label>

        <input
            type="date"
            name="dob"
            class="form-control"
            value="<?= htmlspecialchars($profile['dob']) ?>"
            required>

    </div>

    <div class="col-md-6 mb-3">

    <label class="form-label">Place of Birth</label>

    <input
        type="text"
        name="place_of_birth"
        class="form-control"
        value="<?= htmlspecialchars($profile['place_of_birth'] ?? '') ?>"
        required>

</div>

    <!-- Gender -->
    <div class="col-md-6 mb-3">

        <label class="form-label">Gender</label>

        <select
            name="gender"
            class="form-select"
            required>

            <option value="Male"
                <?= $profile['gender']=="Male" ? "selected" : "" ?>>
                Male
            </option>

            <option value="Female"
                <?= $profile['gender']=="Female" ? "selected" : "" ?>>
                Female
            </option>

            <option value="Other"
                <?= $profile['gender']=="Other" ? "selected" : "" ?>>
                Other
            </option>

        </select>

    </div>
    <!-- Marital Status -->
<div class="col-md-6 mb-3">

    <label class="form-label">Marital Status</label>

    <select
        name="marital_status"
        class="form-select">

        <option value="">Select your status</option>

        <option value="Single" <?= (($profile['marital_status'] ?? '')=='Single') ? 'selected' : '' ?>>Single</option>

        <option value="Married" <?= (($profile['marital_status'] ?? '')=='Married') ? 'selected' : '' ?>>Married</option>

        <option value="Divorced" <?= (($profile['marital_status'] ?? '')=='Divorced') ? 'selected' : '' ?>>Divorced</option>

        <option value="Widowed" <?= (($profile['marital_status'] ?? '')=='Widowed') ? 'selected' : '' ?>>Widowed</option>

        <option value="Separated" <?= (($profile['marital_status'] ?? '')=='Separated') ? 'selected' : '' ?>>Separated</option>

        <option value="Domestic Partnership" <?= (($profile['marital_status'] ?? '')=='Domestic Partnership') ? 'selected' : '' ?>>Domestic Partnership</option>

        <option value="Other" <?= (($profile['marital_status'] ?? '')=='Other') ? 'selected' : '' ?>>Other</option>

    </select>

</div>

    

    <!-- Height Feet -->
    <div class="col-md-3 mb-3">

        <label class="form-label">Height (Feet)</label>

        <input
            type="number"
            name="height_feet"
            class="form-control"
            value="<?= htmlspecialchars($profile['height_feet']) ?>"
            min="1"
            max="8"
            required>

    </div>

    <!-- Height Inches -->
    <div class="col-md-3 mb-3">

        <label class="form-label">Height (Inches)</label>

        <input
            type="number"
            name="height_inches"
            class="form-control"
            value="<?= htmlspecialchars($profile['height_inches']) ?>"
            min="0"
            max="11"
            required>

    </div>

    <!-- Weight -->
    <div class="col-md-3 mb-3">

        <label class="form-label">Weight (Kg)</label>

        <input
            type="number"
            name="weight"
            class="form-control"
            value="<?= htmlspecialchars($profile['weight']) ?>"
            required>

    </div>

    <!-- Blood Group -->
    <div class="col-md-3 mb-3">

        <label class="form-label">Blood Group</label>

        <select
            name="blood_group"
            class="form-select">

            <?php

            $groups=[
                "A+","A-","B+","B-",
                "AB+","AB-","O+","O-"
            ];

            foreach($groups as $group){

            ?>

            <option
                value="<?= $group ?>"
                <?= $profile['blood_group']==$group ? "selected" : "" ?>>

                <?= $group ?>

            </option>

            <?php } ?>

        </select>

    </div>

</div>

<div class="row">

    <!-- Aadhaar -->
    <div class="col-md-6 mb-3">

        <label class="form-label">Aadhaar Number</label>

        <input
            type="text"
            name="aadhaar_no"
            class="form-control"
            maxlength="12"
            value="<?= htmlspecialchars($profile['aadhaar_no']) ?>"
            >

    </div>

    <!-- PAN -->
    <div class="col-md-6 mb-3">

        <label class="form-label">PAN Number</label>

        <input
            type="text"
            name="pan_no"
            class="form-control"
            maxlength="10"
            value="<?= htmlspecialchars($profile['pan_no']) ?>">

    </div>

    <!-- Passport -->
    <div class="col-md-6 mb-3">

        <label class="form-label">Passport Number</label>

        <input
            type="text"
            name="passport_no"
            class="form-control"
            value="<?= htmlspecialchars($profile['passport_no']) ?>">

    </div>

    <div class="col-md-6 mb-3">

    <label class="form-label">
        Alternate Contact Number
    </label>

    <input
        type="text"
        name="alternate_number"
        class="form-control"
        maxlength="10"
        value="<?= htmlspecialchars($profile['alternate_number'] ?? '') ?>">

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">
        Emergency Contact Number
    </label>

    <input
        type="text"
        name="emergency_contact"
        class="form-control"
        maxlength="10"
        value="<?= htmlspecialchars($profile['emergency_contact'] ?? '') ?>"
        required>

</div>

    <!-- Government ID Type -->
<div class="col-md-6 mb-3">

    <label class="form-label">
        Government ID Type
    </label>

    <select
        name="govt_id_type"
        class="form-select"
        required>

        <option value="">Select ID Type</option>

        <option value="Aadhaar Card" <?= (($profile['govt_id_type'] ?? '')=='Aadhaar Card') ? 'selected' : '' ?>>Aadhaar Card</option>

        <option value="Voter ID" <?= (($profile['govt_id_type'] ?? '')=='Voter ID') ? 'selected' : '' ?>>Voter ID</option>

        <option value="Passport" <?= (($profile['govt_id_type'] ?? '')=='Passport') ? 'selected' : '' ?>>Passport</option>

        <option value="Driving License" <?= (($profile['govt_id_type'] ?? '')=='Driving License') ? 'selected' : '' ?>>Driving License</option>

        <option value="PAN Card" <?= (($profile['govt_id_type'] ?? '')=='PAN Card') ? 'selected' : '' ?>>PAN Card</option>

        <option value="Other" <?= (($profile['govt_id_type'] ?? '')=='Other') ? 'selected' : '' ?>>Other</option>


    </select>

</div>

<!-- Government ID Number -->
<div class="col-md-6 mb-3">

    <label class="form-label">
        Government ID Number
    </label>

    <input
        type="text"
        name="govt_id_number"
        class="form-control"
        value="<?= htmlspecialchars($profile['govt_id_number'] ?? '') ?>"
        required>

</div>

<!-- Government ID Image -->
<div class="col-md-12 mb-3">

    <label class="form-label">
        Upload Government ID (Optional)
    </label>

    <input
        type="file"
        name="govt_id_image"
        class="form-control"
        accept=".jpg,.jpeg,.png,.pdf">

    <?php if(!empty($profile['govt_id_image'])){ ?>

        <div class="mt-2">

            <a href="../uploads/govt-id/<?= htmlspecialchars($profile['govt_id_image']) ?>"
               target="_blank">

                View Current ID Card

            </a>

        </div>

    <?php } ?>

</div>

    <!-- Pincode -->
    <div class="col-md-6 mb-3">

        <label class="form-label">Pincode</label>

        <input
            type="text"
            name="pincode"
            id="pincode"
            class="form-control"
            maxlength="6"
            value="<?= htmlspecialchars($profile['pincode']) ?>"
            required>

    </div>

    <!-- Address -->
    <div class="col-md-12 mb-3">

        <label class="form-label">Address</label>

        <textarea
            name="address"
            class="form-control"
            rows="3"
            required><?= htmlspecialchars($profile['address']) ?></textarea>

    </div>

    <!-- City -->
    <div class="col-md-6 mb-3">

        <label class="form-label">City</label>

        <input
            type="text"
            name="city"
            id="city"
            class="form-control"
            value="<?= htmlspecialchars($profile['city']) ?>"
            required>

    </div>

    <!-- State -->
    <div class="col-md-6 mb-3">

        <label class="form-label">State</label>

        <input
            type="text"
            name="state"
            id="state"
            class="form-control"
            value="<?= htmlspecialchars($profile['state']) ?>"
            required>

    </div>

    <hr>

<h5 class="text-primary mb-3">Permanent Address</h5>

<div class="col-md-12 mb-3">

    <div class="form-check">

        <input
            class="form-check-input"
            type="checkbox"
            id="sameAsCurrent">

        <label class="form-check-label" for="sameAsCurrent">
            Permanent Address is same as Current Address
        </label>

    </div>

</div>

<div class="col-md-12 mb-3">

    <label class="form-label">Permanent Address</label>

    <textarea
        name="permanent_address"
        id="permanent_address"
        class="form-control"
        rows="3"
        required><?= htmlspecialchars($profile['permanent_address'] ?? '') ?></textarea>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Permanent Pincode</label>

    <input
        type="text"
        id="permanent_pincode"
        name="permanent_pincode"
        maxlength="6"
        class="form-control"
        value="<?= htmlspecialchars($profile['permanent_pincode'] ?? '') ?>"
        required>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Permanent City</label>

    <input
        type="text"
        id="permanent_city"
        name="permanent_city"
        class="form-control"
        value="<?= htmlspecialchars($profile['permanent_city'] ?? '') ?>"
        required>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">Permanent State</label>

    <input
        type="text"
        id="permanent_state"
        name="permanent_state"
        class="form-control"
        value="<?= htmlspecialchars($profile['permanent_state'] ?? '') ?>"
        required>

</div>


    <!-- Nationality -->
<div class="col-md-6 mb-3">

    <label class="form-label">Nationality</label>

      <select
    class="form-select"
    name="nationality"
    required>

    <option>select country</option>
	<option value="IN">India</option>
    <option value="AF">Afghanistan</option>
    <option value="AX">Åland Islands</option>
    <option value="AL">Albania</option>
    <option value="DZ">Algeria</option>
    <option value="AS">American Samoa</option>
    <option value="AD">Andorra</option>
    <option value="AO">Angola</option>
    <option value="AI">Anguilla</option>
    <option value="AQ">Antarctica</option>
    <option value="AG">Antigua and Barbuda</option>
    <option value="AR">Argentina</option>
    <option value="AM">Armenia</option>
    <option value="AW">Aruba</option>
    <option value="AU">Australia</option>
    <option value="AT">Austria</option>
    <option value="AZ">Azerbaijan</option>
    <option value="BS">Bahamas</option>
    <option value="BH">Bahrain</option>
    <option value="BD">Bangladesh</option>
    <option value="BB">Barbados</option>
    <option value="BY">Belarus</option>
    <option value="BE">Belgium</option>
    <option value="BZ">Belize</option>
    <option value="BJ">Benin</option>
    <option value="BM">Bermuda</option>
    <option value="BT">Bhutan</option>
    <option value="BO">Bolivia (Plurinational State of)</option>
    <option value="BA">Bosnia and Herzegovina</option>
    <option value="BW">Botswana</option>
    <option value="BV">Bouvet Island</option>
    <option value="BR">Brazil</option>
    <option value="IO">British Indian Ocean Territory</option>
    <option value="BN">Brunei Darussalam</option>
    <option value="BG">Bulgaria</option>
    <option value="BF">Burkina Faso</option>
    <option value="BI">Burundi</option>
    <option value="CV">Cabo Verde</option>
    <option value="KH">Cambodia</option>
    <option value="CM">Cameroon</option>
    <option value="CA">Canada</option>
    <option value="BQ">Caribbean Netherlands</option>
    <option value="KY">Cayman Islands</option>
    <option value="CF">Central African Republic</option>
    <option value="TD">Chad</option>
    <option value="CL">Chile</option>
    <option value="CN">China</option>
    <option value="CX">Christmas Island</option>
    <option value="CC">Cocos (Keeling) Islands</option>
    <option value="CO">Colombia</option>
    <option value="KM">Comoros</option>
    <option value="CG">Congo</option>
    <option value="CD">Congo, Democratic Republic of the</option>
    <option value="CK">Cook Islands</option>
    <option value="CR">Costa Rica</option>
    <option value="HR">Croatia</option>
    <option value="CU">Cuba</option>
    <option value="CW">Curaçao</option>
    <option value="CY">Cyprus</option>
    <option value="CZ">Czech Republic</option>
    <option value="CI">Côte d'Ivoire</option>
    <option value="DK">Denmark</option>
    <option value="DJ">Djibouti</option>
    <option value="DM">Dominica</option>
    <option value="DO">Dominican Republic</option>
    <option value="EC">Ecuador</option>
    <option value="EG">Egypt</option>
    <option value="SV">El Salvador</option>
    <option value="GQ">Equatorial Guinea</option>
    <option value="ER">Eritrea</option>
    <option value="EE">Estonia</option>
    <option value="SZ">Eswatini (Swaziland)</option>
    <option value="ET">Ethiopia</option>
    <option value="FK">Falkland Islands (Malvinas)</option>
    <option value="FO">Faroe Islands</option>
    <option value="FJ">Fiji</option>
    <option value="FI">Finland</option>
    <option value="FR">France</option>
    <option value="GF">French Guiana</option>
    <option value="PF">French Polynesia</option>
    <option value="TF">French Southern Territories</option>
    <option value="GA">Gabon</option>
    <option value="GM">Gambia</option>
    <option value="GE">Georgia</option>
    <option value="DE">Germany</option>
    <option value="GH">Ghana</option>
    <option value="GI">Gibraltar</option>
    <option value="GR">Greece</option>
    <option value="GL">Greenland</option>
    <option value="GD">Grenada</option>
    <option value="GP">Guadeloupe</option>
    <option value="GU">Guam</option>
    <option value="GT">Guatemala</option>
    <option value="GG">Guernsey</option>
    <option value="GN">Guinea</option>
    <option value="GW">Guinea-Bissau</option>
    <option value="GY">Guyana</option>
    <option value="HT">Haiti</option>
    <option value="HM">Heard Island and Mcdonald Islands</option>
    <option value="HN">Honduras</option>
    <option value="HK">Hong Kong</option>
    <option value="HU">Hungary</option>
    <option value="IS">Iceland</option>
    <option value="ID">Indonesia</option>
    <option value="IR">Iran</option>
    <option value="IQ">Iraq</option>
    <option value="IE">Ireland</option>
    <option value="IM">Isle of Man</option>
    <option value="IL">Israel</option>
    <option value="IT">Italy</option>
    <option value="JM">Jamaica</option>
    <option value="JP">Japan</option>
    <option value="JE">Jersey</option>
    <option value="JO">Jordan</option>
    <option value="KZ">Kazakhstan</option>
    <option value="KE">Kenya</option>
    <option value="KI">Kiribati</option>
    <option value="KP">Korea, North</option>
    <option value="KR">Korea, South</option>
    <option value="XK">Kosovo</option>
    <option value="KW">Kuwait</option>
    <option value="KG">Kyrgyzstan</option>
    <option value="LA">Lao People's Democratic Republic</option>
    <option value="LV">Latvia</option>
    <option value="LB">Lebanon</option>
    <option value="LS">Lesotho</option>
    <option value="LR">Liberia</option>
    <option value="LY">Libya</option>
    <option value="LI">Liechtenstein</option>
    <option value="LT">Lithuania</option>
    <option value="LU">Luxembourg</option>
    <option value="MO">Macao</option>
    <option value="MK">Macedonia North</option>
    <option value="MG">Madagascar</option>
    <option value="MW">Malawi</option>
    <option value="MY">Malaysia</option>
    <option value="MV">Maldives</option>
    <option value="ML">Mali</option>
    <option value="MT">Malta</option>
    <option value="MH">Marshall Islands</option>
    <option value="MQ">Martinique</option>
    <option value="MR">Mauritania</option>
    <option value="MU">Mauritius</option>
    <option value="YT">Mayotte</option>
    <option value="MX">Mexico</option>
    <option value="FM">Micronesia</option>
    <option value="MD">Moldova</option>
    <option value="MC">Monaco</option>
    <option value="MN">Mongolia</option>
    <option value="ME">Montenegro</option>
    <option value="MS">Montserrat</option>
    <option value="MA">Morocco</option>
    <option value="MZ">Mozambique</option>
    <option value="MM">Myanmar (Burma)</option>
    <option value="NA">Namibia</option>
    <option value="NR">Nauru</option>
    <option value="NP">Nepal</option>
    <option value="NL">Netherlands</option>
    <option value="AN">Netherlands Antilles</option>
    <option value="NC">New Caledonia</option>
    <option value="NZ">New Zealand</option>
    <option value="NI">Nicaragua</option>
    <option value="NE">Niger</option>
    <option value="NG">Nigeria</option>
    <option value="NU">Niue</option>
    <option value="NF">Norfolk Island</option>
    <option value="MP">Northern Mariana Islands</option>
    <option value="NO">Norway</option>
    <option value="OM">Oman</option>
    <option value="PK">Pakistan</option>
    <option value="PW">Palau</option>
    <option value="PS">Palestine</option>
    <option value="PA">Panama</option>
    <option value="PG">Papua New Guinea</option>
    <option value="PY">Paraguay</option>
    <option value="PE">Peru</option>
    <option value="PH">Philippines</option>
    <option value="PN">Pitcairn Islands</option>
    <option value="PL">Poland</option>
    <option value="PT">Portugal</option>
    <option value="PR">Puerto Rico</option>
    <option value="QA">Qatar</option>
    <option value="RE">Reunion</option>
    <option value="RO">Romania</option>
    <option value="RU">Russian Federation</option>
    <option value="RW">Rwanda</option>
    <option value="BL">Saint Barthelemy</option>
    <option value="SH">Saint Helena</option>
    <option value="KN">Saint Kitts and Nevis</option>
    <option value="LC">Saint Lucia</option>
    <option value="MF">Saint Martin</option>
    <option value="PM">Saint Pierre and Miquelon</option>
    <option value="VC">Saint Vincent and the Grenadines</option>
    <option value="WS">Samoa</option>
    <option value="SM">San Marino</option>
    <option value="ST">Sao Tome and Principe</option>
    <option value="SA">Saudi Arabia</option>
    <option value="SN">Senegal</option>
    <option value="RS">Serbia</option>
    <option value="CS">Serbia and Montenegro</option>
    <option value="SC">Seychelles</option>
    <option value="SL">Sierra Leone</option>
    <option value="SG">Singapore</option>
    <option value="SX">Sint Maarten</option>
    <option value="SK">Slovakia</option>
    <option value="SI">Slovenia</option>
    <option value="SB">Solomon Islands</option>
    <option value="SO">Somalia</option>
    <option value="ZA">South Africa</option>
    <option value="GS">South Georgia and the South Sandwich Islands</option>
    <option value="SS">South Sudan</option>
    <option value="ES">Spain</option>
    <option value="LK">Sri Lanka</option>
    <option value="SD">Sudan</option>
    <option value="SR">Suriname</option>
    <option value="SJ">Svalbard and Jan Mayen</option>
    <option value="SE">Sweden</option>
    <option value="CH">Switzerland</option>
    <option value="SY">Syria</option>
    <option value="TW">Taiwan</option>
    <option value="TJ">Tajikistan</option>
    <option value="TZ">Tanzania</option>
    <option value="TH">Thailand</option>
    <option value="TL">Timor-Leste</option>
    <option value="TG">Togo</option>
    <option value="TK">Tokelau</option>
    <option value="TO">Tonga</option>
    <option value="TT">Trinidad and Tobago</option>
    <option value="TN">Tunisia</option>
    <option value="TR">Turkey (Türkiye)</option>
    <option value="TM">Turkmenistan</option>
    <option value="TC">Turks and Caicos Islands</option>
    <option value="TV">Tuvalu</option>
    <option value="UM">U.S. Outlying Islands</option>
    <option value="UG">Uganda</option>
    <option value="UA">Ukraine</option>
    <option value="AE">United Arab Emirates</option>
    <option value="GB">United Kingdom</option>
    <option value="US">United States</option>
    <option value="UY">Uruguay</option>
    <option value="UZ">Uzbekistan</option>
    <option value="VU">Vanuatu</option>
    <option value="VA">Vatican City Holy See</option>
    <option value="VE">Venezuela</option>
    <option value="VN">Vietnam</option>
    <option value="VG">Virgin Islands, British</option>
    <option value="VI">Virgin Islands, U.S</option>
    <option value="WF">Wallis and Futuna</option>
    <option value="EH">Western Sahara</option>
    <option value="YE">Yemen</option>
    <option value="ZM">Zambia</option>
    <option value="ZW">Zimbabwe</option>
</select>

</div>

<!-- State / UT of Domicile -->
<div class="col-md-6 mb-3">

    <label class="form-label">
        State / UT of Domicile
    </label>

    <select
        name="domicile_state"
        class="form-select"
        required>

        <option value="">Select State / UT</option>

        <?php
        $states = [
            "Andaman and Nicobar Islands",
            "Andhra Pradesh",
            "Arunachal Pradesh",
            "Assam",
            "Bihar",
            "Chandigarh",
            "Chhattisgarh",
            "Dadra and Nagar Haveli and Daman and Diu",
            "Delhi",
            "Goa",
            "Gujarat",
            "Haryana",
            "Himachal Pradesh",
            "Jammu and Kashmir",
            "Jharkhand",
            "Karnataka",
            "Kerala",
            "Ladakh",
            "Lakshadweep",
            "Madhya Pradesh",
            "Maharashtra",
            "Manipur",
            "Meghalaya",
            "Mizoram",
            "Nagaland",
            "Odisha",
            "Puducherry",
            "Punjab",
            "Rajasthan",
            "Sikkim",
            "Tamil Nadu",
            "Telangana",
            "Tripura",
            "Uttar Pradesh",
            "Uttarakhand",
            "West Bengal"
        ];

        foreach($states as $state){
        ?>
            <option value="<?= $state ?>"
                <?= (($profile['domicile_state'] ?? '') == $state) ? 'selected' : '' ?>>
                <?= $state ?>
            </option>
        <?php } ?>

    </select>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">
        PwBD (Person with Benchmark Disability)
    </label>

    <select
        name="pwbd"
        id="pwbd"
        class="form-select">

        <option value="No"
            <?= (($profile['pwbd'] ?? 'No')=='No') ? 'selected' : '' ?>>
            No
        </option>

        <option value="Yes"
            <?= (($profile['pwbd'] ?? '')=='Yes') ? 'selected' : '' ?>>
            Yes
        </option>

    </select>

</div>

<div
class="col-md-6 mb-3"
id="pwbdBox"
style="<?= (($profile['pwbd'] ?? 'No')=='Yes') ? '' : 'display:none;' ?>">

    <label class="form-label">
        PwBD ID Number
    </label>

    <input
        type="text"
        name="pwbd_id"
        class="form-control"
        maxlength="18"
        value="<?= htmlspecialchars($profile['pwbd_id'] ?? '') ?>"
        placeholder="Enter PwBD ID Number">

        <div
class="col-md-6 mb-3"
id="pwbdPhotoBox"
style="<?= (($profile['pwbd'] ?? 'No')=='Yes') ? '' : 'display:none;' ?>">

    <label class="form-label">
        Upload PwBD Certificate
    </label>

    <input
        type="file"
        name="pwbd_certificate"
        class="form-control"
        accept=".jpg,.jpeg,.png,.pdf">

    <?php if(!empty($profile['pwbd_certificate'])){ ?>

        <div class="mt-2">

            <a href="../uploads/pwbd/<?= htmlspecialchars($profile['pwbd_certificate']) ?>"
               target="_blank">

                View Current Certificate

            </a>

        </div>

    <?php } ?>

</div>
</div>

<!-- Declaration -->
<div class="col-md-12 mb-3">

    <div class="form-check">

        <input
            class="form-check-input"
            type="checkbox"
            id="declaration"
            name="declaration"
            <?= (($profile['declaration'] ?? 0) == 1) ? 'checked' : '' ?>
            required>

        <label class="form-check-label" for="declaration">

            I hereby by declare.

        </label>

    </div>

</div>


<div class="col-md-6 mb-3">

    <label class="form-label">
        Religion
    </label>

    <select
        name="religion"
        class="form-select">

        <option value="">Select Religion</option>

        <option value="Hindu" <?= (($profile['religion'] ?? '')=='Hindu') ? 'selected' : '' ?>>Hindu</option>

        <option value="Sikh" <?= (($profile['religion'] ?? '')=='Sikh') ? 'selected' : '' ?>>Sikh</option>

        <option value="Christian" <?= (($profile['religion'] ?? '')=='Christian') ? 'selected' : '' ?>>Christian</option>

        <option value="Muslim" <?= (($profile['religion'] ?? '')=='Muslim') ? 'selected' : '' ?>>Muslim</option>

    </select>

</div>

<div class="col-md-6 mb-3">

    <label class="form-label">
        Category
    </label>

    <select
        name="category"
        class="form-select">

        <option value="">Select Category</option>

        <option value="General" <?= (($profile['category'] ?? '')=='General') ? 'selected' : '' ?>>General</option>

        <option value="EWS" <?= (($profile['category'] ?? '')=='EWS') ? 'selected' : '' ?>>EWS</option>

        <option value="OBC" <?= (($profile['category'] ?? '')=='OBC') ? 'selected' : '' ?>>OBC</option>

        <option value="SC" <?= (($profile['category'] ?? '')=='SC') ? 'selected' : '' ?>>SC</option>

        <option value="ST" <?= (($profile['category'] ?? '')=='ST') ? 'selected' : '' ?>>ST</option>

    </select>

</div>


</div>

<div class="text-end mt-3">

    <button
        type="submit"
        class="btn btn-success">

        <i class="fa-solid fa-floppy-disk"></i>

        Save Changes

    </button>

    <button
        type="button"
        id="cancelProfile"
        class="btn btn-secondary ms-2">

        <i class="fa-solid fa-xmark"></i>

        Cancel

    </button>

</div>

</form>

</div>

</div>
<script>
document.getElementById("pincode").addEventListener("keyup", function () {

    let pin = this.value;

    if (pin.length == 6) {

        fetch("https://api.postalpincode.in/pincode/" + pin)
            .then(res => res.json())
            .then(data => {

                if (data[0].Status == "Success") {

                    document.getElementById("city").value =
                        data[0].PostOffice[0].District;

                    document.getElementById("state").value =
                        data[0].PostOffice[0].State;

                }

            });

    }

});
</script>
<script>
document.getElementById("city").addEventListener("blur", function () {

    let city = this.value.trim();

    if(city=="") return;

    fetch("get-state.php?city=" + encodeURIComponent(city))
    .then(res => res.json())
    .then(data => {

        if(data.length){

            document.getElementById("state").value =
                data[0].address.state || "";

        }else{

            document.getElementById("state").value="";

        }

    });

});

document.getElementById("name_changed").addEventListener("change", function(){

    const box = document.getElementById("previousNameBox");

    if(this.value === "Yes"){
        box.style.display = "block";
    }else{
        box.style.display = "none";
        document.querySelector("input[name='previous_name']").value = "";
    }

});
</script>
<script>
const sameAsCurrent = document.getElementById("sameAsCurrent");

if (sameAsCurrent) {

    sameAsCurrent.addEventListener("change", function () {

        if (this.checked) {

            document.getElementById("permanent_address").value =
                document.querySelector("textarea[name='address']").value;

            document.getElementById("permanent_pincode").value =
                document.getElementById("pincode").value;

            document.getElementById("permanent_city").value =
                document.getElementById("city").value;

            document.getElementById("permanent_state").value =
                document.getElementById("state").value;

        }

    });

}

document.getElementById("pwbd").addEventListener("change", function(){

    const box = document.getElementById("pwbdBox");

    if(this.value === "Yes"){
        box.style.display = "block";
    }else{
        box.style.display = "none";
        document.querySelector("input[name='pwbd_id']").value = "";
    }

});
</script>
</div>