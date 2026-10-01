fetch("./services.json")
  .then(function(res) {
    return res.json();
  })
  .then(function(items) {
    document.getElementById("search-box").disabled = false;
    document.getElementById("cat-select").disabled = false;

    for (var i = 0; i < items.length; i++) {
      var alreadyAdded = false;
      var dropdown = document.getElementById("cat-select");
      
      for (var j = 0; j < dropdown.options.length; j++) {
        if (dropdown.options[j].value == items[i].category) {
          alreadyAdded = true;
        }
      }

      if (alreadyAdded == false) {
        var opt = document.createElement("option");
        opt.value = items[i].category;
        opt.textContent = items[i].category;
        dropdown.appendChild(opt);
      }
    }

    loadItems();
  });

function loadItems() {
  var userText = document.getElementById("search-box").value.toLowerCase();
  var chosenCategory = document.getElementById("cat-select").value;
  var resultsBox = document.getElementById("item-list");

  resultsBox.textContent = "";
  fetch("./services.json")
    .then(function(res) {
      return res.json();
    })
    .then(function(data) {
      for (var i = 0; i < data.length; i++) {
        if (chosenCategory == "" || chosenCategory == data[i].category) {
          var everythingInItem = data[i].id + " " + data[i].name + " " + data[i].category + " " + data[i].size + " " + data[i].price;
          everythingInItem = everythingInItem.toLowerCase();

          if (userText == "" || everythingInItem.indexOf(userText) != -1) {
            
            var card = document.createElement("article");
            card.className = "rounded border border-stone-300 bg-white p-4 shadow-sm";

            var p1 = document.createElement("p");
            p1.className = "mb-2 font-semibold";
            p1.textContent = "ID: " + data[i].id + " - " + data[i].name;

            var p2 = document.createElement("p");
            p2.className = "text-sm text-stone-600";
            p2.textContent = "Category: " + data[i].category + " | Size: " + data[i].size + " | Price: $" + data[i].price;

            card.appendChild(p1);
            card.appendChild(p2);
            resultsBox.appendChild(card);
          }

        }
      }
    });
}

document.getElementById("search-box").oninput = function() {
  loadItems();
};

document.getElementById("cat-select").onchange = function() {
  loadItems();
};