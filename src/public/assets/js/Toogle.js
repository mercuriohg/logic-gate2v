const menuButton = document.getElementById("menu-toogle");
const closeButton = document.getElementById("x-mark");
const navList = document.getElementById("list-ul");

if (menuButton && closeButton && navList) {
  const toggleMenu = (open) => {
    navList.classList.toggle("active", open);
    menuButton.style.display = open ? "none" : "flex";
    closeButton.style.display = open ? "flex" : "none";
  };

  menuButton.addEventListener("click", () => toggleMenu(true));
  closeButton.addEventListener("click", () => toggleMenu(false));

  navList.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => toggleMenu(false));
  });
}
