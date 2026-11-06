// Envia o resultado do circuito para o backend (POST /api/fase/tentativa).
// O editor de circuito do jogo deve chamar: LogicGate.enviarSaida(0 | 1)
(function () {
  const box = document.getElementById("fase-play");
  if (!box) return;

  const resultado = document.getElementById("fase-resultado");

  async function enviarSaida(saida) {
    const body = new URLSearchParams({
      id: box.dataset.faseId,
      saida: String(saida),
      csrf_token: box.dataset.csrf,
    });

    let dados;
    try {
      const resp = await fetch("/api/fase/tentativa", {
        method: "POST",
        body,
        headers: { Accept: "application/json" },
      });
      dados = await resp.json();
    } catch (e) {
      resultado.textContent = "Não foi possível falar com o servidor.";
      return null;
    }

    if (dados.erro) {
      resultado.textContent = dados.erro;
    } else if (!dados.acertou) {
      resultado.textContent = `Não foi dessa vez. Tentativas: ${dados.tentativas}`;
    } else if (dados.ja_concluida) {
      resultado.textContent = "Você já tinha concluído esta fase (sem XP extra).";
    } else {
      resultado.textContent =
        `Fase concluída! +${dados.xp_ganho} XP, +${dados.pontos_ganhos} pontos. ` +
        `Nível ${dados.usuario.nivel}.`;
    }
    return dados;
  }

  window.LogicGate = { enviarSaida };

  document.querySelectorAll("[data-saida]").forEach((btn) => {
    btn.addEventListener("click", () => enviarSaida(Number(btn.dataset.saida)));
  });
})();
