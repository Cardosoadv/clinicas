<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * Preflight CORS (OPTIONS): o CodeIgniter só executa os filtros globais para
 * requisições que batem em alguma rota — sem isto, qualquer preflight em uma
 * rota que só aceita POST/PUT/DELETE cai em 404 antes do filtro 'cors' rodar
 * e o navegador bloqueia a requisição real por falta do header
 * Access-Control-Allow-Origin. Esta rota coringa garante que todo OPTIONS
 * sempre bate em algo, deixando o filtro 'cors' (global, before) anexar os
 * headers corretos na resposta.
 */
$routes->options('(:any)', static fn () => service('response')->setStatusCode(204));

// Rotas de autenticação do Shield (login, registro, tokens, etc) — usadas por
// eventuais fluxos web/CLI; o frontend React usa as rotas api/v1/auth/* abaixo.
service('auth')->routes($routes);

/*
 * Autenticação para o frontend React: sempre responde em JSON (o
 * LoginController padrão do Shield responde com redirects HTML).
 */
$routes->group('api/v1/auth', ['namespace' => 'App\Controllers\V1'], static function (RouteCollection $routes) {
    $routes->post('login', 'Auth::login');
    $routes->post('logout', 'Auth::logout');
    $routes->get('me', 'Auth::me');
});

/*
 * Endpoints públicos, acessados via link/QR code (protegidos por hash na
 * própria URL, não por sessão) — ficam fora do filtro 'apiauth'.
 */
$routes->group('api/v1/publico', ['namespace' => 'App\Controllers\V1'], static function (RouteCollection $routes) {
    $routes->get('recibos/(:num)/(:any)', 'Publico::recibo/$1/$2');
});

/*
 * API v1 — todas as rotas abaixo exigem sessão autenticada (filtro 'apiauth',
 * ver app/Filters/ApiAuth.php) e respondem sempre em JSON, para consumo
 * pelo frontend React.
 */
