<?php
session_start();
require_once "../db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// User Details
$stmt = $pdo->prepare("
SELECT full_name
FROM users
WHERE id=?
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Existing Profile
$stmt = $pdo->prepare("SELECT * FROM student_profile WHERE user_id=?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Languages
$lang = $pdo->query("SELECT * FROM languages ORDER BY language_name ASC");
$languages = $lang->fetchAll(PDO::FETCH_ASSOC);

// Selected Languages
$stmt = $pdo->prepare("SELECT * FROM student_languages WHERE user_id=?");
$stmt->execute([$user_id]);
$userLanguages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Step 4 - Personal Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/personal.css">

</head>

<body class="bg-light">
    <header class="prayas-header">
    <div class="prayas-header-inner">

        <div class="prayas-brand">
            <img
                src="../assets/images/prayas-logo.png"
                alt="PRAYAS - The Automation Academy"
                class="prayas-logo">
        </div>

        <nav class="nav__links" aria-label="Main Navigation">
            <a href="../../index.html">Home</a>
            <a href="../../about.html">About Us</a>
            <a href="../../employers.html">Employers & MSMEs</a>
            <a href="../../students.html">Students</a>
            <a href="../../contact.html">Contact</a>
        </nav>

    </div>
</header>

<div class="container py-5">

<div class="card shadow-lg border-0">

<div class="card-header bg-primary text-white">

<h3 class="mb-0">
Step 4 - Complete Personal Profile<br>
चरण 4 – व्यक्तिगत प्रोफ़ाइल पूर्ण करें<br>
टप्पा 4 – वैयक्तिक प्रोफाइल पूर्ण करा
</h3>
<p class="mb-0 mt-2">

Please complete your personal details and language proficiency.<br>
कृपया अपनी व्यक्तिगत जानकारी और भाषा दक्षता पूरी करें।<br>
कृपया तुमची वैयक्तिक माहिती आणि भाषा प्राविण्य पूर्ण करा.

</p>

</div>
<div class="progress rounded-0" style="height:8px;">

<div
class="progress-bar bg-success"
style="width:67%">

</div>

</div>
<div class="card-body">

<form action="save-personal.php" method="POST" enctype="multipart/form-data" id="profileForm">

<div class="row">
<div class="col-md-6 mb-3">

<label class="form-label">

Full Name 

<span class="text-danger">*</span>

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($user['full_name']) ?>"
readonly>


</div>



<div class="col-md-6 mb-3">

<label>Date of Birth 
   / जन्म तिथि 
/ जन्मतारीख 
<span class="text-danger">*</span>

</label>

<input
type="date"
name="dob"
class="form-control"
required
value="<?= $profile['dob'] ?? '' ?>"
max="<?= date('Y-m-d', strtotime('-18 years')); ?>"
>

<div class="col-md-6 mb-3">

<label class="form-label">
Place of Birth /
जन्म स्थान/
जन्मस्थान
</label>

<input
type="text"
name="place_of_birth"
class="form-control"
value="<?= htmlspecialchars($profile['place_of_birth'] ?? '') ?>"
placeholder="Enter Place of Birth">

</div>
</div>


<div class="col-md-6 mb-3">

<label>Gender 
    <span class="text-danger">*</span>

</label>

<select
name="gender"
class="form-select"
required>

<option value="">Select</option>

<option value="Male" <?= (($profile['gender'] ?? '')=='Male') ? 'selected' : '' ?>>Male/पुरुष/
पुरुष</option>
<option value="Female" <?= (($profile['gender'] ?? '')=='Female') ? 'selected' : '' ?>>Female/महिला/
महिला</option>
<option value="Other" <?= (($profile['gender'] ?? '')=='Other') ? 'selected' : '' ?>>Other/ अन्य/ इतर</option>

</select>

</div>

<!-- Marital Status -->
<div class="col-md-6 mb-3">

<label class="form-label">
Marital Status / वैवाहिक स्थिति / वैवाहिक स्थिती
</label>

<select
name="marital_status"
class="form-select">

<option value="">Select your status</option>

<option value="Single" <?= (($profile['marital_status'] ?? '')=='Single') ? 'selected' : '' ?>>Single</option>

<option value="Married" <?= (($profile['marital_status'] ?? '')=='Married') ? 'selected' : '' ?>>Married</option>

<option value="Divorced" <?= (($profile['marital_status'] ?? '')=='Divorced') ? 'selected' : '' ?>>Divorced</option>

<option value="Widowed" <?= (($profile['marital_status'] ?? '')=='Widowed') ? 'selected' : '' ?>>Widowed</option>

<option value="Other" <?= (($profile['marital_status'] ?? '')=='Other') ? 'selected' : '' ?>>Other</option>

</select>

</div>

<div class="col-md-3 mb-3">

    <label>Height (Feet)</label>

    <select name="height_feet" class="form-select">

        <option value="">Feet</option>

        <?php for($i=3;$i<=8;$i++){ ?>

        <option value="<?= $i ?>">
            <?= $i ?> ft
        </option>

        <?php } ?>

    </select>

</div>

<div class="col-md-3 mb-3">

    <label>Height (Inches) </label>

    <select name="height_inches" class="form-select">

        <option value="">Inches</option>

        <?php for($i=0;$i<=11;$i++){ ?>

        <option value="<?= $i ?>">
            <?= $i ?> in
        </option>

        <?php } ?>

    </select>

</div>

<div class="col-md-6 mb-3">

<label>Weight (kg)</label>

<input
type="number"
step="0.01"
name="weight"
class="form-control"
value="<?= $profile['weight'] ?? '' ?>">

</div>

<div class="col-md-6 mb-3">

<label>Blood Group/ रक्त समूह /
रक्तगट</label>

<select name="blood_group" class="form-select">

<option value="">Select Blood Group /रक्त समूह चुनें
/रक्तगट निवडा</option>

<?php
$groups = ["A+","A-","B+","B-","AB+","AB-","O+","O-"];

foreach($groups as $group){
?>
<option value="<?= $group ?>"
<?= (($profile['blood_group'] ?? '') == $group) ? 'selected' : '' ?>>
<?= $group ?>
</option>
<?php } ?>

</select>

</div>

<div class="col-md-6 mb-3">

<label>Passport Number</label>

<input
type="text"
name="passport_no"
class="form-control"
value="<?= $profile['passport_no'] ?? '' ?>">

</div>

<div class="row">

    <!-- Aadhaar Number (Required) -->
    <div class="col-md-6 mb-3">
        <label class="form-label">
            Aadhaar Number 
        </label>
        <input
    type="text"
    name="aadhaar_no"
    class="form-control"
    maxlength="12"
    pattern="[0-9]{12}"
    placeholder="Enter 12-digit Aadhaar Number">
    </div>

    <!-- PAN Number (Optional) -->
    <div class="col-md-6 mb-3">
        <label class="form-label">
            PAN Number/पैन नंबर
/पॅन क्रमांक
        </label>
        <input
            type="text"
            name="pan_no"
            class="form-control"
            maxlength="10"
            pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}"
            style="text-transform:uppercase"
            placeholder="ABCDE1234F">
    </div>

</div>

<hr>

<h4 class="mb-3">
Government Issued Photo Identity Card
</h4>

<div class="row">

    <!-- ID Type -->
    <div class="col-md-4 mb-3">
        <label class="form-label">
            ID Type <span class="text-danger">*</span>
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

    <!-- ID Number -->
    <div class="col-md-4 mb-3">

        <label class="form-label">
            ID Number <span class="text-danger">*</span>
        </label>

        <input
            type="text"
            name="govt_id_number"
            class="form-control"
            required
            value="<?= htmlspecialchars($profile['govt_id_number'] ?? '') ?>"
            placeholder="Enter ID Number">

    </div>

    <!-- Upload -->
    <div class="col-md-4 mb-3">

        <label class="form-label">
            Upload ID Card 
        </label>

        <input
            type="file"
            name="govt_id_image"
            class="form-control"
            accept=".jpg,.jpeg,.png,.pdf">

        <?php if(!empty($profile['govt_id_image'])){ ?>

            <small class="text-success d-block mt-2">
                Current File:
                <a href="../uploads/govt-id/<?= $profile['govt_id_image'] ?>" target="_blank">
                    View Uploaded File
                </a>
            </small>

        <?php } ?>

    </div>

</div>
<hr>

<h4 class="mb-3">
Address for Correspondence
</h4>

<div class="row">

<div class="col-md-12 mb-3">

<label>
Address 
<span class="text-danger">*</span>

</label>

<textarea
name="address"
class="form-control"
rows="3"
required><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>

</div>

<div class="col-md-4 mb-3">

<label>Pincode <span class="text-danger">*</span>
</label>

<input
type="text"
id="pincode"
name="pincode"
maxlength="6"
class="form-control"
required
value="<?= htmlspecialchars($profile['pincode'] ?? '') ?>">

</div>

<div class="col-md-4 mb-3">

<label>City <span class="text-danger">*</span>
</label>

<input
type="text"
id="city"
name="city"
class="form-control"
required
value="<?= htmlspecialchars($profile['city'] ?? '') ?>">

</div>

<div class="col-md-4 mb-3">

<label>State <span class="text-danger">*</span>
</label>

<input
type="text"
id="state"
name="state"
class="form-control"
required
value="<?= htmlspecialchars($profile['state'] ?? '') ?>">

</div>

</div>
</div>
<hr>

<h4 class="mb-3">
Permanent Address
/
स्थायी पता
/
कायमचा पत्ता
</h4>

<div class="form-check mb-3">
    <input
        class="form-check-input"
        type="checkbox"
        id="sameAsCurrent">

    <label class="form-check-label" for="sameAsCurrent">
    Same as Correspondence Address
</label>
</div>

<div class="row">

<div class="col-md-12 mb-3">

<label>Permanent Address <span class="text-danger">*</span>
</label>

<textarea
name="permanent_address"
id="permanent_address"
class="form-control"
rows="3"
required><?= htmlspecialchars($profile['permanent_address'] ?? '') ?></textarea>

</div>

<div class="col-md-4 mb-3">

<label>Permanent Pincode <span class="text-danger">*</span>
</label>

<input
type="text"
id="permanent_pincode"
name="permanent_pincode"
maxlength="6"
class="form-control"
required
value="<?= htmlspecialchars($profile['permanent_pincode'] ?? '') ?>">

</div>

<div class="col-md-4 mb-3">

<label>Permanent City <span class="text-danger">*</span>
</label>

<input
type="text"
id="permanent_city"
name="permanent_city"
class="form-control"
required
value="<?= htmlspecialchars($profile['permanent_city'] ?? '') ?>">

</div>

<div class="col-md-4 mb-3">

<label>Permanent State <span class="text-danger">*</span>
</label>

<input
type="text"
id="permanent_state"
name="permanent_state"
class="form-control"
required
value="<?= htmlspecialchars($profile['permanent_state'] ?? '') ?>">

</div>

</div>

<hr>

<h4 class="mb-3">
Language Proficiency
</h4>

<hr>




<div id="languageContainer">

</div>

<button
type="button"
class="btn btn-success mb-4"
id="addLanguage">

+ Add Language

</button>

<br>

<h4 class="mb-3">
Contact Details
</h4>

<div class="row">

    <div class="col-md-6 mb-3">
        <label class="form-label">
            Alternate Contact Number
        </label>

        <input
            type="tel"
            name="alternate_number"
            class="form-control"
            maxlength="10"
            pattern="[0-9]{10}"
            value="<?= htmlspecialchars($profile['alternate_number'] ?? '') ?>"
            placeholder="Enter Alternate Contact Number">
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">
            Emergency Contact Number <span class="text-danger">*</span>
        </label>

        <input
            type="tel"
            name="emergency_contact"
            class="form-control"
            maxlength="10"
            pattern="[0-9]{10}"
            value="<?= htmlspecialchars($profile['emergency_contact'] ?? '') ?>"
            placeholder="Enter Emergency Contact Number"
            required>
    </div>

</div>
<hr>

<h4 class="mb-4">
Family Details
</h4>

<!-- Father Details -->
<div class="card border-0 shadow-sm mb-4">

<div class="card-header bg-primary text-white">
<h5 class="mb-0">
Father Details
</h5>
</div>

<div class="card-body">

<div class="row">

<div class="col-md-3 mb-3">

<label class="form-label">
Father's Name <span class="text-danger">*</span>
</label>

<input
type="text"
name="father_name"
class="form-control"
required
value="<?= htmlspecialchars($profile['father_name'] ?? '') ?>">

</div>

<div class="col-md-2 mb-3">

    <label class="form-label">
        Age 
    </label>

    <input
        type="number"
        name="father_age"
        class="form-control"
        min="18"
        max="100"
        value="<?= htmlspecialchars($profile['father_age'] ?? '') ?>">

</div>

<div class="col-md-3 mb-3">

    <label class="form-label">
        Qualification 
    </label>

    <input
        type="text"
        name="father_qualification"
        class="form-control"
        value="<?= htmlspecialchars($profile['father_qualification'] ?? '') ?>">

</div>

<div class="col-md-2 mb-3">

<label class="form-label">
Occupation 
</label>

<input
type="text"
name="father_occupation"
class="form-control"
value="<?= htmlspecialchars($profile['father_occupation'] ?? '') ?>">

</div>

<div class="col-md-2 mb-3">

<label class="form-label">
Annual Income (₹)
</label>

<input
type="number"
name="father_income"
class="form-control"
min="0"
step="1000"
value="<?= htmlspecialchars($profile['father_income'] ?? '') ?>">

</div>

</div>

</div>

</div>
<!-- Mother Details -->
<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            Mother Details
        </h5>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-3 mb-3">

                <label class="form-label">
                    Mother's Name <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="mother_name"
                    class="form-control"
                    required
                    value="<?= htmlspecialchars($profile['mother_name'] ?? '') ?>">

            </div>


            <div class="col-md-2 mb-3">

    <label class="form-label">
        Age 
    </label>

    <input
        type="number"
        name="mother_age"
        class="form-control"
        min="18"
        max="100"
        value="<?= htmlspecialchars($profile['mother_age'] ?? '') ?>">

</div>

<div class="col-md-3 mb-3">

    <label class="form-label">
        Qualification 
    </label>

    <input
        type="text"
        name="mother_qualification"
        class="form-control"
        value="<?= htmlspecialchars($profile['mother_qualification'] ?? '') ?>">

</div>
            <div class="col-md-2 mb-3">

                <label class="form-label">
                    Occupation 
                </label>

                <input
                    type="text"
                    name="mother_occupation"
                    class="form-control"
                    value="<?= htmlspecialchars($profile['mother_occupation'] ?? '') ?>">

            </div>

            <div class="col-md-2 mb-3">

                <label class="form-label">
                    Annual Income (₹)
                </label>

                <input
                    type="number"
                    name="mother_income"
                    class="form-control"
                    min="0"
                    step="1000"
                    value="<?= htmlspecialchars($profile['mother_income'] ?? '') ?>">

            </div>

        </div>

    </div>

</div>

<!-- Brother / Sister Dynamic Section -->
<hr>

<h4 class="mb-4">
Brother / Sister Details
</h4>

<div id="siblingsContainer"></div>

<button
type="button"
id="addSibling"
class="btn btn-success">

<i class="fa fa-plus"></i> Add Brother / Sister

</button>

<hr>

<hr>

<h4 class="mb-4">
Nationality & Declaration
</h4>

<div class="row">

    <!-- Nationality -->
    <div class="col-md-6 mb-3">
        
       

<div class="col-md-6 mb-3">

    <label class="form-label">
        Nationality <span class="text-danger">*</span>
    </label>

    <?php
    $countries = [
        "Indian",
        "Afghanistan",
        "Albania",
        "Algeria",
        "Andorra",
        "Angola",
        "Antigua and Barbuda",
        "Argentina",
        "Armenia",
        "Australia",
        "Austria",
        "Azerbaijan",
        "Bahamas",
        "Bahrain",
        "Bangladesh",
        "Barbados",
        "Belarus",
        "Belgium",
        "Belize",
        "Benin",
        "Bhutan",
        "Bolivia",
        "Bosnia and Herzegovina",
        "Botswana",
        "Brazil",
        "Brunei",
        "Bulgaria",
        "Burkina Faso",
        "Burundi",
        "Cambodia",
        "Cameroon",
        "Canada",
        "Cape Verde",
        "Central African Republic",
        "Chad",
        "Chile",
        "China",
        "Colombia",
        "Comoros",
        "Congo",
        "Costa Rica",
        "Croatia",
        "Cuba",
        "Cyprus",
        "Czech Republic",
        "Denmark",
        "Djibouti",
        "Dominica",
        "Dominican Republic",
        "Ecuador",
        "Egypt",
        "El Salvador",
        "Equatorial Guinea",
        "Eritrea",
        "Estonia",
        "Eswatini",
        "Ethiopia",
        "Fiji",
        "Finland",
        "France",
        "Gabon",
        "Gambia",
        "Georgia",
        "Germany",
        "Ghana",
        "Greece",
        "Grenada",
        "Guatemala",
        "Guinea",
        "Guyana",
        "Haiti",
        "Honduras",
        "Hungary",
        "Iceland",
        
        "Indonesia",
        "Iran",
        "Iraq",
        "Ireland",
        "Israel",
        "Italy",
        "Jamaica",
        "Japan",
        "Jordan",
        "Kazakhstan",
        "Kenya",
        "Kuwait",
        "Kyrgyzstan",
        "Laos",
        "Latvia",
        "Lebanon",
        "Lesotho",
        "Liberia",
        "Libya",
        "Lithuania",
        "Luxembourg",
        "Madagascar",
        "Malawi",
        "Malaysia",
        "Maldives",
        "Mali",
        "Malta",
        "Mauritania",
        "Mauritius",
        "Mexico",
        "Moldova",
        "Mongolia",
        "Montenegro",
        "Morocco",
        "Mozambique",
        "Myanmar",
        "Namibia",
        "Nepal",
        "Netherlands",
        "New Zealand",
        "Nicaragua",
        "Niger",
        "Nigeria",
        "North Korea",
        "North Macedonia",
        "Norway",
        "Oman",
        "Pakistan",
        "Palestine",
        "Panama",
        "Papua New Guinea",
        "Paraguay",
        "Peru",
        "Philippines",
        "Poland",
        "Portugal",
        "Qatar",
        "Romania",
        "Russia",
        "Rwanda",
        "Saudi Arabia",
        "Senegal",
        "Serbia",
        "Seychelles",
        "Singapore",
        "Slovakia",
        "Slovenia",
        "Somalia",
        "South Africa",
        "South Korea",
        "South Sudan",
        "Spain",
        "Sri Lanka",
        "Sudan",
        "Suriname",
        "Sweden",
        "Switzerland",
        "Syria",
        "Taiwan",
        "Tajikistan",
        "Tanzania",
        "Thailand",
        "Timor-Leste",
        "Togo",
        "Tonga",
        "Trinidad and Tobago",
        "Tunisia",
        "Turkey",
        "Turkmenistan",
        "Uganda",
        "Ukraine",
        "United Arab Emirates",
        "United Kingdom",
        "United States",
        "Uruguay",
        "Uzbekistan",
        "Vanuatu",
        "Vatican City",
        "Venezuela",
        "Vietnam",
        "Yemen",
        "Zambia",
        "Zimbabwe"
    ];
    ?>

    <select name="nationality" class="form-select" required>

        <option value="">Select Country</option>

        <?php foreach($countries as $country){ ?>

            <option value="<?= $country ?>"
                <?= (($profile['nationality'] ?? '') == $country) ? 'selected' : '' ?>>

                <?= $country ?>

            </option>

        <?php } ?>

    </select>

</div>
<!-- total - 252 -->
</select>
    </div>

    <!-- State / UT of Domicile -->
<div class="col-md-6 mb-3">

    <label class="form-label">
        State / UT of Domicile <span class="text-danger">*</span>
    </label>

    <select
        name="domicile_state"
        class="form-select"
        >

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

</div>

<div class="form-check mb-4">

    <input
        class="form-check-input"
        type="checkbox"
        id="declaration"
        name="declaration"
        required>

    <label class="form-check-label" for="declaration">

        I hereby declare that all the information provided above is true and correct to the best of my knowledge.
    </label>

</div>

<hr>

<h4 class="mb-3">
PwBD (Person with Benchmark Disability)
</h4>

<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Are you a PwBD Candidate?
        </label>

        <select
            name="pwbd"
            id="pwbd"
            class="form-select">

            <option value="No" <?= (($profile['pwbd'] ?? 'No')=='No') ? 'selected' : '' ?>>
                No
            </option>

            <option value="Yes" <?= (($profile['pwbd'] ?? '')=='Yes') ? 'selected' : '' ?>>
                Yes
            </option>

        </select>

    </div>

    <div
        class="col-md-6 mb-3"
        id="pwbdBox"
        style="<?= (($profile['pwbd'] ?? '')=='Yes') ? '' : 'display:none;' ?>">

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

            <div class="col-md-6 mb-3"
     id="pwbdPhotoBox"
     style="<?= (($profile['pwbd'] ?? '')=='Yes') ? '' : 'display:none;' ?>">

    <label class="form-label">
        Upload PwBD Certificate / Photo
    </label>

    <input
        type="file"
        name="pwbd_certificate"
        class="form-control"
        accept=".jpg,.jpeg,.png,.pdf">

    <?php if(!empty($profile['pwbd_certificate'])){ ?>
        <small class="text-success d-block mt-2">
            Current File:
            <a href="../uploads/pwbd/<?= $profile['pwbd_certificate'] ?>" target="_blank">
                View Uploaded File
            </a>
        </small>
    <?php } ?>

</div>

    </div>

</div>

<!-- Religion -->
<div class="col-md-6 mb-3">

    <label class="form-label">
        Religion <span class="text-danger">*</span>
    </label>

    <select
    name="religion"
    id="religion"
    class="form-select"
    required>

        <option value="">Select Religion</option>

        <option value="Hindu" <?= (($profile['religion'] ?? '')=='Hindu') ? 'selected' : '' ?>>
            Hindu
        </option>

        <option value="Sikh" <?= (($profile['religion'] ?? '')=='Sikh') ? 'selected' : '' ?>>
            Sikh
        </option>

        <option value="Christian" <?= (($profile['religion'] ?? '')=='Christian') ? 'selected' : '' ?>>
            Christian
        </option>

        <option value="Muslim" <?= (($profile['religion'] ?? '')=='Muslim') ? 'selected' : '' ?>>
            Muslim
        </option>

         <option value="Other" <?= (($profile['religion'] ?? '')=='Other') ? 'selected' : '' ?>>
    Other
</option>

    </select>
<div class="col-md-6 mb-3" id="otherReligionBox"
     style="<?= (($profile['religion'] ?? '')=='Other') ? '' : 'display:none;' ?>">

    <label class="form-label">Please Specify Religion</label>

    <input
        type="text"
        name="other_religion"
        id="otherReligion"
        class="form-control"
        placeholder="Enter Religion Name"
        value="<?= htmlspecialchars($profile['other_religion'] ?? '') ?>">
</div>
</div>

<div class="col-md-6 mb-3">

    <label class="form-label">
        Category <span class="text-danger">*</span>
    </label>

    <select name="category" class="form-select" required>

        <option value="">Select Category</option>

        <option value="General" <?= (($profile['category'] ?? '')=='General') ? 'selected' : '' ?>>General</option>

        <option value="EWS" <?= (($profile['category'] ?? '')=='EWS') ? 'selected' : '' ?>>EWS</option>

        <option value="OBC" <?= (($profile['category'] ?? '')=='OBC') ? 'selected' : '' ?>>OBC</option>

        <option value="SC" <?= (($profile['category'] ?? '')=='SC') ? 'selected' : '' ?>>SC</option>

        <option value="ST" <?= (($profile['category'] ?? '')=='ST') ? 'selected' : '' ?>>ST</option> 

    </select>

</div>
<button
class="btn btn-primary btn-lg">

Save & Continue

</button>

</form>

</div>

</div>

</div>

<script>

const languages = [

<?php
foreach($languages as $row){
echo "{id:".$row['id'].",name:'".$row['language_name']."'},";
}
?>

];
// Aadhaar - only 12 digits
document.querySelector('input[name="aadhaar_no"]').addEventListener("input", function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 12);
});

