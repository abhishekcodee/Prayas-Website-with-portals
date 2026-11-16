document.getElementById("addProject").addEventListener("click",function(){

let card=document.querySelector(".project-box");

let clone=card.cloneNode(true);

clone.querySelectorAll("input").forEach(input=>input.value="");

clone.querySelectorAll("textarea").forEach(text=>text.value="");

document.getElementById("projectContainer").appendChild(clone);

});