document
.getElementById("addExperience")
.addEventListener("click",function(){

let card=document.querySelector(".experience-box");

let clone=card.cloneNode(true);

clone.querySelectorAll("input").forEach(input=>input.value="");

clone.querySelectorAll("textarea").forEach(text=>text.value="");

document
.getElementById("experienceContainer")
.appendChild(clone);

});