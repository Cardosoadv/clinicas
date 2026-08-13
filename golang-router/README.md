# golang-router

Router HTTP minimalista para Go, inspirado no arquivo de rotas do
CodeIgniter. Toda rota é declarada explicitamente em um mapa
`"MÉTODO /caminho" => handler` dentro de um arquivo `routes.go` — **sem
auto routing**: se a rota não estiver no mapa, ela simplesmente não
existe.

## Por quê

No CodeIgniter, `routes.php` funciona assim:

```php
$route['pacientes']       = 'Pacientes::index';
$route['pacientes/(:num)'] = 'Pacientes::show/$1';
```

A ideia aqui é a mesma — chave (rota) => valor (o que atende a rota) —
só que em Go o valor não é uma string resolvida por reflection em tempo
de execução, e sim a própria função handler, verificada em tempo de
compilação:

```go
var Routes = router.Routes{
    "GET /pacientes":     controllers.ListarPacientes,
    "GET /pacientes/:id": controllers.ObterPaciente,
    "POST /pacientes":    controllers.CriarPaciente,
}
```

## Instalação

```bash
go get github.com/cardosoadv/clinicas/golang-router
```

## Uso

```go
package main

import (
    "net/http"

    "github.com/cardosoadv/clinicas/golang-router/router"
)

func main() {
    rt := router.New()
    rt.Load(Routes) // Routes definido em routes.go, ver abaixo

    http.ListenAndServe(":8080", rt)
}
```

`routes.go` — só o mapeamento, nada de lógica:

```go
package main

var Routes = router.Routes{
    "GET /pacientes":        ListarPacientes,
    "GET /pacientes/:id":    ObterPaciente,
    "POST /pacientes":       CriarPaciente,
    "PUT /pacientes/:id":    AtualizarPaciente,
    "DELETE /pacientes/:id": RemoverPaciente,
}
```

Um exemplo completo e executável está em [`example/`](./example):

```bash
cd golang-router
go run ./example
# em outro terminal:
curl http://localhost:8080/pacientes
```

## Sintaxe das rotas

| Padrão                  | Descrição                                             |
|--------------------------|--------------------------------------------------------|
| `/pacientes`             | Segmento literal                                        |
| `/pacientes/:id`         | `:nome` captura um parâmetro nomeado                     |
| `/arquivos/*`            | `*` no final captura o restante do caminho               |

Parâmetros são lidos dentro do handler:

```go
func ObterPaciente(w http.ResponseWriter, r *http.Request) {
    id := router.Param(r, "id")
    // ...
}
```

Quando mais de uma rota bate com o caminho, a **primeira registrada
vence** — por isso declare rotas literais mais específicas (ex.:
`/pacientes/painel`) antes de rotas com parâmetro (`/pacientes/:id`).

## API

- `router.New() *Router` — cria um router vazio.
- `rt.Load(router.Routes{...})` — carrega o mapa `"METODO /caminho" => handler`.
- `rt.Get/Post/Put/Patch/Delete/Options(pattern, handler)` — registra uma rota isolada.
- `rt.Group(prefix, func(g *Router) { ... })` — agrupa rotas sob um prefixo comum (ex.: `/api/v1`).
- `router.Param(r, "nome")` — lê um parâmetro nomeado da requisição atual.
- `router.Wildcard(r)` — lê o que foi capturado por um `*` no fim da rota.
- `rt.NotFoundHandler` / `rt.MethodNotAllowedHandler` — customizáveis (padrão: 404 e 405).

`Router` implementa `http.Handler`, então funciona com o `net/http`
padrão ou como sub-router dentro de outro mux.

## Testes

```bash
cd golang-router
go test ./...
```
