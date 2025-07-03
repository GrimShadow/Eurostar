<?php

namespace App\Console\Commands;

use App\Models\AutomatedAnnouncementRule;
use App\Models\Announcement;
use App\Models\AviavoxSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Carbon\Carbon;

Schedule::command(ProcessAutomatedAnnouncements::class)->everyMinute();

class ProcessAutomatedAnnouncements extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'announcements:process-automated {--debug : Show debug information}';

    /**
     * The console command description.
     */
    protected $description = 'Process automated announcement rules and trigger announcements when conditions are met';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rules = AutomatedAnnouncementRule::with('aviavoxTemplate')
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            if ($this->option('debug')) {
                $this->info('No active automated announcement rules found');
            }
            return;
        }

        $triggered = 0;

        foreach ($rules as $rule) {
            if ($rule->shouldTrigger()) {
                try {
                    $this->triggerAnnouncement($rule);
                    $rule->markAsTriggered();
                    $triggered++;
                    
                    if ($this->option('debug')) {
                        $this->info("Triggered announcement: {$rule->name}");
                    }
                    
                    
                } catch (\Exception $e) {
                    Log::error("Failed to trigger automated announcement", [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'error' => $e->getMessage()
                    ]);
                    
                    if ($this->option('debug')) {
                        $this->error("Failed to trigger {$rule->name}: {$e->getMessage()}");
                    }
                }
            } else {
                if ($this->option('debug')) {
                    //$this->line("Rule '{$rule->name}' conditions not met");
                }
            }
        }

        if ($this->option('debug')) {
            $this->info("Processed {$rules->count()} rules, triggered {$triggered} announcements");
        }
    }

    /**
     * Trigger an announcement for the given rule
     */
    private function triggerAnnouncement(AutomatedAnnouncementRule $rule): void
    {
        // Create announcement record in the database
        $announcement = Announcement::create([
            'type' => 'audio',
            'message' => $rule->aviavoxTemplate->friendly_name ?? $rule->aviavoxTemplate->name,
            'scheduled_time' => Carbon::now()->format('H:i:s'),
            'author' => 'System (Automated)',
            'area' => $rule->zone,
            'status' => 'Pending',
            'recurrence' => "Every {$rule->interval_minutes} min"
        ]);

        // Update last triggered time
        $rule->update(['last_triggered_at' => Carbon::now()]);

        // Try to send to Aviavox if configured
        $settings = AviavoxSetting::first();
        if ($settings && $settings->ip_address && $settings->port) {
            try {
                $this->sendToAviavox($rule, $settings);
                $announcement->update(['status' => 'Finished']);
                
                
            } catch (\Exception $e) {
                $announcement->update(['status' => 'Failed']);
                
                Log::error("Failed to trigger automated announcement", [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'error' => $e->getMessage()
                ]);
                
                // Don't re-throw the exception - we want to continue processing other rules
                // and still log the announcement attempt in the database
            }
        } else {
            // No Aviavox settings configured - still log the announcement but mark as finished
            $announcement->update(['status' => 'Finished']);
            
            Log::info("Automated announcement logged (Aviavox not configured)", [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'zone' => $rule->zone
            ]);
        }
    }

    /**
     * Send announcement to Aviavox system
     */
    private function sendToAviavox(AutomatedAnnouncementRule $rule, AviavoxSetting $settings): void
    {
        // Generate XML from template and variables
        $xml = $this->generateXml($rule);
        
        // Connect to Aviavox server using the same method as existing system
        $socket = fsockopen($settings->ip_address, $settings->port, $errno, $errstr, 30);
        if (!$socket) {
            throw new \Exception("Failed to connect to Aviavox: $errstr ($errno)");
        }

        try {
            // Authentication flow (same as existing system)
            // Step 1: Send challenge request
            $challengeRequest = "<AIP><MessageID>AuthenticationChallengeRequest</MessageID><ClientID>1234567</ClientID></AIP>";
            fwrite($socket, chr(2) . $challengeRequest . chr(3));
            
            // Step 2: Read challenge response
            $response = fread($socket, 1024);
            preg_match('/<Challenge>(\d+)<\/Challenge>/', $response, $matches);
            $challenge = $matches[1] ?? null;
            
            if (!$challenge) {
                throw new \Exception('Invalid challenge received from Aviavox');
            }

            // Step 3: Generate password hash
            $password = $settings->password;
            $passwordLength = strlen($password);
            $salt = $passwordLength ^ $challenge;
            $saltedPassword = $password . $salt . strrev($password);
            $hash = strtoupper(hash('sha512', $saltedPassword));

            // Step 4: Send authentication request
            $authRequest = "<AIP><MessageID>AuthenticationRequest</MessageID><ClientID>1234567</ClientID><MessageData><Username>{$settings->username}</Username><PasswordHash>{$hash}</PasswordHash></MessageData></AIP>";
            fwrite($socket, chr(2) . $authRequest . chr(3));

            // Step 5: Read authentication response
            $authResponse = fread($socket, 1024);

            if (strpos($authResponse, '<Authenticated>1</Authenticated>') === false) {
                throw new \Exception('Authentication failed');
            }

            // Step 6: Send the announcement
            fwrite($socket, chr(2) . $xml . chr(3));
            
            // Step 7: Read final response
            $finalResponse = fread($socket, 1024);


        } finally {
            fclose($socket);
        }
    }

    /**
     * Generate XML for the announcement
     */
    private function generateXml(AutomatedAnnouncementRule $rule): string
    {
        $template = $rule->aviavoxTemplate;
        $variables = $rule->template_variables ?? [];
        
        // Add zone to variables
        $variables['zone'] = $rule->zone;
        
        // Start with template content
        $xml = $template->content;
        
        // Replace variables in the XML
        foreach ($variables as $key => $value) {
            $xml = str_replace("{{$key}}", $value, $xml);
        }
        
        return $xml;
    }
}
