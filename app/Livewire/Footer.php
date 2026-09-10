<?php

namespace App\Livewire;

use App\Models\Contact;
use Livewire\Component;

class Footer extends Component
{
    public $contactData;

    public bool $hasSocialMedia = false;

    public function mount(): void
    {
        $this->contactData = Contact::with('infoItems')->first();

        $this->hasSocialMedia = (bool) ($this->contactData && (
            $this->contactData->insta ||
            $this->contactData->facebook ||
            $this->contactData->linkedin ||
            $this->contactData->youtube
        ));
    }

    public function render()
    {
        return view('livewire.footer');
    }
}
