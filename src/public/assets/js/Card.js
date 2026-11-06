const infoPorta = document.getElementById("info-porta");

const cardAnd = document.getElementById("and");
const cardOr = document.getElementById("or");
const cardXor = document.getElementById("xor");
const cardNand = document.getElementById("nand");
const cardNor = document.getElementById("nor");
const cardXnor = document.getElementById("xnor");
const cardNot = document.getElementById("not");

function mostrarPorta(porta, imagem) {
  infoPorta.innerHTML = `
        <div class="card-text">
            <header class="mark">
                <i class="fa-solid fa-xmark"></i>
            </header>

            <img src="${imagem}" alt="Porta ${porta.toUpperCase()}" class="card-img">

            <p>${texto(porta)}</p>
        </div>
    `;
  infoPorta.classList.add("active");

  const xMark = infoPorta.querySelector(".fa-solid.fa-xmark");
  if (xMark) {
    xMark.addEventListener("click", () => {
      infoPorta.classList.remove("active");
      infoPorta.innerHTML = "";
    });
  }
}

infoPorta.addEventListener("click", (event) => {
  if (event.target === infoPorta) {
    infoPorta.classList.remove("active");
    infoPorta.innerHTML = "";
  }
});

cardAnd.addEventListener("click", () => {
  mostrarPorta("and", "/assets/img/and.png");
});

cardOr.addEventListener("click", () => {
  mostrarPorta("or", "/assets/img/or.png");
});

cardXor.addEventListener("click", () => {
  mostrarPorta("xor", "/assets/img/xor2.png");
});

cardNand.addEventListener("click", () => {
  mostrarPorta("nand", "/assets/img/nand.png");
});

cardNor.addEventListener("click", () => {
  mostrarPorta("nor", "/assets/img/nor.png");
});

cardXnor.addEventListener("click", () => {
  mostrarPorta("xnor", "/assets/img/xnor.png");
});

cardNot.addEventListener("click", () => {
  mostrarPorta("not", "/assets/img/not.png");
});

function texto(porta) {
  const textos = {
    and: "Porta AND: A saída é verdadeira (1) somente se ambas as entradas forem verdadeiras (1).",
    or: "Porta OR: A saída é verdadeira (1) se pelo menos uma das entradas for verdadeira (1).",
    xor: "Porta XOR: A saída é verdadeira (1) se exatamente uma das entradas for verdadeira (1).",
    nand: "Porta NAND: A saída é falsa (0) somente se ambas as entradas forem verdadeiras (1).",
    nor: "Porta NOR: A saída é verdadeira (1) somente se ambas as entradas forem falsas (0).",
    xnor: "Porta XNOR: A saída é verdadeira (1) se ambas as entradas forem iguais.",
    not: "Porta NOT: A saída é o inverso da entrada.",
  };

  return textos[porta];
}
