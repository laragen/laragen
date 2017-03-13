<?php
/**
 * Created by PhpStorm.
 * User: wxs77577 <wxs77577@gmail.com>
 * Date: 2017/3/10
 * Time: 10:01
 */

namespace Laragen\Laragen\Commands;


use Nette\PhpGenerator\PhpFile;

class ClassFile
{

    public $className;
    public $classFullName;
    public $fileName;
    public $filePath;

    public $file;

    public function __construct(PhpFile $file, $classFullName = null)
    {
        if ($file && $classFullName) {
            $this->load($file, $classFullName);
        }
    }

    public static function toPath($classFullName)
    {
        return base_path() . '/' . strtr($classFullName, [
                '\\' => '/',
                'App' => 'app',
            ]) . '.php';
    }

    public static function toClassFullName($classFullName)
    {


        if (is_array($classFullName)) {
            $classFullName = implode('\\', array_filter($classFullName));
        }

        $classFullName = strtr($classFullName, ['/' => '\\', '\\\\' => '\\']);
        $classFullName = implode('\\', array_filter(explode('\\', $classFullName), function ($value) {
            return studly_case($value);
        }));
        return $classFullName;
    }

    public function load(PhpFile $file, $classFullName): self
    {
        $this->file = $file;
        $classFullName = self::toClassFullName($classFullName);
        $filePath = self::toPath($classFullName);
        $className = class_basename($classFullName);
        $this->classFullName = $classFullName;
        $this->className = $className;
        $this->filePath = $filePath;
        return $this;
    }

    public function save($path = null): bool
    {
        if (!$path) {
            $path = $this->filePath;
        }
        $this->file = strtr($this->file, ["\t" => '    ']);
        !is_dir(dirname($path)) && mkdir(dirname($path), 0777, true);
//        echo $this->file, PHP_EOL;return true; //for debug
        return file_put_contents($path, $this->file);
    }
}