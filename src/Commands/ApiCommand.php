<?php

/**
 * ApiCommand
 *
 * @author wxs77577 <wxs77577@gmail.com>
 */

namespace Laragen\Laragen\Commands;

use Illuminate\Http\Request;

use Nette\PhpGenerator\PhpFile;

class ApiCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laragen:api 
    {name : Controller class name without `Controller`, e.g. `User`}
    {--m|model : Whether generate `model()` function}
    {--actions= : Generate some actions, e.g. `index,view`}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate api controller';

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
        $suffix = 'Controller';

        $classFile = new ClassFile($file, [
            'App','Http', 'Controllers', $path, $version, $name . $suffix
        ]);

        $class = $file->addClass($classFile->classFullName);
        $parentClass = config('laragen.api.parent_class');
        $namespace = $class->getNamespace();

        $namespace->addUse(Request::class);
        $namespace->addUse($parentClass);
        $class->addExtend($parentClass);

        if ($model) {
            $modelClassName = studly_case($name);
            $modelClassFullName = ClassFile::toClassFullName(['App', config('laragen.model.path'), $modelClassName]);
            $namespace->addUse($modelClassFullName);
            $class->addMethod('model')
                ->setBody('return '.$modelClassName.'::class;')
                ->addComment('Specify Model class name')
                ->setReturnType('string');
        }

        foreach ($actions as $action) {
            $class->addMethod($action);
        }

        $classFile->save();
        $this->info('[success] ' . $classFile->classFullName);
    }
}
