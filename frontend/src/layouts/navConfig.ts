import {
  BarChart3,
  BookOpen,
  Boxes,
  CalendarDays,
  ClipboardList,
  LayoutDashboard,
  PackageCheck,
  PawPrint,
  Receipt,
  Settings,
  Stethoscope,
  Store,
  UserCog,
  Users,
  Wallet,
  type LucideIcon,
} from 'lucide-react'

export interface NavItem {
  label: string
  path: string
  icon: LucideIcon
  /** Rotas cuja tela ainda não foi construída — o link existe, mas leva a um placeholder. */
  comingSoon?: boolean
}

export interface NavSection {
  title: string
  items: NavItem[]
}

export const navSections: NavSection[] = [
  {
    title: 'Principal',
    items: [
      { label: 'Dashboard', path: '/', icon: LayoutDashboard },
      { label: 'Agenda', path: '/agenda', icon: CalendarDays },
      { label: 'Pacientes', path: '/pacientes', icon: PawPrint },
      { label: 'Clientes', path: '/clientes', icon: Users },
      { label: 'Prontuários', path: '/prontuarios', icon: ClipboardList },
    ],
  },
  {
    title: 'Operação',
    items: [
      { label: 'Serviços', path: '/servicos', icon: Stethoscope },
      { label: 'Pacotes', path: '/pacotes', icon: PackageCheck },
      { label: 'Estoque', path: '/estoque', icon: Boxes },
      { label: 'Equipe', path: '/equipe', icon: UserCog },
      { label: 'Lojas', path: '/lojas', icon: Store },
    ],
  },
  {
    title: 'Financeiro',
    items: [
      { label: 'Faturamento', path: '/faturamento', icon: Wallet },
      { label: 'Extrato', path: '/relatorios/extrato', icon: Receipt },
      { label: 'DRE', path: '/relatorios/dre', icon: BarChart3 },
      { label: 'Livro Caixa', path: '/relatorios/livro-caixa', icon: BookOpen },
    ],
  },
  {
    title: 'Sistema',
    items: [{ label: 'Configurações', path: '/configuracoes', icon: Settings }],
  },
]

export const allNavItems: NavItem[] = navSections.flatMap((section) => section.items)