// PAN - uppercase
document.querySelector('input[name="pan_no"]').addEventListener("input", function () {
    this.value = this.value.toUpperCase();
});

document.getElementById("pincode").addEventListener("input",function(){

this.value=this.value.replace(/\D/g,"").slice(0,6);

});


document.getElementById("pincode").addEventListener("keyup",function(){

let pin=this.value;

if(pin.length===6){

fetch("https://api.postalpincode.in/pincode/"+pin)

.then(response=>response.json())

.then(data=>{

if(data[0].Status==="Success"){

let office=data[0].PostOffice[0];

document.getElementById("city").value=office.District;

document.getElementById("state").value=office.State;

}

});

}

});



let cityTimer;

document.getElementById("city").addEventListener("input", function () {

    clearTimeout(cityTimer);

    let city = this.value.trim();

    if (city.length < 3) {
        document.getElementById("state").value = "";
        return;
    }

    cityTimer = setTimeout(function () {

        fetch("https://nominatim.openstreetmap.org/search?city=" + encodeURIComponent(city) + "&country=India&format=jsonv2&addressdetails=1&limit=1")
            .then(response => response.json())
            .then(data => {

                if (data.length > 0 && data[0].address.state) {
                    document.getElementById("state").value = data[0].address.state;
                } else {
                    document.getElementById("state").value = "";
                }

            })
            .catch(error => {
                console.log(error);
            });

    }, 500);

});
let siblingIndex = 0;

