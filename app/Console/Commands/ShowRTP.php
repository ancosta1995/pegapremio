<?php

namespace App\Console\Commands;

use App\Models\GameMultiplier;
use Illuminate\Console\Command;

class ShowRTP extends Command
{
    protected $signature = 'game:show-rtp';
    protected $description = 'Mostra as probabilidades e RTP configurados no jogo';

    public function handle()
    {
        $this->info('=== MULTIPLICADORES REAIS (is_demo = false) ===');
        $this->newLine();
        
        $real = GameMultiplier::where('is_demo', false)
            ->where('active', true)
            ->orderBy('order')
            ->get();
        
        if ($real->isEmpty()) {
            $this->warn('Nenhum multiplicador real encontrado!');
        } else {
            $rtpReal = 0;
            foreach ($real as $m) {
                $contribution = $m->multiplier * $m->probability;
                $rtpReal += $contribution;
                $this->line(sprintf(
                    '  %6.2fx → %5.2f%% probabilidade (contribuição: %6.2f%%)',
                    $m->multiplier,
                    $m->probability,
                    $contribution
                ));
            }
            $this->newLine();
            $this->info('RTP Total: ' . number_format($rtpReal, 2) . '%');
        }
        
        $this->newLine();
        $this->info('=== MULTIPLICADORES DEMO (is_demo = true) ===');
        $this->newLine();
        
        $demo = GameMultiplier::where('is_demo', true)
            ->where('active', true)
            ->orderBy('order')
            ->get();
        
        if ($demo->isEmpty()) {
            $this->warn('Nenhum multiplicador demo encontrado!');
        } else {
            $rtpDemo = 0;
            foreach ($demo as $m) {
                $contribution = $m->multiplier * $m->probability;
                $rtpDemo += $contribution;
                $this->line(sprintf(
                    '  %6.2fx → %5.2f%% probabilidade (contribuição: %6.2f%%)',
                    $m->multiplier,
                    $m->probability,
                    $contribution
                ));
            }
            $this->newLine();
            $this->info('RTP Total: ' . number_format($rtpDemo, 2) . '%');
        }
        
        return Command::SUCCESS;
    }
}

