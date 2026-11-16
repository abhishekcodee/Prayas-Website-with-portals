const faceCanvas = document.getElementById("faceCanvas");
const faceCtx = faceCanvas.getContext("2d");
const status = document.getElementById("status");
const video = document.getElementById("video");
const canvas = document.getElementById("canvas");
const capture = document.getElementById("capture");
const preview = document.getElementById("preview");
const retake = document.getElementById("retake");
const save = document.getElementById("save");
const photo = document.getElementById("photo");

navigator.mediaDevices.getUserMedia({
    video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: "user" }
}).then(stream => {
    video.srcObject = stream;
}).catch(err => {
    console.error("Camera access error:", err);
    status.className = "alert alert-danger shadow-sm fw-bold border-0";
    status.innerHTML = "<i class='fa-solid fa-video-slash me-2'></i> Unable to access camera. Please allow camera permissions.";
});

const faceDetection = new FaceDetection({
    locateFile: (file) => {
        return `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`;
    }
});

faceDetection.setOptions({
    model: "short",
    minDetectionConfidence: 0.5
});

faceDetection.onResults((results) => {
    faceCanvas.width = video.videoWidth;
    faceCanvas.height = video.videoHeight;
    faceCtx.clearRect(0, 0, faceCanvas.width, faceCanvas.height);

    if (!results.detections || results.detections.length === 0) {
        capture.disabled = true;
        status.className = "alert alert-danger shadow-sm fw-bold border-0";
        status.innerHTML = "<i class='fa-solid fa-user-slash me-2'></i> Face Not Visible — Position your face inside the frame.";
    }
    else if (results.detections.length > 1) {
        capture.disabled = true;
        status.className = "alert alert-danger shadow-sm fw-bold border-0";
        status.innerHTML = "<i class='fa-solid fa-users me-2'></i> Multiple Faces Detected — Only 1 person allowed in frame.";
    }
    else if (results.detections.length === 1) {
        let box = results.detections[0].boundingBox;
        let frameWidth = video.videoWidth;
        let frameHeight = video.videoHeight;

        let faceWidth = box.width * frameWidth;
        let faceHeight = box.height * frameHeight;
        let faceArea = faceWidth * faceHeight;
        let frameArea = frameWidth * frameHeight;
        let percentage = (faceArea / frameArea) * 100;

        let centerX = box.xCenter;
        let centerY = box.yCenter;

        // Check if face is centered
        if (centerX < 0.15 || centerX > 0.85 || centerY < 0.15 || centerY > 0.85) {
            capture.disabled = true;
            status.className = "alert alert-warning shadow-sm fw-bold border-0";
            status.innerHTML = "<i class='fa-solid fa-expand me-2'></i> Please position your face in the center of the camera.";
            return;
        }

        // Check if face is close enough
        if (percentage < 1.5) {
            capture.disabled = true;
            status.className = "alert alert-warning shadow-sm fw-bold border-0";
            status.innerHTML = "<i class='fa-solid fa-magnifying-glass-plus me-2'></i> Please move slightly closer to the camera.";
            return;
        }

        // Face is clearly visible, centered, and ready for capture!
        capture.disabled = false;
        status.className = "alert alert-success shadow-sm fw-bold border-0";
        status.innerHTML = "<i class='fa-solid fa-circle-check me-2'></i> Face Verified &amp; Ready — Click 'Capture Photo'.";
    }
});

capture.onclick = function () {
    if (capture.disabled) return false;

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    let ctx = canvas.getContext("2d");
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    let img = canvas.toDataURL("image/png");
    photo.value = img;

    preview.src = img;
    preview.style.display = "block";
    video.style.display = "none";

    capture.style.display = "none";
    retake.style.display = "inline-block";
    save.style.display = "inline-block";

    status.className = "alert alert-info shadow-sm fw-bold border-0";
    status.innerHTML = "<i class='fa-solid fa-image me-2'></i> Photo captured! Click 'Save &amp; Continue' or 'Retake'.";
};

retake.onclick = function () {
    preview.style.display = "none";
    video.style.display = "block";

    capture.style.display = "inline-block";
    retake.style.display = "none";
    save.style.display = "none";

    photo.value = "";
    status.className = "alert alert-warning shadow-sm fw-bold border-0";
    status.innerHTML = "<i class='fa-solid fa-spinner fa-spin me-2'></i> Detecting face...";
};

const camera = new Camera(video, {
    onFrame: async () => {
        if (video.style.display !== "none") {
            await faceDetection.send({ image: video });
        }
    },
    width: 1280,
    height: 720
});

camera.start();

save.onclick = function () {
    if (!photo.value) {
        alert("Please capture a photo first.");
        return false;
    }

    save.disabled = true;
    save.innerHTML = "<i class='fa-solid fa-spinner fa-spin me-2'></i> Saving Photo...";
    document.querySelector("form").submit();
};