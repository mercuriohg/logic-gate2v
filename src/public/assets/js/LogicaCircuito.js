(function (root, factory) {
  const api = factory();
  if (typeof module === "object" && module.exports) {
    module.exports = api;
  } else {
    root.CircuitLogic = api;
  }
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
  "use strict";

  const gates = Object.freeze({
    NOT: Object.freeze({ inputs: 1, symbol: "¬" }),
    AND: Object.freeze({ inputs: 2, symbol: "∧" }),
    OR: Object.freeze({ inputs: 2, symbol: "∨" }),
    XOR: Object.freeze({ inputs: 2, symbol: "⊕" }),
    NAND: Object.freeze({ inputs: 2, symbol: "⊼" }),
    NOR: Object.freeze({ inputs: 2, symbol: "⊽" }),
    XNOR: Object.freeze({ inputs: 2, symbol: "≡" }),
  });
  const gateOrder = Object.freeze(["NOT", "AND", "OR", "XOR", "NAND", "NOR", "XNOR"]);

  function evaluateGate(type, values) {
    if (!Object.prototype.hasOwnProperty.call(gates, type)) {
      throw new Error("Tipo de porta inválido.");
    }
    if (values.length !== gates[type].inputs || values.some((value) => value !== 0 && value !== 1)) {
      throw new Error("Quantidade ou valor de entrada inválido.");
    }

    const allOn = values.every((value) => value === 1);
    const anyOn = values.some((value) => value === 1);
    switch (type) {
      case "NOT":
        return values[0] === 1 ? 0 : 1;
      case "AND":
        return allOn ? 1 : 0;
      case "OR":
        return anyOn ? 1 : 0;
      case "XOR":
        return values[0] !== values[1] ? 1 : 0;
      case "NAND":
        return allOn ? 0 : 1;
      case "NOR":
        return anyOn ? 0 : 1;
      case "XNOR":
        return values[0] === values[1] ? 1 : 0;
      default:
        throw new Error("Tipo de porta inválido.");
    }
  }

  function wouldCreateCycle(connections, sourceId, targetId) {
    const downstream = new Map();
    connections.forEach((connection) => {
      if (connection.source.kind === "gate" && connection.target.kind === "gate") {
        const next = downstream.get(connection.source.id) || [];
        next.push(connection.target.id);
        downstream.set(connection.source.id, next);
      }
    });

    const pending = [targetId];
    const visited = new Set();
    while (pending.length) {
      const current = pending.pop();
      if (current === sourceId) {
        return true;
      }
      if (visited.has(current)) {
        continue;
      }
      visited.add(current);
      (downstream.get(current) || []).forEach((next) => pending.push(next));
    }
    return false;
  }

  function buildConfiguredCircuit(inputs, definitions, activeTypes = null) {
    if (!Array.isArray(inputs) || inputs.length === 0 || !Array.isArray(definitions)) {
      throw new Error("A configuração do circuito está incompleta.");
    }

    const inventory = new Map();
    definitions.forEach(({ type, quantity }) => {
      if (!Object.prototype.hasOwnProperty.call(gates, type) ||
          !Number.isInteger(quantity) || quantity < 0) {
        throw new Error(`Configuração inválida para a porta ${type}.`);
      }
      inventory.set(type, (inventory.get(type) || 0) + quantity);
    });

    const selectedTypes = activeTypes === null
      ? [...definitions]
        .sort((a, b) => gateOrder.indexOf(a.type) - gateOrder.indexOf(b.type))
        .flatMap(({ type, quantity }) => Array(quantity).fill(type))
      : activeTypes;
    if (!Array.isArray(selectedTypes)) {
      throw new Error("A seleção de portas do circuito é inválida.");
    }

    const selectedCounts = new Map();
    const circuitGates = selectedTypes.map((type, index) => {
      const selectedCount = (selectedCounts.get(type) || 0) + 1;
      if (!inventory.has(type) || selectedCount > inventory.get(type)) {
        throw new Error(`Não há portas ${type} suficientes para esta seleção.`);
      }
      selectedCounts.set(type, selectedCount);
      return { id: `gate-${index + 1}`, type };
    });

    const connections = [];
    let connectionNumber = 0;
    let source = { kind: "input", id: inputs[0].id };
    circuitGates.forEach((gate, index) => {
      connections.push({
        id: `connection-${++connectionNumber}`,
        source,
        target: { kind: "gate", id: gate.id, pin: 0 },
      });
      if (gates[gate.type].inputs === 2) {
        const secondInput = inputs.length > 1 ? inputs[(index + 1) % inputs.length] : null;
        connections.push({
          id: `connection-${++connectionNumber}`,
          source: secondInput ? { kind: "input", id: secondInput.id } : source,
          target: { kind: "gate", id: gate.id, pin: 1 },
        });
      }
      source = { kind: "gate", id: gate.id };
    });
    connections.push({
      id: `connection-${++connectionNumber}`,
      source,
      target: { kind: "led", id: "led", pin: 0 },
    });

    return { gates: circuitGates, connections };
  }

  function evaluateCircuit(inputs, circuitGates, connections) {
    const inputValues = new Map(inputs.map((input) => [input.id, input.value]));
    const gateMap = new Map(circuitGates.map((gate) => [gate.id, gate]));
    const connectionMap = new Map();
    const errors = [];

    connections.forEach((connection) => {
      const targetKey = `${connection.target.kind}:${connection.target.id}:${connection.target.pin}`;
      if (connectionMap.has(targetKey)) {
        errors.push("Uma entrada está ligada a mais de uma saída.");
        return;
      }
      connectionMap.set(targetKey, connection);

      const sourceExists =
        (connection.source.kind === "input" && inputValues.has(connection.source.id)) ||
        (connection.source.kind === "gate" && gateMap.has(connection.source.id));
      const targetExists =
        (connection.target.kind === "gate" && gateMap.has(connection.target.id)) ||
        (connection.target.kind === "led" && connection.target.id === "led" && connection.target.pin === 0);
      const targetGate = connection.target.kind === "gate" ? gateMap.get(connection.target.id) : null;
      const targetPinValid =
        connection.target.kind === "led" ||
        (targetGate && Object.prototype.hasOwnProperty.call(gates, targetGate.type) &&
          Number.isInteger(connection.target.pin) &&
          connection.target.pin >= 0 && connection.target.pin < gates[targetGate.type].inputs);
      if (!sourceExists || !targetExists || !targetPinValid) {
        errors.push("Há uma conexão inválida no circuito.");
        return;
      }

    });

    circuitGates.forEach((gate) => {
      if (!Object.prototype.hasOwnProperty.call(gates, gate.type)) {
        errors.push("Há uma porta lógica desconhecida no circuito.");
        return;
      }
      for (let pin = 0; pin < gates[gate.type].inputs; pin += 1) {
        if (!connectionMap.has(`gate:${gate.id}:${pin}`)) {
          errors.push(`Conecte a entrada ${pin + 1} da porta ${gate.type}.`);
        }
      }
    });
    if (!connectionMap.has("led:led:0")) {
      errors.push("Conecte a saída final ao LED.");
    }
    if (errors.length) {
      return { valid: false, errors };
    }

    const state = new Map();
    const gateValues = Object.create(null);
    let cycleDetected = false;

    function outputOf(source) {
      if (source.kind === "input") {
        return inputValues.get(source.id);
      }
      if (state.get(source.id) === "visiting") {
        cycleDetected = true;
        return undefined;
      }
      if (state.get(source.id) === "done") {
        return gateValues[source.id];
      }

      const gate = gateMap.get(source.id);
      if (!gate || !Object.prototype.hasOwnProperty.call(gates, gate.type)) {
        return undefined;
      }
      state.set(source.id, "visiting");
      const values = [];
      for (let pin = 0; pin < gates[gate.type].inputs; pin += 1) {
        const connection = connectionMap.get(`gate:${gate.id}:${pin}`);
        const value = connection ? outputOf(connection.source) : undefined;
        if (value !== 0 && value !== 1) {
          return undefined;
        }
        values.push(value);
      }
      gateValues[gate.id] = evaluateGate(gate.type, values);
      state.set(source.id, "done");
      return gateValues[gate.id];
    }

    const ledConnection = connectionMap.get("led:led:0");
    const output = ledConnection ? outputOf(ledConnection.source) : undefined;
    circuitGates.forEach((gate) => outputOf({ kind: "gate", id: gate.id }));
    if (cycleDetected) {
      return { valid: false, errors: ["O circuito contém um ciclo inválido."] };
    }
    if (output !== 0 && output !== 1) {
      return { valid: false, errors: ["Não foi possível calcular todos os sinais do circuito."] };
    }
    return { valid: true, output, gateValues };
  }

  return Object.freeze({ gates, gateOrder, evaluateGate, evaluateCircuit, buildConfiguredCircuit, wouldCreateCycle });
});
