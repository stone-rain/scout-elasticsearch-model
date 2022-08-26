<?php

namespace ScoutElasticModel\Console;

use App\Model\Topic\TopicComment;
use App\Model\Topic\TopicReply;
use App\Model\Topic\Topics;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use ScoutElasticModel\Searchable;
use InvalidArgumentException;

class ElasticModelUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:model-update {model} {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        if (!$model = $this->getModel()) {
            return;
        }
        $this->info('更新ES：' . get_class($model));
        $model = $model::query();
        if ($id = $this->getId()) {
            $model->whereIn('id', $id);
        }
        $count = (clone $model)->count();
        $this->info('条数：' . $count);
        $bar = $this->output->createProgressBar($count);
        $model->chunk(100, function ($items) use(&$bar){
            foreach ($items as $item) {
                //
                $item->updateES();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->info("\n\n");
    }

    protected function getModel()
    {
        $modelClass = trim($this->argument('model'));

        $modelInstance = new $modelClass;
        if (
            !($modelInstance instanceof Model) ||
            !in_array(Searchable::class, class_uses_recursive($modelClass))
        ) {
            throw new InvalidArgumentException(sprintf(
                'The %s class must extend %s and use the %s trait.',
                $modelClass,
                Model::class,
                Searchable::class
            ));
        }

        return $modelInstance;
    }

    protected function getId()
    {
        $id = trim($this->argument('id'));
        if (empty($id)) {
            return false;
        }

        $id = explode(',', $id);

        return $id;
    }
}
