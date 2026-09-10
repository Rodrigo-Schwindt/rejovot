<?php

namespace App\Http\Controllers\Contacto;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactInfoItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactManager extends Controller
{
    public function index()
    {
        $contact = Contact::first();

        return view('livewire.contacto.contact-manager', [
            'contact'   => $contact,
            'infoItems' => $contact ? $contact->infoItems : collect(),
        ]);
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'maps_adm'   => 'nullable|string|max:255',
            'frame_adm'  => 'nullable|string',
            'facebook'   => 'nullable|string|max:255',
            'insta'      => 'nullable|string|max:255',
            'linkedin'   => 'nullable|string|max:255',
            'youtube'    => 'nullable|string|max:255',
            'icono_1_temp' => 'nullable|mimes:jpg,jpeg,png,webp,gif,svg|max:4096',
            'icono_2_temp' => 'nullable|mimes:jpg,jpeg,png,webp,gif,svg|max:4096',
            'icono_3_temp' => 'nullable|mimes:jpg,jpeg,png,webp,gif,svg|max:4096',
            'ci_type'    => 'nullable|array',
            'ci_type.*'  => 'in:direccion,whatsapp,whatsapp_flotante,telefono,email',
            'ci_value'   => 'nullable|array',
            'ci_value.*' => 'nullable|string|max:1000',
            'ci_fixed'   => 'nullable|array',
            'ci_fixed.*' => 'in:0,1',
        ]);

        $contact = Contact::first() ?? new Contact();

        // Los archivos se resuelven aparte: por fill() se guardaría el UploadedFile.
        unset($validated['icono_1_temp'], $validated['icono_2_temp'], $validated['icono_3_temp']);

        $contact->fill($validated);

        foreach ([1, 2, 3] as $i) {
            $campo = "icono_{$i}";

            if ($request->hasFile("icono_{$i}_temp")) {
                if ($contact->{$campo}) {
                    Storage::disk('public')->delete($contact->{$campo});
                }
                $contact->{$campo} = $request->file("icono_{$i}_temp")->store('contact', 'public');
            }

            if ($request->has("remove_icono_{$i}")) {
                if ($contact->{$campo}) {
                    Storage::disk('public')->delete($contact->{$campo});
                }
                $contact->{$campo} = null;
            }
        }

        $contact->save();

        $this->syncInfoItems($request, $contact);

        return back()->with('success', 'Datos guardados correctamente.');
    }

    /**
     * Sincroniza los datos de contacto dinámicos del formulario.
     * El WhatsApp flotante es fijo: se edita pero no se elimina.
     */
    protected function syncInfoItems(Request $request, Contact $contact): void
    {
        $types = $request->input('ci_type', []);
        $values = $request->input('ci_value', []);
        $fixedFlags = $request->input('ci_fixed', []);

        $rows = [];
        $order = 0;

        foreach ($types as $i => $type) {
            $value = trim((string) ($values[$i] ?? ''));
            $isFixed = ($fixedFlags[$i] ?? '0') === '1';

            if ($value === '' && ! $isFixed) {
                continue;
            }

            if ($type === 'whatsapp_flotante') {
                $rows = array_values(array_filter($rows, fn ($r) => $r['type'] !== 'whatsapp_flotante'));
            }

            $rows[] = [
                'contact_id' => $contact->id,
                'type'       => $type,
                'value'      => $value,
                'is_fixed'   => $isFixed,
                'sort_order' => $order++,
            ];
        }

        // Si el fijo no llegó en el request, lo restauramos de la base.
        if (! collect($rows)->contains('type', 'whatsapp_flotante')) {
            $oldFixed = ContactInfoItem::where('contact_id', $contact->id)
                ->where('type', 'whatsapp_flotante')
                ->first();

            if ($oldFixed) {
                $rows[] = [
                    'contact_id' => $contact->id,
                    'type'       => 'whatsapp_flotante',
                    'value'      => $oldFixed->value,
                    'is_fixed'   => true,
                    'sort_order' => $order++,
                ];
            }
        }

        $contact->infoItems()->delete();

        if ($rows) {
            $contact->infoItems()->insert($rows);
        }

        // Espejo en las columnas históricas que consumen layout y footer.
        $byType = collect($rows)->groupBy('type');
        $contact->direction_adm = optional($byType->get('direccion'))->first()['value'] ?? null;
        $contact->phone_amd     = optional($byType->get('telefono'))->first()['value'] ?? null;
        $contact->mail_adm      = optional($byType->get('email'))->first()['value'] ?? null;
        $contact->wssp          = optional($byType->get('whatsapp_flotante'))->first()['value'] ?? null;
        $contact->save();
    }
}
