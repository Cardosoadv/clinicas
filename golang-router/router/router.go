// Package router é um multiplexador HTTP minimalista inspirado no
// arquivo de rotas do CodeIgniter: cada rota é declarada explicitamente
// como "argumento (rota) => método (handler)". Não existe auto routing —
// nenhuma URL é resolvida automaticamente para um controller/método por
// convenção; se a rota não estiver declarada, ela não existe.
package router

import (
	"context"
	"fmt"
	"net/http"
	"strings"
)

// Routes é o formato usado no arquivo routes.go da aplicação: cada chave
// é "METODO /caminho" e o valor é o handler que atende essa rota.
//
//	var Routes = router.Routes{
//		"GET /pacientes":        controllers.ListarPacientes,
//		"GET /pacientes/:id":    controllers.ObterPaciente,
//		"POST /pacientes":       controllers.CriarPaciente,
//		"PUT /pacientes/:id":    controllers.AtualizarPaciente,
//		"DELETE /pacientes/:id": controllers.RemoverPaciente,
//	}
type Routes map[string]http.HandlerFunc

type route struct {
	method   string
	pattern  string
	segments []segment
	handler  http.HandlerFunc
}

// Router acumula rotas declaradas explicitamente e as despacha via
// ServeHTTP, podendo ser usado diretamente com net/http.
type Router struct {
	prefix                  string
	routes                  []route
	NotFoundHandler         http.HandlerFunc
	MethodNotAllowedHandler http.HandlerFunc
}

// New cria um Router pronto para uso.
func New() *Router {
	return &Router{
		NotFoundHandler: http.NotFound,
		MethodNotAllowedHandler: func(w http.ResponseWriter, r *http.Request) {
			http.Error(w, "405 method not allowed", http.StatusMethodNotAllowed)
		},
	}
}

// Handle registra uma rota explicitamente: método HTTP + padrão de
// caminho + handler. Segmentos iniciados por ":" capturam um parâmetro
// nomeado (ex.: ":id"); um segmento "*" no final captura o restante do
// caminho.
func (rt *Router) Handle(method, pattern string, handler http.HandlerFunc) {
	full := joinPath(rt.prefix, pattern)
	rt.routes = append(rt.routes, route{
		method:   strings.ToUpper(method),
		pattern:  full,
		segments: parsePattern(full),
		handler:  handler,
	})
}

func (rt *Router) Get(pattern string, h http.HandlerFunc) { rt.Handle(http.MethodGet, pattern, h) }
func (rt *Router) Post(pattern string, h http.HandlerFunc) {
	rt.Handle(http.MethodPost, pattern, h)
}
func (rt *Router) Put(pattern string, h http.HandlerFunc) { rt.Handle(http.MethodPut, pattern, h) }
func (rt *Router) Patch(pattern string, h http.HandlerFunc) {
	rt.Handle(http.MethodPatch, pattern, h)
}
func (rt *Router) Delete(pattern string, h http.HandlerFunc) {
	rt.Handle(http.MethodDelete, pattern, h)
}
func (rt *Router) Options(pattern string, h http.HandlerFunc) {
	rt.Handle(http.MethodOptions, pattern, h)
}

// Load registra em massa as rotas declaradas no formato do routes.go:
// "METODO /caminho" => handler. É a forma recomendada de carregar o
// arquivo de rotas da aplicação.
func (rt *Router) Load(routes Routes) {
	for key, handler := range routes {
		method, pattern, err := splitRouteKey(key)
		if err != nil {
			panic(fmt.Sprintf("router: %v", err))
		}
		rt.Handle(method, pattern, handler)
	}
}

// Group cria um sub-conjunto de rotas com um prefixo comum, útil para
// versionar a API (ex.: "/api/v1") sem repetir o prefixo em cada rota do
// routes.go.
func (rt *Router) Group(prefix string, build func(g *Router)) {
	g := &Router{prefix: joinPath(rt.prefix, prefix)}
	build(g)
	rt.routes = append(rt.routes, g.routes...)
}

// ServeHTTP implementa http.Handler, despachando a requisição para a
// primeira rota cujo caminho e método batam com a requisição.
func (rt *Router) ServeHTTP(w http.ResponseWriter, r *http.Request) {
	reqSegs := splitPath(r.URL.Path)

	methodMismatch := false
	for _, rte := range rt.routes {
		params, ok := matchSegments(rte.segments, reqSegs)
		if !ok {
			continue
		}
		if rte.method != r.Method {
			methodMismatch = true
			continue
		}
		if len(params) > 0 {
			r = r.WithContext(context.WithValue(r.Context(), paramsCtxKey, params))
		}
		rte.handler(w, r)
		return
	}

	if methodMismatch {
		rt.MethodNotAllowedHandler(w, r)
		return
	}
	rt.NotFoundHandler(w, r)
}

func splitRouteKey(key string) (method, pattern string, err error) {
	parts := strings.Fields(key)
	if len(parts) != 2 {
		return "", "", fmt.Errorf("rota inválida %q — use o formato \"METODO /caminho\"", key)
	}
	if !strings.HasPrefix(parts[1], "/") {
		return "", "", fmt.Errorf("rota inválida %q — o caminho deve começar com \"/\"", key)
	}
	return parts[0], parts[1], nil
}

func joinPath(prefix, pattern string) string {
	p := strings.TrimSuffix(prefix, "/") + "/" + strings.TrimPrefix(pattern, "/")
	if !strings.HasPrefix(p, "/") {
		p = "/" + p
	}
	return p
}
