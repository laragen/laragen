<?php

/**
 * ModelCommand
 *
 * @author wxs77577 <wxs77577@gmail.com>
 */

namespace Laragen\Laragen\Commands;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Nette\PhpGenerator\PhpFile;

class ModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laragen:model 
    {name? : Model class name, e.g. `User`} 
    {--all : Whether Generate all models, argument `name` will be ignored}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model class from table';

    /**
     * @var AbstractSchemaManager
     */
    protected $dsm;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(DatabaseManager $dbm)
    {
        $name = $this->argument('name');
        $all = $this->option('all');
        $this->dsm = $dbm->connection()->getDoctrineSchemaManager();
        if ($all) {
            $tables = $this->dsm->listTables();
            foreach ($tables as $table) {
                $className = studly_case(str_singular($table->getName()));
                $this->generateModel($className);
            }
        } else {
            $this->generateModel($name);
        }
    }

    /**
     * @param $name Model Class Name
     */
    public function generateModel($name)
    {
        $path = config('laragen.model.path');
        $parentClass = config('laragen.model.parent_class', Model::class);

        $file = new PhpFile();
        $classFile = new ClassFile($file, ['App', $path, $name]);

        $className = $classFile->className;
        $plural = $tableName = snake_case(str_plural($className));
        $singular = str_singular(snake_case($className));
        $classFullName = $classFile->classFullName;

        if (
            (in_array($tableName, config('laragen.model.ignore_tables')))
            || (strpos($tableName, 'admin_') === 0 && config('laragen.model.ignore_admin_tables'))
        ) {
            $this->line('ignore: ' . $tableName);
            return;
        } else {
            $this->info('[success] ' . $classFullName);
        }
        $class = $file->addClass($classFullName);
        $namespace = $class->getNamespace();
        $namespace->addUse(Notifiable::class);
        $namespace->addUse($parentClass);
        $namespace->addUse(HasMany::class);
        $namespace->addUse(BelongsTo::class);
        $namespace->addUse(BelongsToMany::class);
        $namespace->addUse(MorphMany::class);
        $namespace->addUse(MorphTo::class);

        if ($className == 'User') {
            $class->addExtend(User::class);
            $class->addTrait(Notifiable::class);
            $class->addProperty('hidden', ['password']);
        } else {
            $class->addExtend($parentClass);
        }

        foreach (config('laragen.model.traits', []) as $trait) {
            $namespace->addUse($trait);
            $class->addTrait($trait);
        }

        $dsm = $this->dsm;
        $table = $dsm->listTableDetails($tableName);
        $fields = collect($dsm->listTableColumns($tableName));

        if ($table->hasColumn('deleted_at')) {
            $namespace->addUse(SoftDeletes::class);
            $class->addTrait(SoftDeletes::class);
        }

        $class->addProperty('fillable', $fields->keys()->reject(function ($name) {
            return in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at']);
        })->values()->toArray());
        $class->addProperty('casts', []);
        $class->addProperty('appends', []);
        $class->addProperty('dates', $fields->keys()->filter(function ($name) {
            return substr($name, -3) === '_at' && !in_array($name, ['created_at', 'updated_at']);
        })->values()->toArray());

        $keys = $dsm->listTableForeignKeys($tableName);
        $tables = $dsm->listTables();
        //belongs to
        foreach ($keys as $key) {

            $foreign = $key->getForeignTableName();
            $localColumnName = implode('_', $key->getLocalColumns());
            $localName = strtr(implode('_', $key->getLocalColumns()), ['_id' => '']);
            $foreignName = str_singular($foreign);
            $class->addMethod($localName)->setBody('return $this->belongsTo(' . studly_case($foreignName)
                . '::class' . ($foreignName != $localName ? ",'" . $localColumnName . "'" : '')
                . ');')->setReturnType(BelongsTo::class)
                ->addComment('Get ' . $localName)
                ->addComment('@return BelongsTo');
        }
        unset($keys);
        //hasMany
        foreach ($tables as $tbl) {
            $keys = $tbl->getForeignKeys();
            $name = $tbl->getName();

            foreach ($keys as $key) {
                $i = 0;
                if ($key->getForeignTableName() === $tableName) {
                    $tblSingular = str_singular($name);
                    $tblPlural = str_plural($name);
                    $methodName = camel_case($tblPlural);
                    $isConflict = false;
                    while (isset($class->methods[$methodName])) {
                        $isConflict = true;
                        $methodName .= ++$i;
                    }


                    $method = $class->addMethod($methodName);
                    $hasManyClassName = studly_case(str_singular($name));
                    $localColumnName = implode('_', $key->getLocalColumns());
                    $localName = strtr(implode('_', $key->getLocalColumns()), ['_id' => '']);
                    $foreignName = str_singular($tableName);

                    $method->addBody('return $this->hasMany(' . $hasManyClassName . '::class' .
                        (($foreignName !== $localName) || $isConflict ? ",'" . $localColumnName . "'" : '') . ');')
                        ->addComment('Get ' . $tblPlural)
                        ->addComment('@return HasMany')
                        ->setReturnType(HasMany::class);
                    if ($isConflict) {
                        $method->addComment('TODO Maybe you should change the name of this relation');
                    }
                }
            }
        }


        //morphMany
        if ($table->hasColumn($singular . 'able_id') && $table->hasColumn($singular . 'able_type')) {
            $class->addMethod($singular . 'able')
                ->setBody('return $this->morphTo();')
                ->setReturnType(MorphTo::class)
                ->addComment('Get ' . $singular . 'able model')
                ->addComment('@return MorphTo');
        }

        $morphMany = config('laragen.model.morph_many', []);
        foreach ($morphMany as $key => $models) {
            if (in_array($className, $models)) {
                $class->addMethod(snake_case(str_plural($key)))->setBody('return $this->morphMany(' . studly_case(str_singular($key))
                    . '::class, ?);', [
                    snake_case($key) . 'able'
                ])->setReturnType(MorphMany::class)
                    ->addComment('Get ' . snake_case(str_plural($key)))
                    ->addComment('@return MorphMany');
            }
        }

        $classFile->save();

        return;
    }
}
