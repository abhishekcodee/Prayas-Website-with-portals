document
.getElementById("addCertificate")
.addEventListener("click",function(){

let card=document.querySelector(".certificate-box");

let clone=card.cloneNode(true);

clone.querySelectorAll("input").forEach(input=>{

if(input.type!="file"){

input.value="";

}

});

document
.getElementById("certificateContainer")
.appendChild(clone);

});