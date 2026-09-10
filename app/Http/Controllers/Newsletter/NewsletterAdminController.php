<?php

namespace App\Http\Controllers\Newsletter;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterAdminController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $subscribers = NewsletterSubscriber::query()
            ->when($search !== '', fn ($query) => $query->where('email', 'like', '%' . $search . '%'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('livewire.newsletter.index', [
            'subscribers'    => $subscribers,
            'search'         => $search,
            'totalActivos'   => NewsletterSubscriber::active()->count(),
            'totalInactivos' => NewsletterSubscriber::where('active', false)->count(),
        ]);
    }

    /** Habilita o deshabilita el envío a un suscriptor, sin borrarlo. */
    public function toggle(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->update(['active' => ! $subscriber->active]);

        return back()->with('success', $subscriber->active
            ? "Se habilitaron los envíos a {$subscriber->email}."
            : "Se deshabilitaron los envíos a {$subscriber->email}.");
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $email = $subscriber->email;
        $subscriber->delete();

        return back()->with('success', "Se eliminó {$email} de la lista.");
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asunto' => ['required', 'string', 'max:200'],
            'cuerpo' => ['required', 'string', 'max:20000'],
        ], [
            'asunto.required' => 'Escribí un asunto.',
            'cuerpo.required' => 'Escribí el mensaje.',
        ]);

        $subscribers = NewsletterSubscriber::active()->get();

        if ($subscribers->isEmpty()) {
            return back()->with('error', 'No hay suscriptores habilitados para enviar.');
        }

        $enviados = 0;
        $fallidos = [];

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email)->send(new NewsletterCampaignMail($data['asunto'], $data['cuerpo']));
                $subscriber->update(['last_sent_at' => now()]);
                $enviados++;
            } catch (\Throwable $e) {
                Log::error('Error al enviar newsletter', ['email' => $subscriber->email, 'error' => $e->getMessage()]);
                $fallidos[] = $subscriber->email;
            }
        }

        if ($fallidos) {
            return back()->with('error', "Se enviaron {$enviados} correos. Fallaron " . count($fallidos) . ': ' . implode(', ', array_slice($fallidos, 0, 5)) . '…');
        }

        return back()->with('success', "Newsletter enviado a {$enviados} suscriptores.");
    }
}
