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

class ChannelCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laragen:channel 
    {name : Channel class name without `Channel`, e.g. `Sms`}
    {--m|message= :  Generate `Message` file with some attributes, e.g. `--message=mobile,title,body`}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a notification channel';

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
        $messageAttributes = $this->option('message');

        $path = config('laragen.channel.path');

        $suffix = 'Channel';
        $classFile = new ClassFile($file, ['App', $path, $name . $suffix]);
        $basename = snake_case(str_replace_last($suffix, '', $classFile->className));
        $methodName = studly_case(str_replace_last($suffix, '', $classFile->className));
        $class = $file->addClass($classFile->classFullName);
        $namespace = $class->getNamespace();
        $namespace->addUse(Notification::class);

        $class->addMethod('__construct')->addComment('Create a new ' . $methodName . ' channel instance.');
        $class->addMethod('send')->setParameters([
            (new Parameter('notifiable')),
            (new Parameter('notification'))->setTypeHint(Notification::class),
        ])->addComment('Send the given notification.')
            ->addComment('@param mixed $notifiable')
            ->addComment('@param Notification $notification')
            ->setBody(<<<EOT
if (! \$to = \$notifiable->routeNotificationFor('{$basename}')) {
    return;
}

\$message = \$notification->to{$methodName}(\$notifiable);

//todo do what you like
EOT
        );
        $classFile->save();
        unset($file);
        $this->info('[success] ' . $classFile->classFullName);
        if ($messageAttributes) {

            $this->call('laragen:message', [
                'name' => $name, '--attributes' => $messageAttributes
            ]);
        }


    }
}
