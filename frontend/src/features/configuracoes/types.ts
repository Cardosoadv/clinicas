export interface TemplateConfig {
  id: number
  meta_key: string
  meta_value: string | null
  description: string | null
}

export interface TemplateDefinition {
  key: string
  titulo: string
  descricao: string
  placeholders: string[]
  padrao: string
}

export const templateDefinitions: TemplateDefinition[] = [
  {
    key: 'whatsapp_template_agendamento',
    titulo: 'Lembrete de Agendamento',
    descricao: 'Enviado ao confirmar/lembrar um agendamento com o tutor.',
    placeholders: ['{tutor}', '{pet}', '{servico}', '{data}', '{hora}', '{clinica}'],
    padrao: 'Olá {tutor}, passando para lembrar do agendamento de {pet} para {servico} em {data} às {hora}. Podemos confirmar? 🐾',
  },
  {
    key: 'whatsapp_template_cobranca',
    titulo: 'Cobrança Pendente',
    descricao: 'Enviado para lembrar o tutor de uma cobrança em aberto.',
    placeholders: ['{tutor}', '{pet}', '{valor}', '{data}', '{clinica}'],
    padrao: 'Olá {tutor}, informamos que consta uma pendência de {pet} no valor de R$ {valor} (vencimento em {data}). Favor regularizar. Obrigado! 💰',
  },
  {
    key: 'whatsapp_template_pos_consulta',
    titulo: 'Pós-Consulta',
    descricao: 'Enviado como acompanhamento após um procedimento ou consulta.',
    placeholders: ['{tutor}', '{pet}', '{servico}', '{clinica}'],
    padrao: 'Olá {tutor}, como está o {pet} após o procedimento de {servico}? Esperamos que esteja se recuperando bem! Qualquer dúvida, estamos à disposição. 🐾',
  },
  {
    key: 'whatsapp_template_aniversario',
    titulo: 'Aniversário do Paciente',
    descricao: 'Enviado no aniversário do paciente para parabenizar o tutor.',
    placeholders: ['{tutor}', '{pet}', '{clinica}'],
    padrao: 'Olá {tutor}, hoje é um dia especial! Queremos desejar um feliz aniversário para o(a) {pet}! Muita saúde e petiscos! 🎂🎉 🐾',
  },
  {
    key: 'whatsapp_template_vacina',
    titulo: 'Lembrete de Vacina',
    descricao: 'Enviado para lembrar o tutor do reforço de uma vacina.',
    placeholders: ['{tutor}', '{pet}', '{vacina}', '{data}', '{clinica}'],
    padrao: 'Olá {tutor}, passando para lembrar que o reforço da vacina {vacina} de {pet} está agendado para {data}. Vamos garantir a proteção do seu amiguinho? 💉🐾',
  },
]
