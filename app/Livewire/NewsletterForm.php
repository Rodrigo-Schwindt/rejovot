<?php

namespace App\Livewire;

use App\Models\NewsletterSubscriber;
use Livewire\Component;

class NewsletterForm extends Component
{
    public string $email = '';

    public function subscribe(): void
    {
        $data = $this->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'Ingresá tu email.',
            'email.email'    => 'Ingresá un email válido.',
        ]);

        $email = mb_strtolower(trim($data['email']));

        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing) {
            // Si se había dado de baja, volver a suscribirse lo reactiva.
            if (! $existing->active) {
                $existing->update(['active' => true]);
            }

            $this->reset('email');
            $this->dispatch('show-toast', message: 'Ya estabas suscripto. ¡Gracias!', type: 'success');

            return;
        }

        NewsletterSubscriber::create(['email' => $email, 'active' => true]);

        $this->reset('email');
        $this->dispatch('show-toast', message: 'Suscripción confirmada. ¡Gracias!', type: 'success');
    }

    public function render()
    {
        return view('livewire.newsletter-form');
    }
}
