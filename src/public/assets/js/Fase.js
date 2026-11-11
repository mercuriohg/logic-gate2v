(function () {
  "use strict";

  const configElement = document.getElementById("circuit-config");
  const board = document.getElementById("circuit-board");
  if (!configElement || !board) return;

  const status = document.getElementById("fase-resultado");
  const inputContainer = document.getElementById("circuit-inputs");
  const gateContainer = document.getElementById("circuit-gates");
  const wireLayer = document.getElementById("circuit-wires");
  const led = document.getElementById("circuit-led");
  const ledValue = document.getElementById("circuit-led-value");
  const gateSummary = document.getElementById("gate-palette");
  const config = JSON.parse(configElement.textContent);

  if (!window.CircuitLogic || !Array.isArray(config.initialInputs) ||
      typeof config.csrf !== "string" || !Array.isArray(config.gates)) {
    throw new Error("A configuração do simulador está incompleta.");
  }

  const inputs = config.initialInputs.map((value, index) => {
    if (value !== "0" && value !== "1") {
      throw new Error(`Estado inicial inválido para a Entrada ${index + 1}.`);
    }
    return { id: `input-${index + 1}`, value: Number(value) };
  });
  if (inputs.length < 1 || inputs.length > 8) {
    throw new Error("A fase deve ter entre 1 e 8 entradas.");
  }

  const gateDefinitions = config.gates.map(({ type, quantity }) => ({ type, quantity: Number(quantity) }));
  const activeTypes = [];
  let gates = [];
  let connections = [];
  const pinElements = new Map();
  const inputPinElements = new Map();
  const gateElements = new Map();
  const baseBoardHeight = Math.max(
    440,
    Math.min(680, Math.round(window.innerHeight * 0.56)),
    100 + inputs.length * 68
  );
  board.style.height = `${baseBoardHeight}px`;
  const ledEndpoint = { kind: "led", id: "led", pin: 0 };
  const ledInput = led.querySelector(".circuit-pin-input");
  pinElements.set(endpointKey(ledEndpoint, "input"), ledInput);

  function endpointKey(endpoint, direction) {
    return `${endpoint.kind}:${endpoint.id}:${direction}:${endpoint.pin ?? ""}`;
  }

  function setStatus(message, kind) {
    status.textContent = message;
    status.dataset.kind = kind || "";
  }

  function makePin(endpoint, direction, label) {
    const pin = document.createElement("span");
    pin.className = `circuit-pin circuit-pin-${direction}`;
    pin.setAttribute("role", "img");
    pin.setAttribute("aria-label", label);
    pin.title = label;
    pinElements.set(endpointKey(endpoint, direction), pin);
    return pin;
  }

  function renderInput(input, index) {
    const card = document.createElement("div");
    card.className = "circuit-input";
    card.style.top = `${Math.max(28, Math.round(baseBoardHeight / 2 - inputs.length * 34 + index * 68))}px`;

    const label = document.createElement("span");
    label.className = "circuit-input-label";
    label.textContent = `ENTRADA ${index + 1}`;
    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "circuit-toggle";
    toggle.dataset.value = String(input.value);
    toggle.textContent = String(input.value);
    toggle.setAttribute("aria-label", `Alternar Entrada ${index + 1}, valor ${input.value}`);
    toggle.addEventListener("click", () => {
      input.value = input.value === 0 ? 1 : 0;
      toggle.dataset.value = String(input.value);
      toggle.textContent = String(input.value);
      toggle.setAttribute("aria-label", `Alternar Entrada ${index + 1}, valor ${input.value}`);
      evaluateCircuit();
      setStatus(`Entrada ${index + 1} alterada para ${input.value}. O circuito atualizou; pressione TEST para validar.`);
    });

    const outputPin = makePin(
      { kind: "input", id: input.id },
      "output",
      `Saída da Entrada ${index + 1}`
    );
    inputPinElements.set(endpointKey({ kind: "input", id: input.id }, "output"), outputPin);
    card.append(label, toggle, outputPin);
    inputContainer.append(card);
  }

  function renderGate(gate, index) {
    const card = document.createElement("article");
    card.className = "circuit-gate";
    card.style.left = `${190 + index * 150}px`;
    card.style.top = `${Math.round((baseBoardHeight - 100) / 2)}px`;

    const header = document.createElement("div");
    header.className = "circuit-gate-head";
    const title = document.createElement("strong");
    title.className = "circuit-gate-title";
    title.textContent = gate.type;
    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "circuit-gate-remove";
    remove.textContent = "×";
    remove.setAttribute("aria-label", `Remover porta ${gate.type} do circuito`);
    remove.addEventListener("click", () => removeGate(index));
    header.append(title, remove);

    const body = document.createElement("div");
    body.className = "circuit-gate-body";
    const gateInputs = document.createElement("div");
    gateInputs.className = "circuit-gate-inputs";
    for (let pin = 0; pin < window.CircuitLogic.gates[gate.type].inputs; pin += 1) {
      gateInputs.append(makePin(
        { kind: "gate", id: gate.id, pin },
        "input",
        `Entrada ${pin + 1} da porta ${gate.type}`
      ));
    }
    const symbol = document.createElement("span");
    symbol.className = "circuit-gate-symbol";
    symbol.textContent = window.CircuitLogic.gates[gate.type].symbol;
    const output = document.createElement("span");
    output.className = "circuit-gate-value";
    output.dataset.outputValue = gate.id;
    output.textContent = "?";
    body.append(
      gateInputs,
      symbol,
      output,
      makePin({ kind: "gate", id: gate.id }, "output", `Saída da porta ${gate.type}`)
    );
    card.append(header, body);
    gateContainer.append(card);
    gateElements.set(gate.id, card);
  }

  function renderGateSummary() {
    gateSummary.replaceChildren();
    [...config.gates]
      .sort((a, b) => window.CircuitLogic.gateOrder.indexOf(a.type) - window.CircuitLogic.gateOrder.indexOf(b.type))
      .forEach(({ type, quantity }) => {
      if (Number(quantity) < 1) return;
      const used = activeTypes.filter((activeType) => activeType === type).length;
      const item = document.createElement("button");
      item.type = "button";
      item.className = "gate-circuit-item";
      item.disabled = used >= Number(quantity);
      item.setAttribute("aria-label", `Adicionar porta ${type}; ${Number(quantity) - used} disponíveis`);
      const title = document.createElement("strong");
      title.textContent = type;
      const image = document.createElement("img");
      image.className = "gate-circuit-image";
      image.src = `/assets/img/${type.toLowerCase() === "xor" ? "xor2" : type.toLowerCase()}.png`;
      image.alt = "";
      const count = document.createElement("span");
      count.textContent = `${used} / ${quantity} no circuito`;
      item.append(title, image, count);
      item.addEventListener("click", () => addGate(type));
      gateSummary.append(item);
    });
    if (!gateDefinitions.some(({ quantity }) => quantity > 0)) {
      const info = document.createElement("p");
      info.className = "circuit-no-gates";
      info.textContent = "Esta fase conecta uma entrada diretamente ao LED.";
      gateSummary.append(info);
    }
  }

  function updateCircuit() {
    const circuit = window.CircuitLogic.buildConfiguredCircuit(inputs, gateDefinitions, activeTypes);
    gates = circuit.gates;
    connections = circuit.connections;
    board.style.width = `${Math.max(760, 420 + gates.length * 150)}px`;
    pinElements.clear();
    inputPinElements.forEach((pin, key) => pinElements.set(key, pin));
    pinElements.set(endpointKey(ledEndpoint, "input"), ledInput);
    gateElements.clear();
    gateContainer.replaceChildren();
    gates.forEach(renderGate);
    renderGateSummary();
    renderWires();
    evaluateCircuit();
  }

  function addGate(type) {
    const configured = gateDefinitions.find((definition) => definition.type === type);
    const used = activeTypes.filter((activeType) => activeType === type).length;
    if (!configured || used >= configured.quantity) {
      setStatus(`Não há mais portas ${type} disponíveis nesta fase.`, "error");
      return;
    }
    activeTypes.push(type);
    updateCircuit();
    setStatus(`Porta ${type} adicionada. Saídas e conexões do circuito atualizadas.`);
  }

  function removeGate(index) {
    const [type] = activeTypes.splice(index, 1);
    updateCircuit();
    setStatus(`Porta ${type} removida e devolvida à faixa de componentes.`);
  }

  function pinCenter(pin) {
    const pinRect = pin.getBoundingClientRect();
    const boardRect = board.getBoundingClientRect();
    return {
      x: pinRect.left + pinRect.width / 2 - boardRect.left,
      y: pinRect.top + pinRect.height / 2 - boardRect.top,
    };
  }

  function renderWires() {
    wireLayer.setAttribute("viewBox", `0 0 ${board.clientWidth} ${board.clientHeight}`);
    wireLayer.replaceChildren();
    connections.forEach((connection) => {
      const from = pinElements.get(endpointKey(connection.source, "output"));
      const to = pinElements.get(endpointKey(connection.target, "input"));
      if (!from || !to) {
        console.error(`Não foi possível renderizar a conexão ${connection.id}.`);
        return;
      }
      const start = pinCenter(from);
      const end = pinCenter(to);
      const curve = Math.max(40, Math.abs(end.x - start.x) / 2);
      const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
      path.setAttribute(
        "d",
        `M ${start.x} ${start.y} C ${start.x + curve} ${start.y}, ${end.x - curve} ${end.y}, ${end.x} ${end.y}`
      );
      path.setAttribute("class", "circuit-wire");
      wireLayer.append(path);
    });
  }

  function evaluateCircuit() {
    const result = window.CircuitLogic.evaluateCircuit(inputs, gates, connections);
    gateElements.forEach((element, id) => {
      const value = result.valid ? result.gateValues[id] : undefined;
      element.querySelector(`[data-output-value="${id}"]`).textContent =
        value === 0 || value === 1 ? String(value) : "?";
    });
    const output = result.valid ? result.output : null;
    led.classList.toggle("on", output === 1);
    ledValue.textContent = output === 0 || output === 1 ? String(output) : "?";
    return result;
  }

  async function submitOutput(output) {
    let response;
    let data;
    try {
      response = await fetch("/api/fase/tentativa", {
        method: "POST",
        body: new URLSearchParams({
          id: String(config.phaseId),
          saida: String(output),
          csrf_token: config.csrf,
        }),
        headers: { Accept: "application/json" },
      });
      data = await response.json();
    } catch (error) {
      console.error("Falha ao enviar o resultado do circuito.", error);
      setStatus("Não foi possível enviar o resultado ao servidor. Verifique a conexão e tente novamente.", "error");
      return;
    }
    if (!data || typeof data !== "object") {
      setStatus("O servidor retornou uma resposta inválida. Tente novamente.", "error");
      return;
    }
    if (!response.ok || data.erro) {
      setStatus(data.erro || `O servidor respondeu com erro (${response.status}).`, "error");
      return;
    }
    if (!data.acertou) {
      setStatus(`O LED recebeu ${output}; o objetivo é 1. Tentativas: ${data.tentativas}.`, "error");
      return;
    }

    setStatus(
      data.ja_concluida
        ? "Esta fase já estava concluída."
        : `Fase concluída! +${data.xp_ganho} XP e +${data.pontos_ganhos} pontos.`,
      "success"
    );
    if (data.proxima_fase_id) {
      const next = document.createElement("a");
      next.href = `/fase?id=${Number(data.proxima_fase_id)}`;
      next.className = "btn-admin";
      next.textContent = "Avançar para a próxima fase";
      status.append(" ", next);
    }
  }

  document.getElementById("circuit-test").addEventListener("click", async () => {
    const result = evaluateCircuit();
    if (!result.valid) {
      setStatus(result.errors.join(" "), "error");
      return;
    }
    await submitOutput(result.output);
  });

  document.getElementById("circuit-reset").addEventListener("click", () => {
    inputs.forEach((input, index) => {
      input.value = Number(config.initialInputs[index]);
      const toggle = inputContainer.querySelectorAll(".circuit-toggle")[index];
      toggle.dataset.value = String(input.value);
      toggle.textContent = String(input.value);
      toggle.setAttribute("aria-label", `Alternar Entrada ${index + 1}, valor ${input.value}`);
    });
    evaluateCircuit();
    setStatus("Entradas restauradas para os valores iniciais da fase.");
  });

  document.getElementById("circuit-info-open").addEventListener("click", () => {
    document.getElementById("circuit-info").showModal();
  });

  inputs.forEach(renderInput);
  updateCircuit();
  window.addEventListener("resize", renderWires);
})();
