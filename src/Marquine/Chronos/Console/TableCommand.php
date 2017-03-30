<?php

namespace Marquine\Chronos\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class TableCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chronos:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the chronos activity log database table';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * @var \Illuminate\Support\Composer
     */
    protected $composer;

    /**
     * Create a new queue job table command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Illuminate\Support\Composer  $composer
     * @return void
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $table = $this->laravel['config']['chronos.table'];

        $this->replaceMigration(
            $this->createBaseMigration($table), $table, Str::studly($table)
        );

        $this->info('Migration created successfully!');

        $this->composer->dumpAutoloads();
    }

    /**
     * Create a base migration file for the table.
     *
     * @param  string  $table
     * @return string
     */
    protected function createBaseMigration($table = 'activities')
    {
        return $this->laravel['migration.creator']->create(
            'create_'.$table.'_table', $this->laravel->databasePath().'/migrations'
        );
    }

    /**
     * Replace the generated migration with the activities table stub.
     *
     * @param  string  $path
     * @param  string  $table
     * @param  string  $tableClassName
     * @return void
     */
    protected function replaceMigration($path, $table, $tableClassName)
    {
        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'],
            [$table, $tableClassName],
            $this->files->get(__DIR__.'/stubs/activities.stub')
        );

        $this->files->put($path, $stub);
    }
}
