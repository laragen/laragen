<?php

namespace Laragen\Laragen\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;

use Nette\PhpGenerator\PhpFile;

class ApiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laragen:api {name} {--model} {--actions=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate api controller from table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param PhpFile $file
     */
    public function handle(PhpFile $file)
    {
        $name = $this->argument('name');
        $actions = array_filter(explode(',', $this->option('actions')));
        $model = $this->option('model');
        $path = config('laragen.api.path');
        $version = config('laragen.api.version');


        $className = studly_case($name). 'Controller';
        $classFullName = 'App\\Controllers' . ($path ? '\\' . $path : '') . ($version ? '\\V' . $version : '') . '\\' . $className;
        $class = $file->addClass($classFullName);
        $parentClass = config('laragen.api.parent_class');
        $namespace = $class->getNamespace();

        $namespace->addUse(Request::class);
        $namespace->addUse($parentClass);
        $class->addExtend($parentClass);

        if ($model) {
            $class->addMethod('model')
                ->setBody('return App\\'.config('laragen.model.path').'\\'.studly_case($name).'::class;')
                ->addComment('Specify Model class name')
                ->setReturnType('string');
        }


        foreach ($actions as $action) {
            $class->addMethod($action)->setReturnType('array');
        }
        echo $file;
    }
}
