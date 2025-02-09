<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AviavoxAnnouncement;
use Illuminate\Support\Facades\File;

class CreateAviavoxAnnouncement extends Component
{
    public $selectedMessage = '';
    public $variables = [];
    public $zones = 'Terminal';
    public $predefinedMessages = [];

    public function mount()
    {
        $this->loadPredefinedMessages();
    }

    private function loadPredefinedMessages()
    {
        $messagesFile = storage_path('app/aviavox/Eurostar - AviaVox AIP Message Triggers.txt');
        if (File::exists($messagesFile)) {
            $content = File::get($messagesFile);
            $messages = [];
            
            // Split content into individual XML blocks
            preg_match_all('/<AIP>.*?<\/AIP>/s', $content, $xmlBlocks);
            
            foreach ($xmlBlocks[0] as $xml) {
                if (preg_match('/<Item ID="MessageName" Value="([^"]+)"/', $xml, $nameMatch)) {
                    $messageName = $nameMatch[1];
                    
                    // Extract all variables (XX or XXXX patterns)
                    preg_match_all('/Value="([^"]*?(?:XX+)[^"]*)"/', $xml, $varMatches);
                    $variables = [];
                    
                    foreach ($varMatches[1] as $value) {
                        if (strpos($value, 'XX') !== false) {
                            $paramName = $this->getParameterName($xml, $value);
                            $variables[$paramName] = '';
                        }
                    }
                    
                    $messages[$messageName] = [
                        'xml_template' => $xml,
                        'variables' => $variables
                    ];
                }
            }
            
            $this->predefinedMessages = $messages;
        }
    }

    private function getParameterName($xml, $value)
    {
        if (preg_match('/ID="([^"]+)" Value="' . preg_quote($value, '/') . '"/', $xml, $match)) {
            return $match[1];
        }
        return '';
    }

    public function updatedSelectedMessage($value)
    {
        if (isset($this->predefinedMessages[$value])) {
            $this->variables = $this->predefinedMessages[$value]['variables'];
        }
    }

    public function save()
    {
        $template = $this->predefinedMessages[$this->selectedMessage]['xml_template'];
        
        // Replace variables in template
        foreach ($this->variables as $param => $value) {
            $template = preg_replace(
                '/(<Item ID="' . $param . '" Value=")[^"]*(")/i',
                '${1}' . $value . '${2}',
                $template
            );
        }

        AviavoxAnnouncement::create([
            'name' => $this->selectedMessage,
            'xml_content' => $template,
            'item_id' => 'MessageName',
            'value' => $this->selectedMessage
        ]);

        $this->dispatch('close-modal');
        session()->flash('success', 'Announcement created successfully.');
    }

    public function render()
    {
        return view('livewire.create-aviavox-announcement');
    }
} 