<?php

namespace App\Livewire;

use App\Models\Reason;
use Livewire\Component;

class ReasonsManager extends Component
{
    public $reasons = [];

    public $code = '';

    public $name = '';

    public $descriptions = ['en' => ''];

    public $editingId = null;

    public $availableLanguages = ['en' => 'English', 'nl' => 'Dutch', 'fr' => 'French'];

    public function mount()
    {
        $this->loadReasons();
    }

    public function loadReasons()
    {
        $this->reasons = Reason::orderBy('code')->get();
    }

    public function save()
    {
        // Ensure English description is always present
        if (empty($this->descriptions['en'])) {
            $this->descriptions['en'] = '';
        }

        // Validate descriptions
        $rules = [
            'code' => 'required|string|max:50|unique:reasons,code'.($this->editingId ? ",{$this->editingId}" : ''),
            'name' => 'required|string|max:255',
            'descriptions.en' => 'nullable|string',
        ];

        // Add validation for additional languages if they exist
        foreach (['nl', 'fr'] as $lang) {
            if (isset($this->descriptions[$lang])) {
                $rules["descriptions.{$lang}"] = 'nullable|string';
            }
        }

        $this->validate($rules);

        // Filter out empty descriptions (except English)
        $filteredDescriptions = ['en' => $this->descriptions['en'] ?? ''];
        foreach (['nl', 'fr'] as $lang) {
            if (isset($this->descriptions[$lang]) && trim($this->descriptions[$lang]) !== '') {
                $filteredDescriptions[$lang] = $this->descriptions[$lang];
            }
        }

        if ($this->editingId) {
            $reason = Reason::find($this->editingId);
            $reason->update([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $filteredDescriptions,
            ]);
        } else {
            Reason::create([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $filteredDescriptions,
            ]);
        }

        $this->reset(['code', 'name', 'descriptions', 'editingId']);
        $this->descriptions = ['en' => ''];
        $this->loadReasons();
    }

    public function edit($id)
    {
        $reason = Reason::find($id);
        $this->editingId = $id;
        $this->code = $reason->code;
        $this->name = $reason->name;

        // Load descriptions from the model's raw attribute
        $allDescriptions = $reason->getAllDescriptions();
        $this->descriptions = [
            'en' => $allDescriptions['en'] ?? '',
            'nl' => $allDescriptions['nl'] ?? '',
            'fr' => $allDescriptions['fr'] ?? '',
        ];
    }

    public function delete($id)
    {
        Reason::find($id)->delete();
        $this->loadReasons();
    }

    public function cancel()
    {
        $this->reset(['code', 'name', 'descriptions', 'editingId']);
        $this->descriptions = ['en' => ''];
    }

    public function addDescriptionLanguage($lang)
    {
        if (! isset($this->descriptions[$lang]) && in_array($lang, ['nl', 'fr'])) {
            $this->descriptions[$lang] = '';
        }
    }

    public function removeDescriptionLanguage($lang)
    {
        if (isset($this->descriptions[$lang]) && $lang !== 'en') {
            unset($this->descriptions[$lang]);
        }
    }

    public function getActiveLanguages()
    {
        $active = ['en']; // English is always active

        foreach (['nl', 'fr'] as $lang) {
            if (isset($this->descriptions[$lang]) && trim($this->descriptions[$lang]) !== '') {
                $active[] = $lang;
            }
        }

        return $active;
    }

    public function getAvailableAdditionalLanguages()
    {
        $active = $this->getActiveLanguages();

        return array_filter(['nl', 'fr'], fn ($lang) => ! in_array($lang, $active));
    }

    public function render()
    {
        return view('livewire.reasons-manager');
    }
}
