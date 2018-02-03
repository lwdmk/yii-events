<?php

namespace app\modules\events\models;

use app\modules\events\Module;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "events".
 *
 * @property integer $id
 * @property string $title
 * @property string $event_type
 * @property string $model_class
 * @property string $additional_expression
 * @property string $created_at
 * @property string $updated_at
 */
class Events extends \yii\db\ActiveRecord
{
    /**
     * @var string $eventHandler class implementing EventHandlerInterface
     */
    protected $eventHandler;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'events';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'timestamp' => [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()')
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->eventHandler = Module::getInstance()->getHandler();
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'model_class'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['title', 'model_class'], 'string', 'max' => 120],
            [['event_type'], 'string', 'max' => 30],
            [['additional_expression'], 'string', 'max' => 255],
            [['additional_expression'], 'syntaxCheck'],
        ];
    }

    /**
     * Validation function for additional_expression attribute php syntax check
     *
     * @return void
     */
    public function syntaxCheck()
    {
        $syntax_check = "<?php (function (){ ".$this->additional_expression." }); ?>";
        if(trim(shell_exec("echo " . escapeshellarg($syntax_check) . " | php -l")) != "No syntax errors detected in -") {
            $this->addError('additional_expression',
                'Additional expression syntax error, please use return statement at the beginning and ; at the end');
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                    => 'ID',
            'title'                 => 'Title',
            'event_type'            => 'Event Type',
            'model_class'           => 'Model Class',
            'additional_expression' => 'Additional Expression',
            'created_at'            => 'Created At',
            'updated_at'            => 'Updated At',
        ];
    }

    /**
     * Common method to forward request to custom event handler
     *
     * @param \yii\base\Event $yii_event
     *
     * @return mixed
     */
    public function handle($yii_event)
    {
        return $this->eventHandler->handle($this, $yii_event);
    }

    /**
     * ActiveRecord event types list
     *
     * @return array
     */
    public static function getEventTypes()
    {
        return [
            ActiveRecord::EVENT_INIT            => 'EVENT_INIT',
            ActiveRecord::EVENT_AFTER_FIND      => 'EVENT_AFTER_FIND',
            ActiveRecord::EVENT_BEFORE_INSERT   => 'EVENT_BEFORE_INSERT',
            ActiveRecord::EVENT_AFTER_INSERT    => 'EVENT_AFTER_INSERT',
            ActiveRecord::EVENT_BEFORE_UPDATE   => 'EVENT_BEFORE_UPDATE',
            ActiveRecord::EVENT_AFTER_UPDATE    => 'EVENT_AFTER_UPDATE',
            ActiveRecord::EVENT_BEFORE_DELETE   => 'EVENT_BEFORE_DELETE',
            ActiveRecord::EVENT_AFTER_DELETE    => 'EVENT_AFTER_DELETE',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'EVENT_BEFORE_VALIDATE',
            ActiveRecord::EVENT_AFTER_VALIDATE  => 'EVENT_AFTER_VALIDATE',
        ];
    }

    /**
     * @param $class_name
     * @return array|Events[]
     */
    public static function getAllForClass($class_name)
    {
        return self::find()->where(['model_class' => $class_name])->all();
    }

    /**
     * Getting model classes from cache or form paths
     * @return array|mixed
     * @throws InvalidConfigException
     */
    public static function getModelClasses()
    {
        $paths       = Module::getInstance()->modelPaths;
        $class_names = [];
        foreach ($paths as $path) {
            if(false === ($alias = Yii::getAlias($path))) {
                throw new InvalidConfigException('Model Paths param should consist real aliases');
            }
            if(false === ($class_names = Yii::$app->cache->get('model_class_names'))) {
                $class_names = self::getModelClassesFromDir($alias);
                Yii::$app->cache->set('model_class_names', $class_names, 24000);
            }
        }
        return array_combine($class_names, $class_names);
    }

    /**
     * Forming list of models from specified paths
     *
     * @param $alias
     *
     * @return array
     */
    public function getModelClassesFromDir($alias)
    {
        $rii   = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($alias));
        $files = [];
        foreach ($rii as $file) {
            if ($file->isDir()){
                continue;
            }
            $files[] = $file->getPathname();
        }

        $class_names = [];
        foreach($files as $item) {
            $file = file_get_contents($item);
            preg_match('/^namespace \S+/m', $file, $matches);
            if(isset($matches[0])) {
                $namespace = str_replace(['namespace ', ';'], '', $matches[0]);
                preg_match('/^class \S+/m', $file, $matches);
                if(isset($matches[0])) {
                    $class_names[] = $namespace . '\\' .  str_replace('class ', '', $matches[0]);
                }
            }
        }
        return $class_names;
    }
}
