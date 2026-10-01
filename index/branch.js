function getCurrentUTCMinutes() {
  const now = new Date();
  return (now.getUTCHours() * 60) + now.getUTCMinutes();
}

const branches = [
  { name: "Branch 1", address: "Shop 1, Road 1", hoursIST: "09:00 AM – 3:00 PM", utcOpen: 210, utcClose: 570 },
  { name: "Branch 2", address: "Shop 2, Road 2", hoursIST: "09:30 AM – 9:00 PM", utcOpen: 240, utcClose: 930 },
  { name: "Branch 3", address: "Shop 3, Road 3", hoursIST: "09:30 AM – 3:00 PM", utcOpen: 240, utcClose: 570 },
  { name: "Branch 4", address: "Shop 4, Road 4", hoursIST: "09:30 AM – 9:00 PM", utcOpen: 240, utcClose: 930 },
  { name: "Branch 5", address: "Shop 5, Road 5", hoursIST: "09:30 AM – 3:00 PM", utcOpen: 240, utcClose: 570 },
  { name: "Branch 6", address: "Shop 6, Road 6", hoursIST: "09:30 AM – 9:00 PM", utcOpen: 240, utcClose: 930 }
];

function checkIsOpen(branch) {
  const currentUTC = getCurrentUTCMinutes();
  return currentUTC >= branch.utcOpen && currentUTC < branch.utcClose;
}

function renderBranches() {
  const container = document.getElementById("branch-container");
  if (!container) return;

  container.textContent = "";

  branches.forEach(branch => {
    const card = document.createElement("div");
    card.className = "border border-black p-4 m-2 bg-white";

    const title = document.createElement("p");
    title.className = "font-bold";
    const flag = checkIsOpen(branch) ? " [OPEN]" : " [CLOSED]";
    title.textContent = branch.name + flag;

    const addr = document.createElement("p");
    addr.textContent = branch.address;

    const hours = document.createElement("p");
    hours.textContent = branch.hoursIST;

    card.appendChild(title);
    card.appendChild(addr);
    card.appendChild(hours);
    container.appendChild(card);
  });
}

document.addEventListener("DOMContentLoaded", renderBranches);