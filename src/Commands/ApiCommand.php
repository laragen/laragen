<?php

/**
 * ApiCommand
 *
 * @author wxs77577 <wxs77577@gmail.com>
 */

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
    protected $signature = 'laragen:api 
    {name : Controller class name without `Controller`, e.g. `User`}
    {--m|model : Whether generate `model()` function}
    {--a|actions= : Generate some actions, e.g. `index,view`}';

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
        $classFullName = implode('\\', array_filter([
            'App',
            'Http',
            'Controllers',
            $path,
            $version ? 'V'.$version : null,
            $className
        ]));
        $filePath = implode('/', array_filter([
            app_path(),
            'Http',
            'Controllers',
            $path,
            $version ? 'V'.$version : null,
            $className . '.php'
        ]));

        $class = $file->addClass($classFullName);
        $parentClass = config('laragen.api.parent_class');
        $namespace = $class->getNamespace();

        $namespace->addUse(Request::class);
        $namespace->addUse($parentClass);
        $class->addExtend($parentClass);

        if ($model) {
            $modelClassName = studly_case($name);
            $modelClassFullName = implode('\\', array_filter(['App', config('laragen.model.path'), $modelClassName]));
            $namespace->addUse($modelClassFullName);
            $class->addMethod('model')
                ->setBody('return '.$modelClassName.'::class;')
                ->addComment('Specify Model class name')
                ->setReturnType('string');
        }

        foreach ($actions as $action) {
            $class->addMethod($action);
        }

        !is_dir(dirname($filePath)) && mkdir(dirname($filePath), 0777, true);
        file_put_contents($filePath, $file);
        $this->info('[success] ' . $classFullName);

    }
}
