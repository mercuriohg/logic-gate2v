const assert = require("node:assert/strict");
const test = require("node:test");
const CircuitLogic = require("../src/public/assets/js/LogicaCircuito.js");

test("calcula as tabelas-verdade das sete portas", () => {
  const cases = {
    NOT: [[[0], 1], [[1], 0]],
    AND: [[[0, 0], 0], [[0, 1], 0], [[1, 0], 0], [[1, 1], 1]],
    OR: [[[0, 0], 0], [[0, 1], 1], [[1, 0], 1], [[1, 1], 1]],
    XOR: [[[0, 0], 0], [[0, 1], 1], [[1, 0], 1], [[1, 1], 0]],
    NAND: [[[0, 0], 1], [[0, 1], 1], [[1, 0], 1], [[1, 1], 0]],
    NOR: [[[0, 0], 1], [[0, 1], 0], [[1, 0], 0], [[1, 1], 0]],
    XNOR: [[[0, 0], 1], [[0, 1], 0], [[1, 0], 0], [[1, 1], 1]],
  };

  Object.entries(cases).forEach(([type, rows]) => {
    rows.forEach(([values, expected]) => {
      assert.equal(CircuitLogic.evaluateGate(type, values), expected, `${type}(${values.join(",")})`);
    });
  });
});

test("propaga sinais por portas encadeadas até o LED", () => {
  const result = CircuitLogic.evaluateCircuit(
    [{ id: "input-1", value: 1 }, { id: "input-2", value: 1 }],
    [{ id: "gate-1", type: "AND" }, { id: "gate-2", type: "NOT" }],
    [
      { source: { kind: "input", id: "input-1" }, target: { kind: "gate", id: "gate-1", pin: 0 } },
      { source: { kind: "input", id: "input-2" }, target: { kind: "gate", id: "gate-1", pin: 1 } },
      { source: { kind: "gate", id: "gate-1" }, target: { kind: "gate", id: "gate-2", pin: 0 } },
      { source: { kind: "gate", id: "gate-2" }, target: { kind: "led", id: "led", pin: 0 } },
    ]
  );

  assert.deepEqual(
    { valid: result.valid, output: result.output, and: result.gateValues["gate-1"], not: result.gateValues["gate-2"] },
    { valid: true, output: 0, and: 1, not: 0 }
  );
});

test("adiciona ao circuito somente as portas clicadas, respeitando o estoque do administrador", () => {
  const inputs = [{ id: "input-1", value: 1 }, { id: "input-2", value: 1 }];
  const inventory = [
    { type: "AND", quantity: 1 },
    { type: "NOT", quantity: 1 },
  ];
  const circuit = CircuitLogic.buildConfiguredCircuit(inputs, inventory, ["AND"]);
  const result = CircuitLogic.evaluateCircuit(inputs, circuit.gates, circuit.connections);

  assert.deepEqual(circuit.gates.map((gate) => gate.type), ["AND"]);
  assert.equal(circuit.connections.length, 3);
  assert.deepEqual(
    { valid: result.valid, output: result.output },
    { valid: true, output: 1 }
  );

  assert.throws(
    () => CircuitLogic.buildConfiguredCircuit(inputs, inventory, ["AND", "AND"]),
    /suficientes/
  );
  assert.throws(
    () => CircuitLogic.buildConfiguredCircuit(inputs, inventory, ["XOR"]),
    /suficientes/
  );
});

test("liga uma entrada diretamente ao LED quando a fase não tem portas", () => {
  const inputs = [{ id: "input-1", value: 1 }];
  const circuit = CircuitLogic.buildConfiguredCircuit(inputs, [], []);
  const result = CircuitLogic.evaluateCircuit(inputs, circuit.gates, circuit.connections);

  assert.equal(circuit.gates.length, 0);
  assert.deepEqual(
    { valid: result.valid, output: result.output },
    { valid: true, output: 1 }
  );
});

test("detecta portas e saída sem conexões válidas", () => {
  const result = CircuitLogic.evaluateCircuit(
    [{ id: "input-1", value: 0 }],
    [{ id: "gate-1", type: "NOT" }],
    []
  );

  assert.equal(result.valid, false);
  assert.match(result.errors.join(" "), /entrada 1 da porta NOT/);
  assert.match(result.errors.join(" "), /LED/);
});

test("detecta conexões que criam ciclos no circuito", () => {
  const currentConnections = [
    { source: { kind: "gate", id: "gate-a" }, target: { kind: "gate", id: "gate-b", pin: 0 } },
  ];
  assert.equal(CircuitLogic.wouldCreateCycle(currentConnections, "gate-b", "gate-a"), true);

  const result = CircuitLogic.evaluateCircuit(
    [{ id: "input-1", value: 1 }],
    [{ id: "gate-a", type: "AND" }],
    [
      { source: { kind: "gate", id: "gate-a" }, target: { kind: "gate", id: "gate-a", pin: 0 } },
      { source: { kind: "input", id: "input-1" }, target: { kind: "gate", id: "gate-a", pin: 1 } },
      { source: { kind: "gate", id: "gate-a" }, target: { kind: "led", id: "led", pin: 0 } },
    ]
  );
  assert.equal(result.valid, false);
  assert.match(result.errors[0], /ciclo/i);
});
