import assert from 'node:assert/strict'
import { test } from 'node:test'
import { deslocarMes, hojeISO, parcelasDoEscopo, pluralParcelas, resumoEscopo, rotuloParcela, statusExibido } from './despesas.ts'

const grupo = [
  { id: 1, des_parcela_num: 1, data_vencimento: '2026-09-10', status: 'Pago' },
  { id: 2, des_parcela_num: 2, data_vencimento: '2026-10-10', status: 'Pendente' },
  { id: 3, des_parcela_num: 3, data_vencimento: '2026-11-10', status: 'Pendente' },
  { id: 4, des_parcela_num: 4, data_vencimento: '2026-12-10', status: 'Cancelado' },
]

test('statusExibido marca Pendente vencida como Atrasado, sem mexer nos demais', () => {
  assert.equal(statusExibido('Pendente', '2026-10-14', '2026-10-15'), 'Atrasado')
  assert.equal(statusExibido('Pendente', '2026-10-15', '2026-10-15'), 'Pendente')
  assert.equal(statusExibido('Pago', '2020-01-01', '2026-10-15'), 'Pago')
  assert.equal(statusExibido('Cancelado', '2020-01-01', '2026-10-15'), 'Cancelado')
})

test('rotuloParcela só existe para despesas parceladas', () => {
  assert.equal(rotuloParcela({ des_parcela_num: 2, des_parcela_total: 6 }), 'Parcela 2/6')
  assert.equal(rotuloParcela({ des_parcela_num: null, des_parcela_total: null }), null)
})

test('deslocarMes atravessa o ano nos dois sentidos', () => {
  assert.equal(deslocarMes('2026-12', 1), '2027-01')
  assert.equal(deslocarMes('2026-01', -1), '2025-12')
  assert.equal(deslocarMes('2026-10', 0), '2026-10')
  assert.equal(deslocarMes('2026-03', -15), '2024-12')
})

test('hojeISO usa a data local', () => {
  assert.equal(hojeISO(new Date(2026, 9, 5, 23, 59)), '2026-10-05')
})

test('parcelasDoEscopo: esta e seguintes a partir da parcela 3', () => {
  assert.deepEqual(parcelasDoEscopo(grupo, grupo[2], 'esta_e_seguintes').map((d) => d.id), [3, 4])
  assert.deepEqual(parcelasDoEscopo(grupo, grupo[2], 'apenas_esta').map((d) => d.id), [3])
  assert.equal(parcelasDoEscopo(grupo, grupo[2], 'todas').length, 4)
})

test('parcelasDoEscopo usa a data em recorrências sem número de parcela', () => {
  const fixa = [
    { id: 10, des_parcela_num: null, data_vencimento: '2026-09-05', status: 'Pendente' },
    { id: 11, des_parcela_num: null, data_vencimento: '2026-10-05', status: 'Pendente' },
    { id: 12, des_parcela_num: null, data_vencimento: '2026-11-05', status: 'Pendente' },
  ]
  assert.deepEqual(parcelasDoEscopo(fixa, fixa[1], 'esta_e_seguintes').map((d) => d.id), [11, 12])
})

test('despesa sem grupo carregado afeta só ela', () => {
  assert.deepEqual(parcelasDoEscopo([], grupo[1], 'todas'), [grupo[1]])
})

test('resumoEscopo separa pendentes (canceláveis) e pagas (preservadas)', () => {
  assert.deepEqual(resumoEscopo(grupo, grupo[1], 'todas'), { afetadas: 4, pendentes: 2, pagas: 1 })
  assert.deepEqual(resumoEscopo(grupo, grupo[2], 'esta_e_seguintes'), { afetadas: 2, pendentes: 1, pagas: 0 })
})

test('pluralParcelas', () => {
  assert.equal(pluralParcelas(1), '1 parcela')
  assert.equal(pluralParcelas(3), '3 parcelas')
})
