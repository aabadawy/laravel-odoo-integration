<?php

namespace Aabadawy\LaravelOdooIntegration\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeOdooModule extends GeneratorCommand
{

    public $signature = 'make:odoo-module {name} --moduleName';

    public $description = 'make new odoo module';


    protected function getStub()
    {
        return __DIR__.'/../stubs/odooModule.stub';
    }

    protected function buildClass($name)
    {
        $module = class_basename(Str::ucfirst(str_replace('name', '', $name)));

        $namespace = $this->getNamespace("App\\OdooModules\\");

        $replace = [
            '{{ OdooModuleNamespace }}' => $namespace,
            '{{OdooModuleName}}' => $module,
        ];

        return str_replace(
            array_keys($replace), array_values($replace), parent::buildClass($name)
        );
    }
    
    protected function getPath($name)
    {
        $name = (string) Str::of($name)->replaceFirst($this->rootNamespace(), '')->finish('Module');

        return $this->laravel->basePath("/app/OdooModules/") . str_replace('\\', '/', $name).'.php';
    }
}