$routes->group('api/v1', ['namespace' => 'App\Controllers\V1', 'filter' => 'apiauth'], static function (RouteCollection $routes) {
    $routes->get('dashboard', 'Home::dashboard');

    // Pacientes (pets)
    $routes->get('pacientes', 'Pets::getAll');
    $routes->get('pacientes/painel', 'Pets::getPainel');
    $routes->get('pacientes/busca', 'Pets::search');
    $routes->post('pacientes/mix-create', 'Pets::mixCreate');
    $routes->post('pacientes/import', 'PetsImport::upload');
    $routes->get('pacientes/(:num)/pacotes-disponiveis', 'Pacotes::getDisponiveis/$1');
    $routes->get('pacientes/(:num)/avatar/(:any)', 'Pets::viewAvatar/$1/$2');
    $routes->get('pacientes/(:num)', 'Pets::getById/$1');
    $routes->post('pacientes', 'Pets::create');
    $routes->put('pacientes/(:num)', 'Pets::update/$1');
    $routes->delete('pacientes/(:num)', 'Pets::delete/$1');

    // Agendamentos
    $routes->get('agendamentos', 'Agenda::index');
    $routes->get('agendamentos/dia', 'Agenda::getDayData');
    $routes->get('agendamentos/proximos', 'Agenda::getUpcoming');
    $routes->get('agendamentos/buscar-pacientes', 'Agenda::searchPets');
    $routes->get('agendamentos/dias-do-mes', 'Agenda::getMonthDays');
    $routes->post('agendamentos', 'Agenda::create');
    $routes->put('agendamentos/(:num)', 'Agenda::update/$1');
    $routes->patch('agendamentos/(:num)/status', 'Agenda::updateStatus/$1');
    $routes->post('agendamentos/(:num)/faturar', 'Agenda::faturar/$1');

    // Comunicação (links de WhatsApp)
    $routes->get('comunicacao/agendamentos/(:num)/whatsapp', 'Comunicacao::getWhatsappAgendamento/$1');
    $routes->get('comunicacao/cobrancas/(:num)/whatsapp', 'Comunicacao::getWhatsappCobranca/$1');
    $routes->get('comunicacao/pos-consulta/(:num)/whatsapp', 'Comunicacao::getWhatsappPosConsulta/$1');
    $routes->get('comunicacao/vacinas/(:num)/whatsapp', 'Comunicacao::getWhatsappVacina/$1');
    $routes->get('comunicacao/pacientes/(:num)/aniversario/whatsapp', 'Comunicacao::getWhatsappAniversario/$1');

    // Configurações
    $routes->get('configuracoes/templates', 'Configuracoes::getTemplates');
    $routes->put('configuracoes/templates', 'Configuracoes::updateTemplate');

    // Clientes
    $routes->get('clientes', 'Clientes::getAll');
    $routes->get('clientes/painel', 'Clientes::getPainel');
    $routes->get('clientes/duplicados', 'Clientes::getDuplicados');
    $routes->post('clientes/merge', 'Clientes::merge');
    $routes->post('clientes/import', 'ClientesImport::upload');
    $routes->get('clientes/(:num)', 'Clientes::getById/$1');
    $routes->post('clientes', 'Clientes::create');
    $routes->put('clientes/(:num)', 'Clientes::update/$1');
    $routes->delete('clientes/(:num)', 'Clientes::delete/$1');
    $routes->get('clientes/(:num)/pacientes', 'Clientes::getPacientes/$1');
    $routes->get('clientes/(:num)/notas', 'Clientes::getNotas/$1');
    $routes->post('clientes/(:num)/notas', 'Clientes::createNota/$1');
    $routes->delete('clientes/(:num)/notas/(:num)', 'Clientes::deleteNota/$1/$2');
    $routes->get('clientes/(:num)/comunicacoes', 'Clientes::getComunicacoes/$1');
    $routes->post('clientes/(:num)/comunicacoes', 'Clientes::createComunicacao/$1');

    // Equipe
    $routes->get('equipe', 'Equipe::getAll');
    $routes->get('equipe/nao-vinculados', 'Equipe::getUnlinkedUsers');
    $routes->get('equipe/(:num)', 'Equipe::getById/$1');
    $routes->post('equipe', 'Equipe::create');
    $routes->put('equipe/(:num)', 'Equipe::update/$1');
    $routes->delete('equipe/(:num)', 'Equipe::delete/$1');

    // Estoque
    $routes->get('estoque/produtos', 'Estoque::getAll');
    $routes->get('estoque/alertas', 'Estoque::getAlertas');
    $routes->get('estoque/produtos/(:num)', 'Estoque::getById/$1');
    $routes->post('estoque/produtos', 'Estoque::create');
    $routes->put('estoque/produtos/(:num)', 'Estoque::update/$1');
    $routes->delete('estoque/produtos/(:num)', 'Estoque::delete/$1');
    $routes->post('estoque/movimentacoes/entrada', 'Estoque::registrarEntrada');
    $routes->post('estoque/movimentacoes/saida', 'Estoque::registrarSaida');

    // Faturamento
    $routes->get('faturamento/dashboard', 'Faturamento::dashboard');
    $routes->get('faturamento/lancamentos', 'Faturamento::lancamentos');
    $routes->post('faturamento/cobrancas', 'Faturamento::createCobranca');
    $routes->put('faturamento/cobrancas/(:num)', 'Faturamento::updateCobranca/$1');
    $routes->post('faturamento/despesas', 'Faturamento::createDespesa');
    $routes->post('faturamento/despesas/import', 'DespesasImport::upload');
    $routes->put('faturamento/despesas/(:num)', 'Faturamento::updateDespesa/$1');
    $routes->post('faturamento/despesas/(:num)/comprovante', 'Faturamento::uploadComprovantesDespesa/$1');
    $routes->get('faturamento/despesas/(:num)/comprovante/(:any)', 'Faturamento::viewComprovante/$1/$2');
    $routes->get('faturamento/notas', 'Faturamento::notas');
    $routes->post('faturamento/notas', 'Faturamento::createNota');
    $routes->post('receitas/import', 'ReceitasImport::upload');

    // Lojas
    $routes->get('lojas', 'Lojas::getAll');
    $routes->get('lojas/(:num)/logo/(:any)', 'Lojas::viewLogo/$1/$2');
    $routes->get('lojas/(:num)', 'Lojas::getById/$1');
    $routes->post('lojas', 'Lojas::create');
    // POST (não PUT): PHP só popula $_POST/$_FILES em multipart/form-data para POST.
    $routes->post('lojas/(:num)', 'Lojas::update/$1');
    $routes->delete('lojas/(:num)', 'Lojas::delete/$1');

    // Pacotes
    $routes->get('pacotes', 'Pacotes::getAll');
    $routes->get('pacotes/(:num)', 'Pacotes::show/$1');
    $routes->post('pacotes', 'Pacotes::create');

    // Perfil
    $routes->put('perfil/senha', 'Perfil::alterarSenha');

    // Prontuários
    $routes->get('prontuarios', 'Prontuarios::listPacientes');
    $routes->get('prontuarios/prescricoes/(:num)', 'Prontuarios::getPrescricao/$1');
    $routes->put('prontuarios/evolucoes/(:num)', 'Prontuarios::editEvolucao/$1');
    $routes->delete('prontuarios/evolucoes/(:num)', 'Prontuarios::deleteEvolucao/$1');
    $routes->delete('prontuarios/vacinas/(:num)', 'Prontuarios::deleteVacina/$1');
    $routes->delete('prontuarios/pesos/(:num)', 'Prontuarios::deletePeso/$1');
    $routes->get('prontuarios/pacientes/(:num)/imagens/(:any)', 'Prontuarios::viewImagem/$1/$2');
    // POST (não PUT): PHP só popula $_POST/$_FILES em multipart/form-data para POST.
    $routes->post('prontuarios/pacientes/(:num)/anamnese', 'Prontuarios::saveAnamnese/$1');
    $routes->put('prontuarios/pacientes/(:num)/odontograma', 'Prontuarios::saveOdontograma/$1');
    $routes->post('prontuarios/pacientes/(:num)/evolucoes', 'Prontuarios::addEvolucao/$1');
    $routes->post('prontuarios/pacientes/(:num)/vacinas', 'Prontuarios::addVacina/$1');
    $routes->post('prontuarios/pacientes/(:num)/pesos', 'Prontuarios::addPeso/$1');
    $routes->post('prontuarios/pacientes/(:num)/imagens', 'Prontuarios::addImagem/$1');
    $routes->post('prontuarios/pacientes/(:num)/prescricoes', 'Prontuarios::addPrescricao/$1');
    $routes->get('prontuarios/pacientes/(:num)', 'Prontuarios::show/$1');

    // Relatórios
    $routes->get('relatorios/extrato', 'Relatorios::extrato');
    $routes->get('relatorios/dre', 'Relatorios::dre');
    $routes->get('relatorios/livro-caixa', 'Relatorios::livroCaixa');

    // Serviços
    $routes->get('servicos', 'Servicos::getAll');
    $routes->get('servicos/(:num)', 'Servicos::getById/$1');
    $routes->post('servicos', 'Servicos::create');
    $routes->put('servicos/(:num)', 'Servicos::update/$1');
    $routes->delete('servicos/(:num)', 'Servicos::delete/$1');
    $routes->put('servicos/(:num)/produtos', 'Servicos::syncProducts/$1');
});
