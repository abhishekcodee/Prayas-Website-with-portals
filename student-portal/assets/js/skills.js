document.getElementById("addSkill").addEventListener("click", function () {

    let card = document.querySelector(".skill-item");

    let clone = card.cloneNode(true);

    // Sabhi select ko reset karo
    clone.querySelectorAll("select").forEach(function(select) {
        select.selectedIndex = 0;
    });

    document.getElementById("skillContainer").appendChild(clone);

});