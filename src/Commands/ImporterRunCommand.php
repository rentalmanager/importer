<?php
namespace RentalManager\Importer\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Created by PhpStorm.
 * User: gorankrgovic
 * Date: 9/11/18
 * Time: 10:45 AM
 */


class ImporterRunCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'rm-importer:run {provider}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the tasks for the provider';

    /**
     * Commands to call with their description.
     *
     * @var array
     */
    protected $calls = [
        [
            'command' => 'rm-importer:download',
        ],
        [
            'command' => 'rm-importer:parse'
        ],
        [
            'command' => 'rm-importer:geocode-new'
        ],
        [
            'command' => 'rm-importer:geocode-new'
        ],
        [
            'command' => 'rm-importer:geocode-new'
        ],
        [
            'command' => 'rm-importer:import-all'
        ],
        [
            'command' => 'rm-importer:deactivate-all'
        ]
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->calls as $iterate => $command) {
            $this->call($command['command'], ['provider' => $this->argument('provider')]);
        }
    }
}
