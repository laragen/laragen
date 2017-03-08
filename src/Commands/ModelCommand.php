<?php

namespace Laragen\Laragen\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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
    protected $signature = 'laragen:model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model class from table';

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
     * @param DatabaseManager $dbm
     * @param PhpFile $file
     */
    public function handle(DatabaseManager $dbm, PhpFile $file)
    {
        $name = $this->argument('name');
        $path = config('laragen.model.path');
        $parentClass = config('laragen.api.parent_class');

        $className = studly_case($name);
        $tableName = str_plural(snake_case($name));
        $classFullName = 'App' . ($path ? '\\' . $path : '') . '\\' . $className;
        $class = $file->addClass($classFullName);
        $namespace = $class->getNamespace();
        $namespace->addUse(Notifiable::class);
        $namespace->addUse($parentClass);
        $namespace->addUse(HasMany::class);
        $namespace->addUse(BelongsTo::class);
        $namespace->addUse(BelongsToMany::class);
        $namespace->addUse(MorphMany::class);

        if ($className == 'User') {
            $class->addExtend(User::class);
            $class->addTrait(Notifiable::class);
            $class->addProperty('hidden', ['password']);
        } else {
            $class->addExtend($parentClass);
        }

        foreach (config('laragen.model.traits') as $trait) {
            $namespace->addUse($trait);
            $class->addTrait($trait);
        }


        $dsm = $dbm->connection()->getDoctrineSchemaManager();
        $fields = collect($dsm->listTableColumns($tableName));

        $class->addProperty('fillable', $fields->keys()->reject(function ($name) {
            return in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at']);
        })->values()->toArray());
        $class->addProperty('casts', []);
        $class->addProperty('appends', []);
        $class->addProperty('dates', $fields->keys()->filter(function ($name) {
            return substr($name, -3) === '_at';
        })->values()->toArray());

        $keys = $dsm->listTableForeignKeys($tableName);
        $tables = $dsm->listTables();
        //belongs to
        foreach ($keys as $key) {
            $foreign = $key->getForeignTableName();
            $class->addMethod(snake_case(str_singular($foreign)))->setBody('return $this->belongsTo(' .
                $namespace->getName() . '\\' . studly_case(str_singular($foreign))
                . ':class);')->setReturnType(BelongsTo::class);
        }
        unset($keys);
        //hasMany
        foreach ($tables as $table) {
            $keys = $table->getForeignKeys();
            $name = $table->getName();
            foreach ($keys as $key) {
                if ($key->getForeignTableName() === $tableName) {
                    $singular = str_singular($name);
                    $plural = str_plural($name);
                    //is morph many
                    if ($table->hasColumn($singular . 'able_id') && $table->hasColumn($singular . 'able_type')) {
                        $class->addMethod(snake_case($plural))->setBody('return $this->morphMany(' .
                            $namespace->getName() . '\\' . studly_case($singular)
                            . ':class, ?);', [$singular . 'able'])->setReturnType(MorphMany::class);
                    } else {
                        $class->addMethod(snake_case($plural))->setBody('return $this->hasMany(' .
                            $namespace->getName() . '\\' . studly_case(str_singular($name))
                            . ':class);')->setReturnType(HasMany::class);
                    }
                }
            }
        }

        echo $file;
    }
}
