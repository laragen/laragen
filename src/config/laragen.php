<?php
/**
 * Created by PhpStorm.
 * User: wxs77577 <wxs77577@gmail.com>
 * Date: 2017/3/8
 * Time: 14:29
 */

return [
    'model' => [
        'path' => 'Models',
        'soft_delete' => true,
        'traits' => ['App\\Traits\\PublicScopesTrait'],
        'parent_class' => 'Illuminate\Database\Eloquent\Model',

    ],
    'api' => [
        'path' => 'Api',
        'version' => 1,
        'parent_class' => 'App\Http\Controllers\Controller',
    ],
];