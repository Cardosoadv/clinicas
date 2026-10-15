import assert from 'node:assert/strict'
import { test } from 'node:test'
import { dividirEmCentavos, gerarParcelas, validarParcelamento, vencimentoDaParcela } from './parcelas.ts'

test('600,00 em 6x gera seis parcelas de 100,00 com vencimentos mensais', () => {
  const parcelas = gerarParcelas('600', '6', '2026-09-10', 'mensal')
  assert.deepEqual(
    parcelas.map((p) => p.valor),
    ['100.00', '100.00', '100.00', '100.00', '100.00', '100.00'],
  )
  assert.deepEqual(
    parcelas.map((p) => p.vencimento),
    ['2026-09-10', '2026-10-10', '2026-11-10', '2026-12-10', '2027-01-10', '2027-02-10'],
  )
})

test('100,00 em 3x: a última parcela absorve a diferença de centavos', () => {
  const parcelas = gerarParcelas('100', '3', '2026-09-10', 'mensal')
  assert.deepEqual(
    parcelas.map((p) => p.valor),
    ['33.33', '33.33', '33.34'],
  )
  assert.equal(dividirEmCentavos(10000, 3).reduce((a, b) => a + b, 0), 10000)
})

test('mensal não pula mês no fim do mês (31/01 → 28/02 → 31/03 → 30/04)', () => {
  const datas = [0, 1, 2, 3].map((i) => vencimentoDaParcela('2026-01-31', 'mensal', i))
  assert.deepEqual(datas, ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'])
})

test('mensal em ano bissexto usa 29/02 e vira o ano corretamente', () => {
  assert.equal(vencimentoDaParcela('2027-12-31', 'mensal', 2), '2028-02-29')
  assert.equal(vencimentoDaParcela('2026-11-15', 'mensal', 3), '2027-02-15')
})

test('semanal e quinzenal somam dias a partir da data base', () => {
  assert.equal(vencimentoDaParcela('2026-09-01', 'semanal', 2), '2026-09-15')
  assert.equal(vencimentoDaParcela('2026-09-01', 'quinzenal', 2), '2026-09-29')
})

test('dados incompletos ou inválidos não geram prévia', () => {
  assert.deepEqual(gerarParcelas('', '3', '2026-09-10', 'mensal'), [])
  assert.deepEqual(gerarParcelas('100', '1', '2026-09-10', 'mensal'), [])
  assert.deepEqual(gerarParcelas('100', '61', '2026-09-10', 'mensal'), [])
  assert.deepEqual(gerarParcelas('100', '2.5', '2026-09-10', 'mensal'), [])
  assert.deepEqual(gerarParcelas('100', '3', '2026-02-31', 'mensal'), [])
  assert.deepEqual(gerarParcelas('0.02', '3', '2026-09-10', 'mensal'), [])
})

test('validarParcelamento aceita parcelas que fecham o total', () => {
  const parcelas = gerarParcelas('100', '3', '2026-09-10', 'mensal')
  assert.equal(validarParcelamento('100', parcelas), null)
})

test('validarParcelamento aponta a diferença quando a soma não fecha', () => {
  const parcelas = gerarParcelas('100', '3', '2026-09-10', 'mensal')
  parcelas[0] = { ...parcelas[0], valor: '40.00' }
  const erro = validarParcelamento('100', parcelas)
  assert.match(erro ?? '', /acima do valor total/)
  assert.match(erro ?? '', /6,67/)
})

test('validarParcelamento recusa parcela zerada e vencimento vazio', () => {
  const parcelas = gerarParcelas('100', '2', '2026-09-10', 'mensal')
  assert.match(validarParcelamento('100', [{ ...parcelas[0], valor: '0' }, parcelas[1]]) ?? '', /maior que zero/)
  assert.match(validarParcelamento('100', [{ ...parcelas[0], vencimento: '' }, parcelas[1]]) ?? '', /vencimento/)
  assert.match(validarParcelamento('100', []) ?? '', /Informe/)
})
