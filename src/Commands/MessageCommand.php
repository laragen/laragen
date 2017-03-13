<?php

/**
 * ApiCommand
 *
 * @author wxs77577 <wxs77577@gmail.com>
 */

namespace Laragen\Laragen\Commands;


use Illuminate\Notifications\Notification;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;

class MessageCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laragen:message 
    {name : Message class name without `Message`, e.g. `Sms`}
    {--attributes= :  Generate `Message` file with some attributes, e.g. `--attributes=mobile,title,body`}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a notification message';

    public function handle(PhpFile $file)
    {
        $attributes = $this->option('attributes');
        $name = $this->argument('name');
        $attributes = array_filter(explode(',', $attributes));
        $messagePath = config('laragen.message.path');
        $suffix = 'Message';
        $classFile = new ClassFile($file, ['App', $messagePath, $name . $suffix]);
        $basename = snake_case(str_replace_last($suffix, '', $classFile->className));
        $class = $file->addClass($classFile->classFullName);
        foreach ($attributes as $attribute) {
            $class->addProperty($attribute)->addComment(sprintf('The %s %s', $basename, $attribute));
            $class->addMethod($attribute)->setParameters([
                (new Parameter($attribute)),
            ])->addComment(sprintf('The %s %s', $basename, $attribute))
                ->addComment('@param mixed $' . $attribute)
                ->addComment('@return self')
                ->addBody("\$this->$attribute = \$$attribute;")
                ->addBody("return \$this;")
                ->setReturnType('self')
            ;
        }
        $classFile->save();
        $this->info('[success] ' . $classFile->classFullName);
    }
}
