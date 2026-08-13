package router

import "net/http"

type ctxKey struct{}

var paramsCtxKey = ctxKey{}

// Param retorna o valor do parâmetro nomeado capturado pela rota
// (ex.: ":id" em "/pacientes/:id"). Retorna string vazia se o
// parâmetro não existir.
func Param(r *http.Request, name string) string {
	params, _ := r.Context().Value(paramsCtxKey).(map[string]string)
	return params[name]
}

// Wildcard retorna o restante do caminho capturado por um segmento "*"
// no fim do padrão da rota (ex.: "/arquivos/*").
func Wildcard(r *http.Request) string {
	return Param(r, "*")
}