document.getElementById("addSibling").addEventListener("click", function () {

    const card = document.createElement("div");

    card.className = "card shadow-sm border mb-4 sibling-card";

    card.innerHTML = `

        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">

            <strong>Brother / Sister</strong>

            <button type="button" class="btn btn-sm btn-danger removeSibling">
                Remove
            </button>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-2 mb-3">

                    <label>Relation *</label>

                    <select name="siblings[${siblingIndex}][relation]" class="form-select" required>

                        <option value="">Select</option>

                        <option value="Brother">Brother</option>

                        <option value="Sister">Sister</option>

                    </select>

                </div>

                <div class="col-md-3 mb-3">

                    <label>Name *</label>

                    <input
                    type="text"
                    name="siblings[${siblingIndex}][name]"
                    class="form-control"
                    required>

                </div>

                <div class="col-md-1 mb-3">

                    <label>Age </label>

                    <input
                    type="number"
                    name="siblings[${siblingIndex}][age]"
                    class="form-control"
                    min="1"
                    max="100"
                    required>

                </div>

                <div class="col-md-2 mb-3">

                    <label>Qualification</label>

                    <input
                    type="text"
                    name="siblings[${siblingIndex}][qualification]"
                    class="form-control">

                </div>

                <div class="col-md-2 mb-3">

                    <label>Occupation</label>

                    <input
                    type="text"
                    name="siblings[${siblingIndex}][occupation]"
                    class="form-control">

                </div>

                <div class="col-md-2 mb-3">

                    <label>Annual Income</label>

                    <input
                    type="number"
                    name="siblings[${siblingIndex}][annual_income]"
                    class="form-control"
                    min="0">

                </div>

            </div>

        </div>

    `;


    document.getElementById("siblingsContainer").appendChild(card);

    siblingIndex++;

});

