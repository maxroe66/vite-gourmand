import { fireEvent } from '@testing-library/dom'
import { describe, it, expect } from 'vitest'
import fs from 'fs'
import path from 'path'

describe('reset password form (client validation)', () => {
  it('affiche une erreur si les mots de passe ne correspondent pas', async () => {
    // Charger le HTML de la page (chemin relatif depuis frontend/)
    const htmlPath = path.resolve(process.cwd(), 'frontend/pages/motdepasse-oublie.html')
    const html = fs.readFileSync(htmlPath, 'utf-8')
    document.body.innerHTML = html

    // Fournir un token dans l'URL pour permettre au script de fonctionner
    Object.defineProperty(window, 'location', {
      value: new URL('http://localhost/reset-password?token=abc'),
      writable: true
    })

    // Importer le script (il attend le DOMContentLoaded)
    await import('../../js/pages/motdepasse-oublie.js')
    // Simuler DOMContentLoaded pour initialiser les listeners
    document.dispatchEvent(new Event('DOMContentLoaded'))

    const newPwd = document.getElementById('newPassword')
    const conf = document.getElementById('confirmPassword')
    const form = document.getElementById('forgotPasswordForm')

    // Remplir champs et soumettre
    newPwd.value = 'password123'
    conf.value = 'different123'
    fireEvent.submit(form)

    // On attend que le message d'erreur apparaisse
    const banner = document.querySelector('.general-error')
    expect(banner).not.toBeNull()
    expect(banner.textContent).toMatch(/ne correspondent pas/i)
  })
})
