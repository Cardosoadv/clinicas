import { BASE_URL } from '../lib/api'
import './PacienteAvatar.css'
interface PacienteAvatarProps {
  avatar?: string | null
  pacienteId?: number
  className?: string
  alt?: string
}

export function PacienteAvatar({ avatar, pacienteId, className = 'paciente-avatar', alt = 'Avatar' }: PacienteAvatarProps) {
  const hasAvatarFile = Boolean(avatar && avatar.includes('/'))

  if (hasAvatarFile && pacienteId) {
    const filename = avatar!.split('/').pop()!
    const url = `${BASE_URL}/pacientes/${pacienteId}/avatar/${filename}`
    return (
      <div className="paciente-avatar-wrapper">
        <img
          className={`${className} ${className}-img`}
          src={url}
          alt={alt}
          crossOrigin="use-credentials"
        />
        <img
          className="paciente-avatar-zoom"
          src={url}
          alt={alt}
          crossOrigin="use-credentials"
        />
      </div>
    )
  }

  return (
    <span className={className}>
      {avatar || '🐾'}
    </span>
  )
}