document.addEventListener("click", function (e) {

    const btn = e.target.closest(".removeSibling");

    if (!btn) return;

    const card = btn.closest(".sibling-card");

    if (card) {
        card.remove();
    }

});



document.getElementById("sameAsCurrent").addEventListener("change", function () {

    if (this.checked) {

        document.getElementById("permanent_address").value =
            document.querySelector("[name='address']").value;

        document.getElementById("permanent_pincode").value =
            document.getElementById("pincode").value;

        document.getElementById("permanent_city").value =
            document.getElementById("city").value;

        document.getElementById("permanent_state").value =
            document.getElementById("state").value;

    }
});
</script>
<script>
    const sameAsCurrent = document.getElementById("sameAsCurrent");

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

    } else {

        document.getElementById("permanent_address").value = "";
        document.getElementById("permanent_pincode").value = "";
        document.getElementById("permanent_city").value = "";
        document.getElementById("permanent_state").value = "";

    }

});

function syncPermanentAddress() {

    if (!sameAsCurrent.checked) return;

    document.getElementById("permanent_address").value =
        document.querySelector("textarea[name='address']").value;

    document.getElementById("permanent_pincode").value =
        document.getElementById("pincode").value;

    document.getElementById("permanent_city").value =
        document.getElementById("city").value;

    document.getElementById("permanent_state").value =
        document.getElementById("state").value;
}

