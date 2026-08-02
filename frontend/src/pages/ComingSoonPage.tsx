import { Construction } from 'lucide-react'

interface ComingSoonPageProps {
  title: string
}

export function ComingSoonPage({ title }: ComingSoonPageProps) {
  return (
    <div className="coming-soon">
      <Construction size={32} strokeWidth={1.5} />
      <h2>{title}</h2>
      <p>Esta tela ainda não foi construída.</p>
    </div>
  )
}
