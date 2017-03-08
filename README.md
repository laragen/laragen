#Laragen
####A powerful yet lightweight generator for Laravel 5.4  and php7

###Installation

1. Install via composer.

    ```bash
    composer require laragen/laragen --dev
    ```
    
1. Add `LaragenServiceProvider` to `app/Providers/AppServiceProvider.php`.

    ```php
    if ($this->app->environment('local')) {    
        $this->app->re.gister(\Laragen\Laragen\LaragenServiceProvider::class);
        $this->app->re.gister(IdeHelperServiceProvider::class);
        $this->app->re.gister(DuskServiceProvider::class);
    }
    ```
    
1. Publish config files.

     ```bash
     php artisan vendor:publish --tag=laragen.config
     ```
     
###Usage

1. Generate Eloquent Model Class.
    ```bash
    php artisan laragen:model User
    php artisan laragen:model --all
    ```
    
    Looks like this:
    ```php
    <?php
    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Database\Eloquent\Relations\BelongsToMany;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    use Illuminate\Database\Eloquent\Relations\MorphMany;
    use Illuminate\Database\Eloquent\Relations\MorphTo;
    use Illuminate\Database\Eloquent\SoftDeletes;
    use Illuminate\Notifications\Notifiable;
    
    class Comment extends Model
    {
        use SoftDeletes;
    
        public $fillable = ['user_id', 'commentable_id', 'commentable_type', 'content'];
    
        public $casts = [];
    
        public $appends = [];
    
        public $dates = ['deleted_at'];
    
    
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }
    
    
        public function commentable(): MorphTo
        {
            return $this->morphTo();
        }
    
    }

    ```
1. Generate Api Controller Class.
    ```bash
    php artisan laragen:api User
    php artisan laragen:api User --model --actions=index,view
    php artisan laragen:api User -m -a=index,view
    ```
    
    Looks like this:
    
    ```php
    <?php
    namespace App\Http\Controllers\Api\V1;
    
    use App\Http\Controllers\Controller;
    use App\Models\News;
    use Illuminate\Http\Request;
    
    class NewsController extends Controller
    {
    
    	/**
    	 * Specify Model class name
    	 */
    	public function model(): string
    	{
    		return News::class;
    	}
    
    
    	public function index()
    	{
    	}
    
    
    	public function view()
    	{
    	}
    
    }
    ```
    
###Config
  
  ```php
  return [
      'model' => [
          'path' => 'Models', // path after `app/`
          'soft_delete' => true, //add deleted_at for $dates
          'traits' => [], // traits for model
          'parent_class' => 'Illuminate\Database\Eloquent\Model',
          'ignore_admin_tables' => true, //ignore admin tables generated by laravel-admin plugin
          'ignore_tables' => ['jobs', 'migrations', 'notifications'], //ignore system tables
          'morph_many' => [ //see https://laravel.com/docs/5.4/eloquent-relationships#polymorphic-relations
              'Comment' => ['News', 'Post'],
              'Like' => ['News', 'Post'],
          ],
  
      ],
      'api' => [
          'path' => 'Api', // path after `app/Controllers/`
          'version' => 1, // real path is `app/Controllers/{path}/V{version}`
          'parent_class' => 'App\Http\Controllers\Controller',
      ],
  ];
  ```
  
##Any issue or pull request is appreciated :)