document.querySelector("textarea[name='address']").addEventListener("input", syncPermanentAddress);
document.getElementById("pincode").addEventListener("input", syncPermanentAddress);
document.getElementById("city").addEventListener("input", syncPermanentAddress);
document.getElementById("state").addEventListener("input", syncPermanentAddress);
</script>
<script>
    document.getElementById("pwbd").addEventListener("change", function () {

    const idBox = document.getElementById("pwbdBox");
    const photoBox = document.getElementById("pwbdPhotoBox");

    if (this.value === "Yes") {
        idBox.style.display = "block";
        photoBox.style.display = "block";
    } else {
        idBox.style.display = "none";
        photoBox.style.display = "none";

        document.querySelector("input[name='pwbd_id']").value = "";
        document.querySelector("input[name='pwbd_certificate']").value = "";
    }

});
</script>
<script>
    document.getElementById("permanent_pincode").addEventListener("input", function () {

    this.value = this.value.replace(/\D/g, "").slice(0, 6);

    let pin = this.value;

    if (pin.length === 6) {

        fetch("https://api.postalpincode.in/pincode/" + pin)
            .then(res => res.json())
            .then(data => {

                if (data[0].Status === "Success") {

                    let office = data[0].PostOffice[0];

                    document.getElementById("permanent_city").value = office.District;
                    document.getElementById("permanent_state").value = office.State;
                }

            });

    }

});

let permanentCityTimer;

document.getElementById("permanent_city").addEventListener("input", function () {

    clearTimeout(permanentCityTimer);

    let city = this.value.trim();

    if (city.length < 2) {
        document.getElementById("permanent_state").value = "";
        return;
    }

    permanentCityTimer = setTimeout(function () {

        fetch("https://nominatim.openstreetmap.org/search?city="
            + encodeURIComponent(city)
            + "&country=India&format=jsonv2&addressdetails=1&limit=1")

            .then(res => res.json())
            .then(data => {

                if (data.length > 0) {

                    let address = data[0].address;

                    document.getElementById("permanent_state").value =
                        address.state || "";

                } else {

                    document.getElementById("permanent_state").value = "";

                }

            });

    }, 500);

});

</script>
<script>
document.getElementById("religion").addEventListener("change", function () {

    const box = document.getElementById("otherReligionBox");
    const input = document.getElementById("otherReligion");

    if (this.value === "Other") {
        box.style.display = "block";
    } else {
        box.style.display = "none";
        input.value = "";
    }
});


</script>
<script src="../assets/js/personal.js"></script>

</body>
</html>