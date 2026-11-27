<?php

declare(strict_types=1);

use CraftCms\WpImport\Console\ImportCommand;
use CraftCms\WpImport\Tests\TestCase;
use Illuminate\Console\OutputStyle;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

uses(TestCase::class)->in('Feature');

/**
 * Returns an import command that generators and importers can run against, without a WordPress site.
 */
function importCommand(): ImportCommand
{
    $output = new BufferedOutput;
    Prompt::setOutput($output);

    $command = new ImportCommand;
    $command->setLaravel(app());
    $command->setOutput(new OutputStyle(new ArrayInput([]), $output));
    $command->wpInfo = ['field_groups' => []];
    app()->instance(ImportCommand::class, $command);

    return $command;
}
