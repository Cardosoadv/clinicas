package main

import "github.com/cardosoadv/clinicas/golang-router/router"

// routes.go contém apenas o mapeamento rota => handler, sem lógica.
//
// Cada chave segue o formato "METODO /caminho", no espírito do
// $route['padrao'] = 'Controller::metodo' do CodeIgniter — mas aqui o
// valor é a própria função Go que atende a rota, então não há
// resolução automática de controller a partir da URL (sem auto routing).
var Routes = router.Routes{
	"GET /pacientes":        ListarPacientes,
	"GET /pacientes/:id":    ObterPaciente,
	"POST /pacientes":       CriarPaciente,
	"PUT /pacientes/:id":    AtualizarPaciente,
	"DELETE /pacientes/:id": RemoverPaciente,
}